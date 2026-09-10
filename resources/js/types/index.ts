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
  url: string
}

export interface LocaleOption {
  code: string
  label: string
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
  locale: string
  locales: LocaleOption[]
}

declare module '@inertiajs/vue3' {
  interface PageProps extends SharedProps {}
}
