<script setup lang="ts">
/**
 * SpecialCard -- renders a single daily special (food or drink promotion).
 * Displays the special's title, optional type badge (e.g. "food", "drink"),
 * description, date range, and an AcknowledgeButton for staff read-receipts.
 * Used on the staff Dashboard and the Specials view.
 */
import type { Special } from '@/types'
import { useAcknowledgments } from '@/composables/useAcknowledgments'
import AcknowledgeButton from '@/components/AcknowledgeButton.vue'
import BadgePill from '@/components/ui/BadgePill.vue'

// Props:
// - special: The Special record with title, description, type, starts_at, ends_at, etc.
const props = defineProps<{
  special: Special
}>()

// Pulls the isAcknowledged checker to see if the current user has ack'd this special
const { isAcknowledged } = useAcknowledgments()

// Formats an ISO date string to a short date display (e.g. "Feb 19"), returns empty string for null
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
