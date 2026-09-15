declare global {
  interface Window {
    ym?: (id: number, action: string, ...args: unknown[]) => void
    yandexMetrikaId?: number
  }
}

export interface User {
  id: number
  name: string
  email: string
  avatar?: string
  first_name?: string
  last_name?: string
  middle_name?: string
  has_password?: boolean
  is_admin?: boolean
}

export interface SocialProviderConfig {
  key: string
  configured: boolean
  app?: number | null
  redirectUrl?: string | null
}

export interface AgreementDocument {
  label: string
  genitive?: string
  url: string
}

export interface CompanyRequisites {
  name?: string
  inn?: string
  ogrnip?: string
  address?: string
  account?: string
  corr_account?: string
  bik?: string
  email?: string
}

export interface LocaleOption {
  code: string
  label: string
}

export type FeedbackBotKey = 'telegram' | 'max'

export interface FeedbackBotInfo {
  key: FeedbackBotKey
  url: string | null
  configured: boolean
  primary: boolean
}

export interface FeedbackInfo {
  primary: FeedbackBotKey
  bots: FeedbackBotInfo[]
}

export type TariffFeature = 'students' | 'lessons' | 'topics'

export interface TariffInfo {
  plan: string
  is_paid: boolean
  period: 'm' | 'y' | null
  until: string | null
  started_at: string | null
  expired: boolean
  expired_at: string | null
  limits: Record<TariffFeature, number | null>
  usage: Record<TariffFeature, number>
  can: Record<TariffFeature, boolean>
  price_month: number
  price_year: number
}

export interface PromoBannerInfo {
  id: number
  title: string | null
  message: string
  button_text: string | null
  button_url: string | null
  dismiss_days: number
  updated_at: string | null
}

export interface SharedProps {
  [key: string]: unknown
  auth: {
    user: User | null
  }
  flash: {
    success: string | null
    error: string | null
  }
  social: SocialProviderConfig[]
  agreements: AgreementDocument[]
  requisites: CompanyRequisites
  feedback: FeedbackInfo
  tariff: TariffInfo | null
  promoBanners: PromoBannerInfo[]
  locale: string
  locales: LocaleOption[]
  translations: Record<string, unknown>
  adminTranslations?: Record<string, unknown>
}

declare module '@inertiajs/core' {
  interface InertiaConfig {
    sharedPageProps: SharedProps
  }
}
