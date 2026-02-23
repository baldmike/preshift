<script setup lang="ts">
/**
 * AnnouncementCard.vue
 *
 * Renders a compact, purple-themed card for a single announcement. Displays
 * the title, a color-coded priority badge (urgent/important/normal), optional
 * body text, the poster's name, and an expiration timestamp. Staff see an
 * AcknowledgeButton to mark the announcement as read; managers and admins
 * see an Edit link that navigates to /manage/announcements instead.
 *
 * Props:
 *   - announcement: Announcement
 */
import { computed } from 'vue'
import type { Announcement } from '@/types'
import { useAcknowledgments } from '@/composables/useAcknowledgments'
import { useAuth } from '@/composables/useAuth'
import AcknowledgeButton from '@/components/AcknowledgeButton.vue'
import BadgePill from '@/components/ui/BadgePill.vue'

const props = defineProps<{ announcement: Announcement }>()
const { isAcknowledged } = useAcknowledgments()
const { isAdmin, isManager } = useAuth()
const canEdit = computed(() => isAdmin.value || isManager.value)

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
  <div class="rounded-lg bg-purple-500/5 border border-purple-500/10 p-3 cursor-pointer">
    <div class="flex items-start justify-between gap-2">
      <div class="min-w-0 flex-1">
        <div class="flex items-start gap-1.5 flex-wrap">
          <h4 class="font-semibold text-purple-300 text-sm break-words">{{ announcement.title }}</h4>
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
      <router-link
        v-if="canEdit"
        to="/manage/announcements"
        class="inline-flex items-center gap-1 rounded-md bg-purple-500/10 border border-purple-500/20 px-2 py-1 text-[10px] font-medium text-purple-300 hover:bg-purple-500/20 hover:text-purple-200 transition-colors"
        @click.stop
      >
        Edit
      </router-link>
      <AcknowledgeButton
        v-else
        type="announcement"
        :id="announcement.id"
        :acknowledged="isAcknowledged('announcement', announcement.id)"
        size="sm"
        @click.stop
      />
    </div>
  </div>
</template>
