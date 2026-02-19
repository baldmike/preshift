<script setup lang="ts">
import type { PushItem } from '@/types'
import { useAcknowledgments } from '@/composables/useAcknowledgments'
import AcknowledgeButton from '@/components/AcknowledgeButton.vue'
import BadgePill from '@/components/ui/BadgePill.vue'

const props = defineProps<{
  item: PushItem
}>()

const { isAcknowledged } = useAcknowledgments()

const priorityColor = {
  high: 'red' as const,
  medium: 'yellow' as const,
  low: 'green' as const,
}
</script>

<template>
  <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
    <div class="flex items-start justify-between">
      <div class="flex-1">
        <div class="flex items-center gap-2">
          <h4 class="font-semibold text-amber-900 text-base">{{ item.title }}</h4>
          <BadgePill
            v-if="item.priority"
            :label="item.priority"
            :color="priorityColor[item.priority as keyof typeof priorityColor] || 'gray'"
          />
        </div>
        <p v-if="item.description" class="text-sm text-amber-700 mt-1">{{ item.description }}</p>
        <p v-if="item.reason" class="text-xs text-amber-600 mt-1 italic">{{ item.reason }}</p>
      </div>
      <AcknowledgeButton
        type="push_item"
        :id="item.id"
        :acknowledged="isAcknowledged('push_item', item.id)"
      />
    </div>
  </div>
</template>
