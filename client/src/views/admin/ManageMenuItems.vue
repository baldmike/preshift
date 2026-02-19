<script setup lang="ts">
import { ref, onMounted } from 'vue'
import api from '@/composables/useApi'
import AppShell from '@/components/layout/AppShell.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BadgePill from '@/components/ui/BadgePill.vue'
import type { MenuItem, Category } from '@/types'

const menuItems = ref<MenuItem[]>([])
const categories = ref<Category[]>([])
const loading = ref(false)
const submitting = ref(false)

const showForm = ref(false)
const editingId = ref<number | null>(null)
const form = ref({
  name: '',
  description: '',
  price: '',
  type: '',
  category_id: '' as string | number,
  is_active: true,
})

function resetForm() {
  form.value = { name: '', description: '', price: '', type: '', category_id: '', is_active: true }
  editingId.value = null
  showForm.value = false
}

function editItem(item: MenuItem) {
  editingId.value = item.id
  form.value = {
    name: item.name,
    description: item.description || '',
    price: item.price,
    type: item.type || '',
    category_id: item.category_id || '',
    is_active: item.is_active,
  }
  showForm.value = true
}

async function fetchData() {
  loading.value = true
  try {
    const [itemsRes, catsRes] = await Promise.all([
      api.get<MenuItem[]>('/api/menu-items'),
      api.get<Category[]>('/api/categories'),
    ])
    menuItems.value = itemsRes.data
    categories.value = catsRes.data
  } finally {
    loading.value = false
  }
}

async function saveItem() {
  if (!form.value.name.trim()) return
  submitting.value = true
  try {
    const payload = {
      ...form.value,
      category_id: form.value.category_id || null,
      price: parseFloat(form.value.price) || 0,
    }
    if (editingId.value) {
      const { data } = await api.patch(`/api/menu-items/${editingId.value}`, payload)
      const idx = menuItems.value.findIndex((i) => i.id === editingId.value)
      if (idx !== -1) menuItems.value[idx] = data
      toast('Menu item updated', 'success')
    } else {
      const { data } = await api.post('/api/menu-items', payload)
      menuItems.value.push(data)
      toast('Menu item created', 'success')
    }
    resetForm()
  } catch {
    toast('Failed to save menu item', 'error')
  } finally {
    submitting.value = false
  }
}

async function deleteItem(id: number) {
  if (!confirm('Delete this menu item?')) return
  try {
    await api.delete(`/api/menu-items/${id}`)
    menuItems.value = menuItems.value.filter((i) => i.id !== id)
    toast('Menu item deleted', 'success')
  } catch {
    toast('Failed to delete menu item', 'error')
  }
}

function toast(message: string, type: string) {
  window.dispatchEvent(new CustomEvent('toast', { detail: { message, type } }))
}

function getCategoryName(id: number | null): string {
  if (!id) return '-'
  return categories.value.find((c) => c.id === id)?.name || '-'
}

onMounted(fetchData)
</script>

<template>
  <AppShell>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Manage Menu Items</h1>
        <div class="flex gap-2">
          <router-link to="/manage" class="text-sm text-indigo-600 hover:text-indigo-800">Back</router-link>
          <BaseButton v-if="!showForm" size="sm" @click="showForm = true">Add Item</BaseButton>
        </div>
      </div>

      <!-- Form -->
      <div v-if="showForm" class="bg-white rounded-lg shadow p-4">
        <h3 class="font-semibold text-gray-900 mb-3">{{ editingId ? 'Edit' : 'Create' }} Menu Item</h3>
        <form @submit.prevent="saveItem" class="space-y-3">
          <div class="grid grid-cols-2 gap-3">
            <BaseInput v-model="form.name" label="Name" placeholder="Item name" />
            <BaseInput v-model="form.price" label="Price" type="number" placeholder="0.00" />
          </div>
          <BaseInput v-model="form.description" label="Description" placeholder="Item description" />
          <div class="grid grid-cols-2 gap-3">
            <BaseInput v-model="form.type" label="Type" placeholder="e.g. food, drink" />
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
              <select
                v-model="form.category_id"
                class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
              >
                <option value="">No Category</option>
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
              </select>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <input type="checkbox" id="is_active" v-model="form.is_active" class="rounded" />
            <label for="is_active" class="text-sm text-gray-700">Active</label>
          </div>
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
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="item in menuItems" :key="item.id">
              <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ item.name }}</td>
              <td class="px-4 py-3 text-sm text-gray-500">{{ getCategoryName(item.category_id) }}</td>
              <td class="px-4 py-3 text-sm text-gray-500">${{ item.price }}</td>
              <td class="px-4 py-3">
                <BadgePill :label="item.is_active ? 'Active' : 'Inactive'" :color="item.is_active ? 'green' : 'gray'" />
              </td>
              <td class="px-4 py-3 text-right space-x-2">
                <BaseButton size="sm" variant="secondary" @click="editItem(item)">Edit</BaseButton>
                <BaseButton size="sm" variant="danger" @click="deleteItem(item.id)">Delete</BaseButton>
              </td>
            </tr>
            <tr v-if="!menuItems.length">
              <td colspan="5" class="px-4 py-8 text-center text-gray-500">No menu items</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppShell>
</template>
