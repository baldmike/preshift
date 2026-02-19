<script setup lang="ts">
/**
 * ManageUsers -- admin/manager CRUD view for staff user accounts.
 * Provides a toggleable create/edit form with name, email, password,
 * and role fields. Password is required when creating a new user but
 * optional when editing (left blank to keep the existing password).
 * A table lists all users with their role badge and Edit/Remove actions.
 * The "Remove" action uses a confirm dialog labeled "Deactivate" to
 * signal that the user account is being disabled rather than permanently
 * deleted.
 */
import { ref, onMounted } from 'vue'
import api from '@/composables/useApi'
import AppShell from '@/components/layout/AppShell.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BadgePill from '@/components/ui/BadgePill.vue'
import type { User } from '@/types'

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
  password: '',     // Required for create, optional for edit (blank = keep existing)
  role: 'server',   // Default role for new users: 'server', 'bartender', 'manager', or 'admin'
})

// Clears all form fields, exits edit mode, and hides the form panel
function resetForm() {
  form.value = { name: '', email: '', password: '', role: 'server' }
  editingId.value = null
  showForm.value = false
}

// Populates the form with an existing user's data for editing.
// Password is left blank so the user can optionally change it.
function editUser(user: User) {
  editingId.value = user.id
  form.value = {
    name: user.name,
    email: user.email,
    password: '',
    role: user.role,
  }
  showForm.value = true
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
 * Only includes the password field in the payload if it is non-empty.
 * Validates that password is provided when creating a new user.
 * Updates the local list in-place on success and resets the form.
 */
async function saveUser() {
  if (!form.value.name.trim() || !form.value.email.trim()) return
  submitting.value = true
  try {
    const payload: Record<string, any> = {
      name: form.value.name,
      email: form.value.email,
      role: form.value.role,
    }
    // Only include password if provided (avoids overwriting on edit)
    if (form.value.password) {
      payload.password = form.value.password
    }

    if (editingId.value) {
      const { data } = await api.patch(`/api/users/${editingId.value}`, payload)
      const idx = users.value.findIndex((u) => u.id === editingId.value)
      if (idx !== -1) users.value[idx] = data
      toast('User updated', 'success')
    } else {
      // Password is mandatory for new user creation
      if (!form.value.password) {
        toast('Password is required for new users', 'error')
        submitting.value = false
        return
      }
      const { data } = await api.post('/api/users', payload)
      users.value.push(data)
      toast('User created', 'success')
    }
    resetForm()
  } catch (e: any) {
    toast(e.response?.data?.message || 'Failed to save user', 'error')
  } finally {
    submitting.value = false
  }
}

/**
 * Removes/deactivates a user after confirmation.
 * Removes the user from the local list on success.
 */
async function deleteUser(id: number) {
  if (!confirm('Deactivate this user?')) return
  try {
    await api.delete(`/api/users/${id}`)
    users.value = users.value.filter((u) => u.id !== id)
    toast('User removed', 'success')
  } catch {
    toast('Failed to remove user', 'error')
  }
}

// Helper to dispatch a global toast notification via CustomEvent
function toast(message: string, type: string) {
  window.dispatchEvent(new CustomEvent('toast', { detail: { message, type } }))
}

// Maps user roles to BadgePill color values for the table's role column
const roleColor: Record<string, 'red' | 'yellow' | 'blue' | 'green' | 'gray'> = {
  admin: 'red',
  manager: 'yellow',
  server: 'blue',
  bartender: 'green',
}

// Fetch users on component mount
onMounted(fetchUsers)
</script>

<template>
  <AppShell>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Manage Users</h1>
        <div class="flex gap-2">
          <router-link to="/manage" class="text-sm text-indigo-600 hover:text-indigo-800">Back</router-link>
          <BaseButton v-if="!showForm" size="sm" @click="showForm = true">Add User</BaseButton>
        </div>
      </div>

      <!-- Form -->
      <div v-if="showForm" class="bg-white rounded-lg shadow p-4">
        <h3 class="font-semibold text-gray-900 mb-3">{{ editingId ? 'Edit' : 'Create' }} User</h3>
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
              <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
              <select
                v-model="form.role"
                class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
              >
                <option value="server">Server</option>
                <option value="bartender">Bartender</option>
                <option value="manager">Manager</option>
                <option value="admin">Admin</option>
              </select>
            </div>
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

      <div v-else class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="user in users" :key="user.id">
              <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ user.name }}</td>
              <td class="px-4 py-3 text-sm text-gray-500">{{ user.email }}</td>
              <td class="px-4 py-3">
                <BadgePill :label="user.role" :color="roleColor[user.role] || 'gray'" />
              </td>
              <td class="px-4 py-3 text-right space-x-2">
                <BaseButton size="sm" variant="secondary" @click="editUser(user)">Edit</BaseButton>
                <BaseButton size="sm" variant="danger" @click="deleteUser(user.id)">Remove</BaseButton>
              </td>
            </tr>
            <tr v-if="!users.length">
              <td colspan="4" class="px-4 py-8 text-center text-gray-500">No users</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppShell>
</template>
