<script setup lang="ts">
import { ChevronBack, ChevronForward } from '@vicons/ionicons5'
import { NAlert, NButton, NDataTable, NDatePicker, NIcon, NInput, NRadioButton, NRadioGroup, NSelect, NSpace, NTag, type DataTableColumns } from 'naive-ui'
import { computed, h, onMounted, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import { api, errorMessage } from '@/api/client'
import type { DailySheet } from '@/api/attendanceTypes'
import type { ApiEnvelope, Branch } from '@/api/types'
import MarkDayDialog from '@/components/MarkDayDialog.vue'
import StatCard from '@/components/StatCard.vue'
import { dayFlags, DAY_STATUS, formatMinutes, localTime, statusLabel } from '@/lib/attendance'

/** HR's day: who's in, late, absent, on leave or not in yet — and fixing a day. */
type Row = DailySheet['rows'][number]

const localToday = () => {
  const d = new Date()
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
}

const date = ref(localToday())
const branchId = ref<number | null>(null)
const branches = ref<Branch[]>([])
const sheet = ref<DailySheet | null>(null)
const loading = ref(true)
const error = ref('')
const filter = ref<'all' | 'attention' | 'not_in'>('all')
const search = ref('')

async function load() {
  loading.value = true
  try {
    const { data } = await api.get<ApiEnvelope<DailySheet>>('/attendance/daily', { params: { date: date.value, branch_id: branchId.value ?? undefined } })
    sheet.value = data.data
    error.value = ''
  } catch (e) {
    error.value = errorMessage(e)
  } finally {
    loading.value = false
  }
}

function shift(by: number) {
  const d = new Date(`${date.value}T00:00:00`)
  d.setDate(d.getDate() + by)
  date.value = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
}

const needsAttention = (r: Row) => !!r.day && (r.day.status === 'absent' || r.day.is_late || r.day.left_early || r.day.missed_checkout || r.day.needs_review)
const shown = computed(() => {
  const q = search.value.trim().toLowerCase()
  return (sheet.value?.rows ?? [])
    .filter((r) => (filter.value === 'attention' ? needsAttention(r) : filter.value === 'not_in' ? r.expected.kind === 'work' && !r.day : true))
    .filter((r) => !q || r.staff.name.toLowerCase().includes(q))
})

const marking = ref<{ userId: number; name: string; date: string; day: Row['day'] } | null>(null)

const columns: DataTableColumns<Row> = [
  {
    title: 'Person',
    key: 'name',
    render: (r) => h('div', [h(RouterLink, { to: { name: 'attendance-person', params: { id: r.staff.id } }, class: 'name' }, { default: () => r.staff.name }), h('small', r.staff.designation ?? '')]),
  },
  {
    title: 'Expected',
    key: 'expected',
    render: (r) =>
      r.expected.kind === 'work'
        ? `${localTime(r.expected.start)}–${localTime(r.expected.end)}`
        : r.expected.kind === 'visits' ? 'Client visits' : r.expected.kind === 'holiday' ? (r.expected.holiday ?? 'Holiday') : 'Day off',
  },
  {
    title: 'Status',
    key: 'status',
    render: (r) =>
      r.day
        ? h(NTag, { size: 'small', round: true, type: DAY_STATUS[r.day.status].type }, { default: () => statusLabel(r.day!) })
        : r.expected.kind === 'work' ? h(NTag, { size: 'small', round: true }, { default: () => (date.value === localToday() ? 'Not in yet' : 'No record') }) : '—',
  },
  { title: 'In', key: 'in', render: (r) => localTime(r.day?.first_in_at) || '—' },
  { title: 'Out', key: 'out', render: (r) => (r.day?.status === 'open' ? '—' : localTime(r.day?.last_out_at) || '—') },
  { title: 'Worked', key: 'worked', render: (r) => formatMinutes(r.day?.worked_minutes) },
  { title: '', key: 'flags', render: (r) => (r.day ? h('span', { class: 'flags' }, dayFlags(r.day).join(' · ')) : null) },
  {
    title: '',
    key: 'actions',
    align: 'right',
    render: (r) => h(NButton, { size: 'small', onClick: () => (marking.value = { userId: r.staff.id, name: r.staff.name, date: date.value, day: r.day }) }, { default: () => 'Mark' }),
  },
]

watch([date, branchId], load)
onMounted(async () => {
  await load()
  branches.value = (await api.get<ApiEnvelope<Branch[]>>('/branches/list').catch(() => ({ data: { data: [] } }))).data.data
})
</script>

<template>
  <div class="page wide">
    <div class="page-header">
      <div>
        <h1>Attendance</h1>
        <p class="muted subtitle">Who’s in, late or away — and fixing a day.</p>
      </div>
      <NSpace align="center">
        <NButton quaternary circle aria-label="Previous day" @click="shift(-1)"><template #icon><NIcon><ChevronBack /></NIcon></template></NButton>
        <NDatePicker v-model:formatted-value="date" value-format="yyyy-MM-dd" type="date" style="width: 150px" />
        <NButton quaternary circle aria-label="Next day" @click="shift(1)"><template #icon><NIcon><ChevronForward /></NIcon></template></NButton>
        <NSelect v-model:value="branchId" :options="branches.map((b) => ({ label: b.name, value: b.id }))" placeholder="All branches" clearable style="width: 170px" />
      </NSpace>
    </div>

    <div v-if="sheet" class="stats">
      <StatCard label="Expected" :value="sheet.summary.expected" />
      <StatCard label="In" :value="sheet.summary.in" />
      <StatCard label="Late" :value="sheet.summary.late" :tone="sheet.summary.late ? 'warning' : 'default'" />
      <StatCard :label="date === localToday() ? 'Not in yet' : 'No record'" :value="sheet.summary.not_in_yet" />
      <StatCard label="Absent" :value="sheet.summary.absent" :tone="sheet.summary.absent ? 'warning' : 'default'" />
      <StatCard label="On leave" :value="sheet.summary.on_leave" />
    </div>

    <div class="toolbar">
      <NRadioGroup v-model:value="filter" size="small">
        <NRadioButton value="all">Everyone</NRadioButton>
        <NRadioButton value="attention">Needs attention</NRadioButton>
        <NRadioButton value="not_in">{{ date === localToday() ? 'Not in yet' : 'No record' }}</NRadioButton>
      </NRadioGroup>
      <NInput v-model:value="search" size="small" placeholder="Find someone" clearable style="width: 200px" />
    </div>

    <NAlert v-if="error" type="error" :show-icon="false">{{ error }}</NAlert>
    <NDataTable v-else :columns="columns" :data="shown" :loading="loading" :bordered="false" :row-key="(r: Row) => r.staff.id" />

    <MarkDayDialog :target="marking" @close="marking = null" @saved="load" />
  </div>
</template>

<style scoped>
.wide {
  max-width: 1240px;
}

.subtitle {
  margin: 6px 0 0;
}

.stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 12px;
  margin-bottom: 16px;
}

.toolbar {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
  margin-bottom: 12px;
}

:deep(.name) {
  font-weight: 600;
  color: inherit;
}

:deep(small) {
  display: block;
  opacity: 0.6;
}

:deep(.flags) {
  font-size: 0.85rem;
  color: #b7791f;
}
</style>
