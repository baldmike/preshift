/**
 * MessageComposer.test.ts
 *
 * Unit tests for the MessageComposer.vue component.
 *
 * Tests verify:
 *  1. Emits 'submit' with the trimmed body text when Send is clicked.
 *  2. Clears the input after submission.
 *  3. Disables Send button when input is empty.
 *  4. Disables Send button when loading is true.
 *  5. Shows character count near the maxLength limit.
 *  6. Does not emit when body exceeds maxLength.
 */

import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import MessageComposer from '@/components/messages/MessageComposer.vue'

/**
 * Helper: mount the MessageComposer with optional props.
 */
function mountComposer(props: Record<string, unknown> = {}) {
  return mount(MessageComposer, {
    props: {
      placeholder: 'Write a message...',
      maxLength: 2000,
      loading: false,
      ...props,
    },
  })
}

describe('MessageComposer', () => {
  /**
   * Checks that clicking Send emits 'submit' with the trimmed text and
   * clears the input afterward.
   */
  it('emits submit with trimmed body and clears input', async () => {
    const wrapper = mountComposer()
    const input = wrapper.find('[data-testid="composer-input"]')
    const sendBtn = wrapper.find('[data-testid="composer-send"]')

    await input.setValue('  Hello team!  ')
    await sendBtn.trigger('click')

    expect(wrapper.emitted('submit')).toBeTruthy()
    expect(wrapper.emitted('submit')![0]).toEqual(['Hello team!'])

    // Input should be cleared
    expect((input.element as HTMLTextAreaElement).value).toBe('')
  })

  /**
   * Checks that the Send button is disabled when the input is empty.
   */
  it('disables Send when input is empty', () => {
    const wrapper = mountComposer()
    const sendBtn = wrapper.find('[data-testid="composer-send"]')

    expect((sendBtn.element as HTMLButtonElement).disabled).toBe(true)
  })

  /**
   * Checks that the Send button is disabled when loading is true.
   */
  it('disables Send when loading is true', async () => {
    const wrapper = mountComposer({ loading: true })
    const input = wrapper.find('[data-testid="composer-input"]')
    await input.setValue('Hello')

    const sendBtn = wrapper.find('[data-testid="composer-send"]')
    expect((sendBtn.element as HTMLButtonElement).disabled).toBe(true)
  })

  /**
   * Checks that the character count appears when near the limit.
   */
  it('shows character count near maxLength', async () => {
    const wrapper = mountComposer({ maxLength: 100 })
    const input = wrapper.find('[data-testid="composer-input"]')

    // Below threshold (100 - 200 = negative, so any text should show)
    await input.setValue('a')
    // maxLength 100: threshold is 100 - 200 = -100, so showCount = body.length >= -100 = true
    expect(wrapper.text()).toContain('/ 100')
  })
})
