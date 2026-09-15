<script setup lang="ts">
import { computed, ref } from 'vue'
import { Head, usePage, router } from '@inertiajs/vue3'
import {
  UsersRound,
  BadgeCheck,
  GraduationCap,
  CalendarCheck,
  ShieldCheck,
  CreditCard,
  Ban,
} from 'lucide-vue-next'
import AppLayout from '@/Layouts/AppLayout.vue'
import Card from '@/components/ui/Card.vue'
import Table from '@/components/ui/Table.vue'
import TableHeader from '@/components/ui/TableHeader.vue'
import TableBody from '@/components/ui/TableBody.vue'
import TableRow from '@/components/ui/TableRow.vue'
import TableHead from '@/components/ui/TableHead.vue'
import TableCell from '@/components/ui/TableCell.vue'
import UserAvatar from '@/components/ui/UserAvatar.vue'
import IconButton from '@/components/ui/IconButton.vue'
import Checkbox from '@/components/ui/Checkbox.vue'
import AdminSubscriptionPopup from '@/components/popups/AdminSubscriptionPopup.vue'
import type { SubscriptionFormData } from '@/components/popups/AdminSubscriptionPopup.vue'
import ConfirmDialog from '@/components/popups/ConfirmDialog.vue'
import TelegramIcon from '@/components/social/TelegramIcon.vue'
import { useToast } from '@/lib/toast'
import { useI18n } from '@/lib/i18n'
import { cn } from '@/lib/utils'
import type { SharedProps } from '@/types'

defineOptions({ layout: AppLayout })

interface UserRow {
  id: number
  name: string
  email: string
  avatar: string | null
  registered_at: string | null
  is_admin: boolean
  role_titles: string[]
  plan: string
  plan_until: string | null
  plan_period: string | null
  telegram_linked: boolean
  telegram_username: string | null
  students_count: number
  lessons_count: number
  topics_count: number
  last_lesson_at: string | null
}

interface RoleRow {
  title: string
}

interface UsersStats {
  users: number
  paid: number
  students: number
  lessons: number
}

interface UsersPageProps extends SharedProps {
  users: UserRow[]
  stats: UsersStats
  roles: RoleRow[]
  plans: string[]
  periods: string[]
}

const page = usePage<UsersPageProps>()
const { t, intlLocale } = useI18n()
const toast = useToast()

const users = computed(() => page.props.users ?? [])
const stats = computed(() => page.props.stats ?? { users: 0, paid: 0, students: 0, lessons: 0 })
const roles = computed(() => page.props.roles ?? [])
const plans = computed(() => page.props.plans ?? [])
const periods = computed(() => page.props.periods ?? [])

const summary = computed(() => [
  { key: 'users', label: t('ui.users.summary.users'), value: stats.value.users, icon: UsersRound },
  { key: 'paid', label: t('ui.users.summary.paid'), value: stats.value.paid, icon: BadgeCheck },
  { key: 'students', label: t('ui.users.summary.students'), value: stats.value.students, icon: GraduationCap },
  { key: 'lessons', label: t('ui.users.summary.lessons'), value: stats.value.lessons, icon: CalendarCheck },
])

const showSubscription = ref(false)
const subscriptionTarget = ref<UserRow | null>(null)
const subscriptionInitial = computed<SubscriptionFormData | null>(() => {
  const user = subscriptionTarget.value
  if (!user) return null

  return {
    user_id: user.id,
    plan: user.plan,
    period: user.plan_period ?? periods.value[0] ?? 'm',
    expires_at: user.plan_until ?? '',
  }
})

const showCancel = ref(false)
const cancelling = ref<UserRow | null>(null)

function formatDate(value: string | null): string {
  if (!value) return '—'

  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return '—'

  return date.toLocaleDateString(intlLocale.value)
}

function planLabel(plan: string): string {
  return t(`ui.tariff.plans.${plan}`)
}

function roleLabel(title: string): string {
  const key = `ui.users.roles_names.${title}`
  const label = t(key)

  return label === key ? title : label
}

function telegramLabel(user: UserRow): string {
  if (!user.telegram_linked) return t('ui.users.telegram_not_linked')
  return user.telegram_username ? `@${user.telegram_username}` : t('ui.users.telegram_linked')
}

function hasRole(user: UserRow, title: string): boolean {
  return (user.role_titles ?? []).includes(title)
}

function toggleRole(user: UserRow, title: string, checked: boolean): void {
  const next = new Set(user.role_titles ?? [])

  if (checked) next.add(title)
  else next.delete(title)

  router.post('/admin/users/roles', { user_id: user.id, roles: Array.from(next) }, {
    preserveScroll: true,
    onError: () => toast.error(t('error.admin_roles_update')),
  })
}

function openSubscription(user: UserRow): void {
  subscriptionTarget.value = user
  showSubscription.value = true
}

function closeSubscription(): void {
  showSubscription.value = false
  subscriptionTarget.value = null
}

function submitSubscription(data: SubscriptionFormData): void {
  router.post('/admin/users/subscription', data, {
    preserveScroll: true,
    onSuccess: () => closeSubscription(),
    onError: () => toast.error(t('error.admin_subscription')),
  })
}

function askCancelSubscription(user: UserRow): void {
  cancelling.value = user
  showCancel.value = true
}

function confirmCancelSubscription(): void {
  const user = cancelling.value
  showCancel.value = false
  cancelling.value = null

  if (!user) return

  router.post('/admin/users/subscription/cancel', { user_id: user.id }, {
    preserveScroll: true,
    onError: () => toast.error(t('error.admin_subscription_cancel')),
  })
}

function cancelCancelSubscription(): void {
  showCancel.value = false
  cancelling.value = null
}
</script>

<template>
  <Head :title="t('ui.users.title')" />

  <div class="max-w-6xl space-y-6 animate-fade-up">
    <div>
      <h1 class="page-title text-3xl">{{ t('ui.users.title') }}</h1>
      <p class="mt-1 text-sm text-muted-foreground">{{ t('ui.users.subtitle') }}</p>
    </div>

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
      <Card v-for="item in summary" :key="item.key" class="p-4">
        <div class="flex items-center gap-3">
          <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
            <component :is="item.icon" class="h-5 w-5" />
          </div>
          <div class="min-w-0">
            <p class="text-2xl font-semibold leading-tight text-foreground">{{ item.value }}</p>
            <p class="truncate text-xs text-muted-foreground">{{ item.label }}</p>
          </div>
        </div>
      </Card>
    </div>

    <Card v-if="users.length" class="overflow-hidden p-0">
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>{{ t('ui.users.table.user') }}</TableHead>
            <TableHead>{{ t('ui.users.table.roles') }}</TableHead>
            <TableHead>{{ t('ui.users.table.plan') }}</TableHead>
            <TableHead>{{ t('ui.users.table.telegram') }}</TableHead>
            <TableHead>{{ t('ui.users.table.registered') }}</TableHead>
            <TableHead>{{ t('ui.users.table.students') }}</TableHead>
            <TableHead>{{ t('ui.users.table.lessons') }}</TableHead>
            <TableHead>{{ t('ui.users.table.topics') }}</TableHead>
            <TableHead>{{ t('ui.users.table.last_lesson') }}</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow v-for="user in users" :key="user.id">
            <TableCell>
              <div class="flex items-center gap-3">
                <UserAvatar
                  :name="user.name"
                  :src="user.avatar"
                  class="h-9 w-9 shrink-0 bg-gradient-to-br from-primary to-purple-500 text-xs font-semibold text-white shadow"
                />
                <div class="min-w-0">
                  <p class="flex items-center gap-1.5 font-medium text-foreground">
                    <span class="truncate">{{ user.name || user.email }}</span>
                    <ShieldCheck
                      v-if="user.is_admin"
                      class="h-3.5 w-3.5 shrink-0 text-primary"
                      :title="t('ui.users.admin')"
                    />
                  </p>
                  <p class="truncate text-xs text-muted-foreground">{{ user.email }}</p>
                </div>
              </div>
            </TableCell>
            <TableCell>
              <div class="space-y-1.5">
                <Checkbox
                  v-for="role in roles"
                  :key="role.title"
                  :model-value="hasRole(user, role.title)"
                  @update:model-value="toggleRole(user, role.title, $event)"
                >
                  {{ roleLabel(role.title) }}
                </Checkbox>
              </div>
            </TableCell>
            <TableCell>
              <div class="space-y-1.5">
                <div class="flex items-center gap-1.5">
                  <span
                    :class="cn(
                      'pill px-2.5 py-0.5 text-xs font-medium',
                      user.plan === 'free'
                        ? 'bg-muted text-muted-foreground'
                        : 'bg-primary/10 text-primary',
                    )"
                  >
                    {{ planLabel(user.plan) }}
                  </span>
                  <IconButton
                    variant="primary"
                    :title="t('ui.users.subscription.open')"
                    @click="openSubscription(user)"
                  >
                    <CreditCard />
                  </IconButton>
                  <IconButton
                    v-if="user.plan !== 'free'"
                    variant="destructive"
                    :title="t('ui.users.subscription.cancel')"
                    @click="askCancelSubscription(user)"
                  >
                    <Ban />
                  </IconButton>
                </div>
                <p v-if="user.plan !== 'free' && user.plan_until" class="text-xs text-muted-foreground">
                  {{ t('ui.users.until', { date: formatDate(user.plan_until) }) }}
                </p>
              </div>
            </TableCell>
            <TableCell>
              <span
                :class="cn(
                  'pill inline-flex items-center gap-1 px-2.5 py-0.5 text-xs font-medium',
                  user.telegram_linked
                    ? 'bg-[#229ED9]/10 text-[#229ED9]'
                    : 'bg-muted text-muted-foreground',
                )"
                :title="user.telegram_linked ? t('ui.users.telegram_linked') : t('ui.users.telegram_not_linked')"
              >
                <TelegramIcon class="h-3.5 w-3.5" />
                <span class="whitespace-nowrap">{{ telegramLabel(user) }}</span>
              </span>
            </TableCell>
            <TableCell class="whitespace-nowrap text-muted-foreground">{{ formatDate(user.registered_at) }}</TableCell>
            <TableCell class="text-center text-foreground">{{ user.students_count }}</TableCell>
            <TableCell class="text-center text-foreground">{{ user.lessons_count }}</TableCell>
            <TableCell class="text-center text-foreground">{{ user.topics_count }}</TableCell>
            <TableCell class="whitespace-nowrap text-muted-foreground">{{ formatDate(user.last_lesson_at) }}</TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </Card>

    <Card v-else class="p-10 text-center">
      <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-muted">
        <UsersRound class="h-6 w-6 text-muted-foreground" />
      </div>
      <p class="font-medium text-foreground">{{ t('ui.users.empty') }}</p>
    </Card>
  </div>

  <AdminSubscriptionPopup
    :show="showSubscription"
    :initial="subscriptionInitial"
    :plans="plans"
    :periods="periods"
    @close="closeSubscription"
    @submit="submitSubscription"
  />

  <ConfirmDialog
    :show="showCancel"
    :title="t('ui.users.subscription.cancel')"
    :message="t('ui.users.subscription.cancel_confirm')"
    :confirm-text="t('ui.users.subscription.cancel')"
    variant="danger"
    @confirm="confirmCancelSubscription"
    @cancel="cancelCancelSubscription"
  />
</template>
