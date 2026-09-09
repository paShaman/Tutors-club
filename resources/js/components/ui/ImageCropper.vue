<script setup lang="ts">
import { onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { ZoomIn, ZoomOut, RotateCcw, X } from 'lucide-vue-next'
import Button from '@/components/ui/Button.vue'

const props = defineProps<{
  src: string | null
}>()

const emit = defineEmits<{
  cancel: []
  apply: [blob: Blob]
}>()

const MAX_ZOOM = 8
const EXPORT_SIZE = 1024

const view = ref(280)

const containerRef = ref<HTMLDivElement | null>(null)
const loadedImage = ref<HTMLImageElement | null>(null)
const naturalW = ref(0)
const naturalH = ref(0)
const zoom = ref(1)
const pan = reactive({ x: 0, y: 0 })

const dragging = ref(false)
const activePointer = ref<number | null>(null)
const dragStart = { x: 0, y: 0, panX: 0, panY: 0 }

const scale0 = () => (naturalW.value && naturalH.value ? view.value / Math.min(naturalW.value, naturalH.value) : 0)
const dispW = () => naturalW.value * scale0() * zoom.value
const dispH = () => naturalH.value * scale0() * zoom.value
const overW = () => Math.max(0, dispW() - view.value)
const overH = () => Math.max(0, dispH() - view.value)

function clampPan() {
  pan.x = Math.min(Math.max(pan.x, -overW() / 2), overW() / 2)
  pan.y = Math.min(Math.max(pan.y, -overH() / 2), overH() / 2)
}

function applyZoom(next: number) {
  const z = Math.min(Math.max(next, 1), MAX_ZOOM)
  const ratio = z / zoom.value
  if (ratio === 1) return
  // Keep the natural point currently under the viewport center in place
  pan.x *= ratio
  pan.y *= ratio
  zoom.value = z
  clampPan()
}

function resetView() {
  zoom.value = 1
  pan.x = 0
  pan.y = 0
}

function loadImage() {
  if (!props.src) return
  const img = new Image()
  img.onload = () => {
    naturalW.value = img.naturalWidth
    naturalH.value = img.naturalHeight
    loadedImage.value = img
    resetView()
  }
  img.src = props.src
}

watch(() => props.src, () => loadImage(), { immediate: true })

onMounted(() => {
  view.value = Math.max(200, Math.min(280, window.innerWidth - 72))
  containerRef.value?.addEventListener('wheel', onWheel, { passive: false })
})

onBeforeUnmount(() => {
  containerRef.value?.removeEventListener('wheel', onWheel)
})

function onWheel(e: WheelEvent) {
  e.preventDefault()
  applyZoom(zoom.value * Math.exp(-e.deltaY * 0.002))
}

function onPointerDown(e: PointerEvent) {
  if (activePointer.value !== null) return
  activePointer.value = e.pointerId
  dragging.value = true
  dragStart.x = e.clientX
  dragStart.y = e.clientY
  dragStart.panX = pan.x
  dragStart.panY = pan.y
  ;(e.currentTarget as HTMLElement).setPointerCapture(e.pointerId)
}

function onPointerMove(e: PointerEvent) {
  if (!dragging.value || e.pointerId !== activePointer.value) return
  pan.x = dragStart.panX + (e.clientX - dragStart.x)
  pan.y = dragStart.panY + (e.clientY - dragStart.y)
  clampPan()
}

function onPointerUp(e: PointerEvent) {
  if (e.pointerId !== activePointer.value) return
  activePointer.value = null
  dragging.value = false
  try {
    ;(e.currentTarget as HTMLElement).releasePointerCapture(e.pointerId)
  } catch {
    // capture may already be released on pointercancel
  }
}

function visibleSource() {
  const s = scale0() * zoom.value
  const L = (view.value - dispW()) / 2 + pan.x
  const T = (view.value - dispH()) / 2 + pan.y
  let side = view.value / s
  let sx = -L / s
  let sy = -T / s

  const maxSide = Math.min(naturalW.value, naturalH.value)
  side = Math.min(side, maxSide)
  sx = Math.min(Math.max(sx, 0), Math.max(naturalW.value - side, 0))
  sy = Math.min(Math.max(sy, 0), Math.max(naturalH.value - side, 0))

  return { sx, sy, side }
}

function apply() {
  const img = loadedImage.value
  if (!img || !naturalW.value || !naturalH.value) return

  const { sx, sy, side } = visibleSource()

  const canvas = document.createElement('canvas')
  canvas.width = EXPORT_SIZE
  canvas.height = EXPORT_SIZE
  const ctx = canvas.getContext('2d')
  if (!ctx) return

  // White background: JPEG has no alpha channel
  ctx.fillStyle = '#ffffff'
  ctx.fillRect(0, 0, EXPORT_SIZE, EXPORT_SIZE)
  ctx.imageSmoothingEnabled = true
  ctx.imageSmoothingQuality = 'high'
  ctx.drawImage(img, sx, sy, side, side, 0, 0, EXPORT_SIZE, EXPORT_SIZE)

  canvas.toBlob((blob) => {
    if (blob) emit('apply', blob)
  }, 'image/jpeg', 0.9)
}

const zoomPercent = () => Math.round(zoom.value * 100)
</script>

<template>
  <Teleport to="body">
    <Transition name="overlay">
      <div class="fixed inset-0 z-80 bg-black/40 backdrop-blur-sm" @click="emit('cancel')" />
    </Transition>
    <Transition name="modal">
      <div class="fixed inset-0 z-90 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4" @click.self="emit('cancel')">
          <div class="w-full max-w-sm rounded-2xl border border-border bg-white p-5 shadow-xl">
            <div class="flex items-center justify-between mb-4">
              <h2 class="text-xl font-semibold text-foreground">Область фото</h2>
              <button
                type="button"
                class="rounded-lg p-1.5 text-muted-foreground hover:bg-accent hover:text-foreground transition-colors cursor-pointer"
                @click="emit('cancel')"
              >
                <X class="h-5 w-5" />
              </button>
            </div>

            <p class="text-sm text-muted-foreground mb-4">
              Перетащите фото, чтобы выбрать область. Лицо можно выровнять по кругу и изменить масштаб.
            </p>

            <!-- Circular viewport -->
            <div
              ref="containerRef"
              class="relative mx-auto cursor-grab touch-none select-none overflow-hidden rounded-full shadow-inner ring-4 ring-white/60 active:cursor-grabbing"
              :style="{ width: `${view}px`, height: `${view}px` }"
              @pointerdown="onPointerDown"
              @pointermove="onPointerMove"
              @pointerup="onPointerUp"
              @pointercancel="onPointerUp"
            >
              <img
                v-if="loadedImage"
                :src="src ?? ''"
                draggable="false"
                alt=""
                class="absolute max-w-none select-none"
                :style="{
                  width: `${dispW()}px`,
                  height: `${dispH()}px`,
                  left: `${(view - dispW()) / 2 + pan.x}px`,
                  top: `${(view - dispH()) / 2 + pan.y}px`,
                }"
              />
            </div>

            <!-- Zoom controls -->
            <div class="mt-5 flex items-center gap-3">
              <button
                type="button"
                class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-input bg-background text-muted-foreground transition-colors hover:bg-accent hover:text-foreground cursor-pointer disabled:opacity-40"
                :disabled="zoom <= 1"
                @click="applyZoom(zoom - 0.5)"
              >
                <ZoomOut class="h-4 w-4" />
              </button>
              <input
                type="range"
                min="1"
                :max="MAX_ZOOM"
                step="0.01"
                :value="zoom"
                style="accent-color: hsl(252 87% 67%)"
                class="h-2 w-full cursor-pointer"
                @input="applyZoom(Number(($event.target as HTMLInputElement).value))"
              />
              <button
                type="button"
                class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-input bg-background text-muted-foreground transition-colors hover:bg-accent hover:text-foreground cursor-pointer disabled:opacity-40"
                :disabled="zoom >= MAX_ZOOM"
                @click="applyZoom(zoom + 0.5)"
              >
                <ZoomIn class="h-4 w-4" />
              </button>
              <span class="w-11 text-right text-xs tabular-nums text-muted-foreground">{{ zoomPercent() }}%</span>
              <button
                type="button"
                class="ml-auto inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-input bg-background text-muted-foreground transition-colors hover:bg-accent hover:text-foreground cursor-pointer"
                title="Сбросить"
                @click="resetView"
              >
                <RotateCcw class="h-4 w-4" />
              </button>
            </div>

            <div class="mt-5 flex items-center gap-3">
              <Button type="button" variant="outline" class="flex-1" @click="emit('cancel')">
                Отмена
              </Button>
              <Button type="button" class="flex-1" @click="apply">
                Применить
              </Button>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
