<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { usePage, Link, router } from '@inertiajs/vue3'
import type { SharedProps } from '@/types'
import {
  LayoutDashboard,
  Calendar,
  Users,
  GraduationCap,
  ListTree,
  Settings,
  LogOut,
  Menu,
  X,
  ChevronDown,
  GitCommit,
  AlertTriangle,
} from 'lucide-vue-next'
import Button from '@/components/ui/Button.vue'
import UserAvatar from '@/components/ui/UserAvatar.vue'
import ChangelogModal from '@/components/popups/ChangelogModal.vue'
import RequisitesModal from '@/components/popups/RequisitesModal.vue'
import { cn } from '@/lib/utils'
import { useI18n } from '@/lib/i18n'

const { t, intlLocale } = useI18n()

const page = usePage<SharedProps>()

const sidebarOpen = ref(false)
const userMenuOpen = ref(false)
const userMenuRef = ref<HTMLElement | null>(null)
const changelogOpen = ref(false)
const requisitesOpen = ref(false)

// Compact sidebar (1024px – 1199px)
const sidebarCompact = ref(false)

function onSidebarResize() {
  sidebarCompact.value = window.innerWidth >= 1024 && window.innerWidth < 1280
}

interface NavItem {
  labelKey: string
  href: string
  icon: any
  activeRoute: string
}

const navItems: NavItem[] = [
  {
    labelKey: 'ui.nav.dashboard',
    href: '/',
    icon: LayoutDashboard,
    activeRoute: 'home',
  },
  {
    labelKey: 'ui.nav.calendar',
    href: '/calendar',
    icon: Calendar,
    activeRoute: 'calendar',
  },
  {
    labelKey: 'ui.nav.students',
    href: '/students',
    icon: GraduationCap,
    activeRoute: 'students',
  },
  {
    labelKey: 'ui.nav.lessons',
    href: '/lessons',
    icon: Users,
    activeRoute: 'lessons',
  },
  {
    labelKey: 'ui.nav.planning',
    href: '/planning',
    icon: ListTree,
    activeRoute: 'planning',
  },
]

function isActive(route: string): boolean {
  const currentRoute = page.url
  if (route === 'home') return currentRoute === '/'
  return currentRoute.startsWith(`/${route}`)
}

const user = computed(() => page.props.auth?.user ?? null)
const tariff = computed(() => page.props.tariff ?? null)
const agreements = computed(() => page.props.agreements ?? [])
const requisites = computed(() => page.props.requisites ?? {})

// Уведомление об истёкшем тарифе: закрытие запоминаем в localStorage,
// чтобы не показывать его снова, пока тариф не обновится (меняется expired_at).
const TARIFF_NOTICE_STORAGE_PREFIX = 'tariff-expired-notice:'
const dismissedTariffNotice = ref<string | null>(null)

function readDismissedTariffNotice(): void {
  if (typeof window === 'undefined') return

  const key = TARIFF_NOTICE_STORAGE_PREFIX + (user.value?.id ?? 'guest')

  try {
    dismissedTariffNotice.value = window.localStorage.getItem(key)
  } catch {
    dismissedTariffNotice.value = null
  }
}

function dismissTariffNotice(): void {
  const expiredAt = tariff.value?.expired_at
  if (!expiredAt) return

  dismissedTariffNotice.value = expiredAt

  const key = TARIFF_NOTICE_STORAGE_PREFIX + (user.value?.id ?? 'guest')

  try {
    window.localStorage.setItem(key, expiredAt)
  } catch {
    // localStorage может быть недоступен — не критично
  }
}

watch(() => user.value?.id, readDismissedTariffNotice, { immediate: true })

// В настройках уведомление всегда видно в блоке тарифа и не закрывается
const showTariffNotice = computed(() => {
  const info = tariff.value
  if (!info?.expired || !info.expired_at) return false
  if (page.url.startsWith('/settings')) return false
  return dismissedTariffNotice.value !== info.expired_at
})

function logout(): void {
  router.visit('/logout', { method: 'get' })
}

// Форматированная текущая дата
const todayDate = computed(() => {
  const now = new Date()
  const formatted = now.toLocaleDateString(intlLocale.value, {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    weekday: 'long',
  })
  return formatted.charAt(0).toUpperCase() + formatted.slice(1)
})

// Close mobile sidebar on page navigation
watch(() => page.url, () => {
  sidebarOpen.value = false
  userMenuOpen.value = false
})

// Закрытие dropdown по клику снаружи
function handleClickOutside(e: MouseEvent) {
  if (userMenuRef.value && !userMenuRef.value.contains(e.target as Node)) {
    userMenuOpen.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
  window.addEventListener('resize', onSidebarResize)
  onSidebarResize()
})
onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
  window.removeEventListener('resize', onSidebarResize)
})
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-50 via-purple-50/20 to-white">
    <!-- Mobile overlay (below 1024px) -->
    <div
      v-if="sidebarOpen"
      class="fixed inset-0 z-40 bg-black/20 backdrop-blur-sm transition-opacity"
      @click="sidebarOpen = false"
    />

    <!-- Sidebar -->
    <aside
      :class="cn(
        'fixed inset-y-0 left-0 z-50 flex flex-col transition-all duration-300',
        sidebarOpen
          ? 'w-64 translate-x-0'
          : (sidebarCompact ? 'w-16 lg:translate-x-0' : 'w-64 -translate-x-full lg:translate-x-0'),
      )"
    >
      <div class="glass flex h-full flex-col border-r border-white/20 px-3 py-4">
        <!-- Logo -->
        <div class="flex items-center px-2 mb-8" :class="(sidebarCompact && !sidebarOpen) ? 'justify-center' : 'justify-between'">
          <Link href="/" class="flex items-center gap-2.5">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-primary shadow-lg shadow-primary/25">
              <GraduationCap class="h-5 w-5 text-primary-foreground" />
            </div>
            <span
              :class="cn(
                'text-lg font-semibold tracking-tight text-foreground whitespace-nowrap transition-opacity duration-200',
                (sidebarCompact && !sidebarOpen) ? 'hidden' : 'opacity-100',
              )"
            >
              Tutors Club
            </span>
          </Link>
          <button
            v-if="sidebarOpen || !sidebarCompact"
            class="rounded-lg p-1.5 text-muted-foreground hover:bg-accent hover:text-foreground lg:hidden cursor-pointer"
            @click="sidebarOpen = false"
          >
            <X class="h-5 w-5" />
          </button>
        </div>

        <!-- Main nav -->
        <nav class="flex-1 space-y-0.5">
          <Link
            v-for="item in navItems"
            :key="item.href"
            :href="item.href"
            :class="cn(
              'group relative flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-200',
              sidebarCompact && !sidebarOpen && 'justify-center px-2',
              isActive(item.activeRoute)
                ? 'bg-primary/10 text-primary'
                : 'text-muted-foreground hover:bg-accent hover:text-foreground cursor-pointer',
            )"
          >
            <component :is="item.icon" class="h-5 w-5 shrink-0" />
            <span
              :class="cn(
                'whitespace-nowrap transition-opacity duration-200',
                (sidebarCompact && !sidebarOpen) ? 'hidden' : 'opacity-100',
              )"
            >
              {{ t(item.labelKey) }}
            </span>
            <!-- Tooltip в компактном режиме -->
            <span
              v-if="sidebarCompact && !sidebarOpen"
              class="pointer-events-none absolute left-full ml-3 z-100 rounded-lg bg-gray-900 px-2.5 py-1.5 text-xs font-medium text-white whitespace-nowrap opacity-0 transition-opacity duration-150 group-hover:opacity-100 shadow-lg"
            >
              {{ t(item.labelKey) }}
            </span>
          </Link>
        </nav>

        <!-- Changelog button -->
        <div class="mt-auto pt-3 px-2 space-y-2">
          <button
            v-if="!sidebarCompact || sidebarOpen"
            class="flex items-center gap-2 rounded-xl px-2.5 py-2 text-xs font-medium text-muted-foreground hover:bg-accent hover:text-foreground transition-colors cursor-pointer w-full"
            @click="changelogOpen = true"
          >
            <GitCommit class="h-3.5 w-3.5" />
            <span class="whitespace-nowrap">{{ t('ui.changelog.title') }}</span>
          </button>
          <div
            v-if="(!sidebarCompact || sidebarOpen) && (agreements.length || requisites.name)"
            class="space-y-1"
          >
            <a
              v-for="(doc, index) in agreements"
              :key="index"
              :href="doc.url"
              target="_blank"
              rel="noopener"
              class="block text-xs leading-snug text-muted-foreground/60 hover:text-primary hover:underline transition-colors"
            >
              {{ t(doc.label) }}
            </a>
            <button
              v-if="requisites.name"
              type="button"
              class="block text-left text-xs leading-snug text-muted-foreground/60 hover:text-primary hover:underline transition-colors cursor-pointer"
              @click="requisitesOpen = true"
            >
              {{ t('ui.requisites.title') }}
            </button>
          </div>
          <p
            :class="cn(
              'text-xs text-muted-foreground/60 whitespace-nowrap transition-opacity duration-200',
              (sidebarCompact && !sidebarOpen) ? 'opacity-0' : 'opacity-100',
            )"
          >
            © {{ new Date().getFullYear() }} Tutors Club
          </p>
        </div>
      </div>
    </aside>

    <!-- Main content -->
    <div
      :class="cn(
        'transition-all duration-300',
        sidebarCompact ? 'lg:pl-16' : 'lg:pl-64',
      )"
    >
      <!-- Top bar -->
      <header class="sticky top-0 z-30 flex h-16 items-center gap-4 border-b border-border/50 bg-white/60 backdrop-blur-xl px-4 sm:px-6">
        <!-- Иконка проекта в шапке (только мобилка, без названия) -->
        <Link
          href="/"
          class="lg:hidden flex items-center shrink-0"
        >
          <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-primary shadow-sm shadow-primary/25">
            <GraduationCap class="h-4.5 w-4.5 text-primary-foreground" />
          </div>
        </Link>

        <button
          :class="cn(
            'rounded-lg p-1.5 text-muted-foreground hover:bg-accent hover:text-foreground cursor-pointer',
            sidebarCompact ? '' : 'lg:hidden',
          )"
          @click="sidebarOpen = true"
        >
          <Menu class="h-6 w-6" />
        </button>

        <!-- Текущая дата -->
        <p class="hidden sm:block text-sm text-muted-foreground">
          {{ todayDate }}
        </p>

        <div class="flex-1" />

        <!-- User menu -->
        <div v-if="user" ref="userMenuRef" class="relative">
          <button
            class="flex items-center gap-2.5 rounded-xl px-2.5 py-1.5 text-sm transition-colors hover:bg-accent cursor-pointer"
            @click="userMenuOpen = !userMenuOpen"
          >
            <UserAvatar
              :name="`${user.last_name ?? ''} ${user.first_name ?? ''}`"
              :src="user.avatar ?? null"
              class="h-8 w-8 bg-gradient-to-br from-primary to-purple-500 text-xs font-semibold text-white shadow"
            />
            <span class="hidden sm:inline font-medium text-foreground">
              {{ user.last_name }} {{ user.first_name }}
            </span>
            <ChevronDown
              :class="cn(
                'h-4 w-4 text-muted-foreground transition-transform duration-200',
                userMenuOpen && 'rotate-180',
              )"
            />
          </button>

          <!-- Dropdown -->
          <Transition name="dropdown">
            <div
              v-if="userMenuOpen"
              class="absolute right-0 top-full mt-2 w-48 rounded-xl border border-border bg-white shadow-lg ring-1 ring-black/5 py-1 z-50"
            >
              <Link
                href="/settings"
                class="flex items-center gap-3 px-4 py-2.5 text-sm text-muted-foreground hover:bg-accent hover:text-foreground transition-colors cursor-pointer"
                @click="userMenuOpen = false"
              >
                <Settings class="h-4 w-4" />
                {{ t('ui.nav.settings') }}
              </Link>
              <button
                class="flex w-full items-center gap-3 px-4 py-2.5 text-sm text-muted-foreground hover:bg-destructive/10 hover:text-destructive transition-colors cursor-pointer"
                @click="logout"
              >
                <LogOut class="h-4 w-4" />
                {{ t('ui.logout') }}
              </button>
            </div>
          </Transition>
        </div>
      </header>

      <!-- Page content -->
      <main class="p-6">
        <div
          v-if="showTariffNotice"
          class="mb-4 flex items-start gap-3 rounded-xl border border-amber-200/60 bg-amber-50/60 px-4 py-3 text-sm text-amber-700"
        >
          <AlertTriangle class="mt-0.5 h-4 w-4 shrink-0" />
          <div class="flex-1">
            <p class="font-medium">{{ t('ui.tariff.expired.title') }}</p>
            <p class="text-amber-700/80">{{ t('ui.tariff.expired.text', { date: tariff?.expired_at ?? '' }) }}</p>
          </div>
          <button
            type="button"
            class="-mr-1 shrink-0 rounded-lg p-1 opacity-60 transition-opacity hover:opacity-100 cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-current"
            :aria-label="t('ui.toast.close')"
            @click="dismissTariffNotice"
          >
            <X class="h-4 w-4" />
          </button>
        </div>
        <slot />
      </main>
    </div>

    <!-- Changelog Modal -->
    <ChangelogModal :show="changelogOpen" @close="changelogOpen = false" />

    <!-- Requisites Modal -->
    <RequisitesModal :show="requisitesOpen" @close="requisitesOpen = false" />
  </div>
</template>
