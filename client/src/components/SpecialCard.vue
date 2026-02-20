<script setup lang="ts">
import type { Special } from '@/types'
import { useAcknowledgments } from '@/composables/useAcknowledgments'
import AcknowledgeButton from '@/components/AcknowledgeButton.vue'
import BadgePill from '@/components/ui/BadgePill.vue'

const props = defineProps<{ special: Special }>()
const { isAcknowledged } = useAcknowledgments()

function formatDate(dateStr: string | null) {
  if (!dateStr) return ''
  return new Date(dateStr).toLocaleDateString([], { month: 'short', day: 'numeric' })
}
</script>

<template>
  <div class="rounded-lg bg-blue-500/5 border border-blue-500/10 p-3">
    <div class="flex items-start justify-between gap-2">
      <div class="min-w-0 flex-1">
        <div class="flex items-center gap-1.5">
          <h4 class="font-semibold text-blue-300 text-sm truncate">{{ special.title }}</h4>
          <BadgePill v-if="special.type" :label="special.type" color="blue" />
        </div>
        <p v-if="special.description" class="text-xs text-blue-400/70 mt-0.5 line-clamp-2">{{ special.description }}</p>
        <div class="text-[10px] text-blue-500/60 mt-1.5">
          <span>{{ formatDate(special.starts_at) }}</span>
          <span v-if="special.ends_at"> – {{ formatDate(special.ends_at) }}</span>
        </div>
      </div>
      <AcknowledgeButton
        type="special"
        :id="special.id"
        :acknowledged="isAcknowledged('special', special.id)"
        size="sm"
      />
    </div>
  </div>
</template>
