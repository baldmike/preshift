<script setup lang="ts">
/**
 * PushItemCard -- renders a single "push item" that management wants
 * staff to actively promote or upsell during their shift.
 * Displays the item title, a priority badge (high/medium/low),
 * an optional description and reason, plus an AcknowledgeButton.
 */
import type { PushItem } from '@/types'
import { useAcknowledgments } from '@/composables/useAcknowledgments'
import AcknowledgeButton from '@/components/AcknowledgeButton.vue'
import BadgePill from '@/components/ui/BadgePill.vue'

// Props:
// - item: The PushItem record with title, description, reason, priority, etc.
const props = defineProps<{
  item: PushItem
}>()

// Pulls the isAcknowledged checker for the current user's ack status on this push item
const { isAcknowledged } = useAcknowledgments()

// Maps priority levels to BadgePill color values for visual urgency indication
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
