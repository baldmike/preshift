/**
 * UserAvatar.test.ts
 *
 * Comprehensive unit tests for the UserAvatar.vue component.
 *
 * UserAvatar is a reusable avatar component that displays a user's profile
 * photo with an initials-based fallback. It handles broken image URLs by
 * hiding the <img> on error and showing initials instead.
 *
 * Props:
 *   - user  ({ name: string; profile_photo_url?: string | null } | null)
 *       The user to display. When null, shows "?" as fallback.
 *   - size  ('xs' | 'sm' | 'md' | 'lg', default 'sm')
 *       Controls the avatar dimensions via Tailwind size classes.
 *   - bg    (string, default '')
 *       Optional Tailwind background class override for the initials circle.
 *
 * Slots:
 *   - default : overlay content (e.g. unread indicator dots)
 *
 * These tests verify:
 *   1. Initials are computed correctly from a two-word name.
 *   2. Single-word names produce a single-letter initial.
 *   3. Null user shows "?" as fallback.
 *   4. Photo URL renders an <img> element when provided.
 *   5. Null photo URL shows initials instead of an <img>.
 *   6. Image error hides the <img> and falls back to initials.
 *   7. Size "xs" applies the correct Tailwind dimension classes.
 *   8. Size "lg" applies the correct Tailwind dimension classes.
 *   9. Custom bg prop overrides the default background class.
 *  10. Default slot content is rendered (for overlay elements like unread dots).
 */

import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import UserAvatar from '@/components/ui/UserAvatar.vue'

describe('UserAvatar.vue', () => {
  /**
   * Test 1 — Two-word name initials
   *
   * Given a user with name "Jane Doe", the component should display "JD"
   * as the initials. Initials are extracted from the first letter of each
   * word, uppercased, and capped at 2 characters.
   */
  it('computes initials from a two-word name', () => {
    const wrapper = mount(UserAvatar, {
      props: {
        user: { name: 'Jane Doe', profile_photo_url: null },
      },
    })

    // The rendered text should be the first letter of each word, uppercased.
    expect(wrapper.text()).toBe('JD')
  })

  /**
   * Test 2 — Single-word name
   *
   * A user with only a first name (e.g. "Alice") should produce a single
   * initial letter "A". The component splits on spaces, so one word yields
   * one letter.
   */
  it('shows single initial for single-word name', () => {
    const wrapper = mount(UserAvatar, {
      props: {
        user: { name: 'Alice', profile_photo_url: null },
      },
    })

    expect(wrapper.text()).toBe('A')
  })

  /**
   * Test 3 — Null user fallback
   *
   * When the user prop is null (e.g. unknown author), the component should
   * display "?" as a safe fallback instead of crashing.
   */
  it('shows "?" when user is null', () => {
    const wrapper = mount(UserAvatar, {
      props: {
        user: null,
      },
    })

    expect(wrapper.text()).toBe('?')
  })

  /**
   * Test 4 — Photo URL renders an <img>
   *
   * When the user has a profile_photo_url, the component should render an
   * <img> element with that URL as the src attribute, and the user's name
   * as the alt text for accessibility.
   */
  it('renders an <img> when profile_photo_url is provided', () => {
    const wrapper = mount(UserAvatar, {
      props: {
        user: { name: 'Jane Doe', profile_photo_url: 'https://example.com/photo.jpg' },
      },
    })

    const img = wrapper.find('img')
    expect(img.exists()).toBe(true)
    expect(img.attributes('src')).toBe('https://example.com/photo.jpg')
    expect(img.attributes('alt')).toBe('Jane Doe')
  })

  /**
   * Test 5 — Null photo URL shows initials
   *
   * When profile_photo_url is null, no <img> should be rendered. Instead
   * the component falls back to showing the text initials.
   */
  it('shows initials when profile_photo_url is null', () => {
    const wrapper = mount(UserAvatar, {
      props: {
        user: { name: 'Bob Smith', profile_photo_url: null },
      },
    })

    expect(wrapper.find('img').exists()).toBe(false)
    expect(wrapper.text()).toBe('BS')
  })

  /**
   * Test 6 — Image error falls back to initials
   *
   * If the <img> fails to load (broken URL, 404, etc.), the component
   * listens for the @error event and hides the image, showing initials
   * instead. This ensures a graceful degradation.
   */
  it('falls back to initials on image load error', async () => {
    const wrapper = mount(UserAvatar, {
      props: {
        user: { name: 'Jane Doe', profile_photo_url: 'https://example.com/broken.jpg' },
      },
    })

    // Before error, the <img> should be present.
    expect(wrapper.find('img').exists()).toBe(true)

    // Simulate the image failing to load.
    await wrapper.find('img').trigger('error')

    // After error, the <img> should be hidden and initials should show.
    expect(wrapper.find('img').exists()).toBe(false)
    expect(wrapper.text()).toBe('JD')
  })

  /**
   * Test 7 — Size "xs" applies correct classes
   *
   * The "xs" size preset maps to Tailwind classes "w-6 h-6 text-[10px]".
   * This is the smallest variant, used for reply avatars in board posts.
   */
  it('applies xs size classes', () => {
    const wrapper = mount(UserAvatar, {
      props: {
        user: { name: 'Test', profile_photo_url: null },
        size: 'xs',
      },
    })

    const el = wrapper.element
    expect(el.classList.contains('w-6')).toBe(true)
    expect(el.classList.contains('h-6')).toBe(true)
  })

  /**
   * Test 8 — Size "lg" applies correct classes
   *
   * The "lg" size preset maps to Tailwind classes "w-12 h-12 text-base".
   * This is the largest variant, used in the profile view and employee modal.
   */
  it('applies lg size classes', () => {
    const wrapper = mount(UserAvatar, {
      props: {
        user: { name: 'Test', profile_photo_url: null },
        size: 'lg',
      },
    })

    const el = wrapper.element
    expect(el.classList.contains('w-12')).toBe(true)
    expect(el.classList.contains('h-12')).toBe(true)
  })

  /**
   * Test 9 — Custom bg prop overrides default
   *
   * When no photo is present and a custom `bg` class is provided (e.g.
   * "bg-amber-500 text-gray-950" for the TopBar avatar), it should be
   * applied instead of the default "bg-gray-700 text-gray-300".
   */
  it('applies custom bg class when provided', () => {
    const wrapper = mount(UserAvatar, {
      props: {
        user: { name: 'Test', profile_photo_url: null },
        bg: 'bg-amber-500 text-gray-950',
      },
    })

    const el = wrapper.element
    expect(el.classList.contains('bg-amber-500')).toBe(true)
    expect(el.classList.contains('text-gray-950')).toBe(true)
    // Default bg should NOT be present when custom bg is provided.
    expect(el.classList.contains('bg-gray-700')).toBe(false)
  })

  /**
   * Test 10 — Default slot renders overlay content
   *
   * The component provides a default slot for overlay elements like unread
   * indicator dots (used in ConversationListItem). Slot content should be
   * rendered inside the avatar wrapper.
   */
  it('renders default slot content', () => {
    const wrapper = mount(UserAvatar, {
      props: {
        user: { name: 'Test', profile_photo_url: null },
      },
      slots: {
        default: '<div class="unread-dot" />',
      },
    })

    expect(wrapper.find('.unread-dot').exists()).toBe(true)
  })
})
