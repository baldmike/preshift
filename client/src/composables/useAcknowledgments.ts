/**
 * composables/useAcknowledgments.ts
 *
 * Provides helpers for the "acknowledgment" workflow.  Staff members are
 * expected to acknowledge that they have read certain items (announcements,
 * specials, etc.) before or during their shift.  This composable wraps the
 * API call and the local store mutation into a single, easy-to-use function.
 *
 * Flow:
 *  1. Component calls `acknowledge(type, id)` when the user taps "Got it".
 *  2. A POST request is sent to `/api/acknowledge` which persists the
 *     acknowledgment on the server (creates an Acknowledgment model).
 *  3. On success, the local preshift store is updated immediately via
 *     `store.markAcknowledged()` so the UI reflects the change without
 *     needing to re-fetch.
 *  4. `isAcknowledged(type, id)` can be called at any time to check
 *     whether the current user has already acknowledged a given item.
 */

import api from '@/composables/useApi'
import { usePreshiftStore } from '@/stores/preshift'

/**
 * Composable that exposes acknowledgment-related helpers.
 *
 * @returns An object with:
 *  - `acknowledge(type, id)` : async function to acknowledge an item
 *  - `isAcknowledged(type, id)` : sync function to check acknowledgment status
 */
export function useAcknowledgments() {
  const store = usePreshiftStore()

  /**
   * Sends an acknowledgment to the backend and updates the local store.
   *
   * @param type - The entity type shorthand (e.g. "announcement", "special")
   * @param id   - The primary key of the entity being acknowledged
   * @throws Will throw if the API request fails (e.g. network error, 401)
   */
  async function acknowledge(type: string, id: number) {
    // POST the acknowledgment to the server
    await api.post('/api/acknowledge', { type, id })
    // Optimistically update the local store so the UI reacts immediately
    store.markAcknowledged(type, id)
  }

  /**
   * Checks whether the current user has already acknowledged a given item
   * by searching the local acknowledgments array in the preshift store.
   *
   * @param type - The entity type shorthand (e.g. "announcement", "special")
   * @param id   - The primary key of the entity to check
   * @returns `true` if the item has been acknowledged, `false` otherwise
   */
  function isAcknowledged(type: string, id: number): boolean {
    return store.acknowledgments.some(
      (ack) => ack.type === type && ack.id === id
    )
  }

  return {
    acknowledge,
    isAcknowledged,
  }
}
