<script setup lang="ts">
/**
 * AppTour.vue
 *
 * Full-screen onboarding walkthrough overlay. Renders a dark backdrop with
 * a spotlight cutout around the currently highlighted element, plus a
 * positioned tooltip with title, description, step counter, and
 * Next/Skip buttons.
 *
 * Teleported to <body> at z-[200] so it sits above all other content.
 * Each step finds its target via `document.querySelector('[data-tour="..."]')`,
 * scrolls it into view, and positions the tooltip above or below based on
 * available viewport space.
 */
import { ref, watch, nextTick, onMounted, onUnmounted } from 'vue'
import { useOnboarding } from '@/composables/useOnboarding'

const { isActive, currentStep, stepIndex, totalSteps, nextStep, skipTour } = useOnboarding()

/** Bounding rect of the current target element */
const spotlightRect = ref({ top: 0, left: 0, width: 0, height: 0 })

/** Tooltip position and arrow direction */
const tooltipStyle = ref<Record<string, string>>({})
const arrowPosition = ref<'top' | 'bottom'>('top')

/** Padding around the spotlight cutout */
const SPOTLIGHT_PADDING = 8

/** Recalculate spotlight and tooltip position for the current step */
function positionStep() {
  if (!currentStep.value) return

  const el = document.querySelector(`[data-tour="${currentStep.value.target}"]`)
  if (!el) return

  el.scrollIntoView({ behavior: 'smooth', block: 'center' })

  /* Small delay to let scroll settle before measuring */
  setTimeout(() => {
    const rect = el.getBoundingClientRect()
    spotlightRect.value = {
      top: rect.top - SPOTLIGHT_PADDING,
      left: rect.left - SPOTLIGHT_PADDING,
      width: rect.width + SPOTLIGHT_PADDING * 2,
      height: rect.height + SPOTLIGHT_PADDING * 2,
    }

    const viewportHeight = window.innerHeight
    const viewportWidth = window.innerWidth

    /* Tooltip dimensions (estimate) */
    const tooltipWidth = Math.min(300, viewportWidth - 32)
    const tooltipHeight = 160

    /* Decide: tooltip below if target is in the top half, above otherwise */
    const targetMidY = rect.top + rect.height / 2
    const showBelow = targetMidY < viewportHeight / 2

    let top: number
    if (showBelow) {
      top = rect.bottom + SPOTLIGHT_PADDING + 12
      arrowPosition.value = 'top'
    } else {
      top = rect.top - SPOTLIGHT_PADDING - tooltipHeight - 12
      arrowPosition.value = 'bottom'
    }

    /* Clamp vertical position to viewport */
    top = Math.max(8, Math.min(top, viewportHeight - tooltipHeight - 8))

    /* Center horizontally on target, clamp to viewport edges */
    let left = rect.left + rect.width / 2 - tooltipWidth / 2
    left = Math.max(16, Math.min(left, viewportWidth - tooltipWidth - 16))

    tooltipStyle.value = {
      position: 'fixed',
      top: `${top}px`,
      left: `${left}px`,
      width: `${tooltipWidth}px`,
      zIndex: '201',
    }
  }, 350)
}

/* Reposition when step changes */
watch(stepIndex, () => {
  if (isActive.value) {
    nextTick(() => positionStep())
  }
})

/* Position on first activation */
watch(isActive, (active) => {
  if (active) {
    nextTick(() => positionStep())
  }
})

/* Reposition on resize */
function onResize() {
  if (isActive.value) positionStep()
}

onMounted(() => window.addEventListener('resize', onResize))
onUnmounted(() => window.removeEventListener('resize', onResize))
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition-opacity duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-150"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div v-if="isActive && currentStep" class="tour-overlay" @click.self="skipTour">
        <!-- Spotlight cutout -->
        <div
          class="tour-spotlight"
          :style="{
            top: spotlightRect.top + 'px',
            left: spotlightRect.left + 'px',
            width: spotlightRect.width + 'px',
            height: spotlightRect.height + 'px',
          }"
        />

        <!-- Tooltip -->
        <div
          class="tour-tooltip"
          :class="arrowPosition === 'top' ? 'tour-tooltip--arrow-top' : 'tour-tooltip--arrow-bottom'"
          :style="tooltipStyle"
        >
          <!-- Step counter -->
          <div class="flex items-center justify-between mb-2">
            <span class="text-[10px] font-semibold text-amber-400 uppercase tracking-wider">
              Step {{ stepIndex + 1 }} of {{ totalSteps }}
            </span>
            <button
              @click="skipTour"
              class="text-[10px] text-gray-500 hover:text-gray-300 transition-colors"
            >
              Skip tour
            </button>
          </div>

          <!-- Content -->
          <h3 class="text-sm font-bold text-white mb-1">{{ currentStep.title }}</h3>
          <p class="text-xs text-gray-400 leading-relaxed mb-4">{{ currentStep.description }}</p>

          <!-- Actions -->
          <div class="flex items-center justify-end gap-2">
            <button
              @click="nextStep"
              class="px-4 py-1.5 text-xs font-semibold rounded-md bg-amber-500 text-gray-950 hover:bg-amber-400 transition-colors"
            >
              {{ stepIndex === totalSteps - 1 ? 'Done' : 'Next' }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
/* Full-screen overlay container */
.tour-overlay {
  position: fixed;
  inset: 0;
  z-index: 200;
}

/* Spotlight: transparent hole with huge box-shadow as the dark overlay */
.tour-spotlight {
  position: fixed;
  z-index: 200;
  border-radius: 8px;
  box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.75);
  transition: all 0.3s ease;
  pointer-events: none;
}

/* Tooltip card */
.tour-tooltip {
  position: fixed;
  background: #1f2937;
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 0.75rem;
  padding: 1rem;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
}

/* Arrow pointing up (tooltip is below target) */
.tour-tooltip--arrow-top::before {
  content: '';
  position: absolute;
  top: -6px;
  left: 50%;
  transform: translateX(-50%);
  width: 0;
  height: 0;
  border-left: 6px solid transparent;
  border-right: 6px solid transparent;
  border-bottom: 6px solid #1f2937;
}

/* Arrow pointing down (tooltip is above target) */
.tour-tooltip--arrow-bottom::after {
  content: '';
  position: absolute;
  bottom: -6px;
  left: 50%;
  transform: translateX(-50%);
  width: 0;
  height: 0;
  border-left: 6px solid transparent;
  border-right: 6px solid transparent;
  border-top: 6px solid #1f2937;
}
</style>
