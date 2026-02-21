<script setup lang="ts">
/**
 * ProfileView -- staff profile page for editing name and availability.
 * Read-only display for email, role, and location. Uses the same
 * AvailabilityGrid component as MyScheduleView.
 */
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import api from '@/composables/useApi'
import AppShell from '@/components/layout/AppShell.vue'
import BadgePill from '@/components/ui/BadgePill.vue'
import AvailabilityGrid from '@/components/AvailabilityGrid.vue'

const authStore = useAuthStore()

// ── Name editing ────────────────────────────────────────────────────
const editingName = ref(false)
const nameInput = ref('')
const savingName = ref(false)

function startEditName() {
  nameInput.value = authStore.user?.name || ''
  editingName.value = true
}

async function saveName() {
  if (!nameInput.value.trim()) return
  savingName.value = true
  try {
    const { data } = await api.put('/api/profile', { name: nameInput.value.trim() })
    authStore.user = data
    editingName.value = false
    toast('Name updated', 'success')
  } catch {
    toast('Failed to update name', 'error')
  } finally {
    savingName.value = false
  }
}

// ── Availability ────────────────────────────────────────────────────
const DAYS = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as const

function emptyAvailability(): Record<string, string[]> {
  return Object.fromEntries(DAYS.map(d => [d, []]))
}

const availability = ref<Record<string, string[]>>(emptyAvailability())
const savingAvailability = ref(false)
const editingAvailability = ref(false)

function hasAvailability(): boolean {
  const a = authStore.user?.availability
  if (!a) return false
  return DAYS.some(d => (a[d] ?? []).length > 0)
}

function loadAvailability() {
  const user = authStore.user
  if (user?.availability) {
    availability.value = { ...emptyAvailability(), ...user.availability }
  }
  editingAvailability.value = !hasAvailability()
}

async function saveAvailability() {
  savingAvailability.value = true
  try {
    const { data } = await api.put('/api/profile', { availability: availability.value })
    authStore.user = data
    editingAvailability.value = false
    toast('Availability saved', 'success')
  } catch {
    toast('Failed to save availability', 'error')
  } finally {
    savingAvailability.value = false
  }
}

function toast(message: string, type: string) {
  window.dispatchEvent(new CustomEvent('toast', { detail: { message, type } }))
}

onMounted(loadAvailability)
</script>

<template>
  <AppShell>
    <div class="space-y-4">

      <!-- Page Header -->
      <div>
        <div class="flex items-center gap-2">
          <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
          </svg>
          <h1 class="text-xl font-bold text-white">My Profile</h1>
        </div>
        <p class="text-xs text-gray-500 mt-0.5">Manage your name and availability</p>
      </div>

      <!-- Profile Info -->
      <section class="rounded-xl border border-white/[0.06] bg-white/[0.03] p-4 space-y-4">

        <!-- Name (editable) -->
        <div class="flex items-center justify-between">
          <div>
            <label class="text-[10px] font-bold text-gray-500 uppercase tracking-wide">Name</label>
            <div v-if="!editingName" class="flex items-center gap-2 mt-0.5">
              <span class="text-sm text-white font-medium">{{ authStore.user?.name }}</span>
              <button
                @click="startEditName"
                class="text-gray-500 hover:text-amber-400 transition-colors"
              >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
              </button>
            </div>
            <div v-else class="flex items-center gap-2 mt-0.5">
              <input
                v-model="nameInput"
                type="text"
                class="px-2 py-1 text-sm bg-gray-800 border border-gray-600 rounded text-white
                       placeholder-gray-500 focus:outline-none focus:border-amber-500 w-48"
                @keyup.enter="saveName"
              />
              <button
                @click="saveName"
                :disabled="savingName"
                class="px-2 py-1 text-xs font-medium bg-amber-500 text-gray-950 rounded
                       hover:bg-amber-400 disabled:opacity-50 transition-colors"
              >
                {{ savingName ? 'Saving...' : 'Save' }}
              </button>
              <button
                @click="editingName = false"
                class="px-2 py-1 text-xs font-medium bg-gray-600 text-gray-200 rounded
                       hover:bg-gray-500 transition-colors"
              >
                Cancel
              </button>
            </div>
          </div>
        </div>

        <!-- Email (read-only) -->
        <div>
          <label class="text-[10px] font-bold text-gray-500 uppercase tracking-wide">Email</label>
          <p class="text-sm text-gray-300 mt-0.5">{{ authStore.user?.email }}</p>
        </div>

        <!-- Role (read-only badge) -->
        <div>
          <label class="text-[10px] font-bold text-gray-500 uppercase tracking-wide">Role</label>
          <div class="mt-1">
            <BadgePill
              v-if="authStore.user"
              :label="authStore.user.role"
              :color="authStore.user.role === 'admin' ? 'red' : authStore.user.role === 'manager' ? 'yellow' : 'blue'"
            />
          </div>
        </div>

        <!-- Location (read-only) -->
        <div>
          <label class="text-[10px] font-bold text-gray-500 uppercase tracking-wide">Location</label>
          <p class="text-sm text-gray-300 mt-0.5">{{ authStore.user?.location?.name || 'N/A' }}</p>
        </div>
      </section>

      <!-- Availability Section -->
      <section class="rounded-xl border border-white/[0.06] bg-white/[0.03] p-4 space-y-3">
        <div v-if="!editingAvailability" class="flex items-center justify-between">
          <div>
            <h2 class="text-sm font-bold text-gray-400 uppercase tracking-wide">My Availability</h2>
            <p class="text-xs text-gray-500 mt-0.5">Tap edit to change your weekly availability</p>
          </div>
          <button
            type="button"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-md bg-blue-500/20 text-blue-300 hover:bg-blue-500/30 transition-colors"
            @click="editingAvailability = true"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            Edit
          </button>
        </div>

        <template v-else>
          <div>
            <h2 class="text-sm font-bold text-gray-400 uppercase tracking-wide">My Availability</h2>
            <p class="text-[10px] text-gray-600 mt-0.5">Tap the times you can work each day. OPEN = available all day.</p>
          </div>
          <AvailabilityGrid
            v-model="availability"
            :saving="savingAvailability"
            @save="saveAvailability"
          />
        </template>
      </section>

    </div>
  </AppShell>
</template>
