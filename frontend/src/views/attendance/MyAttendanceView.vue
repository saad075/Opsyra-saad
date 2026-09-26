<script setup lang="ts">
import { ChevronBack, ChevronForward } from '@vicons/ionicons5'
import { NAlert, NButton, NCard, NDataTable, NForm, NFormItem, NIcon, NInput, NModal, NRadioButton, NRadioGroup, NSpace, NTag, NTimePicker, useMessage, type DataTableColumns } from 'naive-ui'
import { computed, h, onMounted, reactive, ref } from 'vue'
import { api, errorMessage } from '@/api/client'
import type { AttendanceCorrectionRow, AttendanceDayRecord, AttendanceSummary } from '@/api/attendanceTypes'
import type { ApiEnvelope } from '@/api/types'
import ClockCard from '@/components/ClockCard.vue'
import StatCard from '@/components/StatCard.vue'
import { dayFlags, DAY_STATUS, formatMinutes, localTime, statusLabel } from '@/lib/attendance'
import { formatDate } from '@/lib/staff'

/** My attendance: clock in/out, my month, and asking for a day to be fixed. */
const message = useMessage()

const now = new Date()
const month = ref(`${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`)
const days = ref<AttendanceDayRecord[]>([])
const summary = ref<AttendanceSummary | null>(null)
const requests = ref<AttendanceCorrectionRow[]>([])
const error = ref('')

async function load() {
  try {
    const { data } = await api.get<ApiEnvelope<{ month: string; summary: AttendanceSummary; days: AttendanceDayRecord[] }>>('/attendance/month', { params: { month: month.value } })
    days.value = data.data.days
    summary.value = data.data.summary
    error.value = ''
  } catch (e) {
    error.value = errorMessage(e)
  }
  requests.value = (await api.get<ApiEnvelope<AttendanceCorrectionRow[]>>('/attendance/corrections/mine').catch(() => ({ data: { data: [] } }))).data.data
}

function shiftMonth(by: number) {
  const [y, m] = month.value.split('-').map(Number)
  const d = new Date(y, m - 1 + by, 1)
  month.value = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`
  load()
}

const monthLabel = computed(() => new Date(`${month.value}-01T00:00:00`).toLocaleDateString(undefined, { month: 'long', year: 'numeric' }))
const openRequestDates = computed(() => new Set(requests.value.filter((r) => r.status === 'open').map((r) => r.date.slice(0, 10))))

const columns: DataTableColumns<AttendanceDayRecord> = [
  { title: 'Day', key: 'date', render: (d) => new Date(`${d.date}T00:00:00`).toLocaleDateString(undefined, { weekday: 'short', day: 'numeric', month: 'short' }) },
  { title: 'Status', key: 'status', render: (d) => h(NTag, { size: 'small', round: true, type: DAY_STATUS[d.status].type }, { default: () => statusLabel(d) }) },
  { title: 'In', key: 'in', render: (d) => localTime(d.first_in_at) || '—' },
  { title: 'Out', key: 'out', render: (d) => localTime(d.last_out_at) || '—' },
  { title: 'Worked', key: 'worked', render: (d) => formatMinutes(d.worked_minutes) },
  { title: '', key: 'flags', render: (d) => h('span', { class: 'flags' }, dayFlags(d).join(' · ')) },
  {
    title: '',
    key: 'ask',
    align: 'right',
    render: (d) =>
      openRequestDates.value.has(d.date)
        ? h(NTag, { size: 'small', round: true, type: 'warning' }, { default: () => 'Request sent' })
        : ['absent', 'half_day'].includes(d.status) || d.missed_checkout || d.is_late || d.left_early
          ? h(NButton, { size: 'tiny', quaternary: true, onClick: () => ask(d.date) }, { default: () => 'Something wrong?' })
          : null,
  },
]

// Ask for a correction.
const asking = ref<string | null>(null)
const saving = ref(false)
const form = reactive({ kind: 'times' as 'times' | 'remote' | 'on_leave', in: null as string | null, out: null as string | null, reason: '' })

function ask(date: string) {
  asking.value = date
  const day = days.value.find((d) => d.date === date)
  Object.assign(form, { kind: 'times', in: localTime(day?.first_in_at) || '09:00', out: localTime(day?.last_out_at) || null, reason: '' })
}

async function send() {
  saving.value = true
  try {
    await api.post('/attendance/corrections/create', {
      date: asking.value,
      reason: form.reason,
      ...(form.kind === 'times' ? { requested_in: form.in, requested_out: form.out } : { requested_status: form.kind }),
    })
    message.success('Sent. HR or your manager will look at it.')
    asking.value = null
    await load()
  } catch (e) {
    message.error(errorMessage(e))
  } finally {
    saving.value = false
  }
}

onMounted(load)
</script>

<template>
  <div class="page wide">
    <div class="page-header">
      <h1>My attendance</h1>
    </div>

    <ClockCard class="gap" @changed="load" />

    <div class="month-head">
      <NButton quaternary circle aria-label="Previous month" @click="shiftMonth(-1)"><template #icon><NIcon><ChevronBack /></NIcon></template></NButton>
      <h2>{{ monthLabel }}</h2>
      <NButton quaternary circle aria-label="Next month" @click="shiftMonth(1)"><template #icon><NIcon><ChevronForward /></NIcon></template></NButton>
    </div>

    <div v-if="summary" class="stats">
      <StatCard label="Days worked" :value="summary.present + summary.half_day + summary.remote" />
      <StatCard label="Time worked" :value="formatMinutes(summary.worked_minutes)" />
      <StatCard label="Late" :value="summary.late" :tone="summary.late ? 'warning' : 'default'" />
      <StatCard label="Absent" :value="summary.absent" :tone="summary.absent ? 'warning' : 'default'" />
    </div>

    <NAlert v-if="error" type="error" :show-icon="false">{{ error }}</NAlert>
    <NCard v-else>
      <NDataTable :columns="columns" :data="days" :bordered="false" size="small" :row-key="(d: AttendanceDayRecord) => d.id" />
    </NCard>

    <NCard v-if="requests.length" title="My requests" class="gap-top">
      <div v-for="r in requests" :key="r.id" class="request">
        <NTag size="small" round :type="r.status === 'open' ? 'warning' : r.status === 'approved' ? 'success' : 'default'">
          {{ r.status === 'open' ? 'Waiting' : r.status === 'approved' ? 'Approved' : 'Declined' }}
        </NTag>
        <strong>{{ formatDate(r.date) }}</strong>
        <span class="muted">{{ r.requested_status === 'remote' ? 'Working remotely' : r.requested_status === 'on_leave' ? 'On leave' : `${r.requested_in ?? ''}–${r.requested_out ?? ''}` }} · {{ r.reason }}</span>
        <span v-if="r.response" class="muted">— {{ r.response }}</span>
      </div>
    </NCard>

    <NModal :show="!!asking" preset="card" :title="`Fix ${asking ? formatDate(asking) : ''}`" style="max-width: 460px" @update:show="(v: boolean) => !v && (asking = null)">
      <NForm @submit.prevent="send">
        <NFormItem label="What happened?">
          <NRadioGroup v-model:value="form.kind">
            <NSpace vertical>
              <NRadioButton value="times">My times are wrong</NRadioButton>
              <NRadioButton value="remote">I was working remotely</NRadioButton>
              <NRadioButton value="on_leave">I was on leave</NRadioButton>
            </NSpace>
          </NRadioGroup>
        </NFormItem>
        <NSpace v-if="form.kind === 'times'">
          <NFormItem label="In"><NTimePicker v-model:formatted-value="form.in" value-format="HH:mm" format="HH:mm" /></NFormItem>
          <NFormItem label="Out"><NTimePicker v-model:formatted-value="form.out" value-format="HH:mm" format="HH:mm" clearable /></NFormItem>
        </NSpace>
        <NFormItem label="Why">
          <NInput v-model:value="form.reason" type="textarea" :autosize="{ minRows: 2 }" placeholder="e.g. My phone died so I couldn’t clock out." />
        </NFormItem>
        <NSpace justify="end">
          <NButton @click="asking = null">Cancel</NButton>
          <NButton type="primary" attr-type="submit" :loading="saving" :disabled="!form.reason.trim() || (form.kind === 'times' && !form.in)">Send</NButton>
        </NSpace>
      </NForm>
    </NModal>
  </div>
</template>

<style scoped>
.wide {
  max-width: 1080px;
}

.gap {
  margin-bottom: 20px;
}

.gap-top {
  margin-top: 16px;
}

.month-head {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
}

.month-head h2 {
  margin: 0;
  font-size: 1.15rem;
  min-width: 170px;
  text-align: center;
}

.stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
  gap: 12px;
  margin-bottom: 16px;
}

.request {
  display: flex;
  gap: 8px;
  align-items: center;
  flex-wrap: wrap;
  padding: 6px 0;
}

:deep(.flags) {
  font-size: 0.85rem;
  color: #b7791f;
}
</style>
