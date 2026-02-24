/**
 * BaseButton.test.ts
 *
 * Unit tests for the BaseButton.vue component.
 *
 * BaseButton is a reusable button primitive that supports three visual
 * variants (primary, secondary, danger), three sizes (sm, md, lg), and
 * a loading state that disables the button and shows a spinner SVG.
 * Content is injected via the default slot.
 *
 * Props:
 *   - variant: 'primary' | 'secondary' | 'danger' (default: primary)
 *   - size:    'sm' | 'md' | 'lg' (default: md)
 *   - loading: boolean (default: false)
 *
 * Tests verify:
 *   1. Default slot content is rendered inside the button.
 *   2. Primary variant applies indigo background classes by default.
 *   3. Secondary variant applies gray background classes.
 *   4. Danger variant applies red background classes.
 *   5. Small size applies compact padding and text-xs.
 *   6. Large size applies spacious padding and text-base.
 *   7. Loading state disables the button.
 *   8. Loading state renders the spinner SVG.
 *   9. Spinner SVG is hidden when not loading.
 */

import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import BaseButton from '@/components/ui/BaseButton.vue'

describe('BaseButton.vue', () => {
  /**
   * Test 1 — Renders slot content
   *
   * The button should render whatever content is passed in the default
   * slot, allowing callers to provide text, icons, or any template content.
   */
  it('renders default slot content inside the button', () => {
    const wrapper = mount(BaseButton, {
      slots: { default: 'Save Changes' },
    })

    expect(wrapper.text()).toBe('Save Changes')
    expect(wrapper.find('button').exists()).toBe(true)
  })

  /**
   * Test 2 — Primary variant (default)
   *
   * When no variant is specified, or variant is 'primary', the button
   * should apply the indigo background and white text classes.
   */
  it('applies primary variant classes by default', () => {
    const wrapper = mount(BaseButton, {
      slots: { default: 'Submit' },
    })

    const button = wrapper.find('button')
    expect(button.classes()).toContain('bg-indigo-600')
    expect(button.classes()).toContain('text-white')
  })

  /**
   * Test 3 — Secondary variant
   *
   * When variant is 'secondary', the button should apply gray background
   * and dark text classes instead of the primary indigo.
   */
  it('applies secondary variant classes', () => {
    const wrapper = mount(BaseButton, {
      props: { variant: 'secondary' },
      slots: { default: 'Cancel' },
    })

    const button = wrapper.find('button')
    expect(button.classes()).toContain('bg-gray-200')
    expect(button.classes()).toContain('text-gray-800')
    expect(button.classes()).not.toContain('bg-indigo-600')
  })

  /**
   * Test 4 — Danger variant
   *
   * When variant is 'danger', the button should apply red background
   * and white text classes for destructive actions.
   */
  it('applies danger variant classes', () => {
    const wrapper = mount(BaseButton, {
      props: { variant: 'danger' },
      slots: { default: 'Delete' },
    })

    const button = wrapper.find('button')
    expect(button.classes()).toContain('bg-red-600')
    expect(button.classes()).toContain('text-white')
    expect(button.classes()).not.toContain('bg-indigo-600')
  })

  /**
   * Test 5 — Small size
   *
   * When size is 'sm', the button should apply compact padding
   * and smaller text sizing classes.
   */
  it('applies small size classes', () => {
    const wrapper = mount(BaseButton, {
      props: { size: 'sm' },
      slots: { default: 'Go' },
    })

    const button = wrapper.find('button')
    expect(button.classes()).toContain('px-2.5')
    expect(button.classes()).toContain('py-1.5')
    expect(button.classes()).toContain('text-xs')
  })

  /**
   * Test 6 — Large size
   *
   * When size is 'lg', the button should apply more spacious padding
   * and larger text sizing classes.
   */
  it('applies large size classes', () => {
    const wrapper = mount(BaseButton, {
      props: { size: 'lg' },
      slots: { default: 'Continue' },
    })

    const button = wrapper.find('button')
    expect(button.classes()).toContain('px-6')
    expect(button.classes()).toContain('py-3')
    expect(button.classes()).toContain('text-base')
  })

  /**
   * Test 7 — Loading disables the button
   *
   * When loading is true, the button should have the disabled attribute
   * so the user cannot interact with it during an async operation.
   */
  it('disables the button when loading is true', () => {
    const wrapper = mount(BaseButton, {
      props: { loading: true },
      slots: { default: 'Saving...' },
    })

    const button = wrapper.find('button')
    expect(button.attributes('disabled')).toBeDefined()
  })

  /**
   * Test 8 — Loading shows the spinner SVG
   *
   * When loading is true, an animated SVG spinner should be rendered
   * inside the button to give visual feedback.
   */
  it('renders spinner SVG when loading is true', () => {
    const wrapper = mount(BaseButton, {
      props: { loading: true },
      slots: { default: 'Saving...' },
    })

    const svg = wrapper.find('svg')
    expect(svg.exists()).toBe(true)
    expect(svg.classes()).toContain('animate-spin')
  })

  /**
   * Test 9 — Spinner is hidden when not loading
   *
   * When loading is false (or not provided), the spinner SVG should
   * not be rendered — only the slot content appears.
   */
  it('does not render spinner SVG when loading is false', () => {
    const wrapper = mount(BaseButton, {
      props: { loading: false },
      slots: { default: 'Save' },
    })

    const svg = wrapper.find('svg')
    expect(svg.exists()).toBe(false)
  })
})
