<script setup lang="ts">
import { NAlert, NCard, NEmpty, NGrid, NGridItem, NSkeleton } from 'naive-ui'
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { api, errorMessage } from '@/api/client'
import type { AttendanceDashboardData, DailySheet } from '@/api/attendanceTypes'
import type { ApiEnvelope } from '@/api/types'
import OChart from '@/components/charts/OChart.vue'
import StatCard from '@/components/StatCard.vue'

/** The Attendance module's dashboard: today, the last two weeks, and what's waiting. */
const data = ref<AttendanceDashboardData | null>(null)
const today = ref<DailySheet['summary'] | null>(null)
const error = ref('')

const dayLabel = (date: string) => new Date(`${date}T00:00:00`).toLocaleDateString(undefined, { weekday: 'short', day: 'numeric' })

onMounted(async () => {
  try {
    const { data: res } = await api.get<ApiEnvelope<AttendanceDashboardData>>('/attendance/dashboard')
    data.value = res.data
  } catch (e) {
    error.value = errorMessage(e)
  }
  // Loaded separately: a slow daily sheet shouldn't hold the rest.
  api.get<ApiEnvelope<DailySheet>>('/attendance/daily').then(({ data: res }) => (today.value = res.data.summary)).catch(() => {})
})
</script>

<template>
  <div class="page wide">
    <div class="page-header">
      <div>
        <h1>Attendance dashboard</h1>
        <p class="muted subtitle">Today, the last two weeks, and what needs looking at.</p>
      </div>
    </div>

    <NAlert v-if="error" type="error" :show-icon="false">{{ error }}</NAlert>
    <template v-else>
      <NGrid :cols="4" :x-gap="16" :y-gap="16" item-responsive responsive="screen" class="gap">
        <NGridItem span="4 s:2 l:1">
          <StatCard v-if="today" label="In today" :value="`${today.in} of ${today.expected}`" :hint="`${today.not_in_yet} not in yet`" :to="{ name: 'attendance-today' }" />
          <NSkeleton v-else height="104px" :sharp="false" />
        </NGridItem>
        <NGridItem span="4 s:2 l:1">
          <StatCard v-if="today" label="Late today" :value="today.late" :tone="today.late ? 'warning' : 'default'" :to="{ name: 'attendance-today' }" />
          <NSkeleton v-else height="104px" :sharp="false" />
        </NGridItem>
        <NGridItem span="4 s:2 l:1">
          <StatCard v-if="data" label="Attendance this month" :value="data.month.attendance_rate === null ? '—' : `${data.month.attendance_rate}%`" :hint="`${data.month.absences} absences · ${data.month.lates} lates`" />
          <NSkeleton v-else height="104px" :sharp="false" />
        </NGridItem>
        <NGridItem span="4 s:2 l:1">
          <StatCard v-if="data" label="Requests waiting" :value="data.waiting.corrections" :tone="data.waiting.corrections ? 'warning' : 'default'" :to="{ name: 'attendance-requests' }" />
          <NSkeleton v-else height="104px" :sharp="false" />
        </NGridItem>
      </NGrid>

      <template v-if="data">
        <NGrid :cols="3" :x-gap="16" :y-gap="16" item-responsive responsive="screen" class="gap">
          <NGridItem span="3 l:2">
            <NCard title="Last two weeks">
              <OChart
                type="bar"
                :labels="data.trend.map((t) => dayLabel(t.date))"
                :series="[
                  { label: 'Present', data: data.trend.map((t) => t.present) },
                  { label: 'Absent', data: data.trend.map((t) => t.absent) },
                  { label: 'On leave', data: data.trend.map((t) => t.on_leave) },
                  { label: 'Late', data: data.trend.map((t) => t.late) },
                ]"
              />
            </NCard>
          </NGridItem>
          <NGridItem span="3 l:1">
            <NCard title="Needs looking at">
              <ul class="facts">
                <li :class="{ warn: data.waiting.corrections }"><RouterLink :to="{ name: 'attendance-requests' }">Requests waiting</RouterLink><strong>{{ data.waiting.corrections }}</strong></li>
                <li :class="{ warn: data.waiting.far_clock_ins }"><span>Clock-ins away from the branch (7 days)</span><strong>{{ data.waiting.far_clock_ins }}</strong></li>
                <li :class="{ warn: data.waiting.missed_checkouts }"><span>Missed check-outs (7 days)</span><strong>{{ data.waiting.missed_checkouts }}</strong></li>
                <li :class="{ warn: data.waiting.visits_to_review }"><span>Client visits to review</span><strong>{{ data.waiting.visits_to_review }}</strong></li>
              </ul>
            </NCard>
          </NGridItem>
        </NGrid>

        <NGrid :cols="2" :x-gap="16" :y-gap="16" item-responsive responsive="screen">
          <NGridItem v-for="list in [{ title: 'Most often late this month', rows: data.month.most_late, unit: 'late' }, { title: 'Most absences this month', rows: data.month.most_absent, unit: 'absent' }]" :key="list.title" span="2 m:1">
            <NCard :title="list.title">
              <ul v-if="list.rows.length" class="people">
                <li v-for="p in list.rows" :key="p.user_id">
                  <RouterLink :to="{ name: 'attendance-person', params: { id: p.user_id } }">{{ p.name }}</RouterLink>
                  <span>{{ p.count }} {{ p.count === 1 ? 'day' : 'days' }}</span>
                </li>
              </ul>
              <NEmpty v-else description="No one — good month." />
            </NCard>
          </NGridItem>
        </NGrid>
      </template>
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
  margin-bottom: 16px;
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
  gap: 12px;
  padding: 8px 0;
  border-top: 1px solid rgba(127, 127, 127, 0.18);
}

.facts li:first-child,
.people li:first-child {
  border-top: 0;
}

.facts li.warn strong {
  color: #b7791f;
}

a {
  color: inherit;
  font-weight: 600;
}
</style>
