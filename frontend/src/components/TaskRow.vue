<script setup lang="ts">
import { EllipsisHorizontal } from '@vicons/ionicons5'
import { NButton, NCheckbox, NDropdown, NIcon, NTag, useMessage } from 'naive-ui'
import { computed, ref } from 'vue'
import { api, errorMessage } from '@/api/client'
import type { ChecklistTask, TaskStatus } from '@/api/types'
import { formatDate } from '@/lib/staff'
import { assigneeLabel, dueLabel, isOpen, isOverdue, TASK_STATUS } from '@/lib/tasks'

/**
 * One checklist task, used on My tasks and on a checklist page. The tick
 * marks it done (or reopens it); the menu has the other states. The API
 * decides who may change it — the controls are just shortcuts.
 */
const props = defineProps<{ task: ChecklistTask; canChange: boolean; showPerson?: boolean; showAssignee?: boolean }>()
const emit = defineEmits<{ changed: [] }>()
const message = useMessage()
const busy = ref(false)

const done = computed(() => props.task.status === 'done' || props.task.status === 'skipped')

const menu = computed(() =>
  [
    props.task.status === 'todo' && { key: 'in_progress', label: 'Mark in progress' },
    isOpen(props.task) && !props.task.is_required && { key: 'skipped', label: 'Skip (optional task)' },
    !isOpen(props.task) && { key: 'todo', label: 'Reopen' },
  ].filter(Boolean) as { key: TaskStatus; label: string }[],
)

async function setStatus(status: TaskStatus) {
  busy.value = true
  try {
    await api.post('/checklists/task/update', { id: props.task.id, status })
    emit('changed')
  } catch (e) {
    message.error(errorMessage(e))
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <div class="task" :class="{ done, overdue: isOverdue(task) }">
    <NCheckbox :checked="done" :disabled="!canChange || busy" size="large" :aria-label="`Mark ${task.title} ${done ? 'not done' : 'done'}`" @update:checked="(v: boolean) => setStatus(v ? 'done' : 'todo')" />

    <div class="body">
      <div class="title">
        <span>{{ task.title }}</span>
        <NTag v-if="!task.is_required" size="tiny" round>Optional</NTag>
        <NTag v-if="task.status === 'in_progress' || task.status === 'skipped'" size="tiny" round :type="TASK_STATUS[task.status].type">{{ TASK_STATUS[task.status].label }}</NTag>
      </div>
      <p v-if="task.description" class="desc">{{ task.description }}</p>
      <p class="meta">
        <template v-if="showPerson && task.checklist">
          <RouterLink :to="{ name: 'checklist', params: { id: task.checklist.id } }" class="person">
            {{ task.checklist.type === 'onboarding' ? 'Onboarding' : 'Offboarding' }} · {{ task.checklist.staff.name }}
          </RouterLink>
          ·
        </template>
        <span v-if="showAssignee">{{ assigneeLabel(task) }} · </span>
        <span :class="{ late: isOverdue(task) }">{{ dueLabel(task) || `Due ${formatDate(task.due_date)}` }}</span>
        <template v-if="task.completed_by && !isOpen(task)"> · by {{ task.completed_by.name }}</template>
      </p>
    </div>

    <NDropdown v-if="canChange && menu.length" :options="menu" trigger="click" @select="setStatus">
      <NButton quaternary circle size="small" :loading="busy" aria-label="More">
        <template #icon><NIcon><EllipsisHorizontal /></NIcon></template>
      </NButton>
    </NDropdown>
  </div>
</template>

<style scoped>
.task {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 12px 0;
  border-top: 1px solid rgba(127, 127, 127, 0.18);
}

.task:first-child {
  border-top: 0;
}

.body {
  flex: 1;
  min-width: 0;
}

.title {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
  font-weight: 600;
}

.done .title span {
  text-decoration: line-through;
  opacity: 0.55;
}

.desc {
  margin: 4px 0 0;
  opacity: 0.75;
}

.meta {
  margin: 4px 0 0;
  font-size: 0.88rem;
  opacity: 0.75;
}

.late {
  color: #a23b45;
  font-weight: 600;
}

.person {
  color: inherit;
  font-weight: 600;
}
</style>
