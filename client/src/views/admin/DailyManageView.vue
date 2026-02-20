<script setup lang="ts">
/**
 * DailyManageView.vue
 *
 * All-in-one daily management view for admins and managers. Presents a 2x2
 * grid of 86'd Items, Specials, Push Items, and Announcements, each with
 * inline create/edit forms and action buttons (restore, decrement, edit,
 * delete). Also provides quick-nav links to Staff, Schedule, Drops,
 * Time Off, Menu, and Acknowledgments management pages.
 */
import { ref, onMounted } from 'vue'
import api from '@/composables/useApi'
import { useAuth } from '@/composables/useAuth'
import AppShell from '@/components/layout/AppShell.vue'
import type { EightySixed, Special, PushItem, Announcement } from '@/types'

const { isAdmin } = useAuth()

const manageLinks = [
  { to: '/manage/users', label: 'Staff' },
  { to: '/manage/schedule', label: 'Schedule' },
  { to: '/manage/shift-drops', label: 'Drops' },
  { to: '/manage/time-off', label: 'Time Off' },
  { to: '/manage/menu', label: 'Menu' },
  { to: '/manage/acknowledgments', label: "Ack's" },
]

// ─── 86'd Items ─────────────────────────────────────────────────────────────
const eightySixed = ref<EightySixed[]>([])
const esLoading = ref(false)
const esSubmitting = ref(false)
const esShowForm = ref(false)
const esForm = ref({ item_name: '', reason: '' })

async function fetchEightySixed() {
  esLoading.value = true
  try {
    const { data } = await api.get<EightySixed[]>('/api/eighty-sixed')
    eightySixed.value = data
  } finally {
    esLoading.value = false
  }
}

async function addEightySixed() {
  if (!esForm.value.item_name.trim()) return
  esSubmitting.value = true
  try {
    const { data } = await api.post('/api/eighty-sixed', {
      item_name: esForm.value.item_name,
      reason: esForm.value.reason || null,
    })
    eightySixed.value.push(data)
    esForm.value = { item_name: '', reason: '' }
    esShowForm.value = false
    toast("Item 86'd", 'success')
  } catch {
    toast('Failed to add item', 'error')
  } finally {
    esSubmitting.value = false
  }
}

async function restoreItem(id: number) {
  try {
    await api.patch(`/api/eighty-sixed/${id}/restore`)
    eightySixed.value = eightySixed.value.filter((i) => i.id !== id)
    toast('Item restored', 'success')
  } catch {
    toast('Failed to restore item', 'error')
  }
}

// ─── Specials ───────────────────────────────────────────────────────────────
const specials = ref<Special[]>([])
const spLoading = ref(false)
const spSubmitting = ref(false)
const spShowForm = ref(false)
const spEditingId = ref<number | null>(null)
const spForm = ref({ title: '', description: '', type: '', starts_at: '', ends_at: '', quantity: null as number | null })

function resetSpForm() {
  spForm.value = { title: '', description: '', type: '', starts_at: '', ends_at: '', quantity: null }
  spEditingId.value = null
  spShowForm.value = false
}

function editSpecial(s: Special) {
  spEditingId.value = s.id
  spForm.value = {
    title: s.title,
    description: s.description || '',
    type: s.type || '',
    starts_at: s.starts_at ? s.starts_at.split('T')[0] : '',
    ends_at: s.ends_at ? s.ends_at.split('T')[0] : '',
    quantity: s.quantity,
  }
  spShowForm.value = true
}

async function fetchSpecials() {
  spLoading.value = true
  try {
    const { data } = await api.get<Special[]>('/api/specials')
    specials.value = data
  } finally {
    spLoading.value = false
  }
}

async function saveSpecial() {
  if (!spForm.value.title.trim()) return
  spSubmitting.value = true
  try {
    const payload = {
      ...spForm.value,
      quantity: spForm.value.quantity !== null && spForm.value.quantity !== undefined
        ? Number(spForm.value.quantity)
        : null,
    }
    if (spEditingId.value) {
      const { data } = await api.patch(`/api/specials/${spEditingId.value}`, payload)
      const idx = specials.value.findIndex((s) => s.id === spEditingId.value)
      if (idx !== -1) specials.value[idx] = data
      toast('Special updated', 'success')
    } else {
      const { data } = await api.post('/api/specials', {
        ...payload,
        starts_at: spForm.value.starts_at || new Date().toISOString().split('T')[0],
      })
      specials.value.push(data)
      toast('Special created', 'success')
    }
    resetSpForm()
  } catch {
    toast('Failed to save special', 'error')
  } finally {
    spSubmitting.value = false
  }
}

async function decrementSpecial(id: number) {
  try {
    const { data } = await api.patch(`/api/specials/${id}/decrement`)
    const idx = specials.value.findIndex((s) => s.id === id)
    if (idx !== -1) specials.value[idx] = data
    toast('Quantity decremented', 'success')
  } catch {
    toast('Failed to decrement quantity', 'error')
  }
}

async function deleteSpecial(id: number) {
  if (!confirm('Delete this special?')) return
  try {
    await api.delete(`/api/specials/${id}`)
    specials.value = specials.value.filter((s) => s.id !== id)
    toast('Special deleted', 'success')
  } catch {
    toast('Failed to delete special', 'error')
  }
}

// ─── Push Items ─────────────────────────────────────────────────────────────
const pushItems = ref<PushItem[]>([])
const piLoading = ref(false)
const piSubmitting = ref(false)
const piShowForm = ref(false)
const piEditingId = ref<number | null>(null)
const piForm = ref({ title: '', description: '', reason: '', priority: 'medium' })

function resetPiForm() {
  piForm.value = { title: '', description: '', reason: '', priority: 'medium' }
  piEditingId.value = null
  piShowForm.value = false
}

function editPushItem(item: PushItem) {
  piEditingId.value = item.id
  piForm.value = {
    title: item.title,
    description: item.description || '',
    reason: item.reason || '',
    priority: item.priority || 'medium',
  }
  piShowForm.value = true
}

async function fetchPushItems() {
  piLoading.value = true
  try {
    const { data } = await api.get<PushItem[]>('/api/push-items')
    pushItems.value = data
  } finally {
    piLoading.value = false
  }
}

async function savePushItem() {
  if (!piForm.value.title.trim()) return
  piSubmitting.value = true
  try {
    if (piEditingId.value) {
      const { data } = await api.patch(`/api/push-items/${piEditingId.value}`, piForm.value)
      const idx = pushItems.value.findIndex((i) => i.id === piEditingId.value)
      if (idx !== -1) pushItems.value[idx] = data
      toast('Push item updated', 'success')
    } else {
      const { data } = await api.post('/api/push-items', piForm.value)
      pushItems.value.push(data)
      toast('Push item created', 'success')
    }
    resetPiForm()
  } catch {
    toast('Failed to save push item', 'error')
  } finally {
    piSubmitting.value = false
  }
}

async function deletePushItem(id: number) {
  if (!confirm('Delete this push item?')) return
  try {
    await api.delete(`/api/push-items/${id}`)
    pushItems.value = pushItems.value.filter((i) => i.id !== id)
    toast('Push item deleted', 'success')
  } catch {
    toast('Failed to delete push item', 'error')
  }
}

// ─── Announcements ──────────────────────────────────────────────────────────
const announcements = ref<Announcement[]>([])
const anLoading = ref(false)
const anSubmitting = ref(false)
const anShowForm = ref(false)
const anEditingId = ref<number | null>(null)
const anForm = ref({ title: '', body: '', priority: 'normal', expires_at: '' })

function resetAnForm() {
  anForm.value = { title: '', body: '', priority: 'normal', expires_at: '' }
  anEditingId.value = null
  anShowForm.value = false
}

function editAnnouncement(a: Announcement) {
  anEditingId.value = a.id
  anForm.value = {
    title: a.title,
    body: a.body || '',
    priority: a.priority || 'normal',
    expires_at: a.expires_at ? a.expires_at.split('T')[0] : '',
  }
  anShowForm.value = true
}

async function fetchAnnouncements() {
  anLoading.value = true
  try {
    const { data } = await api.get<Announcement[]>('/api/announcements')
    announcements.value = data
  } finally {
    anLoading.value = false
  }
}

async function saveAnnouncement() {
  if (!anForm.value.title.trim()) return
  anSubmitting.value = true
  try {
    const payload = {
      ...anForm.value,
      expires_at: anForm.value.expires_at || null,
    }
    if (anEditingId.value) {
      const { data } = await api.patch(`/api/announcements/${anEditingId.value}`, payload)
      const idx = announcements.value.findIndex((a) => a.id === anEditingId.value)
      if (idx !== -1) announcements.value[idx] = data
      toast('Announcement updated', 'success')
    } else {
      const { data } = await api.post('/api/announcements', payload)
      announcements.value.push(data)
      toast('Announcement posted', 'success')
    }
    resetAnForm()
  } catch {
    toast('Failed to save announcement', 'error')
  } finally {
    anSubmitting.value = false
  }
}

async function deleteAnnouncement(id: number) {
  if (!confirm('Delete this announcement?')) return
  try {
    await api.delete(`/api/announcements/${id}`)
    announcements.value = announcements.value.filter((a) => a.id !== id)
    toast('Announcement deleted', 'success')
  } catch {
    toast('Failed to delete announcement', 'error')
  }
}

// ─── Helpers ────────────────────────────────────────────────────────────────
function toast(message: string, type: string) {
  window.dispatchEvent(new CustomEvent('toast', { detail: { message, type } }))
}

function formatTime(dateStr: string) {
  return new Date(dateStr).toLocaleString([], {
    month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit',
  })
}

function formatDate(dateStr: string | null) {
  if (!dateStr) return 'None'
  return dateStr.split('T')[0]
}

onMounted(() => {
  fetchEightySixed()
  fetchSpecials()
  fetchPushItems()
  fetchAnnouncements()
})
</script>

<template>
  <AppShell>
    <!-- Page header -->
    <div class="flex items-center justify-between mb-3">
      <div>
        <h1 class="text-xl font-bold text-white tracking-tight">Daily Management</h1>
        <p class="text-xs text-gray-500 mt-0.5">Manage today's pre-shift content</p>
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

    <!-- Quick-nav to other management pages -->
    <div class="grid grid-cols-3 gap-2 mb-4">
      <router-link
        to="/manage/users"
        class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-md text-[11px] font-semibold whitespace-nowrap bg-white/[0.06] text-gray-400 hover:bg-white/[0.1] hover:text-white transition-colors"
      >
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
        Staff
      </router-link>
      <router-link
        to="/manage/schedule"
        class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-md text-[11px] font-semibold whitespace-nowrap bg-white/[0.06] text-gray-400 hover:bg-white/[0.1] hover:text-white transition-colors"
      >
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
        Schedule
      </router-link>
      <router-link
        to="/manage/shift-drops"
        class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-md text-[11px] font-semibold whitespace-nowrap bg-white/[0.06] text-gray-400 hover:bg-white/[0.1] hover:text-white transition-colors"
      >
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
        </svg>
        Drops
      </router-link>
      <router-link
        to="/manage/time-off"
        class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-md text-[11px] font-semibold whitespace-nowrap bg-white/[0.06] text-gray-400 hover:bg-white/[0.1] hover:text-white transition-colors"
      >
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        Time Off
      </router-link>
      <router-link
        to="/manage/menu"
        class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-md text-[11px] font-semibold whitespace-nowrap bg-white/[0.06] text-gray-400 hover:bg-white/[0.1] hover:text-white transition-colors"
      >
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
        </svg>
        Menu
      </router-link>
      <router-link
        to="/manage/acknowledgments"
        class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-md text-[11px] font-semibold whitespace-nowrap bg-white/[0.06] text-gray-400 hover:bg-white/[0.1] hover:text-white transition-colors"
      >
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        Ack's
      </router-link>
    </div>

    <!-- 2x2 grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

      <!-- ════════════════ 86'd Items ════════════════ -->
      <section class="dm-section dm-section--red">
        <header class="dm-header">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
          </svg>
          <h2 class="flex-1">86'd Items</h2>
          <span class="dm-count" v-if="eightySixed.length">{{ eightySixed.length }}</span>
          <button
            class="dm-add-btn"
            @click="esShowForm = !esShowForm"
            :title="esShowForm ? 'Close form' : '86 an item'"
          >
            <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-45': esShowForm }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
          </button>
        </header>

        <!-- Add form -->
        <div v-if="esShowForm" class="dm-form-wrap">
          <form @submit.prevent="addEightySixed" class="space-y-2">
            <input
              v-model="esForm.item_name"
              placeholder="Item name *"
              required
              class="dm-input"
            />
            <input
              v-model="esForm.reason"
              placeholder="Reason (optional)"
              class="dm-input"
            />
            <div class="flex gap-2">
              <button type="submit" :disabled="esSubmitting" class="dm-btn dm-btn--red">
                {{ esSubmitting ? 'Adding...' : "86 It" }}
              </button>
              <button type="button" class="dm-btn dm-btn--ghost" @click="esShowForm = false; esForm = { item_name: '', reason: '' }">Cancel</button>
            </div>
          </form>
        </div>

        <!-- List -->
        <div class="dm-body">
          <div v-if="esLoading" class="dm-loading">
            <svg class="animate-spin h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
            </svg>
          </div>
          <template v-else-if="eightySixed.length">
            <div v-for="item in eightySixed" :key="item.id" class="dm-item">
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-200 truncate">{{ item.item_name }}</p>
                <p class="text-xs text-gray-500">
                  {{ item.reason || 'No reason' }}
                  <span class="text-gray-600"> &middot; {{ item.user?.name || 'Unknown' }} &middot; {{ formatTime(item.created_at) }}</span>
                </p>
              </div>
              <button class="dm-action-btn text-green-400 hover:text-green-300" @click="restoreItem(item.id)" title="Restore">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
              </button>
            </div>
          </template>
          <p v-else class="text-gray-600 text-xs text-center py-6">Nothing 86'd right now</p>
        </div>
      </section>

      <!-- ════════════════ Specials ════════════════ -->
      <section class="dm-section dm-section--blue">
        <header class="dm-header">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
          </svg>
          <h2 class="flex-1">Specials</h2>
          <span class="dm-count" v-if="specials.length">{{ specials.length }}</span>
          <button
            class="dm-add-btn"
            @click="spShowForm ? resetSpForm() : (spShowForm = true)"
            :title="spShowForm ? 'Close form' : 'Add special'"
          >
            <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-45': spShowForm }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
          </button>
        </header>

        <!-- Form -->
        <div v-if="spShowForm" class="dm-form-wrap">
          <form @submit.prevent="saveSpecial" class="space-y-2">
            <input v-model="spForm.title" placeholder="Title *" required class="dm-input" />
            <input v-model="spForm.description" placeholder="Description" class="dm-input" />
            <div class="grid grid-cols-4 gap-2">
              <select v-model="spForm.type" class="dm-select">
                <option value="">Type</option>
                <option value="daily">Daily</option>
                <option value="weekly">Weekly</option>
                <option value="monthly">Monthly</option>
                <option value="limited_time">Limited Time</option>
              </select>
              <input v-model="spForm.starts_at" type="date" class="dm-input" placeholder="Start" />
              <input v-model="spForm.ends_at" type="date" class="dm-input" placeholder="End" />
              <input v-model.number="spForm.quantity" type="number" min="0" class="dm-input" placeholder="Qty" />
            </div>
            <div class="flex gap-2">
              <button type="submit" :disabled="spSubmitting" class="dm-btn dm-btn--blue">
                {{ spSubmitting ? 'Saving...' : (spEditingId ? 'Update' : 'Create') }}
              </button>
              <button type="button" class="dm-btn dm-btn--ghost" @click="resetSpForm()">Cancel</button>
            </div>
          </form>
        </div>

        <!-- List -->
        <div class="dm-body">
          <div v-if="spLoading" class="dm-loading">
            <svg class="animate-spin h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
            </svg>
          </div>
          <template v-else-if="specials.length">
            <div v-for="s in specials" :key="s.id" class="dm-item">
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                  <p class="text-sm font-medium text-gray-200 truncate">{{ s.title }}</p>
                  <span v-if="s.type" class="dm-badge dm-badge--blue">{{ s.type }}</span>
                  <span v-if="s.quantity != null" class="dm-badge dm-badge--amber">Qty: {{ s.quantity }}</span>
                </div>
                <p class="text-xs text-gray-500">
                  {{ formatDate(s.starts_at) }}{{ s.ends_at ? ` - ${formatDate(s.ends_at)}` : '' }}
                </p>
              </div>
              <div class="flex gap-1">
                <button
                  v-if="s.quantity != null && s.quantity > 0"
                  class="dm-action-btn text-amber-400 hover:text-amber-300"
                  @click="decrementSpecial(s.id)"
                  title="Decrement quantity"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                  </svg>
                </button>
                <button class="dm-action-btn text-blue-400 hover:text-blue-300" @click="editSpecial(s)" title="Edit">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                </button>
                <button class="dm-action-btn text-red-400 hover:text-red-300" @click="deleteSpecial(s.id)" title="Delete">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>
            </div>
          </template>
          <p v-else class="text-gray-600 text-xs text-center py-6">No specials</p>
        </div>
      </section>

      <!-- ════════════════ Push Items ════════════════ -->
      <section class="dm-section dm-section--amber">
        <header class="dm-header">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
          </svg>
          <h2 class="flex-1">Push Items</h2>
          <span class="dm-count" v-if="pushItems.length">{{ pushItems.length }}</span>
          <button
            class="dm-add-btn"
            @click="piShowForm ? resetPiForm() : (piShowForm = true)"
            :title="piShowForm ? 'Close form' : 'Add push item'"
          >
            <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-45': piShowForm }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
          </button>
        </header>

        <!-- Form -->
        <div v-if="piShowForm" class="dm-form-wrap">
          <form @submit.prevent="savePushItem" class="space-y-2">
            <input v-model="piForm.title" placeholder="Title *" required class="dm-input" />
            <input v-model="piForm.description" placeholder="Description" class="dm-input" />
            <div class="grid grid-cols-2 gap-2">
              <input v-model="piForm.reason" placeholder="Reason" class="dm-input" />
              <select v-model="piForm.priority" class="dm-select">
                <option value="low">Low</option>
                <option value="medium">Medium</option>
                <option value="high">High</option>
              </select>
            </div>
            <div class="flex gap-2">
              <button type="submit" :disabled="piSubmitting" class="dm-btn dm-btn--amber">
                {{ piSubmitting ? 'Saving...' : (piEditingId ? 'Update' : 'Create') }}
              </button>
              <button type="button" class="dm-btn dm-btn--ghost" @click="resetPiForm()">Cancel</button>
            </div>
          </form>
        </div>

        <!-- List -->
        <div class="dm-body">
          <div v-if="piLoading" class="dm-loading">
            <svg class="animate-spin h-5 w-5 text-amber-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
            </svg>
          </div>
          <template v-else-if="pushItems.length">
            <div v-for="item in pushItems" :key="item.id" class="dm-item">
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                  <p class="text-sm font-medium text-gray-200 truncate">{{ item.title }}</p>
                  <span v-if="item.priority" class="dm-badge" :class="{
                    'dm-badge--red': item.priority === 'high',
                    'dm-badge--amber': item.priority === 'medium',
                    'dm-badge--green': item.priority === 'low',
                  }">{{ item.priority }}</span>
                </div>
                <p class="text-xs text-gray-500">{{ item.reason || 'No reason specified' }}</p>
              </div>
              <div class="flex gap-1">
                <button class="dm-action-btn text-amber-400 hover:text-amber-300" @click="editPushItem(item)" title="Edit">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                </button>
                <button class="dm-action-btn text-red-400 hover:text-red-300" @click="deletePushItem(item.id)" title="Delete">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>
            </div>
          </template>
          <p v-else class="text-gray-600 text-xs text-center py-6">No push items</p>
        </div>
      </section>

      <!-- ════════════════ Announcements ════════════════ -->
      <section class="dm-section dm-section--purple">
        <header class="dm-header">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
          </svg>
          <h2 class="flex-1">Announcements</h2>
          <span class="dm-count" v-if="announcements.length">{{ announcements.length }}</span>
          <button
            class="dm-add-btn"
            @click="anShowForm ? resetAnForm() : (anShowForm = true)"
            :title="anShowForm ? 'Close form' : 'Add announcement'"
          >
            <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-45': anShowForm }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
          </button>
        </header>

        <!-- Form -->
        <div v-if="anShowForm" class="dm-form-wrap">
          <form @submit.prevent="saveAnnouncement" class="space-y-2">
            <input v-model="anForm.title" placeholder="Title *" required class="dm-input" />
            <textarea v-model="anForm.body" placeholder="Announcement body..." rows="3" class="dm-input dm-textarea"></textarea>
            <div class="grid grid-cols-2 gap-2">
              <select v-model="anForm.priority" class="dm-select">
                <option value="normal">Normal</option>
                <option value="important">Important</option>
                <option value="urgent">Urgent</option>
              </select>
              <input v-model="anForm.expires_at" type="date" class="dm-input" placeholder="Expires" />
            </div>
            <div class="flex gap-2">
              <button type="submit" :disabled="anSubmitting" class="dm-btn dm-btn--purple">
                {{ anSubmitting ? 'Saving...' : (anEditingId ? 'Update' : 'Post') }}
              </button>
              <button type="button" class="dm-btn dm-btn--ghost" @click="resetAnForm()">Cancel</button>
            </div>
          </form>
        </div>

        <!-- List -->
        <div class="dm-body">
          <div v-if="anLoading" class="dm-loading">
            <svg class="animate-spin h-5 w-5 text-purple-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
            </svg>
          </div>
          <template v-else-if="announcements.length">
            <div v-for="a in announcements" :key="a.id" class="dm-item">
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                  <p class="text-sm font-medium text-gray-200 truncate">{{ a.title }}</p>
                  <span v-if="a.priority" class="dm-badge" :class="{
                    'dm-badge--red': a.priority === 'urgent',
                    'dm-badge--amber': a.priority === 'important',
                    'dm-badge--blue': a.priority === 'normal',
                  }">{{ a.priority }}</span>
                </div>
                <p class="text-xs text-gray-500">
                  Expires: {{ a.expires_at ? formatDate(a.expires_at) : 'Never' }}
                </p>
              </div>
              <div class="flex gap-1">
                <button class="dm-action-btn text-purple-400 hover:text-purple-300" @click="editAnnouncement(a)" title="Edit">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                </button>
                <button class="dm-action-btn text-red-400 hover:text-red-300" @click="deleteAnnouncement(a.id)" title="Delete">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>
            </div>
          </template>
          <p v-else class="text-gray-600 text-xs text-center py-6">No announcements</p>
        </div>
      </section>

    </div>
  </AppShell>
</template>

<style scoped>
/* ─── Section container ─────────────────────────────────────────────────── */
.dm-section {
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 0.75rem;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

/* ─── Header ────────────────────────────────────────────────────────────── */
.dm-header {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.625rem 0.75rem;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}

.dm-header h2 {
  font-size: inherit;
  font-weight: inherit;
}

/* ─── Count badge ───────────────────────────────────────────────────────── */
.dm-count {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 1.25rem;
  height: 1.25rem;
  padding: 0 0.375rem;
  border-radius: 9999px;
  font-size: 0.625rem;
  font-weight: 700;
}

/* ─── Add button (+) ────────────────────────────────────────────────────── */
.dm-add-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 1.5rem;
  height: 1.5rem;
  border-radius: 0.375rem;
  background: rgba(255, 255, 255, 0.06);
  color: rgba(255, 255, 255, 0.5);
  transition: background 0.15s, color 0.15s;
}
.dm-add-btn:hover {
  background: rgba(255, 255, 255, 0.1);
  color: rgba(255, 255, 255, 0.8);
}

/* ─── Form wrapper ──────────────────────────────────────────────────────── */
.dm-form-wrap {
  padding: 0.75rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
  background: rgba(0, 0, 0, 0.2);
}

/* ─── Body / item list ──────────────────────────────────────────────────── */
.dm-body {
  flex: 1;
  padding: 0.5rem;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  overflow-y: auto;
  max-height: 45vh;
}

.dm-loading {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem 0;
}

/* ─── Item row ──────────────────────────────────────────────────────────── */
.dm-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 0.375rem;
  border-radius: 0.375rem;
  transition: background 0.1s;
}
.dm-item:hover {
  background: rgba(255, 255, 255, 0.04);
}

/* ─── Action buttons (edit/delete/restore) ──────────────────────────────── */
.dm-action-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 1.75rem;
  height: 1.75rem;
  border-radius: 0.375rem;
  transition: background 0.15s;
  flex-shrink: 0;
}
.dm-action-btn:hover {
  background: rgba(255, 255, 255, 0.06);
}

/* ─── Form inputs ───────────────────────────────────────────────────────── */
.dm-input {
  display: block;
  width: 100%;
  padding: 0.5rem 0.625rem;
  font-size: 0.8125rem;
  color: #f3f4f6;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 0.375rem;
  outline: none;
  transition: border-color 0.15s, box-shadow 0.15s;
}
.dm-input::placeholder { color: #4b5563; }
.dm-input:focus {
  border-color: rgba(255, 255, 255, 0.25);
  box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.05);
}

.dm-textarea {
  resize: vertical;
  min-height: 4rem;
}

.dm-select {
  display: block;
  width: 100%;
  padding: 0.5rem 0.625rem;
  font-size: 0.8125rem;
  color: #f3f4f6;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 0.375rem;
  outline: none;
  transition: border-color 0.15s;
}
.dm-select:focus {
  border-color: rgba(255, 255, 255, 0.25);
}

/* ─── Form buttons ──────────────────────────────────────────────────────── */
.dm-btn {
  padding: 0.375rem 0.75rem;
  font-size: 0.75rem;
  font-weight: 600;
  border-radius: 0.375rem;
  transition: background 0.15s, opacity 0.15s;
}
.dm-btn:disabled { opacity: 0.5; cursor: not-allowed; }

.dm-btn--red { background: rgba(239, 68, 68, 0.25); color: #fca5a5; }
.dm-btn--red:hover:not(:disabled) { background: rgba(239, 68, 68, 0.35); }

.dm-btn--blue { background: rgba(59, 130, 246, 0.25); color: #93c5fd; }
.dm-btn--blue:hover:not(:disabled) { background: rgba(59, 130, 246, 0.35); }

.dm-btn--amber { background: rgba(245, 158, 11, 0.25); color: #fcd34d; }
.dm-btn--amber:hover:not(:disabled) { background: rgba(245, 158, 11, 0.35); }

.dm-btn--purple { background: rgba(139, 92, 246, 0.25); color: #c4b5fd; }
.dm-btn--purple:hover:not(:disabled) { background: rgba(139, 92, 246, 0.35); }

.dm-btn--ghost { background: transparent; color: #9ca3af; }
.dm-btn--ghost:hover { background: rgba(255, 255, 255, 0.06); color: #d1d5db; }

/* ─── Inline badges ─────────────────────────────────────────────────────── */
.dm-badge {
  display: inline-block;
  padding: 0.0625rem 0.375rem;
  font-size: 0.625rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.025em;
  border-radius: 9999px;
  flex-shrink: 0;
}

.dm-badge--red { background: rgba(239, 68, 68, 0.2); color: #fca5a5; }
.dm-badge--blue { background: rgba(59, 130, 246, 0.2); color: #93c5fd; }
.dm-badge--amber { background: rgba(245, 158, 11, 0.2); color: #fcd34d; }
.dm-badge--green { background: rgba(34, 197, 94, 0.2); color: #86efac; }
.dm-badge--purple { background: rgba(139, 92, 246, 0.2); color: #c4b5fd; }

/* ─── Color themes per section ──────────────────────────────────────────── */
.dm-section--red .dm-header { color: #fca5a5; }
.dm-section--red .dm-count { background: rgba(239, 68, 68, 0.2); color: #fca5a5; }
.dm-section--red { border-color: rgba(239, 68, 68, 0.15); }

.dm-section--blue .dm-header { color: #93c5fd; }
.dm-section--blue .dm-count { background: rgba(59, 130, 246, 0.2); color: #93c5fd; }
.dm-section--blue { border-color: rgba(59, 130, 246, 0.15); }

.dm-section--amber .dm-header { color: #fcd34d; }
.dm-section--amber .dm-count { background: rgba(245, 158, 11, 0.2); color: #fcd34d; }
.dm-section--amber { border-color: rgba(245, 158, 11, 0.15); }

.dm-section--purple .dm-header { color: #c4b5fd; }
.dm-section--purple .dm-count { background: rgba(139, 92, 246, 0.2); color: #c4b5fd; }
.dm-section--purple { border-color: rgba(139, 92, 246, 0.15); }
</style>
