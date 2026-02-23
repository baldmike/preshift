/**
 * stores/preshift.ts
 *
 * Pinia store that holds all pre-shift dashboard data for the current
 * location.  "Pre-shift" refers to the information staff need before
 * starting their shift: which items are 86'd, current specials, push items,
 * and management announcements.
 *
 * Data flow:
 *  1. `fetchAll()` makes a single `GET /api/preshift` call that returns a
 *     `PreShiftData` payload containing all four entity arrays plus the
 *     current user's acknowledgment refs.
 *  2. Realtime updates arrive via Laravel Reverb WebSocket events.  The
 *     dashboard components listen on the private location channel and call
 *     the granular add/update/remove methods below to patch the store
 *     without a full re-fetch.
 *
 * State shape:
 *  - `eightySixed`    : Array of currently 86'd items
 *  - `specials`       : Array of active specials
 *  - `pushItems`      : Array of active push items
 *  - `announcements`  : Array of current announcements
 *  - `acknowledgments`: Array of AcknowledgmentRef objects for items the
 *                        current user has already acknowledged
 *  - `loading`        : Boolean flag set to true while `fetchAll()` is in flight
 */

import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/composables/useApi'
import type {
  EightySixed,
  Special,
  PushItem,
  Announcement,
  Event,
  AcknowledgmentRef,
  PreShiftData,
} from '@/types'

export const usePreshiftStore = defineStore('preshift', () => {
  // -------------------------------------------------------------------------
  // State -- each ref holds an array of domain entities for the dashboard
  // -------------------------------------------------------------------------

  /** Items that are currently 86'd (unavailable) at the location */
  const eightySixed = ref<EightySixed[]>([])

  /** Active specials (happy-hour deals, chef specials, etc.) */
  const specials = ref<Special[]>([])

  /** Items management wants staff to actively upsell */
  const pushItems = ref<PushItem[]>([])

  /** Management announcements broadcast to staff */
  const announcements = ref<Announcement[]>([])

  /** Today's daily events for the location */
  const events = ref<Event[]>([])

  /**
   * Lightweight acknowledgment references for the current user.
   * Used by `useAcknowledgments` composable to check if an item has been
   * acknowledged without hitting the API again.
   */
  const acknowledgments = ref<AcknowledgmentRef[]>([])

  /** True while the initial `fetchAll()` request is in progress */
  const loading = ref(false)

  // -------------------------------------------------------------------------
  // Actions -- bulk fetch
  // -------------------------------------------------------------------------

  /**
   * Fetches all pre-shift data for the current user's location in a single
   * API call.
   *
   * Endpoint: `GET /api/preshift`
   * Expected response: `PreShiftData` (see types/index.ts)
   *
   * Sets `loading` to true before the request and resets it in `finally`
   * so the UI can show a loading indicator.
   */
  async function fetchAll() {
    loading.value = true
    try {
      const { data } = await api.get<PreShiftData>('/api/preshift')
      // Populate all state arrays from the combined API response
      eightySixed.value = data.eighty_sixed
      specials.value = data.specials
      pushItems.value = data.push_items
      announcements.value = data.announcements
      events.value = data.events
      acknowledgments.value = data.acknowledgments
    } finally {
      // Always reset loading, even if the request failed
      loading.value = false
    }
  }

  // -------------------------------------------------------------------------
  // Actions -- realtime granular mutations for 86'd items
  // These are called by WebSocket event handlers to patch the store in
  // real time without re-fetching the entire dataset.
  // -------------------------------------------------------------------------

  /**
   * Adds a newly-86'd item to the local list.
   * Called when a `EightySixedCreated` event arrives via Reverb.
   *
   * @param item - The full EightySixed object broadcast from the server
   */
  function addEightySixed(item: EightySixed) {
    eightySixed.value.push(item)
  }

  /**
   * Replaces an existing 86'd item in the local list with an updated version.
   * Called when an `EightySixedUpdated` event arrives via Reverb.
   *
   * @param item - The updated EightySixed object (must include `item.id`)
   */
  function updateEightySixed(item: EightySixed) {
    const idx = eightySixed.value.findIndex((i) => i.id === item.id)
    if (idx !== -1) eightySixed.value[idx] = item
  }

  /**
   * Removes an 86'd item from the local list (i.e. the item has been restored).
   * Called when a `EightySixedRestored` event arrives via Reverb.
   *
   * @param id - The primary key of the EightySixed record to remove
   */
  function removeEightySixed(id: number) {
    eightySixed.value = eightySixed.value.filter((i) => i.id !== id)
  }

  // -------------------------------------------------------------------------
  // Actions -- realtime granular mutations for specials
  // -------------------------------------------------------------------------

  /**
   * Adds a new special to the local list.
   * Called when a `SpecialCreated` event arrives via Reverb.
   *
   * @param item - The full Special object broadcast from the server
   */
  function addSpecial(item: Special) {
    specials.value.push(item)
  }

  /**
   * Replaces an existing special in the local list with an updated version.
   * Called when a `SpecialUpdated` event arrives via Reverb.
   *
   * @param item - The updated Special object (must include `item.id`)
   */
  function updateSpecial(item: Special) {
    const idx = specials.value.findIndex((s) => s.id === item.id)
    if (idx !== -1) specials.value[idx] = item
  }

  /**
   * Removes a special from the local list.
   * Called when a `SpecialDeleted` event arrives via Reverb.
   *
   * @param id - The primary key of the Special to remove
   */
  function removeSpecial(id: number) {
    specials.value = specials.value.filter((s) => s.id !== id)
  }

  // -------------------------------------------------------------------------
  // Actions -- realtime granular mutations for push items
  // -------------------------------------------------------------------------

  /**
   * Adds a new push item to the local list.
   * Called when a `PushItemCreated` event arrives via Reverb.
   *
   * @param item - The full PushItem object broadcast from the server
   */
  function addPushItem(item: PushItem) {
    pushItems.value.push(item)
  }

  /**
   * Replaces an existing push item in the local list with an updated version.
   * Called when a `PushItemUpdated` event arrives via Reverb.
   *
   * @param item - The updated PushItem object (must include `item.id`)
   */
  function updatePushItem(item: PushItem) {
    const idx = pushItems.value.findIndex((p) => p.id === item.id)
    if (idx !== -1) pushItems.value[idx] = item
  }

  /**
   * Removes a push item from the local list.
   * Called when a `PushItemDeleted` event arrives via Reverb.
   *
   * @param id - The primary key of the PushItem to remove
   */
  function removePushItem(id: number) {
    pushItems.value = pushItems.value.filter((p) => p.id !== id)
  }

  // -------------------------------------------------------------------------
  // Actions -- realtime granular mutations for announcements
  // -------------------------------------------------------------------------

  /**
   * Adds a new announcement to the local list.
   * Called when an `AnnouncementCreated` event arrives via Reverb.
   *
   * @param item - The full Announcement object broadcast from the server
   */
  function addAnnouncement(item: Announcement) {
    announcements.value.push(item)
  }

  /**
   * Replaces an existing announcement in the local list with an updated version.
   * Called when an `AnnouncementUpdated` event arrives via Reverb.
   *
   * @param item - The updated Announcement object (must include `item.id`)
   */
  function updateAnnouncement(item: Announcement) {
    const idx = announcements.value.findIndex((a) => a.id === item.id)
    if (idx !== -1) announcements.value[idx] = item
  }

  /**
   * Removes an announcement from the local list.
   * Called when an `AnnouncementDeleted` event arrives via Reverb.
   *
   * @param id - The primary key of the Announcement to remove
   */
  function removeAnnouncement(id: number) {
    announcements.value = announcements.value.filter((a) => a.id !== id)
  }

  // -------------------------------------------------------------------------
  // Actions -- realtime granular mutations for events
  // -------------------------------------------------------------------------

  /**
   * Adds a new event to the local list.
   * Called when an `EventCreated` event arrives via Reverb.
   *
   * @param item - The full Event object broadcast from the server
   */
  function addEvent(item: Event) {
    events.value.push(item)
  }

  /**
   * Replaces an existing event in the local list with an updated version.
   * Called when an `EventUpdated` event arrives via Reverb.
   *
   * @param item - The updated Event object (must include `item.id`)
   */
  function updateEvent(item: Event) {
    const idx = events.value.findIndex((e) => e.id === item.id)
    if (idx !== -1) events.value[idx] = item
  }

  /**
   * Removes an event from the local list.
   * Called when an `EventDeleted` event arrives via Reverb.
   *
   * @param id - The primary key of the Event to remove
   */
  function removeEvent(id: number) {
    events.value = events.value.filter((e) => e.id !== id)
  }

  // -------------------------------------------------------------------------
  // Actions -- acknowledgment tracking
  // -------------------------------------------------------------------------

  /**
   * Records that the current user has acknowledged an item locally.
   * This is called by the `useAcknowledgments` composable after the API
   * POST succeeds.  It prevents duplicate entries by checking existence first.
   *
   * @param type - The entity type shorthand (e.g. "announcement", "special")
   * @param id   - The primary key of the acknowledged entity
   */
  function markAcknowledged(type: string, id: number) {
    // Only add if not already present to avoid duplicates in the array
    if (!acknowledgments.value.some((a) => a.type === type && a.id === id)) {
      acknowledgments.value.push({ type, id })
    }
  }

  // -------------------------------------------------------------------------
  // Public API exposed to components, composables, and WebSocket handlers
  // -------------------------------------------------------------------------
  return {
    // State
    eightySixed,
    specials,
    pushItems,
    announcements,
    events,
    acknowledgments,
    loading,
    // Bulk fetch
    fetchAll,
    // 86'd item mutations
    addEightySixed,
    updateEightySixed,
    removeEightySixed,
    // Special mutations
    addSpecial,
    updateSpecial,
    removeSpecial,
    // Push item mutations
    addPushItem,
    updatePushItem,
    removePushItem,
    // Announcement mutations
    addAnnouncement,
    updateAnnouncement,
    removeAnnouncement,
    // Event mutations
    addEvent,
    updateEvent,
    removeEvent,
    // Acknowledgment tracking
    markAcknowledged,
  }
})
