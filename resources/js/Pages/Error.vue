<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import { ArrowLeft, Home, SearchX } from 'lucide-vue-next'
import Button from '@/components/ui/Button.vue'

withDefaults(defineProps<{ status?: number }>(), { status: 404 })

function goBack(): void {
  if (window.history.length > 1) {
    window.history.back()
  } else {
    router.visit('/')
  }
}
</script>

<template>
  <Head title="Страница не найдена" />

  <div class="flex min-h-screen items-center justify-center px-4 py-10">
    <div class="glass animate-fade-up w-full max-w-md rounded-2xl border border-white/20 p-8 text-center">
      <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-2xl bg-primary shadow-lg shadow-primary/25">
        <SearchX class="h-8 w-8 text-primary-foreground" />
      </div>

      <div class="bg-gradient-to-r from-primary to-purple-500 bg-clip-text text-6xl font-bold tracking-tight text-transparent">
        {{ status }}
      </div>

      <h1 class="mt-3 text-xl font-bold tracking-tight text-foreground">
        Страница не найдена
      </h1>
      <p class="mt-2 text-sm text-muted-foreground">
        Возможно, страница была удалена или вы перешли по неверной ссылке.
      </p>

      <div class="mt-7 flex flex-col gap-3 sm:flex-row">
        <Button class="w-full" @click="router.visit('/')">
          <Home class="h-4 w-4" />
          На главную
        </Button>
        <Button variant="outline" class="w-full" @click="goBack">
          <ArrowLeft class="h-4 w-4" />
          Назад
        </Button>
      </div>
    </div>
  </div>
</template>
