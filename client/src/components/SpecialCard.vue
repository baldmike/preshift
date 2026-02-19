<script setup lang="ts">
import type { Special } from '@/types'
import { useAcknowledgments } from '@/composables/useAcknowledgments'
import AcknowledgeButton from '@/components/AcknowledgeButton.vue'
import BadgePill from '@/components/ui/BadgePill.vue'

const props = defineProps<{
  special: Special
}>()

const { isAcknowledged } = useAcknowledgments()

function formatDate(dateStr: string | null) {
  if (!dateStr) return ''
  return new Date(dateStr).toLocaleDateString([], {
    month: 'short',
    day: 'numeric',
  })
}
</script>

<template>
  <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
    <div class="flex items-start justify-between">
      <div class="flex-1">
        <div class="flex items-center gap-2">
          <h4 class="font-semibold text-blue-900 text-base">{{ special.title }}</h4>
          <BadgePill v-if="special.type" :label="special.type" color="blue" />
        </div>
        <p v-if="special.description" class="text-sm text-blue-700 mt-1">{{ special.description }}</p>
        <div class="text-xs text-blue-500 mt-2">
          <span>{{ formatDate(special.starts_at) }}</span>
          <span v-if="special.ends_at"> - {{ formatDate(special.ends_at) }}</span>
        </div>
      </div>
      <AcknowledgeButton
        type="special"
        :id="special.id"
        :acknowledged="isAcknowledged('special', special.id)"
      />
    </div>
  </div>
</template>
