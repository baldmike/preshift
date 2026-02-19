<script setup lang="ts">
import { ref, onMounted } from 'vue'
import api from '@/composables/useApi'
import AppShell from '@/components/layout/AppShell.vue'
import SpecialCard from '@/components/SpecialCard.vue'
import type { Special } from '@/types'

const specials = ref<Special[]>([])
const loading = ref(false)

async function fetchSpecials() {
  loading.value = true
  try {
    const { data } = await api.get<Special[]>('/api/specials')
    specials.value = data
  } finally {
    loading.value = false
  }
}

onMounted(fetchSpecials)
</script>

<template>
  <AppShell>
    <div class="space-y-6">
      <h1 class="text-2xl font-bold text-gray-900">Today's Specials</h1>

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
