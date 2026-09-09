export interface User {
  id: number
  name: string
  email: string
  avatar?: string
  first_name?: string
  last_name?: string
  middle_name?: string
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
  vkid: {
    app: number | null
    redirectUrl: string | null
  } | null
}

declare module '@inertiajs/vue3' {
  interface PageProps extends SharedProps {}
}
