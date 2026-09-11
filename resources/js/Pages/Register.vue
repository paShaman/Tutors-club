<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import { UserPlus } from 'lucide-vue-next'
import Button from '@/components/ui/Button.vue'
import SocialAuth from '@/components/social/SocialAuth.vue'
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import type { SharedProps } from '@/types'
import { useI18n } from '@/lib/i18n'

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
const { t, locale } = useI18n()

const agreements = computed(() => page.props.agreements ?? [])

const form = useForm({
  last_name: '',
  first_name: '',
  email: '',
  password: '',
  password_confirmation: '',
  'smart-token': '',
  agreement: false,
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
        hl: locale.value,
        callback: (token: string) => {
          captchaToken.value = token
          captchaError.value = ''
        },
      })
    })
    .catch(() => {
      captchaError.value = t('ui.auth.captcha_load_error')
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
    captchaError.value = t('ui.auth.captcha_required')
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
  <Head :title="t('ui.auth.register_page_title')" />

  <div class="flex min-h-screen items-center justify-center px-4 py-8">
    <div class="w-full max-w-md">
      <!-- Header -->
      <div class="mb-8 text-center">
        <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-500 shadow-lg shadow-emerald-500/25">
          <UserPlus class="h-7 w-7 text-white" />
        </div>
        <h1 class="text-2xl font-bold tracking-tight text-foreground">
          {{ t('ui.auth.register_title') }}
        </h1>
        <p class="mt-2 text-sm text-muted-foreground">
          {{ t('ui.auth.register_subtitle') }}
        </p>
      </div>

      <!-- Form -->
      <form
        class="glass rounded-2xl border border-white/20 p-6 space-y-5"
        @submit.prevent="submit"
      >
        <!-- Соцсети -->
        <SocialAuth
          mode="login"
          register
          agreement-required
          :agreement="form.agreement"
        />

        <!-- Consent -->
        <div>
          <label class="flex cursor-pointer items-start gap-2.5 text-xs leading-relaxed text-muted-foreground">
            <input
              v-model="form.agreement"
              type="checkbox"
              class="mt-0.5 h-4 w-4 shrink-0 cursor-pointer rounded border-border accent-primary"
            />
            <span>
              {{ t('ui.auth.agreement_prefix') }}
              <template v-for="(doc, index) in agreements" :key="index">
                <span v-if="index > 0"> {{ t('ui.auth.and') }} </span>
                <a
                  v-if="doc.url"
                  :href="doc.url"
                  target="_blank"
                  rel="noopener"
                  class="font-medium text-primary hover:underline transition-colors"
                >{{ t(doc.genitive ?? doc.label) }}</a>
                <span v-else class="font-medium text-primary">{{ t(doc.genitive ?? doc.label) }}</span>
              </template>
              <span class="text-destructive">*</span>
            </span>
          </label>
          <p v-if="form.errors.agreement" class="mt-1 text-xs text-destructive">{{ form.errors.agreement }}</p>
        </div>

        <!-- Last Name -->
        <div>
          <label for="last_name" class="block text-sm font-medium text-foreground mb-1.5">
            {{ t('ui.auth.last_name') }}
          </label>
          <input
            id="last_name"
            v-model="form.last_name"
            type="text"
            autocomplete="family-name"
            class="w-full rounded-xl border border-border bg-white/50 px-3.5 py-2.5 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
            :placeholder="t('ui.placeholder.last_name')"
          />
          <p v-if="form.errors.last_name" class="mt-1 text-xs text-destructive">{{ form.errors.last_name }}</p>
        </div>

        <!-- First Name -->
        <div>
          <label for="first_name" class="block text-sm font-medium text-foreground mb-1.5">
            {{ t('ui.auth.first_name') }}
          </label>
          <input
            id="first_name"
            v-model="form.first_name"
            type="text"
            autocomplete="given-name"
            class="w-full rounded-xl border border-border bg-white/50 px-3.5 py-2.5 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
            :placeholder="t('ui.placeholder.first_name')"
          />
          <p v-if="form.errors.first_name" class="mt-1 text-xs text-destructive">{{ form.errors.first_name }}</p>
        </div>

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
              autocomplete="new-password"
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

        <!-- Confirm password -->
        <div>
          <label for="password_confirmation" class="block text-sm font-medium text-foreground mb-1.5">
            {{ t('ui.auth.password_confirmation') }}
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
          <p class="block text-sm font-medium text-foreground mb-1.5">
            {{ t('ui.auth.captcha_label') }}
          </p>
          <div
            ref="captchaContainer"
            class="w-full rounded-xl bg-white/50"
            style="height: 100px"
          ></div>
          <p v-if="captchaError" class="mt-1 text-xs text-destructive">{{ captchaError }}</p>
          <p v-else-if="form.errors['smart-token']" class="mt-1 text-xs text-destructive">{{ form.errors['smart-token'] }}</p>
        </div>

        <!-- Actions -->
        <div class="space-y-3 pt-1">
          <Button
            type="submit"
            class="w-full"
            :disabled="form.processing"
          >
            <UserPlus class="h-4 w-4" />
            {{ form.processing ? t('ui.auth.registering') : t('ui.auth.register_action') }}
          </Button>

          <p class="text-center text-sm text-muted-foreground">
            {{ t('ui.auth.have_account') }}&nbsp;
            <Link href="/login" class="font-medium text-primary hover:underline transition-colors cursor-pointer">
              {{ t('ui.auth.login_link') }}
            </Link>
          </p>
        </div>
      </form>
    </div>
  </div>
</template>