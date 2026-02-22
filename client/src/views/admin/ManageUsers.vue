<script setup lang="ts">
/**
 * ManageUsers -- admin/manager CRUD view for employee accounts.
 * Provides a toggleable create/edit form with name, email, password,
 * role, multi-role toggle (server/bartender dual-role), phone, and
 * day-of-week availability fields. Password is required when creating a
 * new user but optional when editing (left blank to keep the existing
 * password). A table lists all employees with their role badge(s),
 * phone, availability summary, and Edit/Remove actions.
 */
import { ref, computed, onMounted } from 'vue'
import api from '@/composables/useApi'
import AppShell from '@/components/layout/AppShell.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BadgePill from '@/components/ui/BadgePill.vue'
import AvailabilityGrid from '@/components/AvailabilityGrid.vue'
import type { User } from '@/types'

// Day-of-week keys matching the availability JSON format
const DAYS = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as const
const DAY_LABELS: Record<string, string> = {
  monday: 'Mon',
  tuesday: 'Tue',
  wednesday: 'Wed',
  thursday: 'Thu',
  friday: 'Fri',
  saturday: 'Sat',
  sunday: 'Sun',
}

// Reactive list of users fetched from the API
const users = ref<User[]>([])
// True while the initial user list is being loaded
const loading = ref(false)
// True while the create/update API call is in-flight
const submitting = ref(false)

// Controls visibility of the create/edit form panel
const showForm = ref(false)
// When non-null, the form is in "edit" mode for the user with this ID
const editingId = ref<number | null>(null)
// Reactive form fields bound to the create/edit form inputs
const form = ref({
  name: '',
  email: '',
  password: '',
  role: 'server',
  phone: '',
  availability: Object.fromEntries(DAYS.map(d => [d, [] as string[]])) as Record<string, string[]>,
  alsoWorksAs: '' as string, // Secondary role for multi-role staff (empty string = none)
})

// Clears all form fields, exits edit mode, and hides the form panel
function resetForm() {
  form.value = {
    name: '',
    email: '',
    password: '',
    role: 'server',
    phone: '',
    availability: Object.fromEntries(DAYS.map(d => [d, [] as string[]])),
    alsoWorksAs: '',
  }
  editingId.value = null
  showForm.value = false
}

// Populates the form with an existing user's data for editing.
/** The alternate staff role label for the checkbox ("Also works as ..."). */
const alternateRoleLabel = computed(() => {
  return form.value.role === 'server' ? 'Bartender' : 'Server'
})

/** Whether the primary role is a staff role that supports dual-role assignment. */
const showDualRole = computed(() => ['server', 'bartender'].includes(form.value.role))

function editUser(user: User) {
  editingId.value = user.id
  const otherRole = user.role === 'server' ? 'bartender' : 'server'
  form.value = {
    name: user.name,
    email: user.email,
    password: '',
    role: user.role,
    phone: user.phone || '',
    availability: user.availability
      ? { ...Object.fromEntries(DAYS.map(d => [d, [] as string[]])), ...user.availability }
      : Object.fromEntries(DAYS.map(d => [d, [] as string[]])),
    alsoWorksAs: user.roles && user.roles.includes(otherRole) ? otherRole : '',
  }
  showForm.value = true
}

/** Returns a short summary of availability like "Mon (10:30, 4:30), Tue (OPEN)" */
function availabilitySummary(availability: Record<string, string[]> | null): string {
  if (!availability) return 'Not set'
  const parts: string[] = []
  for (const d of DAYS) {
    const slots = availability[d]
    if (!slots || slots.length === 0) continue
    if (slots.includes('open')) {
      parts.push(`${DAY_LABELS[d]}`)
    } else {
      const slotLabels: Record<string, string> = { '10:30': '10:30', '16:30': '4:30', '18:00': '6:00', '19:00': '7:00' }
      const labels = slots.map(s => slotLabels[s] ?? s)
      parts.push(`${DAY_LABELS[d]} (${labels.join(', ')})`)
    }
  }
  if (parts.length === 0) return 'None'
  // Check if every day is open
  const allOpen = DAYS.every(d => availability[d]?.includes('open'))
  if (allOpen) return 'Open all week'
  return parts.join(', ')
}

/**
 * Fetches all users from GET /api/users.
 */
async function fetchUsers() {
  loading.value = true
  try {
    const { data } = await api.get<User[]>('/api/users')
    users.value = data
  } finally {
    loading.value = false
  }
}

/**
 * Saves a user -- creates (POST) or updates (PATCH) based on editingId.
 */
async function saveUser() {
  if (!form.value.name.trim() || !form.value.email.trim()) return
  submitting.value = true
  try {
    // Build the roles array: [primary, secondary] when dual-role is checked,
    // or null when single-role (API falls back to the primary `role` field).
    const roles = form.value.alsoWorksAs
      ? [form.value.role, form.value.alsoWorksAs]
      : null
    const payload: Record<string, any> = {
      name: form.value.name,
      email: form.value.email,
      role: form.value.role,
      roles,
      phone: form.value.phone || null,
      availability: form.value.availability,
    }
    if (form.value.password) {
      payload.password = form.value.password
    }

    if (editingId.value) {
      const { data } = await api.patch(`/api/users/${editingId.value}`, payload)
      const idx = users.value.findIndex((u) => u.id === editingId.value)
      if (idx !== -1) users.value[idx] = data
      toast('Employee updated', 'success')
    } else {
      if (!form.value.password) {
        toast('Password is required for new employees', 'error')
        submitting.value = false
        return
      }
      const { data } = await api.post('/api/users', payload)
      users.value.push(data)
      toast('Employee created', 'success')
    }
    resetForm()
  } catch (e: any) {
    toast(e.response?.data?.message || 'Failed to save employee', 'error')
  } finally {
    submitting.value = false
  }
}

/**
 * Removes/deactivates a user after confirmation.
 */
async function deleteUser(id: number) {
  if (!confirm('Deactivate this employee?')) return
  try {
    await api.delete(`/api/users/${id}`)
    users.value = users.value.filter((u) => u.id !== id)
    toast('Employee removed', 'success')
  } catch {
    toast('Failed to remove employee', 'error')
  }
}

function toast(message: string, type: string) {
  window.dispatchEvent(new CustomEvent('toast', { detail: { message, type } }))
}

const roleColor: Record<string, 'red' | 'yellow' | 'blue' | 'green' | 'gray'> = {
  admin: 'red',
  manager: 'yellow',
  server: 'blue',
  bartender: 'green',
}

// ── Sorting ─────────────────────────────────────────────────────────────
type SortKey = 'name' | 'role' | 'availability'
const sortKey = ref<SortKey>('name')
const sortAsc = ref(true)

function toggleSort(key: SortKey) {
  if (sortKey.value === key) {
    sortAsc.value = !sortAsc.value
  } else {
    sortKey.value = key
    sortAsc.value = true
  }
}

const roleOrder: Record<string, number> = { admin: 0, manager: 1, bartender: 2, server: 3 }

function availabilityCount(user: User): number {
  if (!user.availability) return 0
  return DAYS.filter(d => (user.availability![d] ?? []).length > 0).length
}

const sortedUsers = computed(() => {
  const list = [...users.value]
  const dir = sortAsc.value ? 1 : -1
  list.sort((a, b) => {
    if (sortKey.value === 'name') {
      return dir * a.name.localeCompare(b.name)
    }
    if (sortKey.value === 'role') {
      return dir * ((roleOrder[a.role] ?? 99) - (roleOrder[b.role] ?? 99))
    }
    // availability: sort by number of available days
    return dir * (availabilityCount(a) - availabilityCount(b))
  })
  return list
})

onMounted(fetchUsers)
</script>

<template>
  <AppShell>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-white">Employees</h1>
        <div class="flex gap-2">
          <router-link
              to="/manage/daily"
              class="inline-flex items-center justify-center gap-1.5 px-2.5 py-1.5 text-xs font-medium rounded-lg bg-gray-200 text-gray-800 hover:bg-gray-300 transition-colors"
            >
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
              </svg>
              Corner!
            </router-link>
          <BaseButton v-if="!showForm" size="sm" @click="showForm = true">Add Employee</BaseButton>
        </div>
      </div>

      <!-- Form -->
      <div v-if="showForm" class="bg-gray-800 rounded-lg shadow p-4">
        <h3 class="font-semibold text-white mb-3">{{ editingId ? 'Edit' : 'Create' }} Employee</h3>
        <form @submit.prevent="saveUser" class="space-y-3">
          <div class="grid grid-cols-2 gap-3">
            <BaseInput v-model="form.name" label="Name" placeholder="Full name" />
            <BaseInput v-model="form.email" label="Email" type="email" placeholder="email@example.com" />
          </div>
          <div class="grid grid-cols-2 gap-3">
            <BaseInput
              v-model="form.password"
              label="Password"
              type="password"
              :placeholder="editingId ? 'Leave blank to keep current' : 'Password'"
            />
            <div>
              <label class="block text-sm font-medium text-gray-400 mb-1">Role</label>
              <select
                v-model="form.role"
                class="block w-full rounded-lg border border-gray-600 bg-gray-700 px-3 py-2 text-sm text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
              >
                <option value="server">Server</option>
                <option value="bartender">Bartender</option>
                <option value="manager">Manager</option>
                <option value="admin">Admin</option>
              </select>
            </div>
          </div>
          <div v-if="showDualRole" class="flex items-center gap-2">
            <input
              :id="'dual-role'"
              type="checkbox"
              :checked="!!form.alsoWorksAs"
              @change="form.alsoWorksAs = ($event.target as HTMLInputElement).checked ? (form.role === 'server' ? 'bartender' : 'server') : ''"
              class="h-4 w-4 rounded border-gray-600 bg-gray-700 text-indigo-500 focus:ring-indigo-500"
            />
            <label for="dual-role" class="text-sm text-gray-300">Also works as {{ alternateRoleLabel }}</label>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <BaseInput v-model="form.phone" label="Phone" type="tel" placeholder="(555) 123-4567" />
          </div>
          <!-- Availability grid -->
          <div>
            <label class="block text-sm font-medium text-gray-400 mb-2">Availability</label>
            <AvailabilityGrid v-model="form.availability" :saving="false" @save="() => {}" />
          </div>
          <div class="flex gap-2">
            <BaseButton type="submit" :loading="submitting">{{ editingId ? 'Update' : 'Create' }}</BaseButton>
            <BaseButton variant="secondary" @click="resetForm">Cancel</BaseButton>
          </div>
        </form>
      </div>

      <!-- Table -->
      <div v-if="loading" class="flex justify-center py-8">
        <svg class="animate-spin h-8 w-8 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
        </svg>
      </div>

      <div v-else class="bg-gray-800 rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-700">
          <thead class="bg-gray-800/50">
            <tr>
              <th
                class="px-4 py-3 text-left text-xs font-medium uppercase cursor-pointer select-none hover:text-white transition-colors"
                :class="sortKey === 'name' ? 'text-white' : 'text-gray-400'"
                @click="toggleSort('name')"
              >
                Name
                <span v-if="sortKey === 'name'" class="ml-0.5">{{ sortAsc ? '▲' : '▼' }}</span>
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Email</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Phone</th>
              <th
                class="px-4 py-3 text-left text-xs font-medium uppercase cursor-pointer select-none hover:text-white transition-colors"
                :class="sortKey === 'role' ? 'text-white' : 'text-gray-400'"
                @click="toggleSort('role')"
              >
                Role
                <span v-if="sortKey === 'role'" class="ml-0.5">{{ sortAsc ? '▲' : '▼' }}</span>
              </th>
              <th
                class="px-4 py-3 text-left text-xs font-medium uppercase cursor-pointer select-none hover:text-white transition-colors"
                :class="sortKey === 'availability' ? 'text-white' : 'text-gray-400'"
                @click="toggleSort('availability')"
              >
                Availability
                <span v-if="sortKey === 'availability'" class="ml-0.5">{{ sortAsc ? '▲' : '▼' }}</span>
              </th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-400 uppercase">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-700">
            <tr v-for="user in sortedUsers" :key="user.id">
              <td class="px-4 py-3 text-sm font-medium text-white">{{ user.name }}</td>
              <td class="px-4 py-3 text-sm text-gray-400">{{ user.email }}</td>
              <td class="px-4 py-3 text-sm text-gray-400">{{ user.phone || '—' }}</td>
              <td class="px-4 py-3 space-x-1">
                <BadgePill :label="user.role" :color="roleColor[user.role] || 'gray'" />
                <template v-if="user.roles && user.roles.length > 1">
                  <BadgePill
                    v-for="r in user.roles.filter(r => r !== user.role)"
                    :key="r"
                    :label="r"
                    :color="roleColor[r] || 'gray'"
                  />
                </template>
              </td>
              <td class="px-4 py-3 text-sm text-gray-400">{{ availabilitySummary(user.availability) }}</td>
              <td class="px-4 py-3 text-right space-x-2">
                <BaseButton size="sm" variant="secondary" @click="editUser(user)">Edit</BaseButton>
                <BaseButton size="sm" variant="danger" @click="deleteUser(user.id)">Remove</BaseButton>
              </td>
            </tr>
            <tr v-if="!users.length">
              <td colspan="6" class="px-4 py-8 text-center text-gray-400">No employees</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppShell>
</template>
