/**
 * stores/location.ts
 *
 * Pinia store for managing restaurant/bar location data.
 *
 * This store is primarily used by admin-level views that need to list all
 * locations (e.g. the "Manage Locations" page) and by any component that
 * needs to know which location is currently selected.
 *
 * State shape:
 *  - `locations` : The full list of Location objects fetched from the API.
 *  - `current`   : The currently-selected Location (e.g. the one being
 *                   viewed or edited), or null if none is selected.
 *
 * Actions:
 *  - `fetchLocations()` : Loads all locations from the API.
 *  - `setCurrent(loc)`  : Sets the currently-selected location.
 */

import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/composables/useApi'
import type { Location } from '@/types'

export const useLocationStore = defineStore('location', () => {
  // -------------------------------------------------------------------------
  // State
  // -------------------------------------------------------------------------

  /** All locations available in the system (fetched from the API) */
  const locations = ref<Location[]>([])

  /** The location currently being viewed or managed; null if none selected */
  const current = ref<Location | null>(null)

  // -------------------------------------------------------------------------
  // Actions
  // -------------------------------------------------------------------------

  /**
   * Fetches the complete list of locations from `GET /api/locations`.
   *
   * Expected API response: an array of Location objects.
   * Typically called by admin views on mount.
   *
   * @throws Will throw an AxiosError on network failure or auth error
   */
  async function fetchLocations() {
    const { data } = await api.get<Location[]>('/api/locations')
    locations.value = data
  }

  /**
   * Sets the currently-selected location.  Components use this to track
   * which location the user is viewing or editing without passing props
   * through multiple layers.
   *
   * @param location - The Location object to mark as current
   */
  function setCurrent(location: Location) {
    current.value = location
  }

  // -------------------------------------------------------------------------
  // Public API
  // -------------------------------------------------------------------------
  return {
    locations,
    current,
    fetchLocations,
    setCurrent,
  }
})
