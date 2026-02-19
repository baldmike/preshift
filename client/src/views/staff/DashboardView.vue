<script setup lang="ts">
/**
 * DashboardView -- the main staff-facing view that aggregates all pre-shift
 * information in one place: 86'd items, daily specials, push items, and
 * announcements. Data is fetched on mount from the preshift store and then
 * kept in sync via Laravel Reverb WebSocket events scoped to the user's
 * location channel. Each section only renders if it has items, and an
 * empty-state message is shown when there is nothing for the day.
 */
import { onMounted, onUnmounted } from 'vue'
import { usePreshiftStore } from '@/stores/preshift'
import { useAuth } from '@/composables/useAuth'
import { useLocationChannel } from '@/composables/useReverb'
import AppShell from '@/components/layout/AppShell.vue'
import EightySixedCard from '@/components/EightySixedCard.vue'
import SpecialCard from '@/components/SpecialCard.vue'
import PushItemCard from '@/components/PushItemCard.vue'
import AnnouncementCard from '@/components/AnnouncementCard.vue'

// Central Pinia store holding all pre-shift data (86'd, specials, push items, announcements)
const store = usePreshiftStore()
// Current user's location ID, needed to subscribe to the correct Reverb channel
const { locationId } = useAuth()

// Reference to the Echo channel so we can unsubscribe on component teardown
let channel: ReturnType<typeof useLocationChannel> | null = null

onMounted(async () => {
  // Initial data load from the API via the preshift store
  await store.fetchAll()

  // Subscribe to the location-specific WebSocket channel for realtime updates.
  // Each .listen() call registers a handler for a specific broadcast event
  // so the dashboard updates instantly when managers make changes.
  if (locationId.value) {
    channel = useLocationChannel(locationId.value)

    channel
      // 86'd item events -- add new or remove restored items from the store
      .listen('.item.eighty-sixed', (e: any) => {
        store.addEightySixed(e.item || e)
      })
      .listen('.item.restored', (e: any) => {
        store.removeEightySixed(e.item?.id || e.id)
      })
      // Special events -- add, update, or remove specials in realtime
      .listen('.special.created', (e: any) => {
        store.addSpecial(e.special || e)
      })
      .listen('.special.updated', (e: any) => {
        store.updateSpecial(e.special || e)
      })
      .listen('.special.deleted', (e: any) => {
        store.removeSpecial(e.id)
      })
      // Push item events -- add, update, or remove push items in realtime
      .listen('.push-item.created', (e: any) => {
        store.addPushItem(e.pushItem || e)
      })
      .listen('.push-item.updated', (e: any) => {
        store.updatePushItem(e.pushItem || e)
      })
      .listen('.push-item.deleted', (e: any) => {
        store.removePushItem(e.id)
      })
      // Announcement events -- add, update, or remove announcements in realtime
      .listen('.announcement.posted', (e: any) => {
        store.addAnnouncement(e.announcement || e)
      })
      .listen('.announcement.updated', (e: any) => {
        store.updateAnnouncement(e.announcement || e)
      })
      .listen('.announcement.deleted', (e: any) => {
        store.removeAnnouncement(e.id)
      })
  }
})

// Clean up all WebSocket listeners when the component is destroyed
// to prevent memory leaks and duplicate event handling
onUnmounted(() => {
  if (channel && locationId.value) {
    channel.stopListening('.item.eighty-sixed')
    channel.stopListening('.item.restored')
    channel.stopListening('.special.created')
    channel.stopListening('.special.updated')
    channel.stopListening('.special.deleted')
    channel.stopListening('.push-item.created')
    channel.stopListening('.push-item.updated')
    channel.stopListening('.push-item.deleted')
    channel.stopListening('.announcement.posted')
    channel.stopListening('.announcement.updated')
    channel.stopListening('.announcement.deleted')
  }
})
</script>

<template>
  <AppShell>
    <div v-if="store.loading" class="flex items-center justify-center py-12">
      <svg class="animate-spin h-8 w-8 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
      </svg>
    </div>

    <div v-else class="space-y-6">
      <!-- 86'd Items -->
      <section v-if="store.eightySixed.length">
        <h2 class="text-lg font-bold text-red-700 mb-3 flex items-center gap-2">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
          </svg>
          86'd Items
        </h2>
        <div class="space-y-3">
          <EightySixedCard v-for="item in store.eightySixed" :key="item.id" :item="item" />
        </div>
      </section>

      <!-- Specials -->
      <section v-if="store.specials.length">
        <h2 class="text-lg font-bold text-blue-700 mb-3 flex items-center gap-2">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
          </svg>
          Today's Specials
        </h2>
        <div class="space-y-3">
          <SpecialCard v-for="special in store.specials" :key="special.id" :special="special" />
        </div>
      </section>

      <!-- Push Items -->
      <section v-if="store.pushItems.length">
        <h2 class="text-lg font-bold text-amber-700 mb-3 flex items-center gap-2">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
          </svg>
          Push Items
        </h2>
        <div class="space-y-3">
          <PushItemCard v-for="item in store.pushItems" :key="item.id" :item="item" />
        </div>
      </section>

      <!-- Announcements -->
      <section v-if="store.announcements.length">
        <h2 class="text-lg font-bold text-purple-700 mb-3 flex items-center gap-2">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
          </svg>
          Announcements
        </h2>
        <div class="space-y-3">
          <AnnouncementCard
            v-for="announcement in store.announcements"
            :key="announcement.id"
            :announcement="announcement"
          />
        </div>
      </section>

      <!-- Empty State -->
      <div
        v-if="!store.eightySixed.length && !store.specials.length && !store.pushItems.length && !store.announcements.length"
        class="text-center py-12"
      >
        <p class="text-gray-500 text-lg">Nothing for today's pre-shift.</p>
        <p class="text-gray-400 text-sm mt-1">Check back before your next shift.</p>
      </div>
    </div>
  </AppShell>
</template>
