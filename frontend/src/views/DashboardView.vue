<script setup lang="ts">
import { NButton, NCard, NProgress } from 'naive-ui'
import { computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useSetupStore } from '@/stores/setup'

const auth = useAuthStore()
const setup = useSetupStore()

const canSetup = computed(() => auth.can('HRMS-CompanySettings'))
const nextStep = computed(() => setup.status?.steps.find((s) => s.key === setup.status?.next_step))
const percent = computed(() =>
  setup.status ? Math.round((setup.status.completed_count / setup.status.total_count) * 100) : 0,
)

onMounted(() => {
  if (canSetup.value) setup.load().catch(() => {})
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

    <NCard v-else title="Your workspace">
      <p class="muted">More of Opsyra appears here as each module's screens are built.</p>
    </NCard>
  </div>
</template>

<style scoped>
.setup-banner {
  border-left: 4px solid #0e6e62;
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
