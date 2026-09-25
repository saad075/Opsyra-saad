<script setup lang="ts">
import { NAlert, NCard, NEmpty, NGrid, NGridItem, NSkeleton } from 'naive-ui'
import { onMounted, ref } from 'vue'
import { api, errorMessage } from '@/api/client'
import type { ApiEnvelope, HrDashboard } from '@/api/types'
import OChart from '@/components/charts/OChart.vue'
import StatCard from '@/components/StatCard.vue'

const data = ref<HrDashboard | null>(null)
const error = ref('')

const formatDate = (iso: string) => new Date(`${iso}T00:00:00`).toLocaleDateString(undefined, { day: 'numeric', month: 'short' })

onMounted(async () => {
  try {
    const { data: res } = await api.get<ApiEnvelope<HrDashboard>>('/hr/dashboard')
    data.value = res.data
  } catch (e) {
    error.value = errorMessage(e)
  }
})
</script>

<template>
  <div class="page wide">
    <div class="page-header">
      <div>
        <h1>HR dashboard</h1>
        <p class="muted subtitle">Your people at a glance.</p>
      </div>
    </div>

    <NAlert v-if="error" type="error" :show-icon="false">{{ error }}</NAlert>

    <template v-else-if="!data">
      <NGrid :cols="4" :x-gap="16" item-responsive responsive="screen">
        <NGridItem v-for="i in 4" :key="i" span="4 s:2 l:1"><NSkeleton height="104px" :sharp="false" /></NGridItem>
      </NGrid>
      <NSkeleton height="300px" :sharp="false" class="gap" />
    </template>

    <template v-else>
      <NGrid :cols="4" :x-gap="16" :y-gap="16" item-responsive responsive="screen">
        <NGridItem span="4 s:2 l:1">
          <StatCard label="Active staff" :value="data.headcount.active" :hint="data.headcount.starting_soon ? `+${data.headcount.starting_soon} starting soon` : undefined" />
        </NGridItem>
        <NGridItem span="4 s:2 l:1">
          <StatCard label="Joined this month" :value="data.headcount.joined_this_month" />
        </NGridItem>
        <NGridItem span="4 s:2 l:1">
          <StatCard label="Left this month" :value="data.headcount.left_this_month" />
        </NGridItem>
        <NGridItem span="4 s:2 l:1">
          <StatCard
            label="Awaiting approval"
            :value="data.approvals.onboarding + data.approvals.offboarding"
            :hint="`${data.approvals.onboarding} joining · ${data.approvals.offboarding} leaving`"
            :tone="data.approvals.onboarding + data.approvals.offboarding > 0 ? 'warning' : 'default'"
          />
        </NGridItem>
      </NGrid>

      <NGrid :cols="3" :x-gap="16" :y-gap="16" item-responsive responsive="screen" class="gap">
        <NGridItem span="3 l:2">
          <NCard title="Headcount, last 6 months">
            <OChart type="line" :labels="data.trend.map((t) => t.label)" :series="[{ label: 'Staff', data: data.trend.map((t) => t.value) }]" />
          </NCard>
        </NGridItem>
        <NGridItem span="3 l:1">
          <NCard title="By branch">
            <OChart
              v-if="data.by_branch.length"
              type="doughnut"
              :labels="data.by_branch.map((b) => b.label)"
              :series="[{ label: 'Staff', data: data.by_branch.map((b) => b.value) }]"
            />
            <NEmpty v-else description="Assign staff to branches to see this." />
          </NCard>
        </NGridItem>
      </NGrid>

      <NGrid :cols="3" :x-gap="16" :y-gap="16" item-responsive responsive="screen" class="gap">
        <NGridItem span="3 l:1">
          <NCard title="Onboarding & offboarding">
            <ul class="facts">
              <li><span>Checklists in progress</span><strong>{{ data.checklists.in_progress }}</strong></li>
              <li :class="{ warn: data.checklists.overdue_tasks > 0 }"><span>Overdue tasks</span><strong>{{ data.checklists.overdue_tasks }}</strong></li>
              <li><span>Completed this month</span><strong>{{ data.checklists.completed_this_month }}</strong></li>
              <li :class="{ warn: data.approvals.rejected_awaiting_notice > 0 }">
                <span>Rejected hires to notify</span><strong>{{ data.approvals.rejected_awaiting_notice }}</strong>
              </li>
            </ul>
          </NCard>
        </NGridItem>
        <NGridItem span="3 m:1">
          <NCard title="Starting in the next 30 days">
            <ul v-if="data.upcoming_joiners.length" class="people">
              <li v-for="p in data.upcoming_joiners" :key="p.id">
                <span><strong>{{ p.name }}</strong><small>{{ p.designation }}</small></span>
                <span class="date">{{ formatDate(p.date) }}</span>
              </li>
            </ul>
            <NEmpty v-else description="No one starting soon." />
          </NCard>
        </NGridItem>
        <NGridItem span="3 m:1">
          <NCard title="Leaving in the next 30 days">
            <ul v-if="data.upcoming_leavers.length" class="people">
              <li v-for="p in data.upcoming_leavers" :key="p.id">
                <span><strong>{{ p.name }}</strong><small>{{ p.designation }}</small></span>
                <span class="date">{{ formatDate(p.date) }}</span>
              </li>
            </ul>
            <NEmpty v-else description="No one leaving soon." />
          </NCard>
        </NGridItem>
      </NGrid>

      <NCard v-if="data.by_designation.length" title="By job title" class="gap">
        <OChart
          type="bar"
          :height="220"
          :labels="data.by_designation.map((d) => d.label)"
          :series="[{ label: 'Staff', data: data.by_designation.map((d) => d.value) }]"
        />
      </NCard>
    </template>
  </div>
</template>

<style scoped>
.wide {
  max-width: 1280px;
}

.subtitle {
  margin: 6px 0 0;
}

.gap {
  margin-top: 16px;
}

.facts,
.people {
  list-style: none;
  margin: 0;
  padding: 0;
}

.facts li,
.people li {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  padding: 9px 0;
  border-top: 1px solid rgba(127, 127, 127, 0.18);
}

.facts li:first-child,
.people li:first-child {
  border-top: 0;
}

.facts strong {
  font-family: 'Plus Jakarta Sans', 'Segoe UI', sans-serif;
  font-size: 1.1rem;
}

.facts li.warn strong {
  color: #8a5a00;
}

.people small {
  display: block;
  opacity: 0.65;
}

.date {
  font-weight: 600;
  white-space: nowrap;
}
</style>
