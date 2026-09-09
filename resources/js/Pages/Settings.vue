<script setup lang="ts">
import { Head, router, usePage, useForm } from '@inertiajs/vue3'
import { Settings, User, Shield, Save, Link2, Unlink } from 'lucide-vue-next'
import Button from '@/components/ui/Button.vue'
import Card from '@/components/ui/Card.vue'
import CardHeader from '@/components/ui/CardHeader.vue'
import CardTitle from '@/components/ui/CardTitle.vue'
import { computed, ref, watch } from 'vue'
import type { SharedProps } from '@/types'
import AppLayout from '@/Layouts/AppLayout.vue'
import AvatarPicker from '@/components/ui/AvatarPicker.vue'
import VkIdAuth from '@/components/social/VkIdAuth.vue'
import ConfirmDialog from '@/components/popups/ConfirmDialog.vue'

interface SocialBinding {
  provider: string
  social_id: string
  created_at: string | null
}

interface SettingsPageProps extends SharedProps {
  socials: SocialBinding[]
}

const SOCIAL_LABELS: Record<string, string> = {
  vkontakte: 'VK ID',
  facebook: 'Facebook',
  google: 'Google',
}

defineOptions({ layout: AppLayout })

const page = usePage<SettingsPageProps>()

const user = computed(() => page.props.auth?.user ?? null)
const socials = computed(() => page.props.socials ?? [])
const vkBinding = computed(() => socials.value.find((s) => s.provider === 'vkontakte') ?? null)
const otherBindings = computed(() => socials.value.filter((s) => s.provider !== 'vkontakte'))
const vkConfigured = computed(() => Boolean(page.props.vkid?.app))

const form = useForm({
  last_name: user.value?.last_name ?? '',
  first_name: user.value?.first_name ?? '',
  middle_name: user.value?.middle_name ?? '',
  avatar: user.value?.avatar ?? null,
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

function socialLabel(provider: string): string {
  return SOCIAL_LABELS[provider] ?? provider
}
</script>

<template>
  <Head title="Настройки" />

  <div class="max-w-2xl space-y-6 animate-fade-up">
    <!-- Page header -->
    <div>
      <h1 class="text-3xl font-bold tracking-tight text-foreground">
        Настройки
      </h1>
      <p class="mt-1 text-muted-foreground">
        Управление аккаунтом и уведомлениями
      </p>
    </div>

    <!-- Error flash -->
    <div
      v-if="page.props.flash?.error"
      class="rounded-xl bg-destructive/10 px-4 py-3 text-sm text-destructive border border-destructive/20"
    >
      {{ page.props.flash.error }}
    </div>

    <!-- Success flash -->
    <div
      v-if="page.props.flash?.success"
      class="rounded-xl bg-emerald-500/10 px-4 py-3 text-sm text-emerald-700 border border-emerald-500/20"
    >
      {{ page.props.flash.success }}
    </div>

    <!-- Profile section -->
    <Card>
      <CardHeader>
        <div class="flex items-center gap-3">
          <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10">
            <User class="h-5 w-5 text-primary" />
          </div>
          <div>
            <CardTitle>Профиль</CardTitle>
            <p class="text-sm text-muted-foreground">Редактирование личных данных</p>
          </div>
        </div>
      </CardHeader>

      <form class="px-6 pb-6 space-y-4" @submit.prevent="submit">
        <!-- Фото профиля -->
        <div class="border-b border-border/60 pb-5">
          <p class="block text-sm font-medium text-foreground mb-3">
            Фотография профиля
          </p>
          <AvatarPicker
            v-model="form.avatar"
            :name="`${form.last_name ?? ''} ${form.first_name ?? ''} ${form.middle_name ?? ''}`"
            :show-remove="!!user?.avatar || !!form.avatar"
          />
        </div>

        <!-- Фамилия -->
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

        <!-- Имя -->
        <div>
          <label for="first_name" class="block text-sm font-medium text-foreground mb-1.5">
            Имя
          </label>
          <input
            id="first_name"
            v-model="form.first_name"
            type="text"
            autocomplete="given-name"
            class="w-full rounded-xl border border-border bg-white/50 px-3.5 py-2.5 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
            placeholder="Иван"
          />
          <p v-if="form.errors.first_name" class="mt-1 text-xs text-destructive">{{ form.errors.first_name }}</p>
        </div>

        <!-- Отчество -->
        <div>
          <label for="middle_name" class="block text-sm font-medium text-foreground mb-1.5">
            Отчество
          </label>
          <input
            id="middle_name"
            v-model="form.middle_name"
            type="text"
            autocomplete="additional-name"
            class="w-full rounded-xl border border-border bg-white/50 px-3.5 py-2.5 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
            placeholder="Иванович"
          />
          <p v-if="form.errors.middle_name" class="mt-1 text-xs text-destructive">{{ form.errors.middle_name }}</p>
        </div>

        <!-- Email (readonly) -->
        <div>
          <label for="email" class="block text-sm font-medium text-muted-foreground mb-1.5">
            Email (не редактируется)
          </label>
          <p class="text-foreground">{{ user?.email ?? '—' }}</p>
        </div>

        <!-- Save button -->
        <div class="pt-2">
          <Button type="submit" :disabled="form.processing">
            <Save class="h-4 w-4" />
            {{ form.processing ? 'Сохранение...' : 'Сохранить изменения' }}
          </Button>
        </div>
      </form>
    </Card>

    <!-- Social section -->
    <Card>
      <CardHeader>
        <div class="flex items-center gap-3">
          <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-sky-500/10">
            <Link2 class="h-5 w-5 text-sky-500" />
          </div>
          <div>
            <CardTitle>Соцсети</CardTitle>
            <p class="text-sm text-muted-foreground">Вход в аккаунт через VK ID</p>
          </div>
        </div>
      </CardHeader>

      <div class="px-6 pb-6 space-y-3">
        <!-- VK ID binding -->
        <div class="flex items-center justify-between gap-4 rounded-xl border border-border/60 bg-white/40 px-4 py-3">
          <div class="flex items-center gap-3">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#0077FF]/10">
              <span class="text-sm font-bold text-[#0077FF]">VK</span>
            </div>
            <div>
              <p class="text-sm font-medium text-foreground">VK ID</p>
              <p v-if="vkBinding" class="text-xs text-muted-foreground">
                Привязан (id: {{ vkBinding.social_id }})
              </p>
              <p v-else class="text-xs text-muted-foreground">
                {{ vkConfigured ? 'Не привязан' : 'Не настроено' }}
              </p>
            </div>
          </div>
          <Button
            v-if="vkBinding"
            variant="outline"
            size="sm"
            class="text-destructive hover:text-destructive"
            @click="requestUnlink('vkontakte')"
          >
            <Unlink class="h-4 w-4" />
            Отвязать
          </Button>
        </div>

        <!-- Link VK widget -->
        <VkIdAuth v-if="!vkBinding && vkConfigured" mode="link" />

        <!-- Other bound socials -->
        <div
          v-for="binding in otherBindings"
          :key="binding.provider"
          class="flex items-center justify-between gap-4 rounded-xl border border-border/60 bg-white/40 px-4 py-3"
        >
          <div>
            <p class="text-sm font-medium text-foreground">{{ socialLabel(binding.provider) }}</p>
            <p class="text-xs text-muted-foreground">Привязан (id: {{ binding.social_id }})</p>
          </div>
          <Button
            variant="outline"
            size="sm"
            class="text-destructive hover:text-destructive"
            @click="requestUnlink(binding.provider)"
          >
            <Unlink class="h-4 w-4" />
            Отвязать
          </Button>
        </div>
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
            <CardTitle>Безопасность</CardTitle>
            <p class="text-sm text-muted-foreground">Управление доступом к аккаунту</p>
          </div>
        </div>
      </CardHeader>
      <div class="px-6 pb-6">
        <Button variant="outline" @click="logout">
          Выйти из аккаунта
        </Button>
      </div>
    </Card>

    <!-- Unlink confirm -->
    <ConfirmDialog
      :show="showUnlinkConfirm"
      title="Отвязать соцсеть?"
      confirmText="Отвязать"
      variant="danger"
      @confirm="confirmUnlink"
      @cancel="cancelUnlink"
    />
  </div>
</template>
