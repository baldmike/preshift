<script setup lang="ts">
/**
 * SpecialsView -- read-only staff view that lists all current daily specials.
 * Fetches specials from the API on mount and renders each as a SpecialCard.
 * Unlike the admin ManageSpecials view, staff cannot create/edit/delete here;
 * they can only view and acknowledge specials.
 */
import { ref, onMounted } from 'vue'
import api from '@/composables/useApi'
import AppShell from '@/components/layout/AppShell.vue'
import SpecialCard from '@/components/SpecialCard.vue'
import type { Special } from '@/types'

// Reactive list of specials fetched from the API
const specials = ref<Special[]>([])
// True while the specials list is being loaded from the server
const loading = ref(false)

/**
 * Fetches the current specials from GET /api/specials
 * and populates the specials array.
 */
async function fetchSpecials() {
  loading.value = true
  try {
    const { data } = await api.get<Special[]>('/api/specials')
    specials.value = data
  } finally {
    loading.value = false
  }
}

// Load specials when the view mounts
onMounted(fetchSpecials)
</script>

<template>
  <AppShell>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Today's Specials</h1>
        <router-link
          to="/dashboard"
          class="inline-flex items-center justify-center gap-1.5 px-2.5 py-1.5 text-xs font-medium rounded-lg bg-gray-200 text-gray-800 hover:bg-gray-300 transition-colors"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          Corner!
        </router-link>
      </div>

      <div v-if="loading" class="flex justify-center py-8">
        <svg class="animate-spin h-8 w-8 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
        </svg>
      </div>

      <div v-else-if="specials.length" class="space-y-3">
        <SpecialCard v-for="special in specials" :key="special.id" :special="special" />
      </div>

      <div v-else class="text-center py-8 text-gray-500">
        No specials running today.
      </div>
    </div>
  </AppShell>
</template>
