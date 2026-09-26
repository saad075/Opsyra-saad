<script setup lang="ts">
import { NAlert, NCard, NEmpty, NSkeleton } from 'naive-ui'
import { computed, onMounted, ref } from 'vue'
import { api, errorMessage } from '@/api/client'
import type { ApiEnvelope, ChecklistTask } from '@/api/types'
import TaskRow from '@/components/TaskRow.vue'
import { daysUntil } from '@/lib/tasks'

/**
 * Everyone's to-do list from onboarding and offboarding checklists: tasks
 * assigned to me by name or to a role I hold. Done tasks drop off.
 */
const tasks = ref<ChecklistTask[] | null>(null)
const error = ref('')

async function load() {
  try {
    const { data } = await api.get<ApiEnvelope<ChecklistTask[]>>('/checklists/my-tasks')
    tasks.value = data.data
  } catch (e) {
    error.value = errorMessage(e)
  }
}

const groups = computed(() => {
  const all = tasks.value ?? []
  const when = (t: ChecklistTask) => daysUntil(t.due_date)
  return [
    { key: 'overdue', title: 'Overdue', tasks: all.filter((t) => (when(t) ?? 1) < 0) },
    { key: 'week', title: 'This week', tasks: all.filter((t) => { const d = when(t); return d !== null && d >= 0 && d <= 7 }) },
    { key: 'later', title: 'Later', tasks: all.filter((t) => { const d = when(t); return d === null || d > 7 }) },
  ].filter((g) => g.tasks.length)
})

onMounted(load)
</script>

<template>
  <div class="page">
    <div class="page-header">
      <div>
        <h1>My tasks</h1>
        <p class="muted subtitle">Your part in people joining and leaving.</p>
      </div>
    </div>

    <NAlert v-if="error" type="error" :show-icon="false">{{ error }}</NAlert>
    <NSkeleton v-else-if="!tasks" height="240px" :sharp="false" />
    <NCard v-else-if="!tasks.length">
      <NEmpty description="You’re all caught up — no open tasks." />
    </NCard>

    <NCard v-for="group in groups" :key="group.key" :title="`${group.title} (${group.tasks.length})`" class="group" :class="group.key">
      <TaskRow v-for="t in group.tasks" :key="t.id" :task="t" :can-change="true" show-person @changed="load" />
    </NCard>
  </div>
</template>

<style scoped>
.subtitle {
  margin: 6px 0 0;
}

.group + .group {
  margin-top: 16px;
}

.group.overdue {
  border-left: 4px solid #a23b45;
}
</style>
