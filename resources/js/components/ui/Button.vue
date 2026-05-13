<script setup lang="ts">
import { computed, type PropType } from 'vue'
import { cn } from '@/lib/utils'
import { cva, type VariantProps } from 'class-variance-authority'

const buttonVariants = cva(
  'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-xl text-sm font-medium transition-all duration-300 ease-out focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 relative overflow-hidden before:absolute before:inset-0 before:rounded-xl before:opacity-0 before:transition-opacity before:duration-300 before:bg-gradient-to-r before:from-white/0 before:via-white/20 before:to-white/0 before:-skew-x-12 hover:before:opacity-100 active:scale-[0.97]',
  {
    variants: {
      variant: {
        default: 'bg-primary text-primary-foreground shadow hover:shadow-lg hover:shadow-primary/25 hover:-translate-y-0.5 cursor-pointer',
        destructive: 'bg-destructive text-destructive-foreground shadow-sm hover:shadow-md hover:shadow-destructive/25 hover:-translate-y-0.5 cursor-pointer',
        outline: 'border border-input bg-background shadow-sm hover:shadow-md hover:border-foreground/20 hover:-translate-y-0.5 cursor-pointer',
        secondary: 'bg-secondary text-secondary-foreground shadow-sm hover:shadow-md hover:bg-secondary/80 hover:-translate-y-0.5 cursor-pointer',
        ghost: 'hover:bg-accent hover:text-accent-foreground hover:-translate-y-0.5 cursor-pointer',
        link: 'text-primary underline-offset-4 hover:underline hover:text-primary/80 cursor-pointer',
      },
      size: {
        default: 'h-10 px-4 py-2',
        sm: 'h-9 rounded-lg px-3 text-xs',
        lg: 'h-11 rounded-xl px-8',
        icon: 'h-10 w-10',
      },
    },
    defaultVariants: {
      variant: 'default',
      size: 'default',
    },
  },
)

interface Props {
  variant?: VariantProps<typeof buttonVariants>['variant']
  size?: VariantProps<typeof buttonVariants>['size']
  as?: string
  class?: string
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'default',
  size: 'default',
  as: 'button',
})
</script>

<template>
  <component :is="as" :class="cn(buttonVariants({ variant, size }), props.class)">
    <slot />
  </component>
</template>