<script setup lang="ts">
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { X, GitFork, Loader2 } from 'lucide-vue-next'
import Button from '@/components/ui/Button.vue'

const emit = defineEmits<{
  (e: 'close'): void
}>()

interface ChangelogItem {
  text: string
  hash: string
}

interface ChangelogCategory {
  title: string
  items: ChangelogItem[]
}

const categories = ref<ChangelogCategory[]>([])
const loading = ref(true)
const error = ref('')

async function loadChangelog() {
  loading.value = true
  error.value = ''
  try {
    const { data } = await axios.get('/changelog', { params: { days: 1 } })
    categories.value = data.changelog ?? []
  } catch (e: any) {
    error.value = 'Не удалось загрузить список изменений'
    console.error(e)
  } finally {
    loading.value = false
  }
}

onMounted(loadChangelog)
</script>

<template>
  <Teleport to="body">
    <!-- Overlay -->
    <div
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/30 backdrop-blur-sm p-4"
      @click.self="emit('close')"
    >
      <!-- Modal -->
      <div
        class="relative w-full max-w-lg max-h-[80vh] flex flex-col rounded-2xl bg-white shadow-2xl ring-1 ring-black/5 overflow-hidden"
      >
        <!-- Header -->
        <div class="flex items-center justify-between px-5 py-4 border-b border-border/50">
          <div class="flex items-center gap-2.5">
            <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-primary/10">
              <GitFork class="h-4 w-4 text-primary" />
            </div>
            <div>
              <h3 class="text-base font-semibold text-foreground">История изменений</h3>
              <p class="text-xs text-muted-foreground">Сегодня</p>
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
          <div v-else-if="categories.length === 0" class="text-center py-8">
            <div class="flex justify-center mb-3">
              <div class="flex h-10 w-10 items-center justify-center rounded-full bg-muted">
                <GitFork class="h-5 w-5 text-muted-foreground" />
              </div>
            </div>
            <p class="text-sm text-muted-foreground">Нет изменений за сегодня</p>
          </div>

          <!-- Changelog -->
          <div v-else class="space-y-5">
            <div v-for="cat in categories" :key="cat.title">
              <h4 class="text-sm font-semibold text-foreground mb-2">{{ cat.title }}</h4>
              <ul class="space-y-1.5">
                <li
                  v-for="item in cat.items"
                  :key="item.hash"
                  class="flex items-start gap-2 text-sm text-foreground/85 leading-snug pl-3 relative before:absolute before:left-0 before:top-[0.6em] before:h-1 before:w-1 before:rounded-full before:bg-primary/60"
                >
                  {{ item.text }}
                  <code class="shrink-0 font-mono text-[10px] bg-muted px-1.5 py-px rounded text-muted-foreground mt-px">
                    {{ item.hash }}
                  </code>
                </li>
              </ul>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="px-5 py-3 border-t border-border/50 flex justify-end">
          <Button variant="outline" size="sm" @click="emit('close')">
            Закрыть
          </Button>
        </div>
      </div>
    </div>
  </Teleport>
</template>