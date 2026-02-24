/**
 * AppTour.test.ts
 *
 * Unit tests for the AppTour.vue component.
 *
 * AppTour renders a full-screen onboarding walkthrough overlay. It uses
 * Teleport to render at the body level and shows a spotlight cutout around
 * the currently highlighted element, plus a positioned tooltip with title,
 * description, step counter, and Next/Skip buttons.
 *
 * The component relies on the useOnboarding composable for all tour state
 * and navigation. It is purely a presentation layer for the tour steps.
 *
 * Tests verify:
 *   1. The overlay is not rendered when the tour is inactive.
 *   2. The overlay is rendered when the tour is active with a current step.
 *   3. The step title and description are displayed in the tooltip.
 *   4. The step counter shows the correct current step and total.
 *   5. The "Skip tour" button is visible and calls skipTour when clicked.
 *   6. The "Next" button is visible and calls nextStep when clicked.
 *   7. The button shows "Done" on the last step.
 */

import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { ref, computed } from 'vue'
import AppTour from '@/components/AppTour.vue'

// ── Mutable mock state for useOnboarding (real refs for watch compatibility) ─
const mockIsActive = ref(false)
const mockStepIndex = ref(0)
const mockTotalSteps = ref(3)
const mockCurrentStep = ref<{ target: string; title: string; description: string } | null>(null)
const mockNextStep = vi.fn()
const mockSkipTour = vi.fn()

vi.mock('@/composables/useOnboarding', () => ({
  useOnboarding: () => ({
    isActive: mockIsActive,
    currentStep: mockCurrentStep,
    stepIndex: mockStepIndex,
    totalSteps: mockTotalSteps,
    nextStep: mockNextStep,
    skipTour: mockSkipTour,
  }),
}))

describe('AppTour.vue', () => {
  beforeEach(() => {
    mockIsActive.value = false
    mockCurrentStep.value = null
    mockStepIndex.value = 0
    mockTotalSteps.value = 3
    mockNextStep.mockClear()
    mockSkipTour.mockClear()
  })

  function mountTour() {
    return mount(AppTour, {
      global: {
        stubs: { Teleport: true },
      },
    })
  }

  /**
   * Test 1 — Overlay hidden when tour is inactive
   *
   * When isActive is false, the tour overlay should not be rendered
   * at all, keeping the UI clear for normal usage.
   */
  it('does not render the overlay when tour is inactive', () => {
    mockIsActive.value = false
    mockCurrentStep.value = null

    const wrapper = mountTour()

    expect(wrapper.find('.tour-overlay').exists()).toBe(false)
  })

  /**
   * Test 2 — Overlay visible when tour is active
   *
   * When isActive is true and there is a currentStep, the overlay
   * should render with the spotlight and tooltip visible.
   */
  it('renders the overlay when tour is active with a step', () => {
    mockIsActive.value = true
    mockCurrentStep.value = {
      target: 'manage-header',
      title: 'Welcome',
      description: 'This is your dashboard.',
    }

    const wrapper = mountTour()

    expect(wrapper.find('.tour-overlay').exists()).toBe(true)
  })

  /**
   * Test 3 — Step title and description are displayed
   *
   * The tooltip should show the current step's title as a heading
   * and the description as body text so users understand the feature.
   */
  it('displays the step title and description', () => {
    mockIsActive.value = true
    mockCurrentStep.value = {
      target: 'manage-header',
      title: 'Welcome to PreShift',
      description: 'This is your daily management hub.',
    }

    const wrapper = mountTour()

    expect(wrapper.find('h3').text()).toBe('Welcome to PreShift')
    expect(wrapper.text()).toContain('This is your daily management hub.')
  })

  /**
   * Test 4 — Step counter shows correct progress
   *
   * The tooltip should show "Step X of Y" so users know their
   * progress through the tour.
   */
  it('shows the correct step counter', () => {
    mockIsActive.value = true
    mockStepIndex.value = 1
    mockTotalSteps.value = 5
    mockCurrentStep.value = {
      target: 'nav-grid',
      title: 'Navigation',
      description: 'Jump to any area.',
    }

    const wrapper = mountTour()

    expect(wrapper.text()).toContain('Step 2 of 5')
  })

  /**
   * Test 5 — Skip tour button calls skipTour
   *
   * The "Skip tour" button should be present and invoke the skipTour
   * function from useOnboarding when clicked.
   */
  it('calls skipTour when the skip button is clicked', async () => {
    mockIsActive.value = true
    mockCurrentStep.value = {
      target: 'manage-header',
      title: 'Welcome',
      description: 'Tour description.',
    }

    const wrapper = mountTour()

    const skipBtn = wrapper.findAll('button').find(b => b.text().includes('Skip tour'))
    expect(skipBtn).toBeTruthy()
    await skipBtn!.trigger('click')

    expect(mockSkipTour).toHaveBeenCalledTimes(1)
  })

  /**
   * Test 6 — Next button calls nextStep
   *
   * The "Next" button should be present and invoke the nextStep
   * function from useOnboarding when clicked.
   */
  it('calls nextStep when the Next button is clicked', async () => {
    mockIsActive.value = true
    mockStepIndex.value = 0
    mockTotalSteps.value = 3
    mockCurrentStep.value = {
      target: 'manage-header',
      title: 'Welcome',
      description: 'Tour description.',
    }

    const wrapper = mountTour()

    const nextBtn = wrapper.findAll('button').find(b => b.text().includes('Next'))
    expect(nextBtn).toBeTruthy()
    await nextBtn!.trigger('click')

    expect(mockNextStep).toHaveBeenCalledTimes(1)
  })

  /**
   * Test 7 — Button shows "Done" on the last step
   *
   * When the user is on the final step, the Next button should display
   * "Done" instead of "Next" to signal the tour is about to finish.
   */
  it('shows Done instead of Next on the last step', () => {
    mockIsActive.value = true
    mockStepIndex.value = 2
    mockTotalSteps.value = 3
    mockCurrentStep.value = {
      target: 'bottom-nav-messages',
      title: 'Messages',
      description: 'Board posts and DMs.',
    }

    const wrapper = mountTour()

    const doneBtn = wrapper.findAll('button').find(b => b.text().includes('Done'))
    expect(doneBtn).toBeTruthy()

    // Should not show "Next" text
    const nextBtn = wrapper.findAll('button').find(b => b.text() === 'Next')
    expect(nextBtn).toBeUndefined()
  })
})
