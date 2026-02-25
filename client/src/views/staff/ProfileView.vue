<script setup lang="ts">
/**
 * ProfileView -- staff profile page for viewing and editing personal info.
 *
 * Read-only display: email, role (badge), location name.
 * Editable inline: name (pencil icon toggle), availability (AvailabilityGrid).
 *
 * All edits are saved via PUT /api/profile, which only accepts `name` and
 * `availability` -- role, email, and location_id are silently ignored by
 * the backend even if tampered with in the request body.
 */
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import api from '@/composables/useApi'
import AppShell from '@/components/layout/AppShell.vue'
import BadgePill from '@/components/ui/BadgePill.vue'
import AvailabilityGrid from '@/components/AvailabilityGrid.vue'
import UserAvatar from '@/components/ui/UserAvatar.vue'

const authStore = useAuthStore()

// ── Photo upload ─────────────────────────────────────────────────────
// Hidden file input ref for triggering camera/gallery on mobile.
const photoInput = ref<HTMLInputElement | null>(null)
// True while the photo upload POST is in-flight.
const uploadingPhoto = ref(false)

/** Open the native file picker (triggers camera/gallery on mobile). */
function triggerPhotoUpload() {
  photoInput.value?.click()
}

/**
 * Upload the selected photo via POST /api/profile/photo.
 * Updates the auth store with the response so the avatar
 * refreshes everywhere without a page reload.
 */
async function handlePhotoSelected(event: Event) {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  if (!file) return

  uploadingPhoto.value = true
  try {
    const formData = new FormData()
    formData.append('photo', file)
    const { data } = await api.post('/api/profile/photo', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    authStore.user = data
    toast('Photo updated', 'success')
  } catch {
    toast('Failed to upload photo', 'error')
  } finally {
    uploadingPhoto.value = false
    // Reset the input so the same file can be re-selected
    if (photoInput.value) photoInput.value.value = ''
  }
}

/**
 * Delete the profile photo via DELETE /api/profile/photo.
 * Reverts to the initials-based avatar fallback.
 */
async function removePhoto() {
  uploadingPhoto.value = true
  try {
    const { data } = await api.delete('/api/profile/photo')
    authStore.user = data
    toast('Photo removed', 'success')
  } catch {
    toast('Failed to remove photo', 'error')
  } finally {
    uploadingPhoto.value = false
  }
}

// ── Name editing ────────────────────────────────────────────────────
// Controls whether the name field is in display or edit mode.
const editingName = ref(false)
// Temporary input value for the name field while editing.
const nameInput = ref('')
// True while the PUT /api/profile call for name is in-flight.
const savingName = ref(false)

/** Enter name edit mode with the current name pre-filled. */
function startEditName() {
  nameInput.value = authStore.user?.name || ''
  editingName.value = true
}

/**
 * Save the edited name via PUT /api/profile.
 * Updates the auth store with the response so the TopBar reflects
 * the change immediately without a page reload.
 */
async function saveName() {
  if (!nameInput.value.trim()) return
  savingName.value = true
  try {
    const { data } = await api.put('/api/profile', { name: nameInput.value.trim() })
    // Update the auth store so the TopBar initials and dropdown name refresh.
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
// Reuses the same AvailabilityGrid component and pattern from MyScheduleView.
const DAYS = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as const

/** Build an empty availability map (no slots selected for any day). */
function emptyAvailability(): Record<string, string[]> {
  return Object.fromEntries(DAYS.map(d => [d, []]))
}

// The working copy of availability bound to the AvailabilityGrid v-model.
const availability = ref<Record<string, string[]>>(emptyAvailability())
// True while the PUT /api/profile call for availability is in-flight.
const savingAvailability = ref(false)
// Controls whether the availability grid is expanded or collapsed.
const editingAvailability = ref(false)

/** Check if the user has any availability slots set. */
function hasAvailability(): boolean {
  const a = authStore.user?.availability
  if (!a) return false
  return DAYS.some(d => (a[d] ?? []).length > 0)
}

/**
 * Hydrate the local availability ref from the auth store's user.
 * If availability is already set, start with the grid collapsed.
 */
function loadAvailability() {
  const user = authStore.user
  if (user?.availability) {
    availability.value = { ...emptyAvailability(), ...user.availability }
  }
  // Start collapsed if availability already set, expanded if not.
  editingAvailability.value = !hasAvailability()
}

/**
 * Save availability via PUT /api/profile.
 * Updates the auth store with the full user response.
 */
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

/** Dispatch a global toast notification via CustomEvent. */
function toast(message: string, type: string) {
  window.dispatchEvent(new CustomEvent('toast', { detail: { message, type } }))
}

// Hydrate availability from the auth store on component mount.
onMounted(loadAvailability)
</script>

<template>
  <AppShell>
    <div class="space-y-4">

      <!-- Page Header -->
      <div class="flex items-center justify-between">
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
        <router-link
          to="/dashboard"
          class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-md text-[11px] font-semibold whitespace-nowrap bg-white/[0.06] text-gray-400 hover:bg-white/[0.1] hover:text-white transition-colors"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          Corner!
        </router-link>
      </div>

      <!-- Profile Info Card -->
      <section class="rounded-xl border border-white/[0.06] bg-white/[0.03] p-4 space-y-4">

        <!-- Profile Photo -->
        <div class="flex flex-col items-center gap-2">
          <button
            type="button"
            class="relative group focus:outline-none"
            :disabled="uploadingPhoto"
            @click="triggerPhotoUpload"
          >
            <UserAvatar :user="authStore.user ?? null" size="lg" bg="bg-amber-500 text-gray-950" />
            <!-- Camera overlay (always visible on mobile, hover-only on desktop) -->
            <div class="absolute inset-0 rounded-full bg-black/40 flex items-center justify-center opacity-60 sm:opacity-0 sm:group-hover:opacity-100 transition-opacity">
              <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
            </div>
          </button>
          <input
            ref="photoInput"
            type="file"
            accept="image/*"
            class="absolute w-0 h-0 overflow-hidden opacity-0"
            @change="handlePhotoSelected"
          />
          <p v-if="uploadingPhoto" class="text-[10px] text-gray-500">Uploading...</p>
          <button
            v-else-if="authStore.user?.profile_photo_url"
            type="button"
            class="text-[10px] text-red-400 hover:text-red-300 transition-colors"
            @click="removePhoto"
          >
            Remove photo
          </button>
          <p v-else class="text-[10px] text-gray-600">Tap to add a photo</p>
        </div>

        <!-- Name (editable via pencil icon toggle) -->
        <div class="flex items-center justify-between">
          <div>
            <label class="text-[10px] font-bold text-gray-500 uppercase tracking-wide">Name</label>
            <!-- Display mode: show name + pencil icon to enter edit mode -->
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
            <!-- Edit mode: inline text input with save/cancel buttons -->
            <div v-else class="flex items-center gap-2 mt-0.5">
              <input
                v-model="nameInput"
                type="text"
                class="px-2 py-1 text-sm bg-gray-800 border border-gray-600 rounded text-white
                       placeholder-gray-500 focus:outline-none focus:border-amber-500 flex-1 min-w-0"
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

      <!-- Availability Section (reuses AvailabilityGrid from MyScheduleView) -->
      <section class="rounded-xl border border-white/[0.06] bg-white/[0.03] p-4 space-y-3">
        <!-- Collapsed state: summary text + edit button -->
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

        <!-- Expanded state: full interactive grid editor -->
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
