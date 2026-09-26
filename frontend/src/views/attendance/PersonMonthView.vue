<script setup lang="ts">
import { ArrowBackOutline, ChevronBack, ChevronForward } from '@vicons/ionicons5'
import { NAlert, NButton, NCard, NDataTable, NIcon, NSkeleton, NTag, type DataTableColumns } from 'naive-ui'
import { computed, h, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api, errorMessage } from '@/api/client'
import type { AttendanceDayRecord, PersonMonth } from '@/api/attendanceTypes'
import type { ApiEnvelope } from '@/api/types'
import MarkDayDialog from '@/components/MarkDayDialog.vue'
import StatCard from '@/components/StatCard.vue'
import { dayFlags, DAY_STATUS, formatMinutes, localTime, MODE_LABEL, statusLabel } from '@/lib/attendance'

/** One person's month, day by day, with each day's clocking and where. */
const route = useRoute()
const router = useRouter()

const thisMonth = () => {
  const d = new Date()
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`
}

const month = ref((route.query.month as string) || thisMonth())
const data = ref<PersonMonth | null>(null)
const error = ref('')

async function load() {
  try {
    const res = await api.post<ApiEnvelope<PersonMonth>>('/attendance/person-month', { user_id: Number(route.params.id), month: month.value })
    data.value = res.data.data
    error.value = ''
  } catch (e) {
    error.value = errorMessage(e)
  }
}

function shift(by: number) {
  const [y, m] = month.value.split('-').map(Number)
  const d = new Date(y, m - 1 + by, 1)
  month.value = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`
}

const monthLabel = computed(() => new Date(`${month.value}-01T00:00:00`).toLocaleDateString(undefined, { month: 'long', year: 'numeric' }))
const marking = ref<{ userId: number; name: string; date: string; day: AttendanceDayRecord | null } | null>(null)

const columns: DataTableColumns<AttendanceDayRecord> = [
  { type: 'expand', expandable: (d) => !!d.punches?.length, renderExpand: (d) => h('div', { class: 'punches' }, (d.punches ?? []).map((p) => h('div', [
    h('strong', `${p.type === 'in' ? 'In' : 'Out'} ${localTime(p.at)}`),
    h('span', ` · ${p.source}${p.branch ? ` · ${p.branch}` : ''}`),
    p.distance_m !== null ? h('span', { class: p.within_range === false ? 'far' : '' }, ` · ${p.distance_m} m from the branch`) : null,
  ]))) },
  { title: 'Day', key: 'date', render: (d) => new Date(`${d.date}T00:00:00`).toLocaleDateString(undefined, { weekday: 'short', day: 'numeric', month: 'short' }) },
  { title: 'Status', key: 'status', render: (d) => h(NTag, { size: 'small', round: true, type: DAY_STATUS[d.status].type }, { default: () => statusLabel(d) + (d.is_manual ? ' (set by HR)' : '') }) },
  { title: 'In', key: 'in', render: (d) => localTime(d.first_in_at) || '—' },
  { title: 'Out', key: 'out', render: (d) => localTime(d.last_out_at) || '—' },
  { title: 'Worked', key: 'worked', render: (d) => `${formatMinutes(d.worked_minutes)}${d.scheduled_minutes ? ` of ${formatMinutes(d.scheduled_minutes)}` : ''}` },
  { title: '', key: 'flags', render: (d) => h('span', { class: 'flags' }, [...dayFlags(d), d.note].filter(Boolean).join(' · ')) },
  { title: '', key: 'actions', align: 'right', render: (d) => h(NButton, { size: 'small', onClick: () => (marking.value = { userId: data.value!.staff.id, name: data.value!.staff.name, date: d.date, day: d }) }, { default: () => 'Mark' }) },
]

watch(month, load)
watch(() => route.params.id, load)
onMounted(load)
</script>

<template>
  <div class="page wide">
    <NButton text class="back" @click="router.back()">
      <template #icon><NIcon><ArrowBackOutline /></NIcon></template>
      Back
    </NButton>

    <NAlert v-if="error" type="error" :show-icon="false">{{ error }}</NAlert>
    <NSkeleton v-else-if="!data" height="360px" :sharp="false" />

    <template v-else>
      <div class="page-header">
        <div>
          <h1>{{ data.staff.name }}</h1>
          <p class="muted subtitle">{{ data.staff.designation }} · {{ MODE_LABEL[data.staff.attendance_mode as keyof typeof MODE_LABEL] }} · times in {{ data.timezone.replace(/_/g, ' ') }}</p>
        </div>
        <div class="month-nav">
          <NButton quaternary circle aria-label="Previous month" @click="shift(-1)"><template #icon><NIcon><ChevronBack /></NIcon></template></NButton>
          <strong>{{ monthLabel }}</strong>
          <NButton quaternary circle aria-label="Next month" @click="shift(1)"><template #icon><NIcon><ChevronForward /></NIcon></template></NButton>
        </div>
      </div>

      <div class="stats">
        <StatCard label="Days worked" :value="data.summary.present + data.summary.half_day + data.summary.remote" />
        <StatCard label="Time worked" :value="formatMinutes(data.summary.worked_minutes)" />
        <StatCard label="Late" :value="data.summary.late" :tone="data.summary.late ? 'warning' : 'default'" />
        <StatCard label="Absent" :value="data.summary.absent" :tone="data.summary.absent ? 'warning' : 'default'" />
        <StatCard label="On leave" :value="data.summary.on_leave" />
        <StatCard label="Missed check-outs" :value="data.summary.missed_checkout" :tone="data.summary.missed_checkout ? 'warning' : 'default'" />
      </div>

      <NCard>
        <NDataTable :columns="columns" :data="data.days" :bordered="false" size="small" :row-key="(d: AttendanceDayRecord) => d.id" />
      </NCard>
    </template>

    <MarkDayDialog :target="marking" @close="marking = null" @saved="load" />
  </div>
</template>

<style scoped>
.wide {
  max-width: 1180px;
}

.back {
  margin-bottom: 14px;
}

.subtitle {
  margin: 6px 0 0;
}

.month-nav {
  display: flex;
  align-items: center;
  gap: 6px;
}

.month-nav strong {
  min-width: 140px;
  text-align: center;
}

.stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 12px;
  margin-bottom: 16px;
}

:deep(.flags) {
  font-size: 0.85rem;
  color: #b7791f;
}

:deep(.punches) {
  display: flex;
  flex-direction: column;
  gap: 4px;
  font-size: 0.9rem;
}

:deep(.far) {
  color: #a23b45;
  font-weight: 600;
}
</style>
