/**
 * BaseCard.test.ts
 *
 * Unit tests for the BaseCard.vue component.
 *
 * BaseCard is a simple presentational card container that provides a
 * white background, rounded corners, shadow, and padding. It supports
 * an optional title rendered as an <h3> heading and a default slot
 * for body content.
 *
 * Props:
 *   - title: optional string rendered as an <h3> above slot content
 *
 * Tests verify:
 *   1. Default slot content is rendered inside the card.
 *   2. Title heading is rendered when the title prop is provided.
 *   3. Title heading is not rendered when the title prop is omitted.
 *   4. Card container has the expected base styling classes.
 *   5. Title heading has correct styling classes.
 */

import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import BaseCard from '@/components/ui/BaseCard.vue'

describe('BaseCard.vue', () => {
  /**
   * Test 1 — Renders default slot content
   *
   * The card body should display whatever content is passed in the
   * default slot, making it a generic container for any child content.
   */
  it('renders default slot content inside the card', () => {
    const wrapper = mount(BaseCard, {
      slots: { default: '<p>Card body content</p>' },
    })

    expect(wrapper.text()).toContain('Card body content')
    expect(wrapper.find('p').exists()).toBe(true)
  })

  /**
   * Test 2 — Renders title when provided
   *
   * When the title prop is passed, an <h3> element should appear above
   * the slot content showing the title text.
   */
  it('renders h3 heading when title prop is provided', () => {
    const wrapper = mount(BaseCard, {
      props: { title: 'Today\'s Specials' },
      slots: { default: '<p>Body</p>' },
    })

    const heading = wrapper.find('h3')
    expect(heading.exists()).toBe(true)
    expect(heading.text()).toBe('Today\'s Specials')
  })

  /**
   * Test 3 — Hides title when not provided
   *
   * When no title prop is passed, the <h3> element should not be
   * rendered at all — the card should only contain the slot content.
   */
  it('does not render h3 heading when title prop is omitted', () => {
    const wrapper = mount(BaseCard, {
      slots: { default: '<p>Body only</p>' },
    })

    const heading = wrapper.find('h3')
    expect(heading.exists()).toBe(false)
  })

  /**
   * Test 4 — Card container has base styling classes
   *
   * The root div should always have the white background, rounded corners,
   * shadow, and padding classes regardless of props.
   */
  it('applies base styling classes to the card container', () => {
    const wrapper = mount(BaseCard, {
      slots: { default: 'Content' },
    })

    const container = wrapper.find('div')
    expect(container.classes()).toContain('bg-white')
    expect(container.classes()).toContain('rounded-lg')
    expect(container.classes()).toContain('shadow')
    expect(container.classes()).toContain('p-4')
  })

  /**
   * Test 5 — Title heading has correct styling classes
   *
   * When rendered, the title <h3> should have the expected Tailwind classes
   * for font size, weight, color, and bottom margin.
   */
  it('applies correct styling classes to the title heading', () => {
    const wrapper = mount(BaseCard, {
      props: { title: 'Announcements' },
      slots: { default: 'Content' },
    })

    const heading = wrapper.find('h3')
    expect(heading.classes()).toContain('text-lg')
    expect(heading.classes()).toContain('font-semibold')
    expect(heading.classes()).toContain('text-gray-900')
    expect(heading.classes()).toContain('mb-3')
  })
})
