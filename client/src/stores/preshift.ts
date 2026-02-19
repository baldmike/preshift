import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/composables/useApi'
import type {
  EightySixed,
  Special,
  PushItem,
  Announcement,
  AcknowledgmentRef,
  PreShiftData,
} from '@/types'

export const usePreshiftStore = defineStore('preshift', () => {
  const eightySixed = ref<EightySixed[]>([])
  const specials = ref<Special[]>([])
  const pushItems = ref<PushItem[]>([])
  const announcements = ref<Announcement[]>([])
  const acknowledgments = ref<AcknowledgmentRef[]>([])
  const loading = ref(false)

  async function fetchAll() {
    loading.value = true
    try {
      const { data } = await api.get<PreShiftData>('/api/preshift')
      eightySixed.value = data.eighty_sixed
      specials.value = data.specials
      pushItems.value = data.push_items
      announcements.value = data.announcements
      acknowledgments.value = data.acknowledgments
    } finally {
      loading.value = false
    }
  }

  function addEightySixed(item: EightySixed) {
    eightySixed.value.push(item)
  }

  function removeEightySixed(id: number) {
    eightySixed.value = eightySixed.value.filter((i) => i.id !== id)
  }

  function addSpecial(item: Special) {
    specials.value.push(item)
  }

  function updateSpecial(item: Special) {
    const idx = specials.value.findIndex((s) => s.id === item.id)
    if (idx !== -1) specials.value[idx] = item
  }

  function removeSpecial(id: number) {
    specials.value = specials.value.filter((s) => s.id !== id)
  }

  function addPushItem(item: PushItem) {
    pushItems.value.push(item)
  }

  function updatePushItem(item: PushItem) {
    const idx = pushItems.value.findIndex((p) => p.id === item.id)
    if (idx !== -1) pushItems.value[idx] = item
  }

  function removePushItem(id: number) {
    pushItems.value = pushItems.value.filter((p) => p.id !== id)
  }

  function addAnnouncement(item: Announcement) {
    announcements.value.push(item)
  }

  function updateAnnouncement(item: Announcement) {
    const idx = announcements.value.findIndex((a) => a.id === item.id)
    if (idx !== -1) announcements.value[idx] = item
  }

  function removeAnnouncement(id: number) {
    announcements.value = announcements.value.filter((a) => a.id !== id)
  }

  function markAcknowledged(type: string, id: number) {
    if (!acknowledgments.value.some((a) => a.type === type && a.id === id)) {
      acknowledgments.value.push({ type, id })
    }
  }

  return {
    eightySixed,
    specials,
    pushItems,
    announcements,
    acknowledgments,
    loading,
    fetchAll,
    addEightySixed,
    removeEightySixed,
    addSpecial,
    updateSpecial,
    removeSpecial,
    addPushItem,
    updatePushItem,
    removePushItem,
    addAnnouncement,
    updateAnnouncement,
    removeAnnouncement,
    markAcknowledged,
  }
})
