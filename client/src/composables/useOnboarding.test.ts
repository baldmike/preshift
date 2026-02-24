/**
 * useOnboarding.test.ts
 *
 * Unit tests for the useOnboarding composable.
 *
 * These tests verify:
 *  1. Initial state — `isActive` is false, `stepIndex` is 0, `currentStep`
 *     returns the first admin step, and `totalSteps` matches the defined steps.
 *  2. `startTour()` activates the tour and resets to step 0.
 *  3. `nextStep()` advances through steps and calls `markSeen()` on the last step.
 *  4. `skipTour()` deactivates the tour and persists completion to localStorage.
 *  5. `checkAndStart()` only starts the tour for superadmin users who have
 *     not already seen it.
 *  6. `markSeen` (called internally) writes a localStorage key per-user to
 *     prevent the tour from showing again.
 *
 * We mock `@/composables/useAuth` to control the user object returned by
 * the composable.
 */

import { describe, it, expect, vi, beforeEach } from 'vitest'
import { ref } from 'vue'

/* Mock useAuth to control the user object returned to useOnboarding */
const mockUser = ref<any>(null)

vi.mock('@/composables/useAuth', () => ({
  useAuth: () => ({
    user: mockUser,
  }),
}))

import { useOnboarding } from '@/composables/useOnboarding'

// ── Test suite ─────────────────────────────────────────────────────────────

describe('useOnboarding composable', () => {
  beforeEach(() => {
    localStorage.clear()
    mockUser.value = null
    // Reset the module-level state by explicitly resetting through the composable
    const { skipTour } = useOnboarding()
    // skipTour calls markSeen which needs a user, so just directly reset
    const state = useOnboarding()
    state.stepIndex.value = 0
    state.isActive.value = false
  })

  // ── Initial state ──────────────────────────────────────────────────────

  /* Verifies that the tour starts in an inactive state at step 0. */
  it('starts inactive at step 0', () => {
    const { isActive, stepIndex } = useOnboarding()

    expect(isActive.value).toBe(false)
    expect(stepIndex.value).toBe(0)
  })

  /* Verifies that totalSteps matches the number of defined admin steps
     and currentStep returns the first step. */
  it('exposes the correct total steps and first current step', () => {
    const { totalSteps, currentStep } = useOnboarding()

    expect(totalSteps.value).toBe(6)
    expect(currentStep.value).not.toBeNull()
    expect(currentStep.value!.target).toBe('manage-header')
    expect(currentStep.value!.title).toBe('Welcome to PreShift')
  })

  // ── startTour ──────────────────────────────────────────────────────────

  /* Verifies that startTour() sets isActive to true and resets stepIndex
     to 0 even if it was previously at a different step. */
  it('activates the tour and resets to step 0', () => {
    const { isActive, stepIndex, startTour } = useOnboarding()

    stepIndex.value = 3
    startTour()

    expect(isActive.value).toBe(true)
    expect(stepIndex.value).toBe(0)
  })

  // ── nextStep ───────────────────────────────────────────────────────────

  /* Verifies that nextStep() increments the step index when not on the
     last step. */
  it('advances to the next step', () => {
    const { stepIndex, startTour, nextStep, isActive } = useOnboarding()

    startTour()
    expect(stepIndex.value).toBe(0)

    nextStep()
    expect(stepIndex.value).toBe(1)
    expect(isActive.value).toBe(true)

    nextStep()
    expect(stepIndex.value).toBe(2)
  })

  /* Verifies that nextStep() on the last step deactivates the tour and
     stores the seen flag for the user. */
  it('finishes the tour and marks as seen on the last step', () => {
    mockUser.value = { id: 42, is_superadmin: true }
    const { stepIndex, totalSteps, startTour, nextStep, isActive } = useOnboarding()

    startTour()
    // Advance to the last step
    stepIndex.value = totalSteps.value - 1
    nextStep()

    expect(isActive.value).toBe(false)
    expect(stepIndex.value).toBe(0)
    expect(localStorage.getItem('preshift_tour_seen_42')).toBe('true')
  })

  // ── skipTour ───────────────────────────────────────────────────────────

  /* Verifies that skipTour() deactivates the tour and writes the seen
     flag to localStorage for the current user. */
  it('deactivates the tour and persists seen flag', () => {
    mockUser.value = { id: 7, is_superadmin: true }
    const { startTour, skipTour, isActive } = useOnboarding()

    startTour()
    expect(isActive.value).toBe(true)

    skipTour()

    expect(isActive.value).toBe(false)
    expect(localStorage.getItem('preshift_tour_seen_7')).toBe('true')
  })

  /* Verifies that skipTour() handles the case where there is no user
     gracefully (does not throw). */
  it('handles skipTour with no user without throwing', () => {
    mockUser.value = null
    const { startTour, skipTour, isActive } = useOnboarding()

    startTour()
    expect(() => skipTour()).not.toThrow()
    expect(isActive.value).toBe(false)
  })

  // ── checkAndStart ──────────────────────────────────────────────────────

  /* Verifies that checkAndStart() activates the tour for a superadmin
     who has not seen it before. */
  it('starts the tour for a superadmin who has not seen it', () => {
    mockUser.value = { id: 99, is_superadmin: true }
    const { checkAndStart, isActive } = useOnboarding()

    checkAndStart()

    expect(isActive.value).toBe(true)
  })

  /* Verifies that checkAndStart() does nothing for a non-superadmin user. */
  it('does not start the tour for a non-superadmin user', () => {
    mockUser.value = { id: 5, is_superadmin: false }
    const { checkAndStart, isActive } = useOnboarding()

    checkAndStart()

    expect(isActive.value).toBe(false)
  })

  /* Verifies that checkAndStart() does not start the tour when no user
     is loaded (null). */
  it('does not start the tour when user is null', () => {
    mockUser.value = null
    const { checkAndStart, isActive } = useOnboarding()

    checkAndStart()

    expect(isActive.value).toBe(false)
  })

  /* Verifies that checkAndStart() does not restart the tour for a user
     who has already completed or skipped it. */
  it('does not start the tour if the user has already seen it', () => {
    mockUser.value = { id: 88, is_superadmin: true }
    localStorage.setItem('preshift_tour_seen_88', 'true')
    const { checkAndStart, isActive } = useOnboarding()

    checkAndStart()

    expect(isActive.value).toBe(false)
  })

  // ── currentStep edge case ──────────────────────────────────────────────

  /* Verifies that currentStep returns the correct step object as the
     index changes. */
  it('returns the correct step for each index', () => {
    const { stepIndex, currentStep } = useOnboarding()

    stepIndex.value = 0
    expect(currentStep.value!.target).toBe('manage-header')

    stepIndex.value = 3
    expect(currentStep.value!.target).toBe('manage-86-section')

    stepIndex.value = 5
    expect(currentStep.value!.target).toBe('bottom-nav-messages')
  })
})
