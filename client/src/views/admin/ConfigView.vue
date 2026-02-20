<script setup lang="ts">
/**
 * ConfigView.vue
 *
 * SuperAdmin configuration page with establishment name settings
 * and a DANGER ZONE for full database reset.
 */
import { ref, onMounted } from 'vue'
import api from '@/composables/useApi'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'
import AppShell from '@/components/layout/AppShell.vue'

const authStore = useAuthStore()
const router = useRouter()

// ── Establishment Name ──
const establishmentName = ref('')
const saving = ref(false)

onMounted(async () => {
  try {
    const { data } = await api.get('/api/config/settings')
    establishmentName.value = data.establishment_name || ''
  } catch {
    // Settings may not exist yet
  }
})

async function saveSettings() {
  saving.value = true
  try {
    await api.put('/api/config/settings', {
      establishment_name: establishmentName.value,
    })
    window.dispatchEvent(new CustomEvent('toast', {
      detail: { message: 'Establishment name saved.', type: 'success' }
    }))
  } catch (err: any) {
    window.dispatchEvent(new CustomEvent('toast', {
      detail: { message: err.response?.data?.message || 'Failed to save.', type: 'error' }
    }))
  } finally {
    saving.value = false
  }
}

// ── Full Reset ──
const resetConfirmText = ref('')
const resetting = ref(false)
const showResetConfirm = ref(false)

async function performReset() {
  if (resetConfirmText.value !== 'RESET') return

  resetting.value = true
  try {
    await api.post('/api/config/reset')
    window.dispatchEvent(new CustomEvent('toast', {
      detail: { message: 'Database has been reset.', type: 'success' }
    }))
    // Logout and redirect
    await authStore.logout()
    router.push('/login')
  } catch (err: any) {
    window.dispatchEvent(new CustomEvent('toast', {
      detail: { message: err.response?.data?.message || 'Reset failed.', type: 'error' }
    }))
  } finally {
    resetting.value = false
  }
}
</script>

<template>
  <AppShell>
    <div class="space-y-8">
      <!-- Header -->
      <div class="flex items-center gap-3">
        <h2 class="text-2xl font-bold text-white">Configuration</h2>
        <span class="px-2 py-0.5 text-xs font-semibold bg-amber-500/20 text-amber-400 rounded-full">
          SUPERADMIN
        </span>
      </div>

      <!-- Establishment Name Section -->
      <div class="bg-gray-900 border border-gray-800 rounded-xl p-6 space-y-4">
        <h3 class="text-lg font-semibold text-white">Establishment Name</h3>
        <p class="text-sm text-gray-400">
          This name is displayed in the top bar for all users.
        </p>
        <div class="flex gap-3">
          <input
            v-model="establishmentName"
            type="text"
            maxlength="100"
            placeholder="e.g. The Anchor"
            class="flex-1 px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white
                   placeholder-gray-500 focus:outline-none focus:border-amber-500"
          />
          <button
            @click="saveSettings"
            :disabled="saving || !establishmentName.trim()"
            class="px-6 py-2 bg-amber-500 text-gray-950 font-semibold rounded-lg
                   hover:bg-amber-400 disabled:opacity-50 transition-colors"
          >
            {{ saving ? 'Saving...' : 'Save' }}
          </button>
        </div>
      </div>

      <!-- DANGER ZONE -->
      <div class="border-2 border-red-600/50 rounded-xl p-6 space-y-4 bg-red-950/20">
        <div class="flex items-center gap-3">
          <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z" />
          </svg>
          <h3 class="text-lg font-bold text-red-400 uppercase tracking-wide">Danger Zone</h3>
        </div>

        <p class="text-sm text-red-300/80">
          This will permanently delete <strong>ALL</strong> data and reset the database.
          You will be re-created as the sole superadmin with password "password".
          This action cannot be undone.
        </p>

        <div v-if="!showResetConfirm">
          <button
            @click="showResetConfirm = true"
            class="px-6 py-2 bg-red-600 text-white font-semibold rounded-lg
                   hover:bg-red-500 transition-colors"
          >
            Full Reset
          </button>
        </div>

        <div v-else class="space-y-3 bg-red-950/40 border border-red-600/30 rounded-lg p-4">
          <p class="text-sm text-red-300 font-medium">
            Type <code class="px-1.5 py-0.5 bg-red-900/50 rounded text-red-200 font-bold">RESET</code> to confirm:
          </p>
          <input
            v-model="resetConfirmText"
            type="text"
            placeholder="Type RESET"
            class="w-full px-4 py-2 bg-gray-800 border border-red-600/50 rounded-lg text-white
                   placeholder-gray-500 focus:outline-none focus:border-red-500"
          />
          <div class="flex gap-3">
            <button
              @click="performReset"
              :disabled="resetConfirmText !== 'RESET' || resetting"
              class="px-6 py-2 bg-red-600 text-white font-semibold rounded-lg
                     hover:bg-red-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              {{ resetting ? 'Resetting...' : 'Confirm Full Reset' }}
            </button>
            <button
              @click="showResetConfirm = false; resetConfirmText = ''"
              class="px-6 py-2 bg-gray-700 text-gray-300 font-medium rounded-lg
                     hover:bg-gray-600 transition-colors"
            >
              Cancel
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppShell>
</template>
