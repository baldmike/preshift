<script setup lang="ts">
/**
 * DashboardView.vue
 *
 * Primary staff dashboard and pre-shift briefing screen. Displays a 2x2
 * grid of 86'd items, specials, push items, and announcements, plus a
 * "Today's Schedule" section showing who is working each shift. Subscribes
 * to real-time Reverb events on the location channel so cards update live
 * as managers make changes. Also surfaces quick-nav links to the shift
 * drop board and time-off requests.
 */
import { onMounted, onUnmounted, computed } from 'vue'
import { usePreshiftStore } from '@/stores/preshift'
import { useScheduleStore } from '@/stores/schedule'
import { useAuth } from '@/composables/useAuth'
import { useSchedule } from '@/composables/useSchedule'
import { useLocationChannel } from '@/composables/useReverb'
import api from '@/composables/useApi'
import AppShell from '@/components/layout/AppShell.vue'
import EightySixedCard from '@/components/EightySixedCard.vue'
import SpecialCard from '@/components/SpecialCard.vue'
import PushItemCard from '@/components/PushItemCard.vue'
import AnnouncementCard from '@/components/AnnouncementCard.vue'

const store = usePreshiftStore()
const scheduleStore = useScheduleStore()
const { locationId } = useAuth()
const { nextShift, currentWeekShifts, currentWeekRange, formatShiftTime } = useSchedule()

/** Today's date as "YYYY-MM-DD" for filtering schedule entries */
const todayISO = computed(() => {
  const d = new Date()
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
})

/** Human-readable label for today (e.g. "Friday, February 20") */
const todayLabel = computed(() => {
  return new Date().toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' })
})

/** All schedule entries for today, filtered from the published week schedule */
const todayEntries = computed(() => {
  const schedule = scheduleStore.currentSchedule
  if (!schedule?.entries) return []
  return schedule.entries.filter(e => e.date.split('T')[0] === todayISO.value)
})

/**
 * Today's entries grouped by time slot for display.
 * Each group has a `template` (with start_time/end_time for the heading)
 * and an `entries` array of staff assigned to that slot.
 * Uses the eagerly-loaded shift_template from each entry.
 */
const todayByShift = computed(() => {
  const groups: Record<number, { template: any; entries: any[] }> = {}
  for (const entry of todayEntries.value) {
    const tid = entry.shift_template_id
    if (!groups[tid]) {
      groups[tid] = {
        template: entry.shift_template || scheduleStore.shiftTemplates.find(t => t.id === tid),
        entries: [],
      }
    }
    groups[tid].entries.push(entry)
  }
  return Object.values(groups)
})

/**
 * Generates an array of 7 day objects (Monday through Sunday) for the
 * current week.  Each object contains:
 *  - `date`      : ISO date string (YYYY-MM-DD)
 *  - `dayAbbrev` : 3-letter day abbreviation ("Mon", "Tue", etc.)
 *  - `dayNum`    : Calendar day number (1-31)
 *  - `isToday`   : Whether this day is today
 *
 * Used by the template to render the 7-column week strip.
 */
const weekDays = computed(() => {
  const { monday } = currentWeekRange.value
  // Build a Date from the monday ISO string (local time)
  const mon = new Date(monday + 'T00:00:00')
  const today = new Date()
  // Today's ISO string for comparison
  const todayISO = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`

  const days = []
  for (let i = 0; i < 7; i++) {
    const d = new Date(mon)
    d.setDate(mon.getDate() + i)
    const iso = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
    days.push({
      date: iso,
      dayAbbrev: d.toLocaleDateString('en-US', { weekday: 'short' }), // Mon, Tue, …
      dayNum: d.getDate(),
      isToday: iso === todayISO,
    })
  }
  return days
})

/**
 * True when the user has at least one shift in the current Mon–Sun week.
 * Determines whether we show the full weekly strip or the simpler
 * "Next Shift" fallback.
 */
const hasCurrentWeekShifts = computed(() => {
  return Object.keys(currentWeekShifts.value).length > 0
})

/**
 * Human-readable label for the current week's date range.
 * e.g. "Feb 16 – 22" (same month) or "Feb 28 – Mar 6" (cross-month).
 */
const weekRangeLabel = computed(() => {
  const { monday } = currentWeekRange.value
  const mon = new Date(monday + 'T00:00:00')
  const sun = new Date(mon)
  sun.setDate(mon.getDate() + 6)

  const opts: Intl.DateTimeFormatOptions = { month: 'short', day: 'numeric' }
  // If both dates are in the same month, shorten to "Feb 16 – 22"
  if (mon.getMonth() === sun.getMonth()) {
    return `${mon.toLocaleDateString('en-US', opts)} – ${sun.getDate()}`
  }
  // Cross-month: "Feb 28 – Mar 6"
  return `${mon.toLocaleDateString('en-US', opts)} – ${sun.toLocaleDateString('en-US', opts)}`
})

function toast(message: string, type: string) {
  window.dispatchEvent(new CustomEvent('toast', { detail: { message, type } }))
}

async function giveUpShift(entryId: number) {
  try {
    const { data } = await api.post('/api/shift-drops', { schedule_entry_id: entryId })
    scheduleStore.upsertShiftDrop(data)
    toast('Shift dropped — waiting for a volunteer', 'success')
  } catch {
    toast('Failed to drop shift', 'error')
  }
}

let channel: ReturnType<typeof useLocationChannel> | null = null

const hasContent = computed(() =>
  store.eightySixed.length || store.specials.length || store.pushItems.length || store.announcements.length
)

onMounted(async () => {
  await store.fetchAll()
  // Fetch the user's shifts + the full current-week published schedule
  scheduleStore.fetchMyShifts()
  scheduleStore.fetchCurrentWeekSchedule()

  if (locationId.value) {
    channel = useLocationChannel(locationId.value)
    channel
      .listen('.item.eighty-sixed', (e: any) => store.addEightySixed(e.item || e))
      .listen('.item.restored', (e: any) => store.removeEightySixed(e.item?.id || e.id))
      .listen('.special.created', (e: any) => store.addSpecial(e.special || e))
      .listen('.special.updated', (e: any) => store.updateSpecial(e.special || e))
      .listen('.special.deleted', (e: any) => store.removeSpecial(e.id))
      .listen('.push-item.created', (e: any) => store.addPushItem(e.pushItem || e))
      .listen('.push-item.updated', (e: any) => store.updatePushItem(e.pushItem || e))
      .listen('.push-item.deleted', (e: any) => store.removePushItem(e.id))
      .listen('.announcement.posted', (e: any) => store.addAnnouncement(e.announcement || e))
      .listen('.announcement.updated', (e: any) => store.updateAnnouncement(e.announcement || e))
      .listen('.announcement.deleted', (e: any) => store.removeAnnouncement(e.id))
      .listen('.special.low-stock', (e: any) => {
        toast(`Only ${e.quantity} left: ${e.title}!`, 'warning')
      })
      // Scheduling events
      .listen('.schedule.published', (e: any) => {
        scheduleStore.onSchedulePublished(e)
        toast('A new schedule has been published!', 'success')
      })
      .listen('.shift-drop.requested', (e: any) => scheduleStore.upsertShiftDrop(e))
      .listen('.shift-drop.volunteered', (e: any) => scheduleStore.upsertShiftDrop(e))
      .listen('.shift-drop.filled', (e: any) => {
        scheduleStore.upsertShiftDrop(e)
        toast('A shift drop was filled', 'success')
      })
      .listen('.time-off.resolved', (e: any) => {
        scheduleStore.upsertTimeOffRequest(e)
      })
  }
})

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
    channel.stopListening('.special.low-stock')
    channel.stopListening('.schedule.published')
    channel.stopListening('.shift-drop.requested')
    channel.stopListening('.shift-drop.volunteered')
    channel.stopListening('.shift-drop.filled')
    channel.stopListening('.time-off.resolved')
  }
})
</script>

<template>
  <AppShell>
    <!-- Loading -->
    <div v-if="store.loading" class="flex items-center justify-center py-20">
      <div class="flex flex-col items-center gap-3">
        <svg class="animate-spin h-8 w-8 text-amber-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
        </svg>
        <span class="text-sm text-gray-500">Loading pre-shift...</span>
      </div>
    </div>

    <!-- Schedule widget — shows either the full weekly strip or a single "Next Shift" fallback -->
    <div v-else-if="hasContent" class="space-y-3 sm:space-y-4">

      <!-- ═══ Today's Schedule — shows who's working today ═══ -->
      <section v-if="scheduleStore.currentSchedule" class="rounded-xl bg-emerald-500/5 border border-emerald-500/10 p-3">
        <div class="flex items-center justify-between mb-3">
          <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <span class="text-xs font-bold text-emerald-400 uppercase tracking-wide">Today</span>
            <span class="text-[10px] text-emerald-500/60">{{ todayLabel }}</span>
          </div>
          <router-link to="/my-schedule" class="text-[10px] text-emerald-500/60 hover:text-emerald-400 transition-colors">
            Full week
          </router-link>
        </div>

        <!-- Today's shifts grouped by time slot -->
        <div v-if="todayByShift.length" class="space-y-2">
          <div
            v-for="group in todayByShift"
            :key="group.template?.id"
            class="rounded-lg bg-white/[0.03] border border-white/[0.06] p-2.5"
          >
            <div class="flex items-center gap-2 mb-1.5">
              <span v-if="group.template" class="text-xs font-bold text-emerald-300">
                {{ formatShiftTime(group.template.start_time) }} – {{ formatShiftTime(group.template.end_time) }}
              </span>
              <span v-else class="text-xs font-bold text-emerald-300">Shift</span>
            </div>
            <div class="flex flex-wrap gap-1.5">
              <span
                v-for="entry in group.entries"
                :key="entry.id"
                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px]"
                :class="entry.role === 'bartender'
                  ? 'bg-green-500/15 text-green-400'
                  : 'bg-blue-500/15 text-blue-400'"
              >
                {{ entry.user?.name || 'Staff' }}
                <span class="text-[9px] opacity-60">{{ entry.role }}</span>
              </span>
            </div>
          </div>
        </div>
        <p v-else class="text-gray-600 text-xs text-center py-4">No shifts scheduled today</p>

        <!-- Sub-nav pill links -->
        <div class="flex items-center gap-2 mt-3 pt-2 border-t border-emerald-500/10">
          <router-link
            to="/shift-drops"
            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-medium
                   bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20 transition-colors"
          >
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
            </svg>
            Drop Board
          </router-link>
          <router-link
            to="/time-off"
            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-medium
                   bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20 transition-colors"
          >
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            Time Off
          </router-link>
        </div>
      </section>

    <!-- Dashboard grid -->
    <div class="grid grid-cols-2 gap-3 sm:gap-4">

      <!-- 86'd Items — top left -->
      <section class="dash-quarter dash-quarter--red">
        <header class="dash-quarter__header">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
          </svg>
          <h2>86'd</h2>
          <span class="dash-quarter__count" v-if="store.eightySixed.length">{{ store.eightySixed.length }}</span>
        </header>
        <div class="dash-quarter__body">
          <template v-if="store.eightySixed.length">
            <EightySixedCard v-for="item in store.eightySixed" :key="item.id" :item="item" />
          </template>
          <p v-else class="text-gray-600 text-xs text-center py-4">Nothing 86'd</p>
        </div>
      </section>

      <!-- Specials — top right -->
      <section class="dash-quarter dash-quarter--blue">
        <header class="dash-quarter__header">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
          </svg>
          <h2>Specials</h2>
          <span class="dash-quarter__count" v-if="store.specials.length">{{ store.specials.length }}</span>
        </header>
        <div class="dash-quarter__body">
          <template v-if="store.specials.length">
            <SpecialCard v-for="special in store.specials" :key="special.id" :special="special" />
          </template>
          <p v-else class="text-gray-600 text-xs text-center py-4">No specials today</p>
        </div>
      </section>

      <!-- Push Items — bottom left -->
      <section class="dash-quarter dash-quarter--amber">
        <header class="dash-quarter__header">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
          </svg>
          <h2>Push</h2>
          <span class="dash-quarter__count" v-if="store.pushItems.length">{{ store.pushItems.length }}</span>
        </header>
        <div class="dash-quarter__body">
          <template v-if="store.pushItems.length">
            <PushItemCard v-for="item in store.pushItems" :key="item.id" :item="item" />
          </template>
          <p v-else class="text-gray-600 text-xs text-center py-4">No push items</p>
        </div>
      </section>

      <!-- Announcements — bottom right -->
      <section class="dash-quarter dash-quarter--purple">
        <header class="dash-quarter__header">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
          </svg>
          <h2>Announcements</h2>
          <span class="dash-quarter__count" v-if="store.announcements.length">{{ store.announcements.length }}</span>
        </header>
        <div class="dash-quarter__body">
          <template v-if="store.announcements.length">
            <AnnouncementCard
              v-for="announcement in store.announcements"
              :key="announcement.id"
              :announcement="announcement"
            />
          </template>
          <p v-else class="text-gray-600 text-xs text-center py-4">No announcements</p>
        </div>
      </section>
    </div>
    </div>

    <!-- Empty state -->
    <div v-else class="flex flex-col items-center justify-center py-20 text-center">
      <div class="w-16 h-16 rounded-full bg-gray-800 flex items-center justify-center mb-4">
        <svg class="w-8 h-8 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
        </svg>
      </div>
      <p class="text-gray-400 text-base font-medium">Nothing for today's pre-shift</p>
      <p class="text-gray-600 text-sm mt-1">Check back before your next shift</p>
    </div>
  </AppShell>
</template>

<style scoped>
.dash-quarter {
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 0.75rem;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.dash-quarter__header {
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

.dash-quarter__header h2 {
  flex: 1;
  font-size: inherit;
  font-weight: inherit;
}

.dash-quarter__count {
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

.dash-quarter__body {
  flex: 1;
  padding: 0.5rem;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  overflow-y: auto;
  max-height: 40vh;
}

/* Color themes */
.dash-quarter--red .dash-quarter__header { color: #fca5a5; }
.dash-quarter--red .dash-quarter__count { background: rgba(239, 68, 68, 0.2); color: #fca5a5; }
.dash-quarter--red { border-color: rgba(239, 68, 68, 0.15); }

.dash-quarter--blue .dash-quarter__header { color: #93c5fd; }
.dash-quarter--blue .dash-quarter__count { background: rgba(59, 130, 246, 0.2); color: #93c5fd; }
.dash-quarter--blue { border-color: rgba(59, 130, 246, 0.15); }

.dash-quarter--amber .dash-quarter__header { color: #fcd34d; }
.dash-quarter--amber .dash-quarter__count { background: rgba(245, 158, 11, 0.2); color: #fcd34d; }
.dash-quarter--amber { border-color: rgba(245, 158, 11, 0.15); }

.dash-quarter--purple .dash-quarter__header { color: #c4b5fd; }
.dash-quarter--purple .dash-quarter__count { background: rgba(139, 92, 246, 0.2); color: #c4b5fd; }
.dash-quarter--purple { border-color: rgba(139, 92, 246, 0.15); }
</style>
