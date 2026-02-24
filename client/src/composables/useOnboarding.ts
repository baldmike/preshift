/**
 * composables/useOnboarding.ts
 *
 * Manages the first-login onboarding walkthrough for superadmin users.
 * Checks eligibility (logged-in superadmin who hasn't seen the tour),
 * defines the step sequence, and exposes reactive state + navigation
 * methods consumed by the AppTour component.
 *
 * Tour completion is tracked in localStorage so it only shows once
 * per user. No backend migration needed.
 */

import { ref, computed } from 'vue'
import { useAuth } from '@/composables/useAuth'

/** Shape of a single tour step */
export interface TourStep {
  target: string
  title: string
  description: string
}

/** Admin/Manager tour steps shown on /manage/daily */
const adminSteps: TourStep[] = [
  {
    target: 'manage-header',
    title: 'Welcome to PreShift',
    description:
      'This is your daily management hub. Everything you post here shows up on your staff\'s dashboard in real time.',
  },
  {
    target: 'manage-nav-grid',
    title: 'Quick Navigation',
    description:
      'Jump to any management area — staff, schedule, drops, time off, menu, and more.',
  },
  {
    target: 'manage-schedule-link',
    title: 'Schedule Builder',
    description:
      'Build weekly schedules here. Create shifts, assign staff, and publish. Your team sees it instantly.',
  },
  {
    target: 'manage-86-section',
    title: '86\'d Items',
    description:
      'When the kitchen runs out, 86 it here. Staff see it live on their dashboard.',
  },
  {
    target: 'bottom-nav-dashboard',
    title: 'Staff Dashboard',
    description:
      'Tap here to see what your staff sees — their pre-shift briefing with everything you post.',
  },
  {
    target: 'bottom-nav-messages',
    title: 'Messages',
    description:
      'Board posts and direct messages with your team. You\'re all set!',
  },
]

const isActive = ref(false)
const stepIndex = ref(0)

const steps = computed(() => adminSteps)
const totalSteps = computed(() => steps.value.length)
const currentStep = computed(() => steps.value[stepIndex.value] || null)

/**
 * Returns the localStorage key used to track whether a given user
 * has already completed or skipped the tour.
 */
function storageKey(userId: number): string {
  return `preshift_tour_seen_${userId}`
}

/** Marks the tour as seen for the current user and closes it. */
function markSeen() {
  const { user } = useAuth()
  if (user.value?.id) {
    localStorage.setItem(storageKey(user.value.id), 'true')
  }
  isActive.value = false
  stepIndex.value = 0
}

/** Starts the tour from step 0. */
function startTour() {
  stepIndex.value = 0
  isActive.value = true
}

/** Advances to the next step, or finishes the tour on the last step. */
function nextStep() {
  if (stepIndex.value < totalSteps.value - 1) {
    stepIndex.value++
  } else {
    markSeen()
  }
}

/** Skips the tour entirely — marks as seen and closes. */
function skipTour() {
  markSeen()
}

/**
 * Checks eligibility and starts the tour if appropriate.
 * Called from AppShell on mount after a short delay to let the
 * page finish rendering.
 */
function checkAndStart() {
  const { user } = useAuth()

  if (!user.value) return
  if (!user.value.is_superadmin) return
  if (localStorage.getItem(storageKey(user.value.id)) === 'true') return

  startTour()
}

export function useOnboarding() {
  return {
    isActive,
    currentStep,
    stepIndex,
    totalSteps,
    startTour,
    nextStep,
    skipTour,
    checkAndStart,
  }
}
