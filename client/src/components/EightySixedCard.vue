<script setup lang="ts">
/**
 * EightySixedCard.vue
 *
 * Renders a compact, red-themed card for a single 86'd item. Displays
 * the item name, optional reason, the user who 86'd it, and a timestamp.
 * Includes an AcknowledgeButton so staff can mark the item as seen.
 *
 * Props:
 *   - item: EightySixed
 */
import { computed } from 'vue'
import type { EightySixed } from '@/types'
import { useAcknowledgments } from '@/composables/useAcknowledgments'
import { useAuth } from '@/composables/useAuth'
import AcknowledgeButton from '@/components/AcknowledgeButton.vue'

const props = defineProps<{ item: EightySixed }>()
const { isAcknowledged } = useAcknowledgments()
const { isAdmin, isManager } = useAuth()
const canEdit = computed(() => isAdmin.value || isManager.value)

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
  <div class="rounded-lg bg-red-500/5 border border-red-500/10 p-3">
    <div class="flex items-start justify-between gap-2">
      <div class="min-w-0 flex-1">
        <h4 class="font-semibold text-red-300 text-sm break-words">{{ item.item_name }}</h4>
        <p v-if="item.reason" class="text-xs text-red-400/70 mt-0.5 line-clamp-2">{{ item.reason }}</p>
        <div class="flex items-center gap-2 mt-1.5 text-[10px] text-red-500/60">
          <span v-if="item.user">{{ item.user.name }}</span>
          <span>{{ formatTime(item.created_at) }}</span>
        </div>
      </div>
      <router-link
        v-if="canEdit"
        to="/manage/86"
        class="inline-flex items-center gap-1 rounded-md bg-red-500/10 border border-red-500/20 px-2 py-1 text-[10px] font-medium text-red-300 hover:bg-red-500/20 hover:text-red-200 transition-colors"
      >
        Edit
      </router-link>
      <AcknowledgeButton
        v-else
        type="eighty_sixed"
        :id="item.id"
        :acknowledged="isAcknowledged('eighty_sixed', item.id)"
        size="sm"
      />
    </div>
  </div>
</template>
