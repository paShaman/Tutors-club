<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { cn } from '@/lib/utils'

const props = withDefaults(defineProps<{
  name?: string
  src?: string | null
  class?: string
}>(), {
  name: '',
  src: null,
  class: '',
})

const failed = ref(false)

watch(() => props.src, () => {
  failed.value = false
})

const letter = computed(() => (props.name || '?').trim().charAt(0).toUpperCase() || '?')
</script>

<template>
  <div
    :class="cn(
      'relative flex shrink-0 select-none items-center justify-center overflow-hidden rounded-full',
      props.class,
    )"
  >
    <img
      v-if="src && !failed"
      :src="src"
      :alt="name"
      loading="lazy"
      class="absolute inset-0 h-full w-full object-cover"
      @error="failed = true"
    />
    <span v-else>{{ letter }}</span>
  </div>
</template>
