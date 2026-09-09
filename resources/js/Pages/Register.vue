<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import { UserPlus } from 'lucide-vue-next'
import Button from '@/components/ui/Button.vue'
import { onBeforeUnmount, onMounted, ref } from 'vue'
import type { SharedProps } from '@/types'

interface SmartCaptchaApi {
  render: (container: HTMLElement | string, params: SmartCaptchaParams) => number
  reset?: (widgetId?: number) => void
  destroy?: (widgetId?: number) => void
}

interface SmartCaptchaParams {
  sitekey: string
  hl?: string
  callback?: (token: string) => void
}

interface SmartCaptchaWindow extends Window {
  smartCaptcha?: SmartCaptchaApi
  __smartCaptchaReady?: () => void
}

const CAPTCHA_SITEKEY = 'ysc1_JArb9tRxzTMqOzXyeT01wYKXoYLtCQK5DPmkB10x200589eb'

const page = usePage<SharedProps>()

const form = useForm({
  last_name: '',
  first_name: '',
  email: '',
  password: '',
  password_confirmation: '',
  'smart-token': '',
})

const showPassword = ref(false)
const captchaContainer = ref<HTMLElement | null>(null)
const captchaToken = ref('')
const captchaError = ref('')
let captchaWidgetId: number | undefined

function captchaWindow(): SmartCaptchaWindow {
  return window as SmartCaptchaWindow
}

function smartCaptchaApi(): SmartCaptchaApi | undefined {
  return captchaWindow().smartCaptcha
}

function loadCaptchaScript(): Promise<void> {
  return new Promise((resolve, reject) => {
    if (smartCaptchaApi()) {
      resolve()
      return
    }

    const existing = document.getElementById('smartcaptcha-script') as HTMLScriptElement | null
    if (existing) {
      existing.addEventListener('load', () => resolve())
      existing.addEventListener('error', () => reject(new Error('Captcha load failed')))
      return
    }

    captchaWindow().__smartCaptchaReady = () => {
      delete captchaWindow().__smartCaptchaReady
    }

    const script = document.createElement('script')
    script.id = 'smartcaptcha-script'
    script.src = 'https://smartcaptcha.cloud.yandex.ru/captcha.js?render=onload&onload=__smartCaptchaReady'
    script.async = true
    script.addEventListener('load', () => resolve())
    script.addEventListener('error', () => reject(new Error('Captcha load failed')))
    document.head.appendChild(script)
  })
}

function initCaptcha(): void {
  if (!captchaContainer.value) {
    return
  }

  loadCaptchaScript()
    .then(() => {
      if (!captchaContainer.value || !smartCaptchaApi()) {
        return
      }

      captchaWidgetId = smartCaptchaApi()!.render(captchaContainer.value, {
        sitekey: CAPTCHA_SITEKEY,
        hl: 'ru',
        callback: (token: string) => {
          captchaToken.value = token
          captchaError.value = ''
        },
      })
    })
    .catch(() => {
      captchaError.value = 'Не удалось загрузить проверку «Я не робот»'
    })
}

function resetCaptcha(): void {
  captchaToken.value = ''
  captchaError.value = ''
  form['smart-token'] = ''

  if (!captchaContainer.value?.isConnected) {
    return
  }

  smartCaptchaApi()?.reset?.(captchaWidgetId)
}

onMounted(initCaptcha)

onBeforeUnmount(() => {
  if (smartCaptchaApi() && captchaWidgetId !== undefined) {
    smartCaptchaApi()!.destroy?.(captchaWidgetId)
  }
})

function submit(): void {
  if (!captchaToken.value) {
    captchaError.value = 'Подтвердите, что вы не робот'
    return
  }

  captchaError.value = ''
  form['smart-token'] = captchaToken.value

  form.post('/register', {
    onFinish: () => {
      form.reset('password', 'password_confirmation')
      resetCaptcha()
    },
  })
}
</script>

<template>
  <Head title="Регистрация" />

  <div class="flex min-h-screen items-center justify-center px-4 py-8">
    <div class="w-full max-w-md">
      <!-- Header -->
      <div class="mb-8 text-center">
        <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-500 shadow-lg shadow-emerald-500/25">
          <UserPlus class="h-7 w-7 text-white" />
        </div>
        <h1 class="text-2xl font-bold tracking-tight text-foreground">
          Регистрация
        </h1>
        <p class="mt-2 text-sm text-muted-foreground">
          Создайте аккаунт в Tutors Club
        </p>
      </div>

      <!-- Form -->
      <form
        class="glass rounded-2xl border border-white/20 p-6 space-y-5"
        @submit.prevent="submit"
      >
        <!-- Last Name -->
        <div>
          <label for="last_name" class="block text-sm font-medium text-foreground mb-1.5">
            Фамилия
          </label>
          <input
            id="last_name"
            v-model="form.last_name"
            type="text"
            autocomplete="family-name"
            class="w-full rounded-xl border border-border bg-white/50 px-3.5 py-2.5 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
            placeholder="Иванов"
          />
          <p v-if="form.errors.last_name" class="mt-1 text-xs text-destructive">{{ form.errors.last_name }}</p>
        </div>

        <!-- First Name -->
        <div>
          <label for="first_name" class="block text-sm font-medium text-foreground mb-1.5">
            Имя
          </label>
          <input
            id="first_name"
            v-model="form.first_name"
            type="text"
            required
            autocomplete="given-name"
            class="w-full rounded-xl border border-border bg-white/50 px-3.5 py-2.5 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
            placeholder="Иван"
          />
          <p v-if="form.errors.first_name" class="mt-1 text-xs text-destructive">{{ form.errors.first_name }}</p>
        </div>

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
              autocomplete="new-password"
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

        <!-- Confirm password -->
        <div>
          <label for="password_confirmation" class="block text-sm font-medium text-foreground mb-1.5">
            Подтверждение пароля
          </label>
          <input
            id="password_confirmation"
            v-model="form.password_confirmation"
            :type="showPassword ? 'text' : 'password'"
            required
            autocomplete="new-password"
            class="w-full rounded-xl border border-border bg-white/50 px-3.5 py-2.5 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
            placeholder="••••••••"
          />
          <p v-if="form.errors.password_confirmation" class="mt-1 text-xs text-destructive">{{ form.errors.password_confirmation }}</p>
        </div>

        <!-- Captcha -->
        <div>
          <div
            ref="captchaContainer"
            class="w-full rounded-xl bg-white/50"
            style="height: 100px"
          ></div>
          <p v-if="captchaError" class="mt-1 text-xs text-destructive">{{ captchaError }}</p>
          <p v-else-if="form.errors['smart-token']" class="mt-1 text-xs text-destructive">{{ form.errors['smart-token'] }}</p>
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
            <UserPlus class="h-4 w-4" />
            {{ form.processing ? 'Регистрация...' : 'Зарегистрироваться' }}
          </Button>

          <p class="text-center text-sm text-muted-foreground">
            Уже есть аккаунт?&nbsp;
            <Link href="/login" class="font-medium text-primary hover:underline transition-colors cursor-pointer">
              Войти
            </Link>
          </p>
        </div>
      </form>
    </div>
  </div>
</template>