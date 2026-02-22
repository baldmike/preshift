<script setup lang="ts">
/**
 * EmployeeProfileModal.vue
 *
 * Displays an employee's profile in a modal overlay for managers/admins.
 * Shows name, role badge, phone (as a `tel:` link), email (click to copy),
 * weekly availability grid (readonly), and a placeholder "Message" button.
 *
 * Props:
 *   - user: User | null  — the employee to display; null = modal closed
 *
 * Emits:
 *   - close  — fired when the modal should be dismissed
 */
import { computed } from 'vue'
import type { User } from '@/types'
import BaseModal from '@/components/ui/BaseModal.vue'
import BadgePill from '@/components/ui/BadgePill.vue'
import AvailabilityGrid from '@/components/AvailabilityGrid.vue'

const props = defineProps<{
  user: User | null
}>()

defineEmits<{
  close: []
}>()

const DAYS = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as const

/** Whether the modal is open (user is non-null) */
const isOpen = computed(() => props.user !== null)

/** Map role to BadgePill color */
function roleColor(role: string): 'blue' | 'green' | 'yellow' | 'red' {
  if (role === 'bartender') return 'green'
  if (role === 'manager') return 'yellow'
  if (role === 'admin') return 'red'
  return 'blue'
}

/** Whether the user has any availability set */
const hasAvailability = computed(() => {
  const a = props.user?.availability
  if (!a) return false
  return DAYS.some(d => (a[d] ?? []).length > 0)
})

/** Build an availability map for the grid, defaulting empty days */
const availabilityMap = computed<Record<string, string[]>>(() => {
  const base = Object.fromEntries(DAYS.map(d => [d, []])) as Record<string, string[]>
  if (!props.user?.availability) return base
  return { ...base, ...props.user.availability }
})

function toast(message: string, type: string) {
  window.dispatchEvent(new CustomEvent('toast', { detail: { message, type } }))
}

/** Copy the user's email to clipboard and show a toast */
async function copyEmail() {
  if (!props.user?.email) return
  try {
    await navigator.clipboard.writeText(props.user.email)
    toast('Email copied', 'success')
  } catch {
    toast('Failed to copy email', 'error')
  }
}

/** Placeholder for future messaging feature */
function handleMessage() {
  toast('Coming soon', 'info')
}
</script>

<template>
  <BaseModal :open="isOpen" @close="$emit('close')">
    <div v-if="user" class="p-5 space-y-4">
      <!-- Header: name + role badge -->
      <div class="flex items-center gap-3">
        <div class="flex-1 min-w-0">
          <h2 class="text-lg font-bold text-white truncate">{{ user.name }}</h2>
        </div>
        <BadgePill :label="user.role" :color="roleColor(user.role)" />
      </div>

      <!-- Contact info -->
      <div class="space-y-2">
        <!-- Phone -->
        <div class="flex items-center gap-2 text-sm">
          <svg class="w-4 h-4 text-gray-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
          </svg>
          <a
            v-if="user.phone"
            :href="`tel:${user.phone}`"
            class="text-blue-400 hover:text-blue-300 transition-colors"
            data-testid="phone-link"
          >
            {{ user.phone }}
          </a>
          <span v-else class="text-gray-600">Not set</span>
        </div>

        <!-- Email -->
        <div class="flex items-center gap-2 text-sm">
          <svg class="w-4 h-4 text-gray-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
          </svg>
          <button
            type="button"
            class="text-blue-400 hover:text-blue-300 transition-colors text-left truncate"
            data-testid="email-button"
            @click="copyEmail"
          >
            {{ user.email }}
          </button>
        </div>
      </div>

      <!-- Availability -->
      <div>
        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wide mb-2">Availability</h3>
        <AvailabilityGrid
          v-if="hasAvailability"
          :model-value="availabilityMap"
          readonly
        />
        <p v-else class="text-gray-600 text-xs">Not set</p>
      </div>

      <!-- Actions -->
      <div class="flex gap-2 pt-1">
        <button
          type="button"
          class="flex-1 px-3 py-2 text-xs font-semibold rounded-md bg-blue-500/20 text-blue-300 hover:bg-blue-500/30 transition-colors"
          data-testid="message-button"
          @click="handleMessage"
        >
          Message
        </button>
        <button
          type="button"
          class="flex-1 px-3 py-2 text-xs font-semibold rounded-md bg-white/[0.06] text-gray-400 hover:bg-white/[0.1] transition-colors"
          @click="$emit('close')"
        >
          Close
        </button>
      </div>
    </div>
  </BaseModal>
</template>
