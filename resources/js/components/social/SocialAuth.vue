<script setup lang="ts">
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import type { SharedProps } from '@/types'
import VkIdAuth from '@/components/social/VkIdAuth.vue'
import YandexAuth from '@/components/social/YandexAuth.vue'

const props = withDefaults(defineProps<{
  mode?: 'login' | 'link'
  register?: boolean
  agreementRequired?: boolean
  agreement?: boolean
}>(), {
  mode: 'login',
  register: false,
  agreementRequired: false,
  agreement: false,
})

const page = usePage<SharedProps>()

const providers = computed(() => (page.props.social ?? []).filter((p) => p.configured))
const visible = computed(() => providers.value.length > 0)
</script>

<template>
  <div v-if="visible" class="space-y-3">
    <template v-for="provider in providers" :key="provider.key">
      <VkIdAuth
        v-if="provider.key === 'vkontakte'"
        :mode="mode"
        :register="register"
        :agreement-required="agreementRequired"
        :agreement="agreement"
      />
      <YandexAuth
        v-else-if="provider.key === 'yandex'"
        :mode="mode"
        :register="register"
        :agreement-required="agreementRequired"
        :agreement="agreement"
      />
    </template>

    <div class="flex items-center gap-3 text-xs uppercase tracking-wide text-muted-foreground">
      <span class="h-px flex-1 bg-border/70"></span>
      или
      <span class="h-px flex-1 bg-border/70"></span>
    </div>
  </div>
</template>
