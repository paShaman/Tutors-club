<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { LogIn } from 'lucide-vue-next'
import Button from '@/components/ui/Button.vue'
import SocialAuth from '@/components/social/SocialAuth.vue'
import { ref } from 'vue'
import { useI18n } from '@/lib/i18n'

const { t } = useI18n()

const form = useForm({
  email: '',
  password: '',
})

const showPassword = ref(false)

function submit(): void {
  form.post('/login', {
    onFinish: () => form.reset('password'),
  })
}
</script>

<template>
  <Head :title="t('ui.auth.login_page_title')" />

  <div class="flex min-h-screen items-center justify-center px-4 py-8">
    <div class="w-full max-w-md">
      <!-- Header -->
      <div class="mb-8 text-center">
        <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-primary shadow-lg shadow-primary/25">
          <LogIn class="h-7 w-7 text-primary-foreground" />
        </div>
        <h1 class="text-2xl font-bold tracking-tight text-foreground">
          {{ t('ui.auth.login_title') }}
        </h1>
        <p class="mt-2 text-sm text-muted-foreground">
          {{ t('ui.auth.login_subtitle') }}
        </p>
      </div>

      <!-- Form -->
      <form
        class="glass rounded-2xl border border-white/20 p-6 space-y-5"
        @submit.prevent="submit"
      >
        <!-- Соцсети -->
        <SocialAuth mode="login" />

        <!-- Email -->
        <div>
          <label for="email" class="block text-sm font-medium text-foreground mb-1.5">
            Email *
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
            {{ t('ui.auth.password') }}
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
              <span class="text-xs">{{ showPassword ? t('ui.common.hide') : t('ui.common.show') }}</span>
            </button>
          </div>
          <p v-if="form.errors.password" class="mt-1 text-xs text-destructive">{{ form.errors.password }}</p>
        </div>

        <!-- Actions -->
        <div class="space-y-3 pt-1">
          <Button
            type="submit"
            class="w-full"
            :disabled="form.processing"
          >
            <LogIn class="h-4 w-4" />
            {{ form.processing ? t('ui.auth.logging_in') : t('ui.auth.login_action') }}
          </Button>

          <p class="text-center text-sm text-muted-foreground">
            {{ t('ui.auth.no_account') }}&nbsp;
            <Link href="/register" class="font-medium text-primary hover:underline transition-colors cursor-pointer">
              {{ t('ui.auth.register_link') }}
            </Link>
          </p>
        </div>
      </form>
    </div>
  </div>
</template>