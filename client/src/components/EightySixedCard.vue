<script setup lang="ts">
import type { EightySixed } from '@/types'
import { useAcknowledgments } from '@/composables/useAcknowledgments'
import AcknowledgeButton from '@/components/AcknowledgeButton.vue'

const props = defineProps<{
  item: EightySixed
}>()

const { isAcknowledged } = useAcknowledgments()

function formatTime(dateStr: string) {
  return new Date(dateStr).toLocaleString([], {
    month: 'short',
    day: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  })
}
</script>

<template>
  <div class="bg-red-50 border border-red-200 rounded-lg p-4">
    <div class="flex items-start justify-between">
      <div>
        <h4 class="font-semibold text-red-900 text-base">{{ item.item_name }}</h4>
        <p v-if="item.reason" class="text-sm text-red-700 mt-1">{{ item.reason }}</p>
        <div class="flex items-center gap-2 mt-2 text-xs text-red-500">
          <span v-if="item.user">86'd by {{ item.user.name }}</span>
          <span>{{ formatTime(item.created_at) }}</span>
        </div>
      </div>
      <AcknowledgeButton
        type="eighty_sixed"
        :id="item.id"
        :acknowledged="isAcknowledged('eighty_sixed', item.id)"
      />
    </div>
  </div>
</template>
