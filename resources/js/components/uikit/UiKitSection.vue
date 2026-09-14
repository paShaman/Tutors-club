<script setup lang="ts">
import Card from '@/components/ui/Card.vue'
import CodeBlock from './CodeBlock.vue'
import { useI18n } from '@/lib/i18n'

const { t } = useI18n()

const props = defineProps<{
  title: string
  hint?: string
  code?: string
}>()
</script>

<template>
  <section class="space-y-3">
    <div>
      <h2 class="text-xl font-semibold tracking-tight text-foreground">{{ props.title }}</h2>
      <p v-if="props.hint" class="mt-1 max-w-3xl text-sm text-muted-foreground">{{ props.hint }}</p>
    </div>

    <Card class="space-y-5 p-6">
      <div>
        <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
          {{ t('ui.uikit.demo') }}
        </p>
        <slot />
      </div>

      <div v-if="props.code">
        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
          {{ t('ui.uikit.code') }}
        </p>
        <CodeBlock :code="props.code" />
      </div>
    </Card>
  </section>
</template>
