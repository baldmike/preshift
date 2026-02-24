/**
 * BaseModal.test.ts
 *
 * Unit tests for the BaseModal.vue component.
 *
 * BaseModal is a reusable slot-based modal wrapper that renders a backdrop
 * overlay and a centered content card with fade and scale transitions.
 * It uses Teleport to render at the <body> level. Clicking the backdrop
 * emits a 'close' event.
 *
 * Props:
 *   - open: boolean          — controls visibility
 *   - size: 'md' | 'lg'     — max-width of the content card (default 'md')
 *
 * Emits:
 *   - close — fired when the backdrop is clicked
 *
 * Tests verify:
 *   1. Modal content is rendered when open is true.
 *   2. Modal content is not rendered when open is false.
 *   3. Default slot content is rendered inside the content card.
 *   4. Clicking the backdrop emits the close event.
 *   5. Default size applies max-w-md class.
 *   6. Large size applies max-w-lg class.
 *   7. Content card has the expected styling classes.
 */

import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import BaseModal from '@/components/ui/BaseModal.vue'

describe('BaseModal.vue', () => {
  /**
   * Test 1 — Renders when open is true
   *
   * When the open prop is true, the modal overlay and content card
   * should be present in the rendered output.
   */
  it('renders modal content when open is true', () => {
    const wrapper = mount(BaseModal, {
      props: { open: true },
      slots: { default: '<p>Modal body</p>' },
      global: { stubs: { Teleport: true } },
    })

    expect(wrapper.find('.fixed').exists()).toBe(true)
    expect(wrapper.text()).toContain('Modal body')
  })

  /**
   * Test 2 — Hidden when open is false
   *
   * When the open prop is false, no modal content should be rendered.
   * The Teleport target should remain empty.
   */
  it('does not render modal content when open is false', () => {
    const wrapper = mount(BaseModal, {
      props: { open: false },
      slots: { default: '<p>Modal body</p>' },
      global: { stubs: { Teleport: true } },
    })

    expect(wrapper.find('.fixed').exists()).toBe(false)
    expect(wrapper.text()).not.toContain('Modal body')
  })

  /**
   * Test 3 — Renders slot content
   *
   * The default slot content should appear inside the content card
   * when the modal is open, allowing any custom content to be injected.
   */
  it('renders default slot content inside the content card', () => {
    const wrapper = mount(BaseModal, {
      props: { open: true },
      slots: { default: '<div class="test-content">Custom content here</div>' },
      global: { stubs: { Teleport: true } },
    })

    expect(wrapper.find('.test-content').exists()).toBe(true)
    expect(wrapper.text()).toContain('Custom content here')
  })

  /**
   * Test 4 — Clicking backdrop emits close
   *
   * When the user clicks on the backdrop overlay (the semi-transparent
   * dark area behind the content card), the component should emit 'close'.
   */
  it('emits close event when backdrop is clicked', async () => {
    const wrapper = mount(BaseModal, {
      props: { open: true },
      slots: { default: '<p>Content</p>' },
      global: { stubs: { Teleport: true } },
    })

    const backdrop = wrapper.find('.bg-black\\/50')
    await backdrop.trigger('click')

    expect(wrapper.emitted('close')).toBeTruthy()
    expect(wrapper.emitted('close')!.length).toBe(1)
  })

  /**
   * Test 5 — Default size applies max-w-md
   *
   * When no size prop is provided (or size is 'md'), the content card
   * should have the max-w-md class for medium width.
   */
  it('applies max-w-md class for default size', () => {
    const wrapper = mount(BaseModal, {
      props: { open: true },
      slots: { default: '<p>Content</p>' },
      global: { stubs: { Teleport: true } },
    })

    const cards = wrapper.findAll('div').filter(d => d.classes().includes('max-w-md'))
    expect(cards.length).toBeGreaterThan(0)
  })

  /**
   * Test 6 — Large size applies max-w-lg
   *
   * When size is 'lg', the content card should have the max-w-lg class
   * for a wider modal layout.
   */
  it('applies max-w-lg class for large size', () => {
    const wrapper = mount(BaseModal, {
      props: { open: true, size: 'lg' },
      slots: { default: '<p>Content</p>' },
      global: { stubs: { Teleport: true } },
    })

    const cards = wrapper.findAll('div').filter(d => d.classes().includes('max-w-lg'))
    expect(cards.length).toBeGreaterThan(0)
  })

  /**
   * Test 7 — Content card has expected styling
   *
   * The content card should have rounded corners, dark background,
   * border, shadow, and overflow handling classes.
   */
  it('content card has expected styling classes', () => {
    const wrapper = mount(BaseModal, {
      props: { open: true },
      slots: { default: '<p>Content</p>' },
      global: { stubs: { Teleport: true } },
    })

    const card = wrapper.find('.rounded-xl')
    expect(card.exists()).toBe(true)
    expect(card.classes()).toContain('bg-gray-950')
    expect(card.classes()).toContain('shadow-xl')
    expect(card.classes()).toContain('overflow-y-auto')
  })
})
