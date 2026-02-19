<script setup lang="ts">
import { ref, onMounted } from 'vue'
import api from '@/composables/useApi'
import AppShell from '@/components/layout/AppShell.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import type { Location } from '@/types'

const locations = ref<Location[]>([])
const loading = ref(false)
const submitting = ref(false)

const showForm = ref(false)
const editingId = ref<number | null>(null)
const form = ref({
  name: '',
  address: '',
  timezone: 'America/New_York',
})

function resetForm() {
  form.value = { name: '', address: '', timezone: 'America/New_York' }
  editingId.value = null
  showForm.value = false
}

function editLocation(loc: Location) {
  editingId.value = loc.id
  form.value = {
    name: loc.name,
    address: loc.address || '',
    timezone: loc.timezone || 'America/New_York',
  }
  showForm.value = true
}

async function fetchLocations() {
  loading.value = true
  try {
    const { data } = await api.get<Location[]>('/api/locations')
    locations.value = data
  } finally {
    loading.value = false
  }
}

async function saveLocation() {
  if (!form.value.name.trim()) return
  submitting.value = true
  try {
    if (editingId.value) {
      const { data } = await api.patch(`/api/locations/${editingId.value}`, form.value)
      const idx = locations.value.findIndex((l) => l.id === editingId.value)
      if (idx !== -1) locations.value[idx] = data
      toast('Location updated', 'success')
    } else {
      const { data } = await api.post('/api/locations', form.value)
      locations.value.push(data)
      toast('Location created', 'success')
    }
    resetForm()
  } catch {
    toast('Failed to save location', 'error')
  } finally {
    submitting.value = false
  }
}

function toast(message: string, type: string) {
  window.dispatchEvent(new CustomEvent('toast', { detail: { message, type } }))
}

onMounted(fetchLocations)
</script>

<template>
  <AppShell>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Manage Locations</h1>
        <div class="flex gap-2">
          <router-link to="/manage" class="text-sm text-indigo-600 hover:text-indigo-800">Back</router-link>
          <BaseButton v-if="!showForm" size="sm" @click="showForm = true">Add Location</BaseButton>
        </div>
      </div>

      <!-- Form -->
      <div v-if="showForm" class="bg-white rounded-lg shadow p-4">
        <h3 class="font-semibold text-gray-900 mb-3">{{ editingId ? 'Edit' : 'Create' }} Location</h3>
        <form @submit.prevent="saveLocation" class="space-y-3">
          <BaseInput v-model="form.name" label="Name" placeholder="Location name" />
          <BaseInput v-model="form.address" label="Address" placeholder="Full address" />
          <BaseInput v-model="form.timezone" label="Timezone" placeholder="America/New_York" />
          <div class="flex gap-2">
            <BaseButton type="submit" :loading="submitting">{{ editingId ? 'Update' : 'Create' }}</BaseButton>
            <BaseButton variant="secondary" @click="resetForm">Cancel</BaseButton>
          </div>
        </form>
      </div>

      <!-- Table -->
      <div v-if="loading" class="flex justify-center py-8">
        <svg class="animate-spin h-8 w-8 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
        </svg>
      </div>

      <div v-else class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Address</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Timezone</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="loc in locations" :key="loc.id">
              <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ loc.name }}</td>
              <td class="px-4 py-3 text-sm text-gray-500">{{ loc.address || '-' }}</td>
              <td class="px-4 py-3 text-sm text-gray-500">{{ loc.timezone || '-' }}</td>
              <td class="px-4 py-3 text-right">
                <BaseButton size="sm" variant="secondary" @click="editLocation(loc)">Edit</BaseButton>
              </td>
            </tr>
            <tr v-if="!locations.length">
              <td colspan="4" class="px-4 py-8 text-center text-gray-500">No locations</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppShell>
</template>
