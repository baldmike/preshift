<script setup lang="ts">
/**
 * DashboardView.vue
 *
 * Primary staff dashboard and pre-shift briefing screen. Displays a 2x2
 * grid of 86'd items, specials, push items, and announcements, plus a
 * "Today's Schedule" section showing who is working each shift, and a
 * Messages preview with recent board posts and unread DM count.
 * Subscribes to real-time Reverb events on the location channel so
 * cards update live as managers make changes.
 */
import { onMounted, onUnmounted, computed, ref, reactive } from 'vue'
import type { User } from '@/types'
import { usePreshiftStore } from '@/stores/preshift'
import { useScheduleStore } from '@/stores/schedule'
import { useMessageStore } from '@/stores/messages'
import { useAuth } from '@/composables/useAuth'
import { useSchedule } from '@/composables/useSchedule'
import { useLocationChannel } from '@/composables/useReverb'
import api from '@/composables/useApi'
import AppShell from '@/components/layout/AppShell.vue'
import EightySixedCard from '@/components/EightySixedCard.vue'
import SpecialCard from '@/components/SpecialCard.vue'
import PushItemCard from '@/components/PushItemCard.vue'
import AnnouncementCard from '@/components/AnnouncementCard.vue'
import EmployeeProfileModal from '@/components/EmployeeProfileModal.vue'
import TileDetailModal from '@/components/TileDetailModal.vue'

/** Tile type for the detail modal */
type TileType = 'eightySixed' | 'specials' | 'pushItems' | 'announcements'

/** Weather data shape returned by GET /api/weather */
interface WeatherData {
  current: {
    temperature: number
    feels_like: number
    humidity: number
    wind_speed: number
    weather_code: number
    description: string
  }
  today: {
    high: number
    low: number
    weather_code: number
    description: string
  }
}

/**
 * Map WMO weather codes to simple icon names.
 * Returns an SVG path descriptor used in the template.
 */
function weatherIcon(code: number): string {
  if (code === 0) return 'sun'
  if (code <= 3) return 'cloud-sun'
  if (code <= 48) return 'fog'
  if (code <= 57) return 'drizzle'
  if (code <= 67) return 'rain'
  if (code <= 77) return 'snow'
  if (code <= 82) return 'rain'
  if (code <= 86) return 'snow'
  return 'storm'
}

const store = usePreshiftStore()
const scheduleStore = useScheduleStore()
const messageStore = useMessageStore()
const { locationId, user } = useAuth()

/** Whether the current user can manage events (admin or manager) */
const canManageEvents = computed(() => {
  const role = user.value?.role
  return role === 'admin' || role === 'manager'
})

/** Selected user for profile modal (managers only) */
const selectedUser = ref<User | null>(null)

/** Currently active tile for the detail modal (null = closed) */
const activeTile = ref<TileType | null>(null)

/** Controls visibility of the inline event form */
const showEventForm = ref(false)

/** The event currently being edited (null = creating new) */
const editingEventId = ref<number | null>(null)

/** Reactive form data for creating/editing an event */
const eventForm = reactive({
  title: '',
  description: '',
  event_date: '',
  event_time: '',
})

function resetEventForm() {
  eventForm.title = ''
  eventForm.description = ''
  eventForm.event_date = todayISO.value
  eventForm.event_time = ''
  editingEventId.value = null
  showEventForm.value = false
}

function startEditEvent(event: any) {
  editingEventId.value = event.id
  eventForm.title = event.title
  eventForm.description = event.description || ''
  eventForm.event_date = event.event_date?.split('T')[0] || todayISO.value
  eventForm.event_time = event.event_time || ''
  showEventForm.value = true
}

async function saveEvent() {
  const payload = {
    title: eventForm.title,
    description: eventForm.description || null,
    event_date: eventForm.event_date,
    event_time: eventForm.event_time || null,
  }
  try {
    if (editingEventId.value) {
      const { data } = await api.patch(`/api/events/${editingEventId.value}`, payload)
      store.updateEvent(data)
      toast('Event updated', 'success')
    } else {
      const { data } = await api.post('/api/events', payload)
      store.addEvent(data)
      toast('Event created', 'success')
    }
    resetEventForm()
  } catch {
    toast('Failed to save event', 'error')
  }
}

async function deleteEvent(id: number) {
  try {
    await api.delete(`/api/events/${id}`)
    store.removeEvent(id)
    toast('Event deleted', 'success')
  } catch {
    toast('Failed to delete event', 'error')
  }
}

function openNewEventForm() {
  resetEventForm()
  eventForm.event_date = todayISO.value
  showEventForm.value = true
}
/** Weather data — null means not loaded or coordinates not configured */
const weather = ref<WeatherData | null>(null)

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

/** All schedule entries for today, filtered from the published week schedule.
 *  Staff (servers/bartenders) only see their own shifts; managers/admins see everyone. */
const todayEntries = computed(() => {
  const schedule = scheduleStore.currentSchedule
  if (!schedule?.entries) return []
  const entries = schedule.entries.filter(e => e.date.split('T')[0] === todayISO.value)
  if (canManageEvents.value) return entries
  return entries.filter(e => e.user_id === user.value?.id)
})

/**
 * Today's entries grouped by time slot for display.
 * Each group has a `template` (with start_time for the heading)
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

/** Most recent board posts (up to 3) for the dashboard preview */
const recentBoardPosts = computed(() =>
  messageStore.boardMessages
    .filter(m => !m.parent_id)
    .slice(0, 3)
)

const hasContent = computed(() =>
  store.eightySixed.length || store.specials.length || store.pushItems.length || store.announcements.length || store.events.length || scheduleStore.currentSchedule
)

onMounted(async () => {
  await store.fetchAll()
  // Fetch the user's shifts + the full current-week published schedule
  scheduleStore.fetchMyShifts()
  scheduleStore.fetchCurrentWeekSchedule()

  // Fetch ack summary for managers (powers the red dot indicator)
  if (canManageEvents.value) {
    scheduleStore.fetchAckSummary()
  }

  // Fetch board messages for the dashboard preview
  messageStore.fetchBoardMessages()
  messageStore.fetchUnreadCount()

  // Fetch weather (silently ignore 404 when coordinates not configured)
  api.get('/api/weather')
    .then(({ data }) => { weather.value = data })
    .catch(() => { /* coordinates not set or API unavailable — hide widget */ })

  if (locationId.value) {
    channel = useLocationChannel(locationId.value)
    channel
      .listen('.item.eighty-sixed', (e: any) => store.addEightySixed(e.item || e))
      .listen('.item.eighty-sixed-updated', (e: any) => store.updateEightySixed(e.item || e))
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
      .listen('.event.created', (e: any) => store.addEvent(e.event || e))
      .listen('.event.updated', (e: any) => store.updateEvent(e.event || e))
      .listen('.event.deleted', (e: any) => store.removeEvent(e.id))
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
      .listen('.acknowledgment.recorded', (e: any) => {
        scheduleStore.updateUserAckPercentage(e.user_id, e.percentage)
      })
  }
})

onUnmounted(() => {
  if (channel && locationId.value) {
    channel.stopListening('.item.eighty-sixed')
    channel.stopListening('.item.eighty-sixed-updated')
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
    channel.stopListening('.event.created')
    channel.stopListening('.event.updated')
    channel.stopListening('.event.deleted')
    channel.stopListening('.special.low-stock')
    channel.stopListening('.schedule.published')
    channel.stopListening('.shift-drop.requested')
    channel.stopListening('.shift-drop.volunteered')
    channel.stopListening('.shift-drop.filled')
    channel.stopListening('.time-off.resolved')
    channel.stopListening('.acknowledgment.recorded')
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

      <!-- ═══ Weather — current conditions + today's forecast ═══ -->
      <section v-if="weather" class="rounded-xl bg-sky-500/5 border border-sky-500/10 p-3">
        <div class="flex items-center gap-2 mb-2">
          <!-- Weather icon based on current weather code -->
          <svg v-if="weatherIcon(weather.current.weather_code) === 'sun'" class="w-4 h-4 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
          </svg>
          <svg v-else-if="weatherIcon(weather.current.weather_code) === 'cloud-sun'" class="w-4 h-4 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z" />
          </svg>
          <svg v-else-if="weatherIcon(weather.current.weather_code) === 'rain' || weatherIcon(weather.current.weather_code) === 'drizzle'" class="w-4 h-4 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 19v2m4-2v2m4-2v2" />
          </svg>
          <svg v-else-if="weatherIcon(weather.current.weather_code) === 'snow'" class="w-4 h-4 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 19l.5 1m3.5-1l.5 1m3.5-1l.5 1" />
          </svg>
          <svg v-else-if="weatherIcon(weather.current.weather_code) === 'storm'" class="w-4 h-4 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M13 10V3L4 14h7v7l9-11h-7z" />
          </svg>
          <svg v-else class="w-4 h-4 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z" />
          </svg>
          <span class="text-xs font-bold text-sky-400 uppercase tracking-wide">Weather</span>
        </div>

        <div class="flex items-center gap-4">
          <!-- Current temperature (large) -->
          <div class="flex items-baseline gap-1">
            <span class="text-2xl font-bold text-sky-300">{{ weather.current.temperature }}°</span>
            <span class="text-[10px] text-sky-500/60">Feels like {{ weather.current.feels_like }}°</span>
          </div>

          <!-- Condition + high/low -->
          <div class="flex-1 min-w-0">
            <p class="text-xs text-gray-200 truncate">{{ weather.current.description }}</p>
            <p class="text-[10px] text-sky-500/60">
              H: {{ weather.today.high }}° &nbsp; L: {{ weather.today.low }}°
            </p>
          </div>

          <!-- Humidity + wind -->
          <div class="text-right shrink-0">
            <p class="text-[10px] text-sky-500/60">
              <span class="text-sky-400">{{ weather.current.humidity }}%</span> humidity
            </p>
            <p class="text-[10px] text-sky-500/60">
              <span class="text-sky-400">{{ weather.current.wind_speed }}</span> mph wind
            </p>
          </div>
        </div>
      </section>

      <!-- ═══ Events — daily happenings ═══ -->
      <section class="rounded-xl bg-violet-500/5 border border-violet-500/10 p-3">
        <div class="flex items-center justify-between mb-3">
          <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <span class="text-xs font-bold text-violet-400 uppercase tracking-wide">Events</span>
            <span v-if="store.events.length" class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 rounded-full text-[0.625rem] font-bold bg-violet-500/20 text-violet-300">{{ store.events.length }}</span>
          </div>
          <button
            v-if="canManageEvents"
            @click="openNewEventForm"
            class="text-[10px] font-semibold text-violet-400 hover:text-violet-300 transition-colors"
          >
            + Add
          </button>
        </div>

        <!-- Inline add/edit form -->
        <div v-if="showEventForm" class="rounded-lg bg-white/[0.03] border border-white/[0.06] p-2.5 mb-2 space-y-2">
          <input
            v-model="eventForm.title"
            type="text"
            placeholder="Event title"
            class="w-full bg-white/[0.05] border border-white/[0.08] rounded px-2 py-1 text-xs text-gray-200 placeholder-gray-500 focus:outline-none focus:border-violet-500/40"
          />
          <input
            v-model="eventForm.description"
            type="text"
            placeholder="Description (optional)"
            class="w-full bg-white/[0.05] border border-white/[0.08] rounded px-2 py-1 text-xs text-gray-200 placeholder-gray-500 focus:outline-none focus:border-violet-500/40"
          />
          <div class="flex gap-2">
            <input
              v-model="eventForm.event_time"
              type="time"
              class="flex-1 bg-white/[0.05] border border-white/[0.08] rounded px-2 py-1 text-xs text-gray-200 focus:outline-none focus:border-violet-500/40"
            />
            <input
              v-model="eventForm.event_date"
              type="date"
              class="flex-1 bg-white/[0.05] border border-white/[0.08] rounded px-2 py-1 text-xs text-gray-200 focus:outline-none focus:border-violet-500/40"
            />
          </div>
          <div class="flex gap-2 justify-end">
            <button @click="resetEventForm" class="px-2 py-1 text-[10px] font-semibold text-gray-400 hover:text-gray-300 transition-colors">Cancel</button>
            <button @click="saveEvent" :disabled="!eventForm.title" class="px-2.5 py-1 text-[10px] font-semibold rounded bg-violet-500/20 text-violet-300 hover:bg-violet-500/30 transition-colors disabled:opacity-40">
              {{ editingEventId ? 'Update' : 'Create' }}
            </button>
          </div>
        </div>

        <!-- Event list -->
        <div v-if="store.events.length" class="space-y-1.5">
          <div
            v-for="evt in store.events"
            :key="evt.id"
            class="flex items-start gap-2 rounded-lg bg-white/[0.03] border border-white/[0.06] p-2.5"
          >
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-1.5">
                <span v-if="evt.event_time" class="text-xs font-bold text-violet-300">{{ evt.event_time }}</span>
                <span class="text-xs text-gray-200">{{ evt.title }}</span>
              </div>
              <p v-if="evt.description" class="text-[11px] text-gray-500 mt-0.5 line-clamp-2">{{ evt.description }}</p>
            </div>
            <div v-if="canManageEvents" class="flex items-center gap-1 shrink-0">
              <button @click="startEditEvent(evt)" class="text-gray-500 hover:text-violet-400 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                </svg>
              </button>
              <button @click="deleteEvent(evt.id)" class="text-gray-500 hover:text-red-400 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </button>
            </div>
          </div>
        </div>
        <p v-else class="text-gray-600 text-xs text-center py-4">No events today</p>
      </section>

      <!-- ═══ Today's Schedule — shows who's working today ═══ -->
      <section v-if="scheduleStore.currentSchedule" class="rounded-xl bg-emerald-500/5 border border-emerald-500/10 p-3">
        <div class="flex items-center justify-between mb-3">
          <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <span class="text-xs font-bold text-emerald-400 uppercase tracking-wide">{{ canManageEvents ? 'Today' : 'My Schedule' }}</span>
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
                {{ formatShiftTime(group.template.start_time) }}
              </span>
              <span v-else class="text-xs font-bold text-emerald-300">Shift</span>
            </div>
            <div class="flex flex-wrap gap-1.5">
              <component
                :is="canManageEvents && entry.user ? 'button' : 'span'"
                v-for="entry in group.entries"
                :key="entry.id"
                :type="canManageEvents && entry.user ? 'button' : undefined"
                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px]"
                :class="[
                  entry.role === 'bartender'
                    ? 'bg-green-500/15 text-green-400'
                    : 'bg-blue-500/15 text-blue-400',
                  canManageEvents && entry.user_id in scheduleStore.ackSummaryMap && scheduleStore.ackSummaryMap[entry.user_id] < 100
                    ? 'ring-1 ring-red-500/60'
                    : '',
                  canManageEvents && entry.user ? 'hover:brightness-125 transition-all cursor-pointer' : '',
                ]"
                :title="canManageEvents && entry.user_id in scheduleStore.ackSummaryMap && scheduleStore.ackSummaryMap[entry.user_id] < 100
                  ? 'Has not acknowledged all pre-shift items'
                  : undefined"
                @click="canManageEvents && entry.user ? selectedUser = entry.user : undefined"
              >
                {{ entry.user?.name || 'Staff' }}
                <span class="text-[9px] opacity-60">{{ entry.role }}</span>
              </component>
            </div>
          </div>
        </div>
        <p v-else class="text-gray-600 text-xs text-center py-4">No shifts scheduled today</p>

        <!-- Sub-nav pill links: secondary navigation to schedule-related pages -->
        <div class="flex flex-wrap gap-2 mt-2">
          <router-link
            to="/tonights-schedule"
            class="inline-flex items-center gap-1 px-2.5 py-1 text-[10px] font-semibold rounded-full bg-teal-500/15 text-teal-400 hover:bg-teal-500/25 transition-colors"
          >
            Tonight's Schedule
          </router-link>
          <router-link
            to="/shift-drops"
            class="inline-flex items-center gap-1 px-2.5 py-1 text-[10px] font-semibold rounded-full bg-amber-500/15 text-amber-400 hover:bg-amber-500/25 transition-colors"
          >
            Drop Board
          </router-link>
          <router-link
            to="/time-off"
            class="inline-flex items-center gap-1 px-2.5 py-1 text-[10px] font-semibold rounded-full bg-emerald-500/15 text-emerald-400 hover:bg-emerald-500/25 transition-colors"
          >
            Time Off
          </router-link>
        </div>
      </section>

      <!-- ═══ Messages — recent board posts + DM unread count ═══ -->
      <section class="rounded-xl bg-orange-500/5 border border-orange-500/10 p-3">
        <div class="flex items-center justify-between mb-3">
          <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
            <span class="text-xs font-bold text-orange-400 uppercase tracking-wide">Messages</span>
            <span
              v-if="messageStore.unreadDmCount > 0"
              class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 rounded-full text-[0.625rem] font-bold bg-orange-500/20 text-orange-300"
            >
              {{ messageStore.unreadDmCount }} DM{{ messageStore.unreadDmCount !== 1 ? 's' : '' }}
            </span>
          </div>
          <router-link to="/messages" class="text-[10px] text-orange-500/60 hover:text-orange-400 transition-colors">
            All messages
          </router-link>
        </div>

        <!-- Recent board posts -->
        <div v-if="recentBoardPosts.length" class="space-y-1.5">
          <router-link
            v-for="post in recentBoardPosts"
            :key="post.id"
            to="/messages?tab=board"
            class="block rounded-lg bg-white/[0.03] border border-white/[0.06] p-2.5 hover:bg-white/[0.05] transition-colors"
          >
            <div class="flex items-center gap-1.5 mb-0.5">
              <span class="text-[11px] font-semibold text-orange-300">{{ post.user?.name || 'Staff' }}</span>
              <span v-if="post.pinned" class="text-[9px] text-amber-400">pinned</span>
            </div>
            <p class="text-xs text-gray-400 line-clamp-1">{{ post.body }}</p>
          </router-link>
        </div>
        <p v-else class="text-gray-600 text-xs text-center py-4">No board messages</p>

        <!-- Quick links: Board + DMs -->
        <div class="flex flex-wrap gap-2 mt-2">
          <router-link
            to="/messages?tab=board"
            class="inline-flex items-center gap-1 px-2.5 py-1 text-[10px] font-semibold rounded-full bg-orange-500/15 text-orange-400 hover:bg-orange-500/25 transition-colors"
          >
            Board
          </router-link>
          <router-link
            to="/messages?tab=direct"
            class="inline-flex items-center gap-1 px-2.5 py-1 text-[10px] font-semibold rounded-full bg-orange-500/15 text-orange-400 hover:bg-orange-500/25 transition-colors"
          >
            Direct Messages
            <span
              v-if="messageStore.unreadDmCount > 0"
              class="inline-flex items-center justify-center min-w-[14px] h-[14px] px-0.5 rounded-full text-[8px] font-bold bg-orange-500 text-gray-900"
            >
              {{ messageStore.unreadDmCount > 9 ? '9+' : messageStore.unreadDmCount }}
            </span>
          </router-link>
        </div>
      </section>

    <!-- Dashboard grid -->
    <div class="grid grid-cols-2 gap-3 sm:gap-4">

      <!-- 86'd Items — top left -->
      <section class="dash-quarter dash-quarter--red">
        <header class="dash-quarter__header cursor-pointer" @click="activeTile = 'eightySixed'">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
          </svg>
          <h2>86'd</h2>
          <span class="dash-quarter__count" v-if="store.eightySixed.length">{{ store.eightySixed.length }}</span>
        </header>
        <div class="dash-quarter__body">
          <template v-if="store.eightySixed.length">
            <EightySixedCard v-for="item in store.eightySixed" :key="item.id" :item="item" @click="activeTile = 'eightySixed'" />
          </template>
          <p v-else class="text-gray-600 text-xs text-center py-4">Nothing 86'd</p>
        </div>
      </section>

      <!-- Specials — top right -->
      <section class="dash-quarter dash-quarter--blue">
        <header class="dash-quarter__header cursor-pointer" @click="activeTile = 'specials'">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
          </svg>
          <h2>Specials</h2>
          <span class="dash-quarter__count" v-if="store.specials.length">{{ store.specials.length }}</span>
        </header>
        <div class="dash-quarter__body">
          <template v-if="store.specials.length">
            <SpecialCard v-for="special in store.specials" :key="special.id" :special="special" @click="activeTile = 'specials'" />
          </template>
          <p v-else class="text-gray-600 text-xs text-center py-4">No specials today</p>
        </div>
      </section>

      <!-- Push Items — bottom left -->
      <section class="dash-quarter dash-quarter--amber">
        <header class="dash-quarter__header cursor-pointer" @click="activeTile = 'pushItems'">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
          </svg>
          <h2>Push</h2>
          <span class="dash-quarter__count" v-if="store.pushItems.length">{{ store.pushItems.length }}</span>
        </header>
        <div class="dash-quarter__body">
          <template v-if="store.pushItems.length">
            <PushItemCard v-for="item in store.pushItems" :key="item.id" :item="item" @click="activeTile = 'pushItems'" />
          </template>
          <p v-else class="text-gray-600 text-xs text-center py-4">No push items</p>
        </div>
      </section>

      <!-- Announcements — bottom right -->
      <section class="dash-quarter dash-quarter--purple">
        <header class="dash-quarter__header cursor-pointer" @click="activeTile = 'announcements'">
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
              @click="activeTile = 'announcements'"
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

    <EmployeeProfileModal :user="selectedUser" @close="selectedUser = null" />
    <TileDetailModal :tileType="activeTile" @close="activeTile = null" />
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
