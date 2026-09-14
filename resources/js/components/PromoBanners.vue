<script setup lang="ts">
import { computed, ref } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { Megaphone, X } from 'lucide-vue-next'
import Button from '@/components/ui/Button.vue'
import type { PromoBannerInfo, SharedProps } from '@/types'
import { useI18n } from '@/lib/i18n'

const props = defineProps<{
  banners: PromoBannerInfo[]
}>()

const { t, tp } = useI18n()
const page = usePage<SharedProps>()

const DISMISS_PREFIX = 'promo-dismissed:'

// Перерисовываем список после закрытия баннера (localStorage не реактивен).
const revision = ref(0)

function storageKey(id: number): string {
  const userId = page.props.auth?.user?.id ?? 'guest'
  return `${DISMISS_PREFIX}${userId}:${id}`
}

// Скрытие хранится в localStorage: токен (updated_at) + время, до которого баннер не показывать.
function isDismissed(banner: PromoBannerInfo): boolean {
  if (typeof window === 'undefined') return false

  try {
    const raw = window.localStorage.getItem(storageKey(banner.id))
    if (!raw) return false

    const state = JSON.parse(raw) as { token?: string; until?: number }
    if (state.token !== banner.updated_at) return false

    return typeof state.until === 'number' && Date.now() < state.until
  } catch {
    return false
  }
}

const visibleBanners = computed<PromoBannerInfo[]>(() => {
  void revision.value
  return props.banners.filter((banner) => !isDismissed(banner))
})

function dismiss(banner: PromoBannerInfo): void {
  const until = Date.now() + banner.dismiss_days * 24 * 60 * 60 * 1000

  try {
    window.localStorage.setItem(storageKey(banner.id), JSON.stringify({
      token: banner.updated_at,
      until,
    }))
  } catch {
    // localStorage может быть недоступен — просто скрываем баннер на текущей странице
  }

  revision.value++
}

function dismissHint(banner: PromoBannerInfo): string {
  return tp('ui.promo.dismiss_hint', banner.dismiss_days)
}
</script>

<template>
  <TransitionGroup v-if="visibleBanners.length" name="promo" tag="div" class="mb-4 space-y-3">
    <div
      v-for="banner in visibleBanners"
      :key="banner.id"
      class="flex items-start gap-3 rounded-xl border border-primary/20 bg-gradient-to-r from-primary/10 via-purple-500/5 to-transparent px-4 py-3 shadow-sm"
    >
      <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-primary/15 text-primary">
        <Megaphone class="h-4.5 w-4.5" />
      </div>

      <div class="min-w-0 flex-1">
        <p v-if="banner.title" class="text-sm font-semibold text-foreground">{{ banner.title }}</p>
        <p class="text-sm text-foreground/80">{{ banner.message }}</p>
      </div>

      <a
        v-if="banner.button_url && banner.button_text"
        :href="banner.button_url"
        target="_blank"
        rel="noopener"
        class="shrink-0"
      >
        <Button size="sm">{{ banner.button_text }}</Button>
      </a>

      <button
        type="button"
        class="-mr-1 shrink-0 rounded-lg p-1 text-muted-foreground opacity-70 transition-opacity hover:opacity-100 cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40"
        :title="dismissHint(banner)"
        :aria-label="t('ui.toast.close')"
        @click="dismiss(banner)"
      >
        <X class="h-4 w-4" />
      </button>
    </div>
  </TransitionGroup>
</template>

<style scoped>
.promo-enter-active {
  transition: all 0.3s ease;
}
.promo-leave-active {
  transition: all 0.2s ease;
}
.promo-enter-from,
.promo-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}
.promo-move {
  transition: transform 0.3s ease;
}
</style>
