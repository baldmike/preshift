<script setup lang="ts">
/**
 * SpecialCard.vue
 *
 * Renders a compact, blue-themed card for a single daily special. Displays
 * the title, a type badge, optional description, date range, and remaining
 * quantity. Staff see an AcknowledgeButton for confirmation; managers and
 * admins see an Edit link that navigates to /manage/daily instead.
 *
 * Props:
 *   - special: Special
 */
import { computed } from 'vue'
import type { Special } from '@/types'
import { useAcknowledgments } from '@/composables/useAcknowledgments'
import { useAuth } from '@/composables/useAuth'
import AcknowledgeButton from '@/components/AcknowledgeButton.vue'
import BadgePill from '@/components/ui/BadgePill.vue'

const props = defineProps<{ special: Special }>()
const { isAcknowledged } = useAcknowledgments()
const { isAdmin, isManager } = useAuth()
const canEdit = computed(() => isAdmin.value || isManager.value)

function formatDate(dateStr: string | null) {
  if (!dateStr) return ''
  return new Date(dateStr).toLocaleDateString([], { month: 'short', day: 'numeric' })
}
</script>

<template>
  <div class="rounded-lg bg-blue-500/5 border border-blue-500/10 p-3">
    <div class="flex items-start justify-between gap-2">
      <div class="min-w-0 flex-1">
        <div class="flex items-start gap-1.5 flex-wrap">
          <h4 class="font-semibold text-blue-300 text-sm break-words">{{ special.title }}</h4>
          <BadgePill v-if="special.type" :label="special.type" color="blue" />
        </div>
        <p v-if="special.description" class="text-xs text-blue-400/70 mt-0.5 line-clamp-2">{{ special.description }}</p>
        <div class="flex items-center gap-2 text-[10px] text-blue-500/60 mt-1.5">
          <span>{{ formatDate(special.starts_at) }}</span>
          <span v-if="special.ends_at"> – {{ formatDate(special.ends_at) }}</span>
          <span v-if="special.quantity != null" class="text-amber-400/80 font-medium">{{ special.quantity }} left</span>
        </div>
      </div>
      <router-link
        v-if="canEdit"
        to="/manage/daily"
        class="inline-flex items-center gap-1 rounded-md bg-blue-500/10 border border-blue-500/20 px-2 py-1 text-[10px] font-medium text-blue-300 hover:bg-blue-500/20 hover:text-blue-200 transition-colors"
      >
        Edit
      </router-link>
      <AcknowledgeButton
        v-else
        type="special"
        :id="special.id"
        :acknowledged="isAcknowledged('special', special.id)"
        size="sm"
      />
    </div>
  </div>
</template>
