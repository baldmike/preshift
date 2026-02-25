<script setup lang="ts">
/**
 * UserAvatar.vue
 *
 * Reusable avatar component that displays a user's profile photo with an
 * initials-based fallback. Gracefully handles broken image URLs by hiding
 * the <img> on error and showing initials instead.
 *
 * Props:
 *   - user  : { name: string; profile_photo_url?: string | null } — the user to display
 *   - size  : 'xs' | 'sm' | 'md' | 'lg' — avatar size preset
 *   - bg    : string — optional Tailwind background class override for the initials circle
 *
 * Slots:
 *   - default : overlay content (e.g. unread indicator dots)
 */
import { ref, computed, watch } from 'vue'

const props = withDefaults(defineProps<{
  user: { name: string; profile_photo_url?: string | null } | null
  size?: 'xs' | 'sm' | 'md' | 'lg'
  bg?: string
}>(), {
  size: 'sm',
  bg: '',
})

/** Size class mapping matching existing codebase avatar dimensions */
const sizeClasses: Record<string, string> = {
  xs: 'w-6 h-6 text-[10px]',
  sm: 'w-8 h-8 text-xs',
  md: 'w-9 h-9 text-sm',
  lg: 'w-12 h-12 text-base',
}

const sizeClass = computed(() => sizeClasses[props.size] || sizeClasses.sm)

/** Whether the <img> loaded successfully */
const imgLoaded = ref(true)

/** Reset imgLoaded when the photo URL changes */
watch(() => props.user?.profile_photo_url, () => {
  imgLoaded.value = true
})

/** Whether to show the photo (has URL and hasn't errored) */
const showPhoto = computed(() =>
  !!props.user?.profile_photo_url && imgLoaded.value
)

/** Compute initials from the user's name */
const initials = computed(() => {
  if (!props.user?.name) return '?'
  return props.user.name
    .split(' ')
    .map(w => w[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
})

/** Handle image load error — fall back to initials */
function onImgError() {
  imgLoaded.value = false
}
</script>

<template>
  <div
    class="rounded-full shrink-0 relative overflow-hidden flex items-center justify-center font-bold"
    :class="[sizeClass, showPhoto ? '' : (bg || 'bg-gray-700 text-gray-300')]"
  >
    <img
      v-if="showPhoto"
      :src="user!.profile_photo_url!"
      :alt="user!.name"
      class="w-full h-full object-cover"
      @error="onImgError"
    />
    <span v-else>{{ initials }}</span>
    <slot />
  </div>
</template>
