<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import { LogIn } from 'lucide-vue-next'
import Button from '@/components/ui/Button.vue'
import { ref } from 'vue'
import type { SharedProps } from '@/types'

const form = useForm({
  email: '',
  password: '',
})

const page = usePage<SharedProps>()

const showPassword = ref(false)

function submit(): void {
  form.post('/login', {
    onFinish: () => form.reset('password'),
  })
}
</script>

<template>
  <Head title="Вход" />

  <div class="flex min-h-[80vh] items-center justify-center px-4">
    <div class="w-full max-w-md">
      <!-- Header -->
      <div class="mb-8 text-center">
        <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-primary shadow-lg shadow-primary/25">
          <LogIn class="h-7 w-7 text-primary-foreground" />
        </div>
        <h1 class="text-2xl font-bold tracking-tight text-foreground">
          Вход в Tutors Club
        </h1>
        <p class="mt-2 text-sm text-muted-foreground">
          Введите данные для входа в аккаунт
        </p>
      </div>

      <!-- Form -->
      <form
        class="glass rounded-2xl border border-white/20 p-6 space-y-5"
        @submit.prevent="submit"
      >
        <!-- Email -->
        <div>
          <label for="email" class="block text-sm font-medium text-foreground mb-1.5">
            Email
          </label>
          <input
            id="email"
            v-model="form.email"
            type="email"
            required
            autocomplete="email"
            class="w-full rounded-xl border border-border bg-white/50 px-3.5 py-2.5 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
            placeholder="you@example.com"
          />
          <p v-if="form.errors.email" class="mt-1 text-xs text-destructive">{{ form.errors.email }}</p>
        </div>

        <!-- Password -->
        <div>
          <label for="password" class="block text-sm font-medium text-foreground mb-1.5">
            Пароль
          </label>
          <div class="relative">
            <input
              id="password"
              v-model="form.password"
              :type="showPassword ? 'text' : 'password'"
              required
              autocomplete="current-password"
              class="w-full rounded-xl border border-border bg-white/50 px-3.5 py-2.5 pr-10 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
              placeholder="••••••••"
            />
            <button
              type="button"
              class="absolute inset-y-0 right-0 flex items-center pr-3 text-muted-foreground hover:text-foreground transition-colors cursor-pointer"
              @click="showPassword = !showPassword"
            >
              <span class="text-xs">{{ showPassword ? 'Скрыть' : 'Показать' }}</span>
            </button>
          </div>
          <p v-if="form.errors.password" class="mt-1 text-xs text-destructive">{{ form.errors.password }}</p>
        </div>

        <!-- Error flash -->
        <div
          v-if="page.props.flash?.error"
          class="rounded-xl bg-destructive/10 px-4 py-3 text-sm text-destructive"
        >
          {{ page.props.flash.error }}
        </div>

        <!-- Actions -->
        <div class="space-y-3 pt-1">
          <Button
            type="submit"
            class="w-full"
            :disabled="form.processing"
          >
            <LogIn class="h-4 w-4" />
            {{ form.processing ? 'Вход...' : 'Войти' }}
          </Button>

          <p class="text-center text-sm text-muted-foreground">
            Нет аккаунта?&nbsp;
            <Link href="/register" class="font-medium text-primary hover:underline transition-colors cursor-pointer">
              Зарегистрироваться
            </Link>
          </p>
        </div>
      </form>
    </div>
  </div>
</template>