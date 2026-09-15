<script setup lang="ts">
import { Head, router, usePage, useForm } from '@inertiajs/vue3'
import { Settings, User, Shield, Save, Link2, Unlink, Languages, CreditCard, AlertTriangle, RefreshCw } from 'lucide-vue-next'
import Button from '@/components/ui/Button.vue'
import Card from '@/components/ui/Card.vue'
import CardHeader from '@/components/ui/CardHeader.vue'
import CardTitle from '@/components/ui/CardTitle.vue'
import Select from '@/components/ui/Select.vue'
import type { SelectOption } from '@/components/ui/Select.vue'
import { computed, ref, watch } from 'vue'
import type { SharedProps } from '@/types'
import AppLayout from '@/Layouts/AppLayout.vue'
import AvatarPicker from '@/components/ui/AvatarPicker.vue'
import VkIdAuth from '@/components/social/VkIdAuth.vue'
import YandexAuth from '@/components/social/YandexAuth.vue'
import VkIcon from '@/components/social/VkIcon.vue'
import YandexIcon from '@/components/social/YandexIcon.vue'
import TelegramIcon from '@/components/social/TelegramIcon.vue'
import ConfirmDialog from '@/components/popups/ConfirmDialog.vue'
import { providerMeta } from '@/lib/social'
import { useI18n } from '@/lib/i18n'

interface SocialBinding {
  provider: string
  social_id: string
  created_at: string | null
}

interface TelegramInfo {
  configured: boolean
  linked: boolean
  username: string | null
  link_url: string | null
  code: string | null
  ttl: number
}

interface SettingsPageProps extends SharedProps {
  socials: SocialBinding[]
  telegram: TelegramInfo | null
}

defineOptions({ layout: AppLayout })

const page = usePage<SettingsPageProps>()
const { t, intlLocale } = useI18n()

const user = computed(() => page.props.auth?.user ?? null)
const socials = computed(() => page.props.socials ?? [])
const locales = computed(() => page.props.locales ?? [])
const localeOptions = computed<SelectOption<string>[]>(() =>
  locales.value.map((item) => ({ value: item.code, label: item.label })),
)
const configuredProviders = computed(() => (page.props.social ?? []).filter((p) => p.configured))

const tariff = computed(() => page.props.tariff ?? null)
const telegram = computed(() => page.props.telegram ?? null)

function formatTariffDate(raw: string | null | undefined): string | null {
  if (!raw) return null
  return new Date(`${raw}T00:00:00`).toLocaleDateString(intlLocale.value)
}

const tariffUntil = computed(() => formatTariffDate(tariff.value?.until))
const tariffExpiredAt = computed(() => formatTariffDate(tariff.value?.expired_at))

const tariffUsageRows = computed(() => {
  const info = tariff.value
  if (!info) return []

  return (['students', 'lessons', 'topics'] as const).map((feature) => ({
    key: feature,
    label: t(`ui.tariff.usage.${feature}`),
    value: info.limits[feature] === null
      ? t('ui.tariff.unlimited', { used: info.usage[feature] })
      : t('ui.tariff.of_limit', { used: info.usage[feature], limit: info.limits[feature] }),
  }))
})

function binding(provider: string): SocialBinding | null {
  return socials.value.find((s) => s.provider === provider) ?? null
}

function isConfigured(provider: string): boolean {
  return configuredProviders.value.some((p) => p.key === provider)
}

const socialRows = computed(() => {
  const keys: string[] = []

  for (const provider of configuredProviders.value) {
    keys.push(provider.key)
  }

  for (const item of socials.value) {
    if (!keys.includes(item.provider)) {
      keys.push(item.provider)
    }
  }

  return keys
})

const form = useForm({
  last_name: user.value?.last_name ?? '',
  first_name: user.value?.first_name ?? '',
  middle_name: user.value?.middle_name ?? '',
  avatar: user.value?.avatar ?? null,
})

const passwordForm = useForm({
  current_password: '',
  password: '',
  password_confirmation: '',
})

const localeForm = useForm({
  locale: page.props.locale ?? 'ru',
})

// Сохранённый язык может прийти асинхронно — синхронизируем селект
watch(() => page.props.locale, (value) => {
  if (value) {
    localeForm.locale = value
  }
})

// Sync form fields when user data arrives asynchronously
watch(user, (u) => {
  if (u) {
    form.last_name = u.last_name ?? ''
    form.first_name = u.first_name ?? ''
    form.middle_name = u.middle_name ?? ''
    form.avatar = u.avatar ?? null
  }
}, { immediate: true })

function submit(): void {
  form.post('/user/settings', {
    preserveScroll: true,
    onSuccess: () => {
      // form is automatically reset to current values
    },
  })
}

function submitPassword(): void {
  passwordForm.post('/user/password', {
    preserveScroll: true,
    onSuccess: () => {
      passwordForm.reset()
    },
  })
}

function changeLocale(): void {
  localeForm.post('/user/locale', { preserveScroll: true })
}

function updateLocale(value: string): void {
  localeForm.locale = value
  changeLocale()
}

function logout(): void {
  router.visit('/logout', { method: 'get' })
}

// Unlink confirm state
const showUnlinkConfirm = ref(false)
const unlinkProvider = ref<string | null>(null)

function requestUnlink(provider: string): void {
  unlinkProvider.value = provider
  showUnlinkConfirm.value = true
}

function confirmUnlink(): void {
  const provider = unlinkProvider.value
  showUnlinkConfirm.value = false
  unlinkProvider.value = null

  if (!provider) {
    return
  }

  router.post('/user/socials/unlink', { provider }, {
    preserveScroll: true,
  })
}

function cancelUnlink(): void {
  showUnlinkConfirm.value = false
  unlinkProvider.value = null
}

// Telegram unlink / link refresh
const showTelegramUnlinkConfirm = ref(false)

function refreshTelegramLink(): void {
  router.post('/user/telegram/refresh', {}, { preserveScroll: true })
}

function confirmTelegramUnlink(): void {
  showTelegramUnlinkConfirm.value = false
  router.post('/user/telegram/unlink', {}, { preserveScroll: true })
}
</script>

<template>
  <Head :title="t('ui.settings.title')" />

  <div class="max-w-2xl space-y-6 animate-fade-up">
    <!-- Page header -->
    <div>
      <h1 class="page-title text-3xl">
        {{ t('ui.settings.title') }}
      </h1>
      <p class="mt-1 text-muted-foreground">
        {{ t('ui.settings.subtitle') }}
      </p>
    </div>

    <!-- Profile section -->
    <Card>
      <CardHeader>
        <div class="flex items-center gap-3">
          <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10">
            <User class="h-5 w-5 text-primary" />
          </div>
          <div>
            <CardTitle>{{ t('ui.settings.profile') }}</CardTitle>
            <p class="text-sm text-muted-foreground">{{ t('ui.settings.profile_subtitle') }}</p>
          </div>
        </div>
      </CardHeader>

      <form class="px-6 pb-6 space-y-4" @submit.prevent="submit">
        <!-- Фото профиля -->
        <div class="border-b border-border/60 pb-5">
          <p class="block text-sm font-medium text-foreground mb-3">
            {{ t('ui.settings.photo') }}
          </p>
          <AvatarPicker
            v-model="form.avatar"
            :name="`${form.last_name ?? ''} ${form.first_name ?? ''} ${form.middle_name ?? ''}`"
            :show-remove="!!user?.avatar || !!form.avatar"
          />
        </div>

        <!-- Фамилия -->
        <div>
          <label for="last_name" class="field-label">
            {{ t('ui.settings.last_name') }}
          </label>
          <input
            id="last_name"
            v-model="form.last_name"
            type="text"
            autocomplete="family-name"
            class="field w-full px-3.5 py-2.5"
            :placeholder="t('ui.placeholder.last_name')"
          />
          <p v-if="form.errors.last_name" class="field-error">{{ form.errors.last_name }}</p>
        </div>

        <!-- Имя -->
        <div>
          <label for="first_name" class="field-label">
            {{ t('ui.settings.first_name') }}
          </label>
          <input
            id="first_name"
            v-model="form.first_name"
            type="text"
            autocomplete="given-name"
            class="field w-full px-3.5 py-2.5"
            :placeholder="t('ui.placeholder.first_name')"
          />
          <p v-if="form.errors.first_name" class="field-error">{{ form.errors.first_name }}</p>
        </div>

        <!-- Отчество -->
        <div>
          <label for="middle_name" class="field-label">
            {{ t('ui.settings.middle_name') }}
          </label>
          <input
            id="middle_name"
            v-model="form.middle_name"
            type="text"
            autocomplete="additional-name"
            class="field w-full px-3.5 py-2.5"
            :placeholder="t('ui.placeholder.middle_name')"
          />
          <p v-if="form.errors.middle_name" class="field-error">{{ form.errors.middle_name }}</p>
        </div>

        <!-- Email (readonly) -->
        <div>
          <label for="email" class="block text-sm font-medium text-muted-foreground mb-1.5">
            {{ t('ui.settings.email_readonly') }}
          </label>
          <p class="text-foreground">{{ user?.email ?? '—' }}</p>
        </div>

        <!-- Save button -->
        <div class="pt-2">
          <Button type="submit" :disabled="form.processing">
            <Save class="h-4 w-4" />
            {{ form.processing ? t('ui.common.saving') : t('ui.settings.save') }}
          </Button>
        </div>
      </form>
    </Card>

    <!-- Language section -->
    <Card>
      <CardHeader>
        <div class="flex items-center gap-3">
          <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-500/10">
            <Languages class="h-5 w-5 text-emerald-500" />
          </div>
          <div>
            <CardTitle>{{ t('ui.settings.language') }}</CardTitle>
            <p class="text-sm text-muted-foreground">{{ t('ui.settings.language_subtitle') }}</p>
          </div>
        </div>
      </CardHeader>

      <div class="px-6 pb-6">
        <label for="locale" class="field-label">
          {{ t('ui.settings.language_label') }}
        </label>
        <Select
          id="locale"
          :model-value="localeForm.locale"
          :options="localeOptions"
          :disabled="localeForm.processing"
          @update:model-value="updateLocale"
        />
        <p v-if="localeForm.errors.locale" class="field-error">{{ localeForm.errors.locale }}</p>
      </div>
    </Card>

    <!-- Tariff section -->
    <Card>
      <CardHeader>
        <div class="flex items-center gap-3">
          <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-500/10">
            <CreditCard class="h-5 w-5 text-amber-500" />
          </div>
          <div>
            <CardTitle>{{ t('ui.tariff.title') }}</CardTitle>
            <p class="text-sm text-muted-foreground">{{ t('ui.tariff.subtitle') }}</p>
          </div>
        </div>
      </CardHeader>

      <div v-if="tariff" class="px-6 pb-6 space-y-4">
        <div class="flex items-center justify-between rounded-xl border border-border/60 bg-white/40 px-4 py-3">
          <span class="text-sm text-muted-foreground">{{ t('ui.tariff.current') }}</span>
          <span class="text-sm font-semibold text-foreground">{{ t(`ui.tariff.plans.${tariff.plan}`) }}</span>
        </div>

        <p v-if="tariff.is_paid && tariffUntil" class="text-sm text-muted-foreground">
          {{ t('ui.tariff.active_until', { date: tariffUntil }) }}
        </p>

        <div
          v-if="tariff.expired"
          class="flex items-start gap-3 rounded-xl border border-amber-200/60 bg-amber-50/60 px-4 py-3 text-sm text-amber-700"
        >
          <AlertTriangle class="mt-0.5 h-4 w-4 shrink-0" />
          <div>
            <p class="font-medium">{{ t('ui.tariff.expired.title') }}</p>
            <p class="text-amber-700/80">{{ t('ui.tariff.expired.text', { date: tariffExpiredAt ?? '' }) }}</p>
          </div>
        </div>

        <div class="space-y-1.5 border-t border-border/60 pt-4">
          <div
            v-for="row in tariffUsageRows"
            :key="row.key"
            class="flex items-center justify-between text-sm"
          >
            <span class="text-muted-foreground">{{ row.label }}</span>
            <span class="font-medium text-foreground">{{ row.value }}</span>
          </div>
        </div>

        <p v-if="!tariff.is_paid && tariff.price_month" class="text-sm text-muted-foreground">
          {{ t('ui.tariff.upsell', { month: tariff.price_month, year: tariff.price_year }) }}
        </p>

        <p class="text-xs text-muted-foreground/70">{{ t('ui.tariff.manage_hint') }}</p>
      </div>
    </Card>

    <!-- Social section -->
    <Card>
      <CardHeader>
        <div class="flex items-center gap-3">
          <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-sky-500/10">
            <Link2 class="h-5 w-5 text-sky-500" />
          </div>
          <div>
            <CardTitle>{{ t('ui.settings.socials') }}</CardTitle>
            <p class="text-sm text-muted-foreground">{{ t('ui.settings.socials_subtitle') }}</p>
          </div>
        </div>
      </CardHeader>

      <div class="px-6 pb-6 space-y-4">
        <div v-for="key in socialRows" :key="key" class="space-y-3">
          <!-- Provider row -->
          <div class="flex items-center justify-between gap-4 rounded-xl border border-border/60 bg-white/40 px-4 py-3">
            <div class="flex items-center gap-3">
              <div
                class="flex h-9 w-9 items-center justify-center rounded-lg"
                :class="providerMeta(key).badgeClass"
              >
                <VkIcon v-if="key === 'vkontakte'" class="h-4 w-4" />
                <YandexIcon v-else-if="key === 'yandex'" class="h-4 w-2" />
                <span v-else class="text-xs font-bold">{{ providerMeta(key).badge }}</span>
              </div>
              <div>
                <p class="text-sm font-medium text-foreground">{{ t(providerMeta(key).labelKey) }}</p>
                <p v-if="binding(key)" class="text-xs text-muted-foreground">
                  {{ t('ui.settings.social_linked', { id: binding(key)!.social_id }) }}
                </p>
                <p v-else class="text-xs text-muted-foreground">
                  {{ t('ui.settings.social_not_linked') }}
                </p>
              </div>
            </div>
            <Button
              v-if="binding(key)"
              variant="outline"
              size="sm"
              class="text-destructive hover:text-destructive"
              :title="t('ui.settings.unlink')"
              @click="requestUnlink(key)"
            >
              <Unlink class="h-4 w-4" />
              <span class="hidden sm:inline">{{ t('ui.settings.unlink') }}</span>
            </Button>
          </div>

          <!-- Link control for configured providers -->
          <VkIdAuth
            v-if="key === 'vkontakte' && !binding(key) && isConfigured(key)"
            mode="link"
          />
          <YandexAuth
            v-else-if="key === 'yandex' && !binding(key) && isConfigured(key)"
            mode="link"
          />
        </div>
      </div>
    </Card>

    <!-- Telegram section -->
    <Card v-if="telegram?.configured">
      <CardHeader>
        <div class="flex items-center gap-3">
          <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#229ED9]/10">
            <TelegramIcon class="h-5 w-5 text-[#229ED9]" />
          </div>
          <div>
            <CardTitle>{{ t('ui.settings.telegram') }}</CardTitle>
            <p class="text-sm text-muted-foreground">{{ t('ui.settings.telegram_subtitle') }}</p>
          </div>
        </div>
      </CardHeader>

      <div class="px-6 pb-6 space-y-4">
        <p class="text-sm text-muted-foreground">{{ t('ui.settings.telegram_hint') }}</p>

        <div class="flex items-center justify-between gap-4 rounded-xl border border-border/60 bg-white/40 px-4 py-3">
          <div class="flex items-center gap-3">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#229ED9]/10 text-[#229ED9]">
              <TelegramIcon class="h-4 w-4" />
            </div>
            <div>
              <p class="text-sm font-medium text-foreground">{{ t('ui.settings.telegram') }}</p>
              <p v-if="telegram?.linked" class="text-xs text-muted-foreground">
                {{ telegram?.username
                  ? t('ui.settings.telegram_linked', { username: telegram?.username })
                  : t('ui.settings.telegram_linked_plain') }}
              </p>
              <p v-else class="text-xs text-muted-foreground">{{ t('ui.settings.telegram_not_linked') }}</p>
            </div>
          </div>
          <Button
            v-if="telegram?.linked"
            variant="outline"
            size="sm"
            class="text-destructive hover:text-destructive"
            :title="t('ui.settings.unlink')"
            @click="showTelegramUnlinkConfirm = true"
          >
            <Unlink class="h-4 w-4" />
            <span class="hidden sm:inline">{{ t('ui.settings.unlink') }}</span>
          </Button>
        </div>

        <div v-if="!telegram?.linked" class="space-y-3">
          <Button as="a" :href="telegram?.link_url ?? '#'" target="_blank" rel="noopener">
            <TelegramIcon class="h-4 w-4" />
            {{ t('ui.settings.telegram_connect') }}
          </Button>
          <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
            <Button variant="ghost" size="sm" @click="refreshTelegramLink">
              <RefreshCw class="h-4 w-4" />
              {{ t('ui.settings.telegram_refresh') }}
            </Button>
            <span class="text-xs text-muted-foreground">
              {{ t('ui.settings.telegram_code_hint', { ttl: telegram?.ttl ?? 0 }) }}
            </span>
          </div>
        </div>

        <p class="text-xs text-muted-foreground/70">{{ t('ui.settings.telegram_commands_hint') }}</p>
      </div>
    </Card>

    <!-- Security section -->
    <Card>
      <CardHeader>
        <div class="flex items-center gap-3">
          <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-destructive/10">
            <Shield class="h-5 w-5 text-destructive" />
          </div>
          <div>
            <CardTitle>{{ t('ui.settings.security') }}</CardTitle>
            <p class="text-sm text-muted-foreground">{{ t('ui.settings.security_subtitle') }}</p>
          </div>
        </div>
      </CardHeader>
      <div class="px-6 pb-6 space-y-4">
        <!-- Password -->
        <div class="border-b border-border/60 pb-5">
          <p class="block text-sm font-medium text-foreground mb-1">
            {{ t('ui.settings.password') }}
          </p>
          <p class="mb-3 text-xs text-muted-foreground">
            {{ user?.has_password ? t('ui.settings.password_hint_change') : t('ui.settings.password_hint_set') }}
          </p>

          <form class="space-y-3" @submit.prevent="submitPassword">
            <div v-if="user?.has_password">
              <label for="current_password" class="field-label">
                {{ t('ui.settings.current_password') }}
              </label>
              <input
                id="current_password"
                v-model="passwordForm.current_password"
                type="password"
                required
                autocomplete="current-password"
                class="field w-full px-3.5 py-2.5"
              />
              <p v-if="passwordForm.errors.current_password" class="field-error">{{ passwordForm.errors.current_password }}</p>
            </div>

            <div>
              <label for="new_password" class="field-label">
                {{ t('ui.settings.new_password') }}
              </label>
              <input
                id="new_password"
                v-model="passwordForm.password"
                type="password"
                required
                autocomplete="new-password"
                class="field w-full px-3.5 py-2.5"
              />
              <p v-if="passwordForm.errors.password" class="field-error">{{ passwordForm.errors.password }}</p>
            </div>

            <div>
              <label for="password_confirmation" class="field-label">
                {{ t('ui.settings.repeat_password') }}
              </label>
              <input
                id="password_confirmation"
                v-model="passwordForm.password_confirmation"
                type="password"
                required
                autocomplete="new-password"
                class="field w-full px-3.5 py-2.5"
              />
              <p v-if="passwordForm.errors.password_confirmation" class="field-error">{{ passwordForm.errors.password_confirmation }}</p>
            </div>

            <Button type="submit" :disabled="passwordForm.processing">
              {{ passwordForm.processing ? t('ui.common.saving') : (user?.has_password ? t('ui.settings.change_password') : t('ui.settings.set_password')) }}
            </Button>
          </form>
        </div>

        <Button variant="outline" @click="logout">
          {{ t('ui.settings.logout') }}
        </Button>
      </div>
    </Card>

    <!-- Unlink confirm -->
    <ConfirmDialog
      :show="showUnlinkConfirm"
      :title="t('ui.settings.unlink_confirm')"
      :confirmText="t('ui.settings.unlink')"
      variant="danger"
      @confirm="confirmUnlink"
      @cancel="cancelUnlink"
    />

    <!-- Telegram unlink confirm -->
    <ConfirmDialog
      :show="showTelegramUnlinkConfirm"
      :title="t('ui.settings.telegram_unlink_confirm')"
      :confirmText="t('ui.settings.unlink')"
      variant="danger"
      @confirm="confirmTelegramUnlink"
      @cancel="showTelegramUnlinkConfirm = false"
    />
  </div>
</template>
