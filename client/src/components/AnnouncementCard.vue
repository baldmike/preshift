<script setup lang="ts">
import type { Announcement } from '@/types'
import { useAcknowledgments } from '@/composables/useAcknowledgments'
import AcknowledgeButton from '@/components/AcknowledgeButton.vue'
import BadgePill from '@/components/ui/BadgePill.vue'

const props = defineProps<{
  announcement: Announcement
}>()

const { isAcknowledged } = useAcknowledgments()

const priorityColor = {
  urgent: 'red' as const,
  high: 'yellow' as const,
  normal: 'blue' as const,
  low: 'gray' as const,
}

function formatDateTime(dateStr: string | null) {
  if (!dateStr) return ''
  return new Date(dateStr).toLocaleString([], {
    month: 'short',
    day: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  })
}
</script>

<template>
  <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
    <div class="flex items-start justify-between">
      <div class="flex-1">
        <div class="flex items-center gap-2">
          <h4 class="font-semibold text-purple-900 text-base">{{ announcement.title }}</h4>
          <BadgePill
            v-if="announcement.priority"
            :label="announcement.priority"
            :color="priorityColor[announcement.priority as keyof typeof priorityColor] || 'gray'"
          />
        </div>
        <p v-if="announcement.body" class="text-sm text-purple-700 mt-1">{{ announcement.body }}</p>
        <div class="flex items-center gap-3 mt-2 text-xs text-purple-500">
          <span v-if="announcement.poster">Posted by {{ announcement.poster.name }}</span>
          <span v-if="announcement.expires_at">Expires {{ formatDateTime(announcement.expires_at) }}</span>
        </div>
      </div>
      <AcknowledgeButton
        type="announcement"
        :id="announcement.id"
        :acknowledged="isAcknowledged('announcement', announcement.id)"
      />
    </div>
  </div>
</template>
