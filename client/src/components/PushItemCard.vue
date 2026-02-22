<script setup lang="ts">
/**
 * PushItemCard.vue
 *
 * Renders a compact, amber-themed card for a single push item. Shows the
 * item title, a color-coded priority badge (high/medium/low), an optional
 * description and reason, and an AcknowledgeButton for staff confirmation.
 *
 * Props:
 *   - item: PushItem
 */
import type { PushItem } from '@/types'
import { useAcknowledgments } from '@/composables/useAcknowledgments'
import AcknowledgeButton from '@/components/AcknowledgeButton.vue'
import BadgePill from '@/components/ui/BadgePill.vue'

const props = defineProps<{ item: PushItem }>()
const { isAcknowledged } = useAcknowledgments()

const priorityColor = {
  high: 'red' as const,
  medium: 'yellow' as const,
  low: 'green' as const,
}
</script>

<template>
  <div class="rounded-lg bg-amber-500/5 border border-amber-500/10 p-3">
    <div class="flex items-start justify-between gap-2">
      <div class="min-w-0 flex-1">
        <div class="flex items-start gap-1.5 flex-wrap">
          <h4 class="font-semibold text-amber-300 text-sm break-words">{{ item.title }}</h4>
          <BadgePill
            v-if="item.priority"
            :label="item.priority"
            :color="priorityColor[item.priority as keyof typeof priorityColor] || 'gray'"
          />
        </div>
        <p v-if="item.description" class="text-xs text-amber-400/70 mt-0.5 line-clamp-2">{{ item.description }}</p>
        <p v-if="item.reason" class="text-[10px] text-amber-500/50 mt-1 italic">{{ item.reason }}</p>
      </div>
      <AcknowledgeButton
        type="push_item"
        :id="item.id"
        :acknowledged="isAcknowledged('push_item', item.id)"
        size="sm"
      />
    </div>
  </div>
</template>
