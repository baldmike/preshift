/**
 * AvailabilityGrid.test.ts
 *
 * Unit tests for the AvailabilityGrid.vue component.
 *
 * AvailabilityGrid renders a 7-column weekly grid with five toggleable
 * buttons per day: 10:30 AM, 4:30 PM, 6:00 PM, 7:00 PM, and OPEN.
 * There is also a master "Open Availability" button at the top that sets
 * every day to open.
 *
 * Tests verify:
 *   1. Renders 7 day columns with correct labels.
 *   2. Renders correct number of buttons (master + 5 per day + save).
 *   3. Clicking a time slot emits update:modelValue with the slot added.
 *   4. Clicking OPEN replaces existing slots with ['open'].
 *   5. Clicking a time slot while OPEN is active deselects OPEN.
 *   6. Clicking an active slot deselects it.
 *   7. Save button emits the 'save' event.
 *   8. Clicking per-day OPEN while active clears the day.
 *   9. Master "Open Availability" sets every day to ['open'].
 *  10. Master "Open Availability" clears all when already all-open.
 */

import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import AvailabilityGrid from '@/components/AvailabilityGrid.vue'

const DAYS = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']

/** Build an empty availability map */
function emptyAvailability(): Record<string, string[]> {
  return Object.fromEntries(DAYS.map(d => [d, []]))
}

/** Build all-open availability */
function allOpenAvailability(): Record<string, string[]> {
  return Object.fromEntries(DAYS.map(d => [d, ['open']]))
}

// Button layout (4 time slots + OPEN per day = 5 per day):
//   [0] = Master "Open Availability"
//   [1] = Mon 10:30, [2] = Mon 4:30, [3] = Mon 6:00, [4] = Mon 7:00, [5] = Mon OPEN
//   [6] = Tue 10:30, [7] = Tue 4:30, [8] = Tue 6:00, [9] = Tue 7:00, [10] = Tue OPEN
//   ...
//   [31] = Sun 10:30, [32] = Sun 4:30, [33] = Sun 6:00, [34] = Sun 7:00, [35] = Sun OPEN
//   [36] = Save Availability

describe('AvailabilityGrid.vue', () => {
  /**
   * Test 1 — Renders 7 day column labels
   */
  it('renders 7 day column labels', () => {
    const wrapper = mount(AvailabilityGrid, {
      props: { modelValue: emptyAvailability() },
    })

    const text = wrapper.text()
    for (const label of ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']) {
      expect(text).toContain(label)
    }
  })

  /**
   * Test 2 — Renders correct number of buttons
   *
   * 1 master + 7 days * 5 slot buttons + 1 save button = 37
   */
  it('renders master + 5 per day + save buttons', () => {
    const wrapper = mount(AvailabilityGrid, {
      props: { modelValue: emptyAvailability() },
    })

    const buttons = wrapper.findAll('button')
    expect(buttons).toHaveLength(37)
  })

  /**
   * Test 3 — Clicking a time slot emits update:modelValue with that slot
   */
  it('clicking 10:30 AM adds the slot to the day', async () => {
    const wrapper = mount(AvailabilityGrid, {
      props: { modelValue: emptyAvailability() },
    })

    // Button [1] = Mon 10:30 AM
    const buttons = wrapper.findAll('button')
    await buttons[1].trigger('click')

    const emitted = wrapper.emitted('update:modelValue')
    expect(emitted).toBeTruthy()
    expect(emitted![0][0]).toEqual(expect.objectContaining({
      monday: ['10:30'],
    }))
  })

  /**
   * Test 4 — Clicking per-day OPEN replaces existing slots with ['open']
   */
  it('clicking OPEN replaces time slots with open', async () => {
    const avail = { ...emptyAvailability(), monday: ['10:30', '16:30'] }
    const wrapper = mount(AvailabilityGrid, {
      props: { modelValue: avail },
    })

    // Button [5] = Mon OPEN
    const buttons = wrapper.findAll('button')
    await buttons[5].trigger('click')

    const emitted = wrapper.emitted('update:modelValue')
    expect(emitted).toBeTruthy()
    expect(emitted![0][0]).toEqual(expect.objectContaining({
      monday: ['open'],
    }))
  })

  /**
   * Test 5 — Clicking a time slot while OPEN is active deselects OPEN
   */
  it('clicking a slot while OPEN is active deselects OPEN', async () => {
    const avail = { ...emptyAvailability(), monday: ['open'] }
    const wrapper = mount(AvailabilityGrid, {
      props: { modelValue: avail },
    })

    // Button [1] = Mon 10:30 AM
    const buttons = wrapper.findAll('button')
    await buttons[1].trigger('click')

    const emitted = wrapper.emitted('update:modelValue')
    expect(emitted).toBeTruthy()
    expect(emitted![0][0]).toEqual(expect.objectContaining({
      monday: ['10:30'],
    }))
  })

  /**
   * Test 6 — Clicking an active slot deselects it
   */
  it('clicking an active slot deselects it', async () => {
    const avail = { ...emptyAvailability(), monday: ['10:30'] }
    const wrapper = mount(AvailabilityGrid, {
      props: { modelValue: avail },
    })

    // Button [1] = Mon 10:30 AM (already active)
    const buttons = wrapper.findAll('button')
    await buttons[1].trigger('click')

    const emitted = wrapper.emitted('update:modelValue')
    expect(emitted).toBeTruthy()
    expect(emitted![0][0]).toEqual(expect.objectContaining({
      monday: [],
    }))
  })

  /**
   * Test 7 — Save button emits the 'save' event
   */
  it('save button emits save event', async () => {
    const wrapper = mount(AvailabilityGrid, {
      props: { modelValue: emptyAvailability() },
    })

    // Last button = Save Availability
    const buttons = wrapper.findAll('button')
    const saveBtn = buttons[buttons.length - 1]
    expect(saveBtn.text()).toContain('Save Availability')
    await saveBtn.trigger('click')

    expect(wrapper.emitted('save')).toBeTruthy()
  })

  /**
   * Test 8 — Clicking per-day OPEN while active clears the day
   */
  it('clicking OPEN while active clears the day', async () => {
    const avail = { ...emptyAvailability(), tuesday: ['open'] }
    const wrapper = mount(AvailabilityGrid, {
      props: { modelValue: avail },
    })

    // Button [10] = Tue OPEN
    const buttons = wrapper.findAll('button')
    await buttons[10].trigger('click')

    const emitted = wrapper.emitted('update:modelValue')
    expect(emitted).toBeTruthy()
    expect(emitted![0][0]).toEqual(expect.objectContaining({
      tuesday: [],
    }))
  })

  /**
   * Test 9 — Master "Open Availability" sets every day to ['open']
   */
  it('master Open Availability sets all days to open', async () => {
    const wrapper = mount(AvailabilityGrid, {
      props: { modelValue: emptyAvailability() },
    })

    // Button [0] = Master "Open Availability"
    const buttons = wrapper.findAll('button')
    expect(buttons[0].text()).toContain('Open Availability')
    await buttons[0].trigger('click')

    const emitted = wrapper.emitted('update:modelValue')
    expect(emitted).toBeTruthy()
    const result = emitted![0][0] as Record<string, string[]>
    for (const day of DAYS) {
      expect(result[day]).toEqual(['open'])
    }
  })

  /**
   * Test 10 — Master "Open Availability" clears all when already all-open
   */
  it('master Open Availability clears all when already all-open', async () => {
    const wrapper = mount(AvailabilityGrid, {
      props: { modelValue: allOpenAvailability() },
    })

    // Button [0] = Master "Open Availability" (should be active/green)
    const buttons = wrapper.findAll('button')
    await buttons[0].trigger('click')

    const emitted = wrapper.emitted('update:modelValue')
    expect(emitted).toBeTruthy()
    const result = emitted![0][0] as Record<string, string[]>
    for (const day of DAYS) {
      expect(result[day]).toEqual([])
    }
  })

  /**
   * Test 11 — Readonly mode hides "Open Availability" master toggle
   */
  it('readonly hides the master Open Availability button', () => {
    const wrapper = mount(AvailabilityGrid, {
      props: { modelValue: emptyAvailability(), readonly: true },
    })

    const buttons = wrapper.findAll('button')
    const masterBtn = buttons.find(b => b.text().includes('Open Availability'))
    expect(masterBtn).toBeUndefined()
  })

  /**
   * Test 12 — Readonly mode hides the save button
   */
  it('readonly hides the save button', () => {
    const wrapper = mount(AvailabilityGrid, {
      props: { modelValue: emptyAvailability(), readonly: true },
    })

    const buttons = wrapper.findAll('button')
    const saveBtn = buttons.find(b => b.text().includes('Save Availability'))
    expect(saveBtn).toBeUndefined()
  })

  /**
   * Test 13 — Readonly mode adds pointer-events-none to the grid body
   */
  it('readonly adds pointer-events-none to the grid', () => {
    const wrapper = mount(AvailabilityGrid, {
      props: { modelValue: emptyAvailability(), readonly: true },
    })

    const gridBody = wrapper.find('.pointer-events-none')
    expect(gridBody.exists()).toBe(true)
  })
})
