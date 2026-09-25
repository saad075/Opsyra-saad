<script setup lang="ts">
import { CheckmarkCircle, EllipseOutline, PlayForwardCircleOutline } from '@vicons/ionicons5'
import { NAlert, NButton, NCard, NIcon, NProgress, NSkeleton, NSpace, NTag, useMessage } from 'naive-ui'
import { computed, onMounted, ref } from 'vue'
import { errorMessage } from '@/api/client'
import type { SetupStep } from '@/api/types'
import { setupStepRoutes } from '@/lib/setupSteps'
import { useSetupStore } from '@/stores/setup'

const setup = useSetupStore()
const message = useMessage()
const loadError = ref('')
const busyKey = ref<string | null>(null)

const groups = computed(() => {
  const byGroup = new Map<string, { label: string; steps: SetupStep[] }>()
  for (const step of setup.status?.steps ?? []) {
    if (!byGroup.has(step.group)) byGroup.set(step.group, { label: step.group_label, steps: [] })
    byGroup.get(step.group)!.steps.push(step)
  }
  return [...byGroup.values()]
})

const percent = computed(() =>
  setup.status ? Math.round((setup.status.completed_count / setup.status.total_count) * 100) : 0,
)

async function act(action: 'skip' | 'mark-reviewed' | 'reset', step: SetupStep) {
  busyKey.value = step.key
  try {
    await setup.act(action, step.key)
  } catch (e) {
    message.error(errorMessage(e))
  } finally {
    busyKey.value = null
  }
}

onMounted(async () => {
  try {
    await setup.load()
  } catch (e) {
    loadError.value = errorMessage(e)
  }
})
</script>

<template>
  <div class="page">
    <div class="page-header">
      <div>
        <h1>Set up your company</h1>
        <p class="muted subtitle">Work through these in any order. Skip anything and come back to it later.</p>
      </div>
    </div>

    <NAlert v-if="loadError" type="error" :show-icon="false">{{ loadError }}</NAlert>

    <template v-else-if="!setup.status">
      <NSkeleton height="84px" :sharp="false" />
      <NSkeleton height="320px" :sharp="false" class="gap" />
    </template>

    <template v-else>
      <NCard class="progress-card">
        <div class="progress-row">
          <strong>{{ setup.status.completed_count }} of {{ setup.status.total_count }} steps done</strong>
          <NTag v-if="setup.status.is_complete" type="success" round>Setup complete</NTag>
        </div>
        <NProgress type="line" :percentage="percent" :height="10" :show-indicator="false" />
      </NCard>

      <section v-for="group in groups" :key="group.label" class="group">
        <h2>{{ group.label }}</h2>

        <NCard
          v-for="step in group.steps"
          :key="step.key"
          size="small"
          class="step"
          :class="{ next: step.key === setup.status.next_step, done: step.status === 'done' }"
        >
          <div class="step-row">
            <NIcon :size="26" class="step-icon" :class="step.status">
              <CheckmarkCircle v-if="step.status === 'done'" />
              <PlayForwardCircleOutline v-else-if="step.status === 'skipped'" />
              <EllipseOutline v-else />
            </NIcon>

            <div class="step-text">
              <div class="step-title">
                <strong>{{ step.title }}</strong>
                <NTag v-if="step.key === setup.status.next_step" size="small" type="primary" round>Next</NTag>
                <NTag v-if="!step.required" size="small" round>Optional</NTag>
                <NTag v-if="step.status === 'skipped'" size="small" type="warning" round>Skipped</NTag>
              </div>
              <p class="muted step-desc">{{ step.description }}</p>
              <p v-if="step.detail" class="step-detail">{{ step.detail }}</p>
            </div>

            <NSpace class="step-actions" :size="8" justify="end">
              <RouterLink v-if="setupStepRoutes[step.key]" :to="{ name: setupStepRoutes[step.key]! }">
                <NButton :type="step.status === 'done' ? 'default' : 'primary'" size="small">
                  {{ step.status === 'done' ? 'Review' : 'Set up' }}
                </NButton>
              </RouterLink>
              <NButton v-else size="small" disabled>Screen coming soon</NButton>

              <NButton
                v-if="step.kind === 'review' && step.status !== 'done'"
                size="small"
                :loading="busyKey === step.key"
                @click="act('mark-reviewed', step)"
              >
                Mark reviewed
              </NButton>
              <NButton v-if="step.status === 'pending'" size="small" quaternary :loading="busyKey === step.key" @click="act('skip', step)">
                Skip
              </NButton>
              <NButton
                v-if="step.status === 'skipped' || (step.kind === 'review' && step.status === 'done')"
                size="small"
                quaternary
                :loading="busyKey === step.key"
                @click="act('reset', step)"
              >
                {{ step.status === 'skipped' ? 'Undo skip' : 'Reopen' }}
              </NButton>
            </NSpace>
          </div>
        </NCard>
      </section>
    </template>
  </div>
</template>

<style scoped>
.subtitle {
  margin: 6px 0 0;
}

.gap {
  margin-top: 16px;
}

.progress-card {
  margin-bottom: 8px;
}

.progress-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
}

.group {
  margin-top: 24px;
}

.group h2 {
  margin-bottom: 10px;
}

.step {
  margin-bottom: 8px;
}

.step.next {
  border-color: #0e6e62;
  box-shadow: 0 0 0 1px #0e6e62;
}

.step-row {
  display: flex;
  align-items: flex-start;
  gap: 14px;
  flex-wrap: wrap;
}

.step-icon {
  margin-top: 2px;
  opacity: 0.45;
}

.step-icon.done {
  color: #0e6e62;
  opacity: 1;
}

.step-icon.skipped {
  color: #8a5a00;
  opacity: 1;
}

.step-text {
  flex: 1 1 280px;
  min-width: 0;
}

.step-title {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.step-desc {
  margin: 4px 0 0;
}

.step-detail {
  margin: 6px 0 0;
  font-weight: 600;
  font-size: 0.9rem;
}

.step-actions {
  flex: 0 0 auto;
}
</style>
