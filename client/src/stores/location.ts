import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/composables/useApi'
import type { Location } from '@/types'

export const useLocationStore = defineStore('location', () => {
  const locations = ref<Location[]>([])
  const current = ref<Location | null>(null)

  async function fetchLocations() {
    const { data } = await api.get<Location[]>('/api/locations')
    locations.value = data
  }

  function setCurrent(location: Location) {
    current.value = location
  }

  return {
    locations,
    current,
    fetchLocations,
    setCurrent,
  }
})
