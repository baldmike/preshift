/**
 * BaseInput.test.ts
 *
 * Unit tests for the BaseInput.vue component.
 *
 * BaseInput is a reusable text input with optional label and error message.
 * It implements v-model via modelValue prop + update:modelValue emit for
 * two-way binding in parent components.
 *
 * Props:
 *   - modelValue:  Current input value (string | number)
 *   - label:       Optional label text rendered above the input
 *   - type:        HTML input type attribute (defaults to 'text')
 *   - error:       Validation error string; when present, border turns red
 *   - placeholder: Placeholder text shown when input is empty
 *
 * Emits:
 *   - update:modelValue: Fired on each input event with the new string value
 *
 * Tests verify:
 *   1. Label is rendered when the label prop is provided.
 *   2. Label is not rendered when the label prop is omitted.
 *   3. Input value reflects the modelValue prop.
 *   4. Typing emits update:modelValue with the new value.
 *   5. Placeholder text is applied to the input.
 *   6. Error message is displayed when the error prop is set.
 *   7. Error message is not displayed when the error prop is omitted.
 *   8. Input border changes to red when error is present.
 *   9. Input type defaults to 'text' when not specified.
 *  10. Custom input type is applied when specified.
 */

import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import BaseInput from '@/components/ui/BaseInput.vue'

describe('BaseInput.vue', () => {
  /**
   * Test 1 — Renders label when provided
   *
   * When the label prop is passed, a <label> element should appear
   * above the input showing the label text.
   */
  it('renders label when label prop is provided', () => {
    const wrapper = mount(BaseInput, {
      props: { label: 'Email Address' },
    })

    const label = wrapper.find('label')
    expect(label.exists()).toBe(true)
    expect(label.text()).toBe('Email Address')
  })

  /**
   * Test 2 — Hides label when not provided
   *
   * When no label prop is given, the <label> element should not
   * be rendered in the DOM.
   */
  it('does not render label when label prop is omitted', () => {
    const wrapper = mount(BaseInput)

    const label = wrapper.find('label')
    expect(label.exists()).toBe(false)
  })

  /**
   * Test 3 — Input value reflects modelValue
   *
   * The <input> element's value attribute should match the modelValue
   * prop to support the v-model binding pattern.
   */
  it('sets input value from modelValue prop', () => {
    const wrapper = mount(BaseInput, {
      props: { modelValue: 'hello@example.com' },
    })

    const input = wrapper.find('input')
    expect((input.element as HTMLInputElement).value).toBe('hello@example.com')
  })

  /**
   * Test 4 — Emits update:modelValue on input
   *
   * When the user types in the input, the component should emit
   * 'update:modelValue' with the new value for two-way binding.
   */
  it('emits update:modelValue when input value changes', async () => {
    const wrapper = mount(BaseInput, {
      props: { modelValue: '' },
    })

    const input = wrapper.find('input')
    await input.setValue('new value')

    expect(wrapper.emitted('update:modelValue')).toBeTruthy()
    expect(wrapper.emitted('update:modelValue')![0]).toEqual(['new value'])
  })

  /**
   * Test 5 — Placeholder text is applied
   *
   * The placeholder prop should be passed through to the underlying
   * <input> element's placeholder attribute.
   */
  it('applies placeholder text to the input', () => {
    const wrapper = mount(BaseInput, {
      props: { placeholder: 'Enter your name' },
    })

    const input = wrapper.find('input')
    expect(input.attributes('placeholder')).toBe('Enter your name')
  })

  /**
   * Test 6 — Error message is displayed
   *
   * When the error prop is set, a <p> element should appear below
   * the input showing the validation error text.
   */
  it('displays error message when error prop is set', () => {
    const wrapper = mount(BaseInput, {
      props: { error: 'This field is required' },
    })

    const errorEl = wrapper.find('p')
    expect(errorEl.exists()).toBe(true)
    expect(errorEl.text()).toBe('This field is required')
    expect(errorEl.classes()).toContain('text-red-600')
  })

  /**
   * Test 7 — Error message is hidden when no error
   *
   * When the error prop is not provided, no error <p> element should
   * be rendered in the DOM.
   */
  it('does not display error message when error prop is omitted', () => {
    const wrapper = mount(BaseInput)

    const errorEl = wrapper.find('p')
    expect(errorEl.exists()).toBe(false)
  })

  /**
   * Test 8 — Input border turns red on error
   *
   * When an error is present, the input should have red border classes
   * instead of the default gray border classes.
   */
  it('applies red border classes when error is present', () => {
    const wrapper = mount(BaseInput, {
      props: { error: 'Invalid email' },
    })

    const input = wrapper.find('input')
    expect(input.classes()).toContain('border-red-300')
    expect(input.classes()).toContain('text-red-400')
    expect(input.classes()).not.toContain('border-gray-600')
  })

  /**
   * Test 9 — Input type defaults to text
   *
   * When no type prop is provided, the input should default to
   * type="text" as specified in the template fallback.
   */
  it('defaults to type text when type prop is omitted', () => {
    const wrapper = mount(BaseInput)

    const input = wrapper.find('input')
    expect(input.attributes('type')).toBe('text')
  })

  /**
   * Test 10 — Custom input type is applied
   *
   * When a type prop is provided (e.g. 'password'), it should be
   * applied to the underlying <input> element.
   */
  it('applies custom input type when specified', () => {
    const wrapper = mount(BaseInput, {
      props: { type: 'password' },
    })

    const input = wrapper.find('input')
    expect(input.attributes('type')).toBe('password')
  })
})
