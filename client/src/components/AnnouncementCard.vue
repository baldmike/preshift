<script setup lang="ts">
/**
 * AnnouncementCard.vue
 *
 * Renders a compact, purple-themed card for a single announcement. Displays
 * the title, a color-coded priority badge (urgent/important/normal), optional
 * body text, the poster's name, and an expiration timestamp. Includes an
 * AcknowledgeButton so staff can mark the announcement as read.
 *
 * Props:
 *   - announcement: Announcement
 */
import type { Announcement } from '@/types'
import { useAcknowledgments } from '@/composables/useAcknowledgments'
import AcknowledgeButton from '@/components/AcknowledgeButton.vue'
import BadgePill from '@/components/ui/BadgePill.vue'

const props = defineProps<{ announcement: Announcement }>()
const { isAcknowledged } = useAcknowledgments()

const priorityColor = {
  urgent: 'red' as const,
  important: 'yellow' as const,
  normal: 'blue' as const,
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
  <div class="rounded-lg bg-purple-500/5 border border-purple-500/10 p-3">
    <div class="flex items-start justify-between gap-2">
      <div class="min-w-0 flex-1">
        <div class="flex items-center gap-1.5">
          <h4 class="font-semibold text-purple-300 text-sm truncate">{{ announcement.title }}</h4>
          <BadgePill
            v-if="announcement.priority"
            :label="announcement.priority"
            :color="priorityColor[announcement.priority as keyof typeof priorityColor] || 'gray'"
          />
        </div>
        <p v-if="announcement.body" class="text-xs text-purple-400/70 mt-0.5 line-clamp-2">{{ announcement.body }}</p>
        <div class="flex items-center gap-2 mt-1.5 text-[10px] text-purple-500/60">
          <span v-if="announcement.poster">{{ announcement.poster.name }}</span>
          <span v-if="announcement.expires_at">Exp {{ formatDateTime(announcement.expires_at) }}</span>
        </div>
      </div>
      <AcknowledgeButton
        type="announcement"
        :id="announcement.id"
        :acknowledged="isAcknowledged('announcement', announcement.id)"
        size="sm"
      />
    </div>
  </div>
</template>
