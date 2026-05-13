<script setup lang="ts">
import { Head, router, usePage, useForm } from '@inertiajs/vue3'
import { Settings, User, Bell, Shield, Save } from 'lucide-vue-next'
import Button from '@/components/ui/Button.vue'
import Card from '@/components/ui/Card.vue'
import CardHeader from '@/components/ui/CardHeader.vue'
import CardTitle from '@/components/ui/CardTitle.vue'
import { computed, watch } from 'vue'
import type { SharedProps } from '@/types'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const page = usePage<SharedProps>()

const user = computed(() => page.props.auth?.user ?? null)

const form = useForm({
  last_name: user.value?.last_name ?? '',
  first_name: user.value?.first_name ?? '',
  middle_name: user.value?.middle_name ?? '',
})

// Sync form fields when user data arrives asynchronously
watch(user, (u) => {
  if (u) {
    form.last_name = u.last_name ?? ''
    form.first_name = u.first_name ?? ''
    form.middle_name = u.middle_name ?? ''
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
  </div>
</template>