<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { X, GitFork, ChevronDown, Loader2 } from 'lucide-vue-next'
import Button from '@/components/ui/Button.vue'
import { useI18n } from '@/lib/i18n'

const { t, intlLocale } = useI18n()

const props = defineProps<{
  show: boolean
}>()

const emit = defineEmits<{
  (e: 'close'): void
}>()

interface ChangelogCategory {
  title: string
  items: string[]
}

interface ChangelogVersion {
  version: string
  date: string
  categories: ChangelogCategory[]
}

const versions = ref<ChangelogVersion[]>([])
const openVersions = ref<Set<string>>(new Set())
const loading = ref(false)
const error = ref('')
const loaded = ref(false)

const latest = computed(() => versions.value[0] ?? null)

const latestDateLabel = computed(() => {
  const v = latest.value
  return v ? `${formatDate(v.date)}` : ''
})

async function loadChangelog() {
  if (loading.value) {
    return
  }

  loading.value = true
  error.value = ''
  try {
    const response = await fetch('/changelog', {
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    })

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`)
    }

    const data = await response.json()
    versions.value = data.changelog ?? []
    loaded.value = true
    const first = versions.value[0]
    openVersions.value = new Set(first ? [first.version] : [])
  } catch (e: any) {
    error.value = t('ui.changelog.load_error')
    console.error(e)
  } finally {
    loading.value = false
  }
}

function isOpen(version: string): boolean {
  return openVersions.value.has(version)
}

function toggle(version: string): void {
  const next = new Set(openVersions.value)
  if (next.has(version)) {
    next.delete(version)
  } else {
    next.add(version)
  }
  openVersions.value = next
}

function formatDate(isoDate: string): string {
  const [year, month, day] = isoDate.split('-').map(Number)
  const date = new Date(year, (month ?? 1) - 1, day ?? 1)
  const label = date.toLocaleDateString(intlLocale.value, {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  })
  return label.charAt(0).toUpperCase() + label.slice(1)
}

watch(
  () => props.show,
  (visible) => {
    if (visible && !loaded.value) {
      loadChangelog()
    }
  },
  { immediate: true },
)
</script>

<template>
  <Teleport to="body">
    <Transition name="overlay">
      <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/30 backdrop-blur-sm p-4"
        @click.self="emit('close')"
      >
        <Transition name="modal" @after-leave="emit('close')">
          <div
            v-if="show"
            class="relative w-full max-w-lg max-h-[80vh] flex flex-col rounded-2xl bg-white shadow-2xl ring-1 ring-black/5 overflow-hidden"
          >
        <!-- Header -->
        <div class="flex items-center justify-between px-5 py-4 border-b border-border/50">
          <div class="flex items-center gap-2.5">
            <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-primary/10">
              <GitFork class="h-4 w-4 text-primary" />
            </div>
            <div>
              <h3 class="text-base font-semibold text-foreground">{{ t('ui.changelog.title') }}</h3>
              <p class="text-xs text-muted-foreground">
                <template v-if="latest">{{ t('ui.changelog.version', { version: latest.version, date: latestDateLabel }) }}</template>
                <template v-else>{{ t('ui.changelog.app_updates') }}</template>
              </p>
            </div>
          </div>
          <button
            class="rounded-lg p-1.5 text-muted-foreground hover:bg-accent hover:text-foreground transition-colors cursor-pointer"
            @click="emit('close')"
          >
            <X class="h-5 w-5" />
          </button>
        </div>

        <!-- Body -->
        <div class="flex-1 overflow-y-auto px-5 py-4">
          <!-- Loading -->
          <div v-if="loading" class="flex items-center justify-center py-12">
            <Loader2 class="h-6 w-6 animate-spin text-muted-foreground" />
          </div>

          <!-- Error -->
          <div v-else-if="error" class="text-center py-8">
            <p class="text-sm text-destructive">{{ error }}</p>
          </div>

          <!-- Empty -->
          <div v-else-if="versions.length === 0" class="text-center py-8">
            <div class="flex justify-center mb-3">
              <div class="flex h-10 w-10 items-center justify-center rounded-full bg-muted">
                <GitFork class="h-5 w-5 text-muted-foreground" />
              </div>
            </div>
            <p class="text-sm text-muted-foreground">{{ t('ui.changelog.empty') }}</p>
          </div>

          <!-- Versioned accordion -->
          <div v-else class="space-y-3">
            <div
              v-for="ver in versions"
              :key="ver.version"
              class="overflow-hidden rounded-xl border border-border/60 bg-white/50"
            >
              <!-- Version header -->
              <button
                class="w-full flex items-center gap-3 px-4 py-3 text-left hover:bg-accent/50 transition-colors cursor-pointer"
                @click="toggle(ver.version)"
              >
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary/10">
                  <span class="text-xs font-bold text-primary">{{ ver.version }}</span>
                </div>
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-2">
                    <p class="text-sm font-semibold text-foreground">{{ t('ui.changelog.version_label', { version: ver.version }) }}</p>
                  </div>
                  <p class="text-xs text-muted-foreground">{{ formatDate(ver.date) }}</p>
                </div>
                <ChevronDown
                  class="h-4 w-4 shrink-0 text-muted-foreground transition-transform duration-300"
                  :class="isOpen(ver.version) && 'rotate-180'"
                />
              </button>

              <!-- Version body -->
              <Transition name="collapse">
                <div v-if="isOpen(ver.version)" class="border-t border-border/50 px-4 py-4 space-y-4">
                  <div v-for="cat in ver.categories" :key="cat.title">
                    <h4 class="text-sm font-semibold text-foreground mb-2">{{ cat.title }}</h4>
                    <ul class="space-y-1.5">
                      <li
                        v-for="(item, i) in cat.items"
                        :key="i"
                        class="text-sm text-foreground/85 leading-snug pl-3 relative before:absolute before:left-0 before:top-[0.6em] before:h-1 before:w-1 before:rounded-full before:bg-primary/60"
                      >
                        {{ item }}
                      </li>
                    </ul>
                  </div>
                </div>
              </Transition>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="px-5 py-3 border-t border-border/50 flex justify-end">
          <Button variant="outline" size="sm" @click="emit('close')">
            {{ t('ui.common.close') }}
          </Button>
        </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.overlay-enter-active,
.overlay-leave-active {
  transition: opacity 0.2s ease;
}
.overlay-enter-from,
.overlay-leave-to {
  opacity: 0;
}

.modal-enter-active {
  transition: all 0.2s ease;
}
.modal-leave-active {
  transition: all 0.15s ease;
}
.modal-enter-from {
  opacity: 0;
  transform: scale(0.95) translateY(8px);
}
.modal-leave-to {
  opacity: 0;
  transform: scale(0.95) translateY(4px);
}
</style>
