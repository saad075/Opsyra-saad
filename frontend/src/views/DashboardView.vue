<script setup lang="ts">
import { NButton, NCard, NProgress } from 'naive-ui'
import { computed, onMounted, ref } from 'vue'
import { api } from '@/api/client'
import type { ApiEnvelope, ChecklistTask } from '@/api/types'
import { isOverdue } from '@/lib/tasks'
import { useAuthStore } from '@/stores/auth'
import { useSetupStore } from '@/stores/setup'

const auth = useAuthStore()
const setup = useSetupStore()

const canSetup = computed(() => auth.can('HRMS-CompanySettings'))
const nextStep = computed(() => setup.status?.steps.find((s) => s.key === setup.status?.next_step))
const percent = computed(() =>
  setup.status ? Math.round((setup.status.completed_count / setup.status.total_count) * 100) : 0,
)

// Open onboarding/offboarding tasks waiting on me.
const tasks = ref<ChecklistTask[]>([])
const overdue = computed(() => tasks.value.filter(isOverdue).length)

onMounted(() => {
  if (canSetup.value) setup.load().catch(() => {})
  api
    .get<ApiEnvelope<ChecklistTask[]>>('/checklists/my-tasks')
    .then(({ data }) => (tasks.value = data.data))
    .catch(() => {})
})
</script>

<template>
  <div class="page">
    <div class="page-header">
      <h1>Welcome, {{ auth.me?.user.name.split(' ')[0] }}</h1>
    </div>

    <!-- Setup banner for admins until setup is complete (Horilla-style). -->
    <NCard v-if="canSetup && setup.status && !setup.status.is_complete" class="setup-banner">
      <div class="banner-body">
        <div class="banner-text">
          <h2>Finish setting up {{ auth.me?.company.name }}</h2>
          <p class="muted">
            {{ setup.status.completed_count }} of {{ setup.status.total_count }} steps done.
            <template v-if="nextStep">Next: {{ nextStep.title }}.</template>
          </p>
          <NProgress type="line" :percentage="percent" :show-indicator="false" :height="8" class="progress" />
        </div>
        <RouterLink :to="{ name: 'setup' }">
          <NButton type="primary">Continue setup</NButton>
        </RouterLink>
      </div>
    </NCard>

    <NCard v-if="tasks.length" class="tasks-card" :class="{ late: overdue }">
      <div class="banner-body">
        <div class="banner-text">
          <h2>You have {{ tasks.length }} open {{ tasks.length === 1 ? 'task' : 'tasks' }}</h2>
          <p class="muted">
            From people joining and leaving.
            <strong v-if="overdue" class="overdue">{{ overdue }} overdue.</strong>
          </p>
        </div>
        <RouterLink :to="{ name: 'tasks' }">
          <NButton :type="overdue ? 'error' : 'primary'" secondary>View my tasks</NButton>
        </RouterLink>
      </div>
    </NCard>

    <NCard v-if="!tasks.length && !(canSetup && setup.status && !setup.status.is_complete)" title="Your workspace">
      <p class="muted">More of Opsyra appears here as each module's screens are built.</p>
    </NCard>
  </div>
</template>

<style scoped>
.setup-banner {
  border-left: 4px solid #0e6e62;
}

.tasks-card {
  margin-top: 16px;
  border-left: 4px solid #0e6e62;
}

.tasks-card.late {
  border-left-color: #a23b45;
}

.overdue {
  color: #a23b45;
}

.banner-body {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
}

.banner-text {
  flex: 1 1 320px;
}

.banner-text p {
  margin: 6px 0 12px;
}

.progress {
  max-width: 420px;
}
</style>
