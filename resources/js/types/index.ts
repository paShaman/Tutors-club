export interface User {
  id: number
  name: string
  email: string
  avatar?: string
  first_name?: string
  last_name?: string
  middle_name?: string
  has_password?: boolean
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
  tariff: TariffInfo | null
  locale: string
  locales: LocaleOption[]
  translations: Record<string, unknown>
}

declare module '@inertiajs/vue3' {
  interface PageProps extends SharedProps {}
}
