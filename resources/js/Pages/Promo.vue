<script setup lang="ts">
import { computed, ref } from 'vue'
import { Head, usePage, router } from '@inertiajs/vue3'
import { Plus, Pencil, Trash2, Megaphone, Eye, EyeOff, CalendarDays, Clock } from 'lucide-vue-next'
import AppLayout from '@/Layouts/AppLayout.vue'
import Card from '@/components/ui/Card.vue'
import Button from '@/components/ui/Button.vue'
import IconButton from '@/components/ui/IconButton.vue'
import PromoFormPopup from '@/components/popups/PromoFormPopup.vue'
import type { PromoFormData } from '@/components/popups/PromoFormPopup.vue'
import ConfirmDialog from '@/components/popups/ConfirmDialog.vue'
import { useToast } from '@/lib/toast'
import { useI18n } from '@/lib/i18n'
import { cn } from '@/lib/utils'

defineOptions({ layout: AppLayout })

interface PromoBannerRow {
  id: number
  title: string | null
  message: string
  button_text: string | null
  button_url: string | null
  starts_at: string | null
  ends_at: string | null
  dismiss_days: number
  is_active: boolean
}

const page = usePage<{ banners: PromoBannerRow[] }>()
const { t, tp, intlLocale } = useI18n()
const toast = useToast()

const banners = computed(() => page.props.banners ?? [])

const showForm = ref(false)
const editing = ref<PromoFormData | null>(null)
const showConfirm = ref(false)
const deleting = ref<PromoBannerRow | null>(null)

function openAdd() {
  editing.value = null
  showForm.value = true
}

function openEdit(banner: PromoBannerRow) {
  editing.value = {
    banner_id: banner.id,
    title: banner.title ?? '',
    message: banner.message,
    button_text: banner.button_text ?? '',
    button_url: banner.button_url ?? '',
    starts_at: banner.starts_at ?? '',
    ends_at: banner.ends_at ?? '',
    dismiss_days: banner.dismiss_days,
    is_active: banner.is_active,
  }
  showForm.value = true
}

function closeForm() {
  showForm.value = false
  editing.value = null
}

function submit(data: PromoFormData) {
  const isEdit = data.banner_id !== null
  router.post('/admin/promo/edit', data, {
    preserveScroll: true,
    onSuccess: () => closeForm(),
    onError: () => toast.error(t(isEdit ? 'error.edit_promo' : 'error.add_promo')),
  })
}

function toggleActive(banner: PromoBannerRow) {
  router.post('/admin/promo/toggle', { banner_id: banner.id }, {
    preserveScroll: true,
    onError: () => toast.error(t('error.toggle_promo')),
  })
}

function askDelete(banner: PromoBannerRow) {
  deleting.value = banner
  showConfirm.value = true
}

function confirmDelete() {
  if (!deleting.value) return

  const id = deleting.value.id
  showConfirm.value = false
  router.post('/admin/promo/delete', { banner_id: id }, {
    preserveScroll: true,
    onSuccess: () => { deleting.value = null },
    onError: () => toast.error(t('error.delete_promo')),
  })
}

function cancelDelete() {
  showConfirm.value = false
  deleting.value = null
}

function todayIso(): string {
  const now = new Date()
  const month = String(now.getMonth() + 1).padStart(2, '0')
  const day = String(now.getDate()).padStart(2, '0')
  return `${now.getFullYear()}-${month}-${day}`
}

function isVisibleNow(banner: PromoBannerRow): boolean {
  if (!banner.is_active) return false

  const today = todayIso()
  if (banner.starts_at && banner.starts_at > today) return false
  if (banner.ends_at && banner.ends_at < today) return false

  return true
}

function statusLabel(banner: PromoBannerRow): string {
  if (!banner.is_active) return t('ui.promo.status.hidden')
  return isVisibleNow(banner) ? t('ui.promo.status.visible') : t('ui.promo.status.scheduled')
}

function statusClass(banner: PromoBannerRow): string {
  if (!banner.is_active) return 'bg-muted text-muted-foreground'
  return isVisibleNow(banner) ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'
}

function formatDate(value: string | null): string {
  if (!value) return ''

  const [year, month, day] = value.split('-').map(Number)
  return new Date(year, (month ?? 1) - 1, day ?? 1).toLocaleDateString(intlLocale.value, {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  })
}

function periodLabel(banner: PromoBannerRow): string {
  if (!banner.starts_at && !banner.ends_at) return t('ui.promo.always')

  const from = banner.starts_at ? formatDate(banner.starts_at) : '…'
  const to = banner.ends_at ? formatDate(banner.ends_at) : '…'
  return `${from} — ${to}`
}
</script>

<template>
  <Head :title="t('ui.promo.title')" />

  <div class="max-w-4xl space-y-6 animate-fade-up">
    <div class="page-header">
      <div>
        <h1 class="page-title text-3xl">{{ t('ui.promo.title') }}</h1>
        <p class="mt-1 text-sm text-muted-foreground">{{ t('ui.promo.subtitle') }}</p>
      </div>
      <div class="page-header-actions">
        <Button @click="openAdd">
          <Plus />
          {{ t('ui.promo.add') }}
        </Button>
      </div>
    </div>

    <div v-if="banners.length" class="space-y-3">
      <Card v-for="banner in banners" :key="banner.id" class="p-5">
        <div class="flex items-start gap-4">
          <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
            <Megaphone class="h-5 w-5" />
          </div>

          <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
              <p class="font-semibold text-foreground">
                {{ banner.title || t('ui.promo.untitled') }}
              </p>
              <span :class="cn('pill px-2.5 py-0.5 text-xs font-medium', statusClass(banner))">
                {{ statusLabel(banner) }}
              </span>
            </div>

            <p class="mt-1 text-sm text-foreground/80 break-words">{{ banner.message }}</p>

            <div class="mt-2.5 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-muted-foreground">
              <span class="inline-flex items-center gap-1.5">
                <CalendarDays class="h-3.5 w-3.5" />
                {{ periodLabel(banner) }}
              </span>
              <span class="inline-flex items-center gap-1.5">
                <Clock class="h-3.5 w-3.5" />
                {{ tp('ui.promo.dismiss_days_label', banner.dismiss_days) }}
              </span>
            </div>
          </div>

          <div class="flex shrink-0 items-center gap-1">
            <IconButton
              :title="banner.is_active ? t('ui.promo.actions.hide') : t('ui.promo.actions.show')"
              @click="toggleActive(banner)"
            >
              <EyeOff v-if="banner.is_active" />
              <Eye v-else />
            </IconButton>
            <IconButton :title="t('ui.common.edit')" @click="openEdit(banner)">
              <Pencil />
            </IconButton>
            <IconButton
              variant="destructive"
              :title="t('ui.common.delete')"
              @click="askDelete(banner)"
            >
              <Trash2 />
            </IconButton>
          </div>
        </div>
      </Card>
    </div>

    <Card v-else class="p-10 text-center">
      <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-muted">
        <Megaphone class="h-6 w-6 text-muted-foreground" />
      </div>
      <p class="font-medium text-foreground">{{ t('ui.promo.empty') }}</p>
      <p class="mt-1 text-sm text-muted-foreground">{{ t('ui.promo.empty_hint') }}</p>
    </Card>
  </div>

  <PromoFormPopup
    :show="showForm"
    :initial="editing"
    @close="closeForm"
    @submit="submit"
  />

  <ConfirmDialog
    :show="showConfirm"
    :title="t('ui.common.delete')"
    :message="t('ui.promo.delete_confirm')"
    :confirm-text="t('ui.common.delete')"
    variant="danger"
    @confirm="confirmDelete"
    @cancel="cancelDelete"
  />
</template>
