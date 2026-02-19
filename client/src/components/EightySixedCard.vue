<script setup lang="ts">
/**
 * EightySixedCard -- displays a single 86'd (unavailable) menu item.
 * Shown on the staff Dashboard and the 86'd Board views.
 * Each card includes the item name, an optional reason it was 86'd,
 * who 86'd it and when, plus an AcknowledgeButton so staff can
 * confirm they have seen the update.
 */
import type { EightySixed } from '@/types'
import { useAcknowledgments } from '@/composables/useAcknowledgments'
import AcknowledgeButton from '@/components/AcknowledgeButton.vue'

// Props:
// - item: The EightySixed record containing item_name, reason, user, created_at, etc.
const props = defineProps<{
  item: EightySixed
}>()

// Pulls the isAcknowledged checker to determine if the current user has already ack'd this item
const { isAcknowledged } = useAcknowledgments()

// Formats an ISO date string into a short, human-readable timestamp (e.g. "Feb 19, 3:45 PM")
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
