<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Card from '@/components/ui/Card.vue'
import Button from '@/components/ui/Button.vue'
import TopicFormPopup from '@/components/popups/TopicFormPopup.vue'
import type { TopicFormData } from '@/components/popups/TopicFormPopup.vue'
import ConfirmDialog from '@/components/popups/ConfirmDialog.vue'
import { useToast } from '@/lib/toast'
import { useI18n } from '@/lib/i18n'
import { cn } from '@/lib/utils'
import {
  GripVertical,
  ListTree,
  Pencil,
  Plus,
  Trash2,
} from 'lucide-vue-next'

defineOptions({ layout: AppLayout })

interface TopicNode {
  id: number
  name: string
  position: number
  children: TopicNode[]
}

const page = usePage<{
  subjects: string[]
  selectedSubject: string
  topicTree: TopicNode[]
}>()

const toast = useToast()
const { t, tp } = useI18n()

const subjects = computed(() => page.props.subjects ?? [])
const selectedSubject = computed(() => page.props.selectedSubject ?? subjects.value[0] ?? '')

function cloneNode(node: TopicNode): TopicNode {
  return { ...node, children: (node.children ?? []).map(cloneNode) }
}

const tree = ref<TopicNode[]>([])

watch(() => page.props.topicTree, (val) => {
  tree.value = (val ?? []).map(cloneNode)
}, { immediate: true, deep: false })

const totalCount = computed(() =>
  tree.value.reduce((sum, root) => sum + 1 + (root.children?.length ?? 0), 0),
)

function subjectLabel(key: string): string {
  const label = t(key)
  return label === key ? key : label
}

function goSubject(subject: string): void {
  if (subject === selectedSubject.value) return
  router.get('/planning', { subject }, { preserveScroll: true, preserveState: true })
}

// ─── Form state ─────────────────────────────────────────────
const showTopicForm = ref(false)
const formMode = ref<'add' | 'edit'>('add')
const formParent = ref<{ id: number; name: string } | null>(null)
const formInitial = ref<TopicFormData | null>(null)

function openAddRoot(): void {
  formMode.value = 'add'
  formParent.value = null
  formInitial.value = null
  showTopicForm.value = true
}

function openAddSubtopic(root: TopicNode): void {
  formMode.value = 'add'
  formParent.value = { id: root.id, name: root.name }
  formInitial.value = null
  showTopicForm.value = true
}

function openEdit(node: TopicNode): void {
  formMode.value = 'edit'
  formParent.value = null
  formInitial.value = {
    topic_id: node.id,
    subject: selectedSubject.value,
    parent_id: null,
    name: node.name,
  }
  showTopicForm.value = true
}

function closeForm(): void {
  showTopicForm.value = false
}

function submitTopic(form: TopicFormData): void {
  router.post('/topics/edit', form, {
    preserveScroll: true,
    onSuccess: () => {
      showTopicForm.value = false
    },
    onError: () => {
      toast.error(t('error.add_topic'))
    },
  })
}

// ─── Drag & drop (порядок внутри одного уровня) ─────────────
const drag = ref<{ id: number; parentId: number | null } | null>(null)

function onDragStart(id: number, parentId: number | null): void {
  drag.value = { id, parentId }
}

function onDragEnd(): void {
  drag.value = null
}

function onDrop(targetId: number, parentId: number | null): void {
  if (!drag.value) return

  if (drag.value.parentId !== parentId) {
    drag.value = null
    return
  }

  const list = parentId === null
    ? tree.value
    : tree.value.find((node) => node.id === parentId)?.children

  if (!list) {
    drag.value = null
    return
  }

  const from = list.findIndex((node) => node.id === drag.value!.id)
  const to = list.findIndex((node) => node.id === targetId)
  drag.value = null

  if (from === -1 || to === -1 || from === to) return

  const [moved] = list.splice(from, 1)
  list.splice(to, 0, moved)

  const items = list.map((node, index) => ({ id: node.id, position: index }))

  router.post('/topics/reorder', { items }, { preserveScroll: true, preserveState: true })
}

// ─── Delete ─────────────────────────────────────────────────
const showConfirm = ref(false)
const confirmMessage = ref('')
let confirmAction: (() => void) | null = null

function askDelete(node: TopicNode, isSubtopic: boolean): void {
  confirmMessage.value = t(
    isSubtopic ? 'ui.planning.delete_subtopic_confirm' : 'ui.planning.delete_topic_confirm',
  )

  confirmAction = () => {
    router.post('/topics/delete', { topic_id: node.id }, {
      preserveScroll: true,
      preserveState: true,
      onError: () => {
        toast.error(t('error.delete_topic'))
      },
    })
  }

  showConfirm.value = true
}

function onConfirm(): void {
  showConfirm.value = false
  confirmAction?.()
  confirmAction = null
}

function onCancel(): void {
  showConfirm.value = false
  confirmAction = null
}
</script>

<template>
  <Head :title="t('ui.planning.title')" />

  <div class="space-y-6 animate-fade-up">
    <div class="flex flex-wrap items-start justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-foreground">{{ t('ui.planning.title') }}</h1>
        <p class="mt-1 text-sm text-muted-foreground">{{ t('ui.planning.subtitle') }}</p>
      </div>
      <Button @click="openAddRoot">
        <Plus class="h-4 w-4" />
        {{ t('ui.planning.add_topic') }}
      </Button>
    </div>

    <!-- Subject tabs -->
    <div class="flex flex-wrap gap-2">
      <button
        v-for="subject in subjects"
        :key="subject"
        :class="cn(
          'rounded-xl border px-4 py-2 text-sm font-medium transition-colors cursor-pointer',
          subject === selectedSubject
            ? 'border-primary bg-primary text-primary-foreground shadow-sm'
            : 'border-border bg-white/60 text-muted-foreground hover:bg-accent hover:text-foreground',
        )"
        @click="goSubject(subject)"
      >
        {{ subjectLabel(subject) }}
      </button>
    </div>

    <p v-if="tree.length" class="text-sm text-muted-foreground">
      {{ tp('ui.planning.topics_count', totalCount) }}
    </p>

    <!-- Empty state -->
    <Card v-if="!tree.length" class="p-12 text-center">
      <ListTree class="mx-auto h-12 w-12 text-muted-foreground/30" />
      <p class="mt-4 font-medium text-foreground">{{ t('ui.planning.empty') }}</p>
      <p class="mt-1 text-sm text-muted-foreground">{{ t('ui.planning.empty_hint') }}</p>
      <Button class="mt-4" @click="openAddRoot">
        <Plus class="h-4 w-4" />
        {{ t('ui.planning.add_topic') }}
      </Button>
    </Card>

    <!-- Tree -->
    <div v-else class="space-y-3">
      <Card
        v-for="root in tree"
        :key="root.id"
        :draggable="true"
        :class="cn('p-3 transition-opacity', drag?.id === root.id && 'opacity-50')"
        @dragstart="onDragStart(root.id, null)"
        @dragend="onDragEnd"
        @dragover.prevent
        @drop="onDrop(root.id, null)"
      >
        <div class="flex items-center gap-2">
          <GripVertical class="h-5 w-5 shrink-0 cursor-grab text-muted-foreground/50" />
          <p class="min-w-0 flex-1 truncate font-semibold text-foreground">{{ root.name }}</p>
          <span class="hidden text-xs text-muted-foreground sm:inline">{{ root.children.length }}</span>
          <Button variant="ghost" size="sm" @click="openAddSubtopic(root)">
            <Plus class="h-4 w-4" />
            <span class="hidden sm:inline">{{ t('ui.planning.add_subtopic') }}</span>
          </Button>
          <Button variant="ghost" size="sm" @click="openEdit(root)">
            <Pencil class="h-4 w-4" />
          </Button>
          <Button variant="ghost" size="sm" @click="askDelete(root, false)">
            <Trash2 class="h-4 w-4 text-destructive" />
          </Button>
        </div>

        <div v-if="root.children.length" class="mt-2 ml-6 space-y-1.5">
          <div
            v-for="child in root.children"
            :key="child.id"
            :draggable="true"
            :class="cn(
              'flex items-center gap-2 rounded-xl border border-border/70 bg-white/50 px-3 py-2',
              drag?.id === child.id && 'opacity-50',
            )"
            @dragstart.stop="onDragStart(child.id, root.id)"
            @dragend="onDragEnd"
            @dragover.prevent
            @drop.stop="onDrop(child.id, root.id)"
          >
            <GripVertical class="h-4 w-4 shrink-0 cursor-grab text-muted-foreground/50" />
            <span class="min-w-0 flex-1 truncate text-sm text-foreground">{{ child.name }}</span>
            <Button variant="ghost" size="sm" @click="openEdit(child)">
              <Pencil class="h-4 w-4" />
            </Button>
            <Button variant="ghost" size="sm" @click="askDelete(child, true)">
              <Trash2 class="h-4 w-4 text-destructive" />
            </Button>
          </div>
        </div>
      </Card>
    </div>
  </div>

  <TopicFormPopup
    :show="showTopicForm"
    :mode="formMode"
    :subject="selectedSubject"
    :parent="formParent"
    :initial="formInitial"
    @close="closeForm"
    @submit="submitTopic"
  />

  <ConfirmDialog
    :show="showConfirm"
    :title="confirmMessage"
    variant="danger"
    @confirm="onConfirm"
    @cancel="onCancel"
  />
</template>
