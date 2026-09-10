import { t } from '@/lib/i18n'

const MAX_FILE_SIZE = 5 * 1024 * 1024

function xsrfToken(): string | null {
  const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]*)/)
  return match ? decodeURIComponent(match[1]) : null
}

/**
 * Upload an avatar image. The server compresses it and returns the public URL.
 * Accepts a raw File or an already-cropped Blob (from the client-side cropper).
 */
export async function uploadAvatar(file: Blob, filename = 'avatar.jpg'): Promise<string> {
  if (file.size > MAX_FILE_SIZE) {
    throw new Error(t('ui.upload.too_large'))
  }

  const formData = new FormData()
  formData.append('avatar', file, filename)

  const headers: Record<string, string> = {
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  }
  const token = xsrfToken()
  if (token) {
    headers['X-XSRF-TOKEN'] = token
  }

  let response: Response
  try {
    response = await fetch('/avatar/upload', {
      method: 'POST',
      headers,
      body: formData,
    })
  } catch {
    throw new Error(t('ui.upload.network'))
  }

  let data: any = null
  try {
    data = await response.json()
  } catch {
    // non-JSON body (CSRF page, 500, etc.)
  }

  if (response.ok && data?.success && data?.data?.avatar) {
    return data.data.avatar
  }

  const raw = data?.data
  if (typeof raw === 'string') {
    throw new Error(raw)
  }
  if (raw && typeof raw === 'object') {
    throw new Error(Object.values(raw).join('\n'))
  }
  if (response.status === 419) {
    throw new Error(t('ui.upload.session_expired'))
  }
  throw new Error(t('ui.upload.failed'))
}
