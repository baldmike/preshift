<script setup lang="ts">
/**
 * TileDetailModal.vue
 *
 * A generic modal that displays the full, untruncated content of a dashboard
 * tile (86'd Items, Specials, Push Items, or Announcements). Driven by a
 * `tileType` prop that determines which store array to read and which theme
 * colors to apply. Reads items reactively from `usePreshiftStore()` so
 * Reverb updates appear live while the modal is open.
 *
 * Staff see read-only content with AcknowledgeButtons per item.
 * Managers/admins see inline edit forms and delete/restore controls (no
 * AcknowledgeButtons).
 *
 * Props:
 *   - tileType: 'eightySixed' | 'specials' | 'pushItems' | 'announcements' | null
 *               (null = modal closed)
 *
 * Emits:
 *   - close — fired when the modal should be dismissed
 */
import { computed, ref, reactive, watch } from 'vue'
import { usePreshiftStore } from '@/stores/preshift'
import { useAuth } from '@/composables/useAuth'
import { useAcknowledgments } from '@/composables/useAcknowledgments'
import api from '@/composables/useApi'
import BaseModal from '@/components/ui/BaseModal.vue'
import AcknowledgeButton from '@/components/AcknowledgeButton.vue'
import BadgePill from '@/components/ui/BadgePill.vue'

/** The four tile types the modal supports */
type TileType = 'eightySixed' | 'specials' | 'pushItems' | 'announcements'

const props = defineProps<{
  tileType: TileType | null
}>()

const emit = defineEmits<{
  close: []
}>()

const store = usePreshiftStore()
const { isAdmin, isManager } = useAuth()
const { isAcknowledged } = useAcknowledgments()

/** Whether the current user can edit content (admin or manager) */
const canEdit = computed(() => isAdmin.value || isManager.value)

/** Whether the modal is visible */
const isOpen = computed(() => props.tileType !== null)

// ── Tile configuration ──────────────────────────────────────────────────────
/** Static config per tile type: display title, color theme, ack type, empty text */
const TILE_CONFIG: Record<TileType, {
  title: string
  headerColor: string
  cardBg: string
  cardBorder: string
  titleColor: string
  textColor: string
  metaColor: string
  ackType: string
  emptyText: string
}> = {
  eightySixed: {
    title: "86'd Items",
    headerColor: 'text-red-300',
    cardBg: 'bg-red-500/5',
    cardBorder: 'border-red-500/10',
    titleColor: 'text-red-300',
    textColor: 'text-red-400/70',
    metaColor: 'text-red-500/60',
    ackType: 'eighty_sixed',
    emptyText: "Nothing 86'd",
  },
  specials: {
    title: 'Specials',
    headerColor: 'text-blue-300',
    cardBg: 'bg-blue-500/5',
    cardBorder: 'border-blue-500/10',
    titleColor: 'text-blue-300',
    textColor: 'text-blue-400/70',
    metaColor: 'text-blue-500/60',
    ackType: 'special',
    emptyText: 'No specials today',
  },
  pushItems: {
    title: 'Push Items',
    headerColor: 'text-amber-300',
    cardBg: 'bg-amber-500/5',
    cardBorder: 'border-amber-500/10',
    titleColor: 'text-amber-300',
    textColor: 'text-amber-400/70',
    metaColor: 'text-amber-500/60',
    ackType: 'push_item',
    emptyText: 'No push items',
  },
  announcements: {
    title: 'Announcements',
    headerColor: 'text-purple-300',
    cardBg: 'bg-purple-500/5',
    cardBorder: 'border-purple-500/10',
    titleColor: 'text-purple-300',
    textColor: 'text-purple-400/70',
    metaColor: 'text-purple-500/60',
    ackType: 'announcement',
    emptyText: 'No announcements',
  },
}

/** Active tile config (reactive to prop changes) */
const config = computed(() => props.tileType ? TILE_CONFIG[props.tileType] : null)

/** Items for the active tile, read directly from the store */
const items = computed(() => {
  if (!props.tileType) return []
  return store[props.tileType] as any[]
})

// ── Inline editing (managers/admins only) ───────────────────────────────────

/** ID of the item currently being edited, or null */
const editingId = ref<number | null>(null)

/** Whether the edit form is currently saving */
const saving = ref(false)

/** Reactive form fields — populated by startEdit() */
const editForm = reactive<Record<string, any>>({})

/** Reset edit state */
function resetEdit() {
  editingId.value = null
  saving.value = false
  Object.keys(editForm).forEach(k => delete editForm[k])
}

/** Reset edit state when the modal closes */
watch(() => props.tileType, () => {
  resetEdit()
})

/**
 * Populate the edit form for a given item based on the current tile type.
 * Different tile types have different editable fields.
 */
function startEdit(item: any) {
  editingId.value = item.id
  if (props.tileType === 'eightySixed') {
    editForm.item_name = item.item_name
    editForm.reason = item.reason || ''
  } else if (props.tileType === 'specials') {
    editForm.title = item.title
    editForm.description = item.description || ''
    editForm.type = item.type || ''
    editForm.starts_at = item.starts_at?.split('T')[0] || ''
    editForm.ends_at = item.ends_at?.split('T')[0] || ''
    editForm.quantity = item.quantity ?? ''
  } else if (props.tileType === 'pushItems') {
    editForm.title = item.title
    editForm.description = item.description || ''
    editForm.reason = item.reason || ''
    editForm.priority = item.priority || ''
  } else if (props.tileType === 'announcements') {
    editForm.title = item.title
    editForm.body = item.body || ''
    editForm.priority = item.priority || ''
    editForm.expires_at = item.expires_at?.split('T')[0] || ''
  }
}

/** Dispatch a toast event */
function toast(message: string, type: string) {
  window.dispatchEvent(new CustomEvent('toast', { detail: { message, type } }))
}

/** API endpoint path for the current tile type */
function apiPath(id: number): string {
  const map: Record<TileType, string> = {
    eightySixed: `/api/eighty-sixed/${id}`,
    specials: `/api/specials/${id}`,
    pushItems: `/api/push-items/${id}`,
    announcements: `/api/announcements/${id}`,
  }
  return map[props.tileType!]
}

/** Save the inline edit via PATCH, update the Pinia store, show toast */
async function saveEdit() {
  if (!editingId.value || !props.tileType) return
  saving.value = true
  try {
    const payload = { ...editForm }
    // Clean up empty optional fields
    if (payload.reason === '') payload.reason = null
    if (payload.description === '') payload.description = null
    if (payload.body === '') payload.body = null
    if (payload.type === '') payload.type = null
    if (payload.priority === '') payload.priority = null
    if (payload.ends_at === '') payload.ends_at = null
    if (payload.expires_at === '') payload.expires_at = null
    if (payload.quantity === '') payload.quantity = null

    const { data } = await api.patch(apiPath(editingId.value), payload)

    // Update the correct store array
    if (props.tileType === 'eightySixed') store.updateEightySixed(data)
    else if (props.tileType === 'specials') store.updateSpecial(data)
    else if (props.tileType === 'pushItems') store.updatePushItem(data)
    else if (props.tileType === 'announcements') store.updateAnnouncement(data)

    toast('Updated', 'success')
    resetEdit()
  } catch {
    toast('Failed to save', 'error')
  } finally {
    saving.value = false
  }
}

/**
 * Delete an item (or restore for 86'd items).
 * For 86'd: PATCH /api/eighty-sixed/{id}/restore removes it from the active list.
 * For others: DELETE removes the item.
 */
async function deleteItem(id: number) {
  if (!props.tileType) return
  try {
    if (props.tileType === 'eightySixed') {
      await api.post(`/api/eighty-sixed/${id}/restore`)
      store.removeEightySixed(id)
      toast('Item restored', 'success')
    } else if (props.tileType === 'specials') {
      await api.delete(`/api/specials/${id}`)
      store.removeSpecial(id)
      toast('Special deleted', 'success')
    } else if (props.tileType === 'pushItems') {
      await api.delete(`/api/push-items/${id}`)
      store.removePushItem(id)
      toast('Push item deleted', 'success')
    } else if (props.tileType === 'announcements') {
      await api.delete(`/api/announcements/${id}`)
      store.removeAnnouncement(id)
      toast('Announcement deleted', 'success')
    }
    if (editingId.value === id) resetEdit()
  } catch {
    toast('Failed to delete', 'error')
  }
}

/** Format an ISO date string into a short human-readable form */
function formatDate(dateStr: string | null): string {
  if (!dateStr) return ''
  return new Date(dateStr).toLocaleDateString([], { month: 'short', day: 'numeric' })
}

/** Format an ISO datetime string into a short human-readable form */
function formatDateTime(dateStr: string | null): string {
  if (!dateStr) return ''
  return new Date(dateStr).toLocaleString([], {
    month: 'short',
    day: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  })
}

/** Priority badge color mapping for push items */
const pushPriorityColor: Record<string, 'red' | 'yellow' | 'green'> = {
  high: 'red',
  medium: 'yellow',
  low: 'green',
}

/** Priority badge color mapping for announcements */
const announcementPriorityColor: Record<string, 'red' | 'yellow' | 'blue'> = {
  urgent: 'red',
  important: 'yellow',
  normal: 'blue',
}
</script>

<template>
  <BaseModal :open="isOpen" size="lg" @close="$emit('close')">
    <div v-if="config" class="p-5 space-y-4">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <h2 class="text-lg font-bold" :class="config.headerColor">{{ config.title }}</h2>
        <button
          type="button"
          class="text-gray-500 hover:text-gray-300 transition-colors"
          @click="$emit('close')"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Items list -->
      <div v-if="items.length" class="space-y-3">
        <div
          v-for="item in items"
          :key="item.id"
          class="rounded-lg border p-3"
          :class="[config.cardBg, config.cardBorder]"
        >
          <!-- Inline edit form -->
          <template v-if="editingId === item.id">
            <div class="space-y-2">
              <!-- 86'd edit fields -->
              <template v-if="tileType === 'eightySixed'">
                <input
                  v-model="editForm.item_name"
                  type="text"
                  placeholder="Item name"
                  class="w-full bg-white/[0.05] border border-white/[0.08] rounded px-2 py-1 text-xs text-gray-200 placeholder-gray-500 focus:outline-none focus:border-red-500/40"
                />
                <input
                  v-model="editForm.reason"
                  type="text"
                  placeholder="Reason (optional)"
                  class="w-full bg-white/[0.05] border border-white/[0.08] rounded px-2 py-1 text-xs text-gray-200 placeholder-gray-500 focus:outline-none focus:border-red-500/40"
                />
              </template>

              <!-- Specials edit fields -->
              <template v-if="tileType === 'specials'">
                <input
                  v-model="editForm.title"
                  type="text"
                  placeholder="Title"
                  class="w-full bg-white/[0.05] border border-white/[0.08] rounded px-2 py-1 text-xs text-gray-200 placeholder-gray-500 focus:outline-none focus:border-blue-500/40"
                />
                <input
                  v-model="editForm.description"
                  type="text"
                  placeholder="Description (optional)"
                  class="w-full bg-white/[0.05] border border-white/[0.08] rounded px-2 py-1 text-xs text-gray-200 placeholder-gray-500 focus:outline-none focus:border-blue-500/40"
                />
                <div class="flex gap-2">
                  <input
                    v-model="editForm.type"
                    type="text"
                    placeholder="Type (optional)"
                    class="flex-1 bg-white/[0.05] border border-white/[0.08] rounded px-2 py-1 text-xs text-gray-200 placeholder-gray-500 focus:outline-none focus:border-blue-500/40"
                  />
                  <input
                    v-model="editForm.quantity"
                    type="number"
                    placeholder="Qty"
                    class="w-20 bg-white/[0.05] border border-white/[0.08] rounded px-2 py-1 text-xs text-gray-200 placeholder-gray-500 focus:outline-none focus:border-blue-500/40"
                  />
                </div>
                <div class="flex gap-2">
                  <input
                    v-model="editForm.starts_at"
                    type="date"
                    class="flex-1 bg-white/[0.05] border border-white/[0.08] rounded px-2 py-1 text-xs text-gray-200 focus:outline-none focus:border-blue-500/40"
                  />
                  <input
                    v-model="editForm.ends_at"
                    type="date"
                    class="flex-1 bg-white/[0.05] border border-white/[0.08] rounded px-2 py-1 text-xs text-gray-200 focus:outline-none focus:border-blue-500/40"
                  />
                </div>
              </template>

              <!-- Push Items edit fields -->
              <template v-if="tileType === 'pushItems'">
                <input
                  v-model="editForm.title"
                  type="text"
                  placeholder="Title"
                  class="w-full bg-white/[0.05] border border-white/[0.08] rounded px-2 py-1 text-xs text-gray-200 placeholder-gray-500 focus:outline-none focus:border-amber-500/40"
                />
                <input
                  v-model="editForm.description"
                  type="text"
                  placeholder="Description (optional)"
                  class="w-full bg-white/[0.05] border border-white/[0.08] rounded px-2 py-1 text-xs text-gray-200 placeholder-gray-500 focus:outline-none focus:border-amber-500/40"
                />
                <div class="flex gap-2">
                  <input
                    v-model="editForm.reason"
                    type="text"
                    placeholder="Reason (optional)"
                    class="flex-1 bg-white/[0.05] border border-white/[0.08] rounded px-2 py-1 text-xs text-gray-200 placeholder-gray-500 focus:outline-none focus:border-amber-500/40"
                  />
                  <select
                    v-model="editForm.priority"
                    class="bg-white/[0.05] border border-white/[0.08] rounded px-2 py-1 text-xs text-gray-200 focus:outline-none focus:border-amber-500/40"
                  >
                    <option value="">Priority</option>
                    <option value="high">High</option>
                    <option value="medium">Medium</option>
                    <option value="low">Low</option>
                  </select>
                </div>
              </template>

              <!-- Announcements edit fields -->
              <template v-if="tileType === 'announcements'">
                <input
                  v-model="editForm.title"
                  type="text"
                  placeholder="Title"
                  class="w-full bg-white/[0.05] border border-white/[0.08] rounded px-2 py-1 text-xs text-gray-200 placeholder-gray-500 focus:outline-none focus:border-purple-500/40"
                />
                <textarea
                  v-model="editForm.body"
                  placeholder="Body (optional)"
                  rows="3"
                  class="w-full bg-white/[0.05] border border-white/[0.08] rounded px-2 py-1 text-xs text-gray-200 placeholder-gray-500 focus:outline-none focus:border-purple-500/40 resize-none"
                />
                <div class="flex gap-2">
                  <select
                    v-model="editForm.priority"
                    class="flex-1 bg-white/[0.05] border border-white/[0.08] rounded px-2 py-1 text-xs text-gray-200 focus:outline-none focus:border-purple-500/40"
                  >
                    <option value="">Priority</option>
                    <option value="urgent">Urgent</option>
                    <option value="important">Important</option>
                    <option value="normal">Normal</option>
                  </select>
                  <input
                    v-model="editForm.expires_at"
                    type="date"
                    class="flex-1 bg-white/[0.05] border border-white/[0.08] rounded px-2 py-1 text-xs text-gray-200 focus:outline-none focus:border-purple-500/40"
                  />
                </div>
              </template>

              <!-- Save / Cancel buttons -->
              <div class="flex gap-2 justify-end">
                <button
                  type="button"
                  @click="resetEdit"
                  class="px-2 py-1 text-[10px] font-semibold text-gray-400 hover:text-gray-300 transition-colors"
                >
                  Cancel
                </button>
                <button
                  type="button"
                  @click="saveEdit"
                  :disabled="saving"
                  class="px-2.5 py-1 text-[10px] font-semibold rounded bg-white/[0.08] text-gray-200 hover:bg-white/[0.12] transition-colors disabled:opacity-40"
                >
                  {{ saving ? 'Saving...' : 'Save' }}
                </button>
              </div>
            </div>
          </template>

          <!-- Read mode -->
          <template v-else>
            <div class="flex items-start justify-between gap-2">
              <div class="min-w-0 flex-1">
                <!-- 86'd item display -->
                <template v-if="tileType === 'eightySixed'">
                  <h4 class="font-semibold text-sm break-words" :class="config.titleColor">{{ item.item_name }}</h4>
                  <p v-if="item.reason" class="text-xs mt-0.5" :class="config.textColor">{{ item.reason }}</p>
                  <div class="flex items-center gap-2 mt-1.5 text-[10px]" :class="config.metaColor">
                    <span v-if="item.user">{{ item.user.name }}</span>
                    <span>{{ formatDateTime(item.created_at) }}</span>
                  </div>
                </template>

                <!-- Special display -->
                <template v-if="tileType === 'specials'">
                  <div class="flex items-start gap-1.5 flex-wrap">
                    <h4 class="font-semibold text-sm break-words" :class="config.titleColor">{{ item.title }}</h4>
                    <BadgePill v-if="item.type" :label="item.type" color="blue" />
                  </div>
                  <p v-if="item.description" class="text-xs mt-0.5" :class="config.textColor">{{ item.description }}</p>
                  <div class="flex items-center gap-2 text-[10px] mt-1.5" :class="config.metaColor">
                    <span>{{ formatDate(item.starts_at) }}</span>
                    <span v-if="item.ends_at"> &ndash; {{ formatDate(item.ends_at) }}</span>
                    <span v-if="item.quantity != null" class="text-amber-400/80 font-medium">{{ item.quantity }} left</span>
                  </div>
                </template>

                <!-- Push item display -->
                <template v-if="tileType === 'pushItems'">
                  <div class="flex items-start gap-1.5 flex-wrap">
                    <h4 class="font-semibold text-sm break-words" :class="config.titleColor">{{ item.title }}</h4>
                    <BadgePill
                      v-if="item.priority"
                      :label="item.priority"
                      :color="pushPriorityColor[item.priority] || 'gray'"
                    />
                  </div>
                  <p v-if="item.description" class="text-xs mt-0.5" :class="config.textColor">{{ item.description }}</p>
                  <p v-if="item.reason" class="text-[10px] mt-1 italic" :class="config.metaColor">{{ item.reason }}</p>
                </template>

                <!-- Announcement display -->
                <template v-if="tileType === 'announcements'">
                  <div class="flex items-start gap-1.5 flex-wrap">
                    <h4 class="font-semibold text-sm break-words" :class="config.titleColor">{{ item.title }}</h4>
                    <BadgePill
                      v-if="item.priority"
                      :label="item.priority"
                      :color="announcementPriorityColor[item.priority] || 'gray'"
                    />
                  </div>
                  <p v-if="item.body" class="text-xs mt-0.5" :class="config.textColor">{{ item.body }}</p>
                  <div class="flex items-center gap-2 mt-1.5 text-[10px]" :class="config.metaColor">
                    <span v-if="item.poster">{{ item.poster.name }}</span>
                    <span v-if="item.expires_at">Exp {{ formatDateTime(item.expires_at) }}</span>
                  </div>
                </template>
              </div>

              <!-- Action buttons -->
              <div class="flex items-center gap-1.5 shrink-0">
                <!-- Edit / Delete (managers/admins) -->
                <template v-if="canEdit">
                  <button
                    type="button"
                    data-testid="edit-button"
                    @click="startEdit(item)"
                    class="text-gray-500 hover:text-gray-300 transition-colors"
                  >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                  </button>
                  <button
                    type="button"
                    data-testid="delete-button"
                    @click="deleteItem(item.id)"
                    class="text-gray-500 hover:text-red-400 transition-colors"
                  >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </template>

                <!-- Acknowledge button (staff only) -->
                <AcknowledgeButton
                  v-if="config && !canEdit"
                  :type="config.ackType"
                  :id="item.id"
                  :acknowledged="isAcknowledged(config.ackType, item.id)"
                  size="sm"
                />
              </div>
            </div>
          </template>
        </div>
      </div>

      <!-- Empty state -->
      <p v-else class="text-gray-600 text-xs text-center py-6" data-testid="empty-state">
        {{ config.emptyText }}
      </p>
    </div>
  </BaseModal>
</template>
