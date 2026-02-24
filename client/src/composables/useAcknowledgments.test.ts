/**
 * useAcknowledgments.test.ts
 *
 * Unit tests for the useAcknowledgments composable.
 *
 * These tests verify:
 *  1. `acknowledge(type, id)` sends a POST to `/api/acknowledge` and updates
 *     the local preshift store via `markAcknowledged`.
 *  2. `isAcknowledged(type, id)` returns true when the item exists in the
 *     store's acknowledgments array and false otherwise.
 *  3. `acknowledge` propagates API errors to the caller.
 *  4. `isAcknowledged` handles an empty acknowledgments array gracefully.
 *  5. `isAcknowledged` correctly differentiates between types with the same id.
 *
 * We mock `@/composables/useApi` to avoid real HTTP calls and use a real
 * Pinia preshift store to verify local state mutations.
 */

import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { flushPromises } from '@vue/test-utils'
import { usePreshiftStore } from '@/stores/preshift'

/* Mock the default Axios instance used by the composable */
vi.mock('@/composables/useApi', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
    patch: vi.fn(),
    delete: vi.fn(),
  },
  useApi: vi.fn(),
}))

import api from '@/composables/useApi'
import { useAcknowledgments } from '@/composables/useAcknowledgments'

describe('useAcknowledgments composable', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  // ── acknowledge ────────────────────────────────────────────────────────

  /* Verifies that calling acknowledge() sends a POST with the correct
     type and id payload to the backend. */
  it('sends a POST to /api/acknowledge with type and id', async () => {
    vi.mocked(api.post).mockResolvedValue({ data: {} })
    const { acknowledge } = useAcknowledgments()

    await acknowledge('announcement', 5)

    expect(api.post).toHaveBeenCalledWith('/api/acknowledge', {
      type: 'announcement',
      id: 5,
    })
  })

  /* Verifies that after a successful API call, the local preshift store
     is updated so isAcknowledged returns true immediately. */
  it('updates the local store after a successful API call', async () => {
    vi.mocked(api.post).mockResolvedValue({ data: {} })
    const store = usePreshiftStore()
    const { acknowledge, isAcknowledged } = useAcknowledgments()

    expect(isAcknowledged('special', 3)).toBe(false)

    await acknowledge('special', 3)
    await flushPromises()

    expect(store.acknowledgments).toContainEqual({ type: 'special', id: 3 })
    expect(isAcknowledged('special', 3)).toBe(true)
  })

  /* Verifies that if the API call fails, the error is propagated so the
     calling component can display an error message. */
  it('propagates API errors to the caller', async () => {
    const networkError = new Error('Network Error')
    vi.mocked(api.post).mockRejectedValue(networkError)
    const { acknowledge } = useAcknowledgments()

    await expect(acknowledge('announcement', 1)).rejects.toThrow('Network Error')
  })

  // ── isAcknowledged ─────────────────────────────────────────────────────

  /* Verifies that isAcknowledged returns false when the acknowledgments
     array is completely empty. */
  it('returns false when no acknowledgments exist', () => {
    const { isAcknowledged } = useAcknowledgments()

    expect(isAcknowledged('announcement', 1)).toBe(false)
  })

  /* Verifies that isAcknowledged correctly matches both type AND id,
     so items with the same id but different types are not confused. */
  it('differentiates between types with the same id', () => {
    const store = usePreshiftStore()
    store.acknowledgments = [{ type: 'special', id: 1 }]
    const { isAcknowledged } = useAcknowledgments()

    expect(isAcknowledged('special', 1)).toBe(true)
    expect(isAcknowledged('announcement', 1)).toBe(false)
  })

  /* Verifies that isAcknowledged returns true for multiple different items
     stored in the acknowledgments array. */
  it('returns true for each acknowledged item in a mixed array', () => {
    const store = usePreshiftStore()
    store.acknowledgments = [
      { type: 'announcement', id: 10 },
      { type: 'special', id: 20 },
      { type: 'push_item', id: 30 },
    ]
    const { isAcknowledged } = useAcknowledgments()

    expect(isAcknowledged('announcement', 10)).toBe(true)
    expect(isAcknowledged('special', 20)).toBe(true)
    expect(isAcknowledged('push_item', 30)).toBe(true)
    expect(isAcknowledged('announcement', 99)).toBe(false)
  })
})
