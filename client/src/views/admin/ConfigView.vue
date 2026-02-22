<script setup lang="ts">
/**
 * ConfigView.vue
 *
 * SuperAdmin configuration page with establishment name settings,
 * an Initial Setup section for replacing demo data with a real account,
 * and a DANGER ZONE with the full reset option.
 */
import { ref, onMounted } from 'vue'
import api from '@/composables/useApi'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'
import AppShell from '@/components/layout/AppShell.vue'

const authStore = useAuthStore()
const router = useRouter()

function toast(message: string, type: string) {
  window.dispatchEvent(new CustomEvent('toast', { detail: { message, type } }))
}

// ── Establishment Name ──
const establishmentName = ref('')
const saving = ref(false)

/** Whether the initial setup has been completed (gates Danger Zone visibility). */
const setupComplete = ref(false)

onMounted(async () => {
  try {
    const { data } = await api.get('/api/config/settings')
    establishmentName.value = data.establishment_name || ''
    setupComplete.value = data.setup_complete === 'true'
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
    toast('Establishment name saved.', 'success')
  } catch (err: any) {
    toast(err.response?.data?.message || 'Failed to save.', 'error')
  } finally {
    saving.value = false
  }
}

// ── Initial Setup ──
const setupName = ref('')
const setupEmail = ref('')
const setupLocationName = ref('')
const setupActive = ref(false)
const setupProcessing = ref(false)

// Math challenge for Initial Setup
const setupChallengeA = ref(0)
const setupChallengeB = ref(0)
const setupChallengeAnswer = ref('')
const setupChallengePassed = ref(false)

/** Generate a new math challenge for Initial Setup. */
function generateSetupChallenge() {
  setupChallengeA.value = Math.floor(Math.random() * 40) + 10
  setupChallengeB.value = Math.floor(Math.random() * 40) + 10
  setupChallengeAnswer.value = ''
  setupChallengePassed.value = false
}

/** Check if the user's answer matches the sum. */
function checkSetupChallenge() {
  setupChallengePassed.value =
    parseInt(setupChallengeAnswer.value) === setupChallengeA.value + setupChallengeB.value
}

/** Open the Initial Setup form and generate a math challenge. */
function startSetup() {
  setupActive.value = true
  generateSetupChallenge()
}

/** Close the Initial Setup form and reset state. */
function cancelSetup() {
  setupActive.value = false
  setupName.value = ''
  setupEmail.value = ''
  setupLocationName.value = ''
  setupChallengePassed.value = false
}

/** Submit the Initial Setup form — wipe demo data and create a real account. */
async function performSetup() {
  setupProcessing.value = true
  try {
    await api.post('/api/config/initial-setup', {
      name: setupName.value,
      email: setupEmail.value,
      location_name: setupLocationName.value,
    })
    toast('Setup complete. Redirecting to login...', 'success')
    setupActive.value = false
    await authStore.logout()
    router.push('/login')
  } catch (err: any) {
    toast(err.response?.data?.message || 'Initial setup failed.', 'error')
  } finally {
    setupProcessing.value = false
  }
}

// ── Danger Zone Actions ──
const activeAction = ref<string | null>(null)
const confirmText = ref('')
const processing = ref(false)

// Math challenge gate — must solve before typing the confirm word
const challengeA = ref(0)
const challengeB = ref(0)
const challengeAnswer = ref('')
const challengePassed = ref(false)

function generateChallenge() {
  challengeA.value = Math.floor(Math.random() * 40) + 10
  challengeB.value = Math.floor(Math.random() * 40) + 10
  challengeAnswer.value = ''
  challengePassed.value = false
}

function checkChallenge() {
  challengePassed.value = parseInt(challengeAnswer.value) === challengeA.value + challengeB.value
}

interface DangerAction {
  key: string
  label: string
  description: string
  confirmWord: string
  endpoint: string
  logout: boolean
}

const dangerActions: DangerAction[] = [
  {
    key: 'nuclear',
    label: 'Full Reset',
    description: 'Wipes all data — users, locations, menus, schedules, everything. Your account will be re-created as the sole superadmin with password "password". You will be logged out.',
    confirmWord: 'RESET',
    endpoint: '/api/config/reset',
    logout: true,
  },
]

async function performAction(action: DangerAction) {
  if (confirmText.value !== action.confirmWord) return

  processing.value = true
  try {
    await api.post(action.endpoint)
    toast(`${action.label} completed.`, 'success')
    activeAction.value = null
    confirmText.value = ''

    if (action.logout) {
      await authStore.logout()
      router.push('/login')
    }
  } catch (err: any) {
    toast(err.response?.data?.message || `${action.label} failed.`, 'error')
  } finally {
    processing.value = false
  }
}

function startAction(key: string) {
  activeAction.value = key
  confirmText.value = ''
  generateChallenge()
}

function cancelAction() {
  activeAction.value = null
  confirmText.value = ''
  challengePassed.value = false
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

      <!-- Establishment Name Section — only visible after initial setup -->
      <div v-if="setupComplete" class="bg-gray-900 border border-gray-800 rounded-xl p-6 space-y-4">
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

      <!-- Initial Setup — hidden once setup has been completed -->
      <div v-if="!setupComplete" class="bg-gray-900 border border-gray-800 rounded-xl p-6 space-y-4">
        <h3 class="text-lg font-semibold text-white">Initial Setup</h3>
        <p class="text-sm text-gray-400">
          Replace the seeded demo data with your real account. This wipes all existing data and
          creates a new superadmin account with password "password" and a location.
        </p>

        <!-- Collapsed: button to start -->
        <button
          v-if="!setupActive"
          @click="startSetup"
          class="px-6 py-2 bg-amber-500 text-gray-950 font-semibold rounded-lg
                 hover:bg-amber-400 transition-colors"
        >
          Start Setup
        </button>

        <!-- Expanded: challenge + form -->
        <div v-else class="space-y-4">
          <!-- Step 1: Math challenge -->
          <div v-if="!setupChallengePassed" class="space-y-2">
            <p class="text-sm text-amber-300 font-medium">
              Solve to continue: <code class="px-1.5 py-0.5 bg-amber-900/50 rounded text-amber-200 font-bold">{{ setupChallengeA }} + {{ setupChallengeB }} = ?</code>
            </p>
            <div class="flex gap-3">
              <input
                v-model="setupChallengeAnswer"
                type="text"
                inputmode="numeric"
                placeholder="Your answer"
                class="flex-1 px-4 py-2 bg-gray-800 border border-amber-600/50 rounded-lg text-white
                       placeholder-gray-500 focus:outline-none focus:border-amber-500"
                @keyup.enter="checkSetupChallenge"
              />
              <button
                @click="checkSetupChallenge"
                class="px-5 py-1.5 bg-amber-600/30 text-amber-300 text-xs font-semibold rounded-lg
                       hover:bg-amber-600/40 transition-colors"
              >
                Submit
              </button>
              <button
                @click="cancelSetup"
                class="px-5 py-1.5 bg-gray-700 text-gray-300 text-xs font-medium rounded-lg
                       hover:bg-gray-600 transition-colors"
              >
                Cancel
              </button>
            </div>
            <p v-if="setupChallengeAnswer && !setupChallengePassed" class="text-xs text-red-400">Incorrect. Try again.</p>
          </div>

          <!-- Step 2: Setup form fields -->
          <template v-else>
            <div class="space-y-3">
              <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Your Name</label>
                <input
                  v-model="setupName"
                  type="text"
                  placeholder="e.g. Jane Smith"
                  class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white
                         placeholder-gray-500 focus:outline-none focus:border-amber-500"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Email</label>
                <input
                  v-model="setupEmail"
                  type="email"
                  placeholder="e.g. jane@example.com"
                  class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white
                         placeholder-gray-500 focus:outline-none focus:border-amber-500"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Location / Establishment Name</label>
                <input
                  v-model="setupLocationName"
                  type="text"
                  placeholder="e.g. The Anchor"
                  class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white
                         placeholder-gray-500 focus:outline-none focus:border-amber-500"
                />
              </div>
            </div>
            <div class="flex gap-3">
              <button
                @click="performSetup"
                :disabled="!setupName.trim() || !setupEmail.trim() || !setupLocationName.trim() || setupProcessing"
                class="px-6 py-2 bg-amber-500 text-gray-950 font-semibold rounded-lg
                       hover:bg-amber-400 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
              >
                {{ setupProcessing ? 'Setting up...' : 'Create Account & Wipe Demo Data' }}
              </button>
              <button
                @click="cancelSetup"
                class="px-5 py-2 bg-gray-700 text-gray-300 text-sm font-medium rounded-lg
                       hover:bg-gray-600 transition-colors"
              >
                Cancel
              </button>
            </div>
          </template>
        </div>
      </div>

      <!-- DANGER ZONE — only visible after initial setup has been completed -->
      <div v-if="setupComplete" class="border-2 border-red-600/50 rounded-xl p-6 space-y-5 bg-red-950/20">
        <div class="flex items-center gap-3">
          <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z" />
          </svg>
          <h3 class="text-lg font-bold text-red-400 uppercase tracking-wide">Danger Zone</h3>
        </div>

        <!-- Action buttons / confirm panels -->
        <div
          v-for="action in dangerActions"
          :key="action.key"
          class="border border-red-600/40 rounded-lg p-4 bg-red-950/40"
        >
          <!-- Collapsed: label + description + button -->
          <div v-if="activeAction !== action.key" class="flex items-center justify-between gap-4">
            <div class="flex-1">
              <p class="text-sm font-semibold text-red-300">{{ action.label }}</p>
              <p class="text-xs text-red-300/60 mt-0.5">{{ action.description }}</p>
            </div>
            <button
              @click="startAction(action.key)"
              class="shrink-0 px-4 py-1.5 text-xs font-semibold rounded-lg transition-colors
                     bg-red-600 text-white hover:bg-red-500"
            >
              {{ action.label }}
            </button>
          </div>

          <!-- Expanded: challenge + confirmation -->
          <div v-else class="space-y-3">
            <p class="text-sm font-semibold text-red-300">{{ action.label }}</p>
            <p class="text-xs text-red-300/80">{{ action.description }}</p>

            <!-- Step 1: Math challenge -->
            <div v-if="!challengePassed" class="space-y-2">
              <p class="text-sm text-red-300 font-medium">
                Solve to continue: <code class="px-1.5 py-0.5 bg-red-900/50 rounded text-red-200 font-bold">{{ challengeA }} + {{ challengeB }} = ?</code>
              </p>
              <div class="flex gap-3">
                <input
                  v-model="challengeAnswer"
                  type="text"
                  inputmode="numeric"
                  placeholder="Your answer"
                  class="flex-1 px-4 py-2 bg-gray-800 border border-red-600/50 rounded-lg text-white
                         placeholder-gray-500 focus:outline-none focus:border-red-500"
                  @keyup.enter="checkChallenge"
                />
                <button
                  @click="checkChallenge"
                  class="px-5 py-1.5 bg-red-600/30 text-red-300 text-xs font-semibold rounded-lg
                         hover:bg-red-600/40 transition-colors"
                >
                  Submit
                </button>
                <button
                  @click="cancelAction"
                  class="px-5 py-1.5 bg-gray-700 text-gray-300 text-xs font-medium rounded-lg
                         hover:bg-gray-600 transition-colors"
                >
                  Cancel
                </button>
              </div>
              <p v-if="challengeAnswer && !challengePassed" class="text-xs text-red-400">Incorrect. Try again.</p>
            </div>

            <!-- Step 2: Type confirm word -->
            <template v-else>
              <p class="text-sm text-red-300 font-medium">
                Type <code class="px-1.5 py-0.5 bg-red-900/50 rounded text-red-200 font-bold">{{ action.confirmWord }}</code> to confirm:
              </p>
              <input
                v-model="confirmText"
                type="text"
                :placeholder="`Type ${action.confirmWord}`"
                class="w-full px-4 py-2 bg-gray-800 border border-red-600/50 rounded-lg text-white
                       placeholder-gray-500 focus:outline-none focus:border-red-500"
              />
              <div class="flex gap-3">
                <button
                  @click="performAction(action)"
                  :disabled="confirmText !== action.confirmWord || processing"
                  class="px-5 py-1.5 bg-red-600 text-white text-xs font-semibold rounded-lg
                         hover:bg-red-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                >
                  {{ processing ? 'Processing...' : `Confirm ${action.label}` }}
                </button>
                <button
                  @click="cancelAction"
                  class="px-5 py-1.5 bg-gray-700 text-gray-300 text-xs font-medium rounded-lg
                         hover:bg-gray-600 transition-colors"
                >
                  Cancel
                </button>
              </div>
            </template>
          </div>
        </div>
      </div>
    </div>
  </AppShell>
</template>
