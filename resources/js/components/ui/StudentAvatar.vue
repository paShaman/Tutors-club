<script setup lang="ts">
import { computed } from 'vue'
import { Bot, User } from 'lucide-vue-next'
import { cn } from '@/lib/utils'
import { studentColorClass } from '@/lib/studentColors'

const props = withDefaults(defineProps<{
  name?: string
  gender?: string | null
  color?: string | null
  class?: string
}>(), {
  name: '',
  gender: 'boy',
  color: null,
  class: '',
})

const gradient = computed(() => studentColorClass(props.color))
const isGirl = computed(() => props.gender === 'girl')
const isNone = computed(() => props.gender === 'none')
</script>

<template>
  <div
    :class="cn(
      'relative flex shrink-0 select-none items-center justify-center overflow-hidden rounded-full bg-gradient-to-br text-white shadow',
      gradient,
      props.class,
    )"
    role="img"
    :aria-label="name"
  >
    <!-- Пол не выбран (особые группы) -->
    <Bot v-if="isNone" class="h-[52%] w-[52%]" />
    <!-- В lucide нет женского силуэта, поэтому для девочки используем простое платье -->
    <svg
      v-else-if="isGirl"
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      stroke-width="2"
      stroke-linecap="round"
      stroke-linejoin="round"
      class="h-[52%] w-[52%]"
      aria-hidden="true"
    >
      <circle cx="12" cy="6" r="3.6" />
      <path d="M12 11c-1.6 0-2.8.9-3.3 2.2L6.5 21h11l-2.2-7.8C14.8 11.9 13.6 11 12 11z" />
    </svg>
    <User v-else class="h-[52%] w-[52%]" />
  </div>
</template>
