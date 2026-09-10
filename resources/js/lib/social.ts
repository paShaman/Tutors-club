export interface SocialProviderMeta {
  key: string
  labelKey: string
  badge: string
  badgeClass: string
}

const PROVIDER_META: Record<string, Omit<SocialProviderMeta, 'key'>> = {
  vkontakte: {
    labelKey: 'ui.social.vk_label',
    badge: 'VK',
    badgeClass: 'bg-[#0077FF]/10 text-[#0077FF]',
  },
  yandex: {
    labelKey: 'ui.social.yandex_label',
    badge: 'Я',
    badgeClass: 'bg-[#FC3F1D]/10 text-[#FC3F1D]',
  },
}

export function providerMeta(key: string): SocialProviderMeta {
  const meta = PROVIDER_META[key]
  return meta ? { key, ...meta } : { key, labelKey: key, badge: '', badgeClass: '' }
}
