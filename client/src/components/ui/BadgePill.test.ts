/**
 * BadgePill.test.ts
 *
 * Comprehensive unit tests for the BadgePill.vue component.
 *
 * BadgePill is a small, presentational "pill" badge used throughout the app
 * to display status labels (e.g. "open", "approved", "server") with
 * colour-coded backgrounds and text.  It accepts two props:
 *
 *   - label  (string, required)  — the text content rendered inside the <span>
 *   - color  ('blue' | 'green' | 'yellow' | 'red' | 'gray', optional)
 *       Controls which Tailwind colour classes are applied.
 *       Defaults to 'gray' when not provided.
 *
 * The component renders a single <span> element with conditional CSS classes
 * driven by the `color` prop.  There is no interactivity, no slots, and no
 * emitted events — it is purely a display component.
 *
 * These tests verify:
 *   1. The label text is rendered correctly inside the span.
 *   2. The 'blue' colour prop applies the correct bg + text classes.
 *   3. The 'green' colour prop applies the correct bg + text classes.
 *   4. The 'yellow' colour prop applies the correct bg + text classes.
 *   5. The component mounts without errors when all props are provided.
 */

import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import BadgePill from '@/components/ui/BadgePill.vue'

describe('BadgePill.vue', () => {
  /**
   * Test 1 — Label rendering
   *
   * The most fundamental behaviour: whatever string is passed via the `label`
   * prop should appear as the text content of the rendered <span>.
   * We mount the component with label="approved" and assert the text matches.
   */
  it('renders the label text correctly', () => {
    // Mount BadgePill with only the required `label` prop.
    // The `color` prop is intentionally omitted here so it falls back to 'gray'.
    const wrapper = mount(BadgePill, {
      props: {
        label: 'approved',
      },
    })

    // The component renders a single <span> — its text content should
    // exactly equal the label prop value.
    expect(wrapper.text()).toBe('approved')
  })

  /**
   * Test 2 — Blue colour variant
   *
   * When `color` is 'blue', the component should apply Tailwind utility
   * classes for a blue-tinted background and blue text:
   *   - bg-blue-500/15   (translucent blue background)
   *   - text-blue-400    (blue text colour)
   *
   * We check for these classes on the root <span> element.
   */
  it("applies correct CSS classes for 'blue' color", () => {
    const wrapper = mount(BadgePill, {
      props: {
        label: 'server',
        color: 'blue',
      },
    })

    // Grab the root <span> element from the rendered output.
    const span = wrapper.find('span')

    // Verify the blue-specific Tailwind classes are present.
    // These classes are defined in the component's :class binding under
    // the condition `color === 'blue'`.
    expect(span.classes()).toContain('bg-blue-500/15')
    expect(span.classes()).toContain('text-blue-400')

    // Sanity check: make sure no OTHER colour variant classes leaked in.
    // The green classes should NOT be applied when colour is 'blue'.
    expect(span.classes()).not.toContain('bg-green-500/15')
    expect(span.classes()).not.toContain('text-green-400')
  })

  /**
   * Test 3 — Green colour variant
   *
   * When `color` is 'green', the component should apply Tailwind utility
   * classes for a green-tinted background and green text:
   *   - bg-green-500/15  (translucent green background)
   *   - text-green-400   (green text colour)
   *
   * This is the colour used for "filled" shift drops, "bartender" role badges,
   * and "approved" time-off requests elsewhere in the app.
   */
  it("applies correct CSS classes for 'green' color", () => {
    const wrapper = mount(BadgePill, {
      props: {
        label: 'bartender',
        color: 'green',
      },
    })

    const span = wrapper.find('span')

    // Verify the green-specific Tailwind classes are present.
    expect(span.classes()).toContain('bg-green-500/15')
    expect(span.classes()).toContain('text-green-400')

    // Sanity check: blue classes should NOT be present.
    expect(span.classes()).not.toContain('bg-blue-500/15')
    expect(span.classes()).not.toContain('text-blue-400')
  })

  /**
   * Test 4 — Yellow colour variant
   *
   * When `color` is 'yellow', the component should apply:
   *   - bg-yellow-500/15  (translucent yellow background)
   *   - text-yellow-400   (yellow text colour)
   *
   * This colour is used for "pending" time-off requests and "open" shift drops.
   */
  it("applies correct CSS classes for 'yellow' color", () => {
    const wrapper = mount(BadgePill, {
      props: {
        label: 'pending',
        color: 'yellow',
      },
    })

    const span = wrapper.find('span')

    // Verify the yellow-specific Tailwind classes are present.
    expect(span.classes()).toContain('bg-yellow-500/15')
    expect(span.classes()).toContain('text-yellow-400')

    // Sanity check: red classes should NOT be present.
    expect(span.classes()).not.toContain('bg-red-500/15')
    expect(span.classes()).not.toContain('text-red-400')
  })

  /**
   * Test 5 — Component mounts without errors when all props are provided
   *
   * This is a smoke test to confirm that the component can be mounted with
   * every prop filled in (both `label` and `color`) and that the resulting
   * wrapper is a valid, truthy Vue Test Utils wrapper.
   *
   * We also verify the base (always-applied) CSS classes that are present
   * regardless of the colour prop, such as 'inline-flex', 'rounded-full',
   * 'font-semibold', etc.
   */
  it('renders without errors when all props provided', () => {
    const wrapper = mount(BadgePill, {
      props: {
        label: 'denied',
        color: 'red',
      },
    })

    // The wrapper should exist and be a valid mounted component.
    expect(wrapper.exists()).toBe(true)

    // The text content should match the label prop.
    expect(wrapper.text()).toBe('denied')

    // Verify the base CSS classes that are always applied regardless of
    // which colour variant is active.  These come from the first element
    // in the component's :class array binding.
    const span = wrapper.find('span')
    expect(span.classes()).toContain('inline-flex')
    expect(span.classes()).toContain('items-center')
    expect(span.classes()).toContain('rounded-full')
    expect(span.classes()).toContain('font-semibold')
    expect(span.classes()).toContain('uppercase')
    expect(span.classes()).toContain('tracking-wide')

    // And the red-specific classes should be applied since we passed color='red'.
    expect(span.classes()).toContain('bg-red-500/15')
    expect(span.classes()).toContain('text-red-400')
  })
})
