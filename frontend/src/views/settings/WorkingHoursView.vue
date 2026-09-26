<script setup lang="ts">
import { AddOutline } from '@vicons/ionicons5'
import {
  NAlert,
  NButton,
  NCard,
  NCheckbox,
  NDataTable,
  NDatePicker,
  NDrawer,
  NDrawerContent,
  NForm,
  NFormItem,
  NIcon,
  NInput,
  NInputNumber,
  NModal,
  NSelect,
  NSpace,
  NTag,
  NTimePicker,
  useDialog,
  useMessage,
  type DataTableColumns,
  type DataTableRowKey,
} from 'naive-ui'
import { computed, h, onMounted, reactive, ref } from 'vue'
import { api, errorMessage } from '@/api/client'
import type { ScheduledPerson, WorkScheduleDayRow, WorkScheduleRow } from '@/api/attendanceTypes'
import type { ApiEnvelope } from '@/api/types'
import { formatMinutes, MODE_LABEL, WEEKDAYS } from '@/lib/attendance'
import { formatDate } from '@/lib/staff'

/**
 * Working hours: the weekly schedules, and who works which one (anyone not
 * put on one follows the default), plus how each person's attendance is
 * tracked — working hours, client visits, or not at all.
 */
const message = useMessage()
const dialog = useDialog()

const schedules = ref<WorkScheduleRow[]>([])
const people = ref<ScheduledPerson[]>([])
const loading = ref(true)
const error = ref('')

async function load() {
  try {
    const [s, p] = await Promise.all([
      api.get<ApiEnvelope<WorkScheduleRow[]>>('/attendance/schedules/list', { params: { include_inactive: 1 } }),
      api.get<ApiEnvelope<ScheduledPerson[]>>('/attendance/schedules/people'),
    ])
    schedules.value = s.data.data
    people.value = p.data.data
    error.value = ''
  } catch (e) {
    error.value = errorMessage(e)
  } finally {
    loading.value = false
  }
}

const hhmm = (t: string | null) => (t ? t.slice(0, 5) : '')
const minutesOf = (d: WorkScheduleDayRow) => {
  if (!d.is_working || !d.start_time || !d.end_time) return 0
  const [sh, sm] = hhmm(d.start_time).split(':').map(Number)
  const [eh, em] = hhmm(d.end_time).split(':').map(Number)
  let span = eh * 60 + em - (sh * 60 + sm)
  if (span <= 0) span += 24 * 60
  return Math.max(0, span - (d.break_minutes || 0))
}

/** "Mon–Fri 09:00–17:00" when the working days share times; else each day. */
function summary(s: WorkScheduleRow): string {
  const working = s.days.filter((d) => d.is_working)
  if (!working.length) return 'No working days'
  const same = working.every((d) => d.start_time === working[0].start_time && d.end_time === working[0].end_time)
  const days = working.map((d) => WEEKDAYS[d.weekday - 1].slice(0, 3)).join(', ')
  return same ? `${days} · ${hhmm(working[0].start_time)}–${hhmm(working[0].end_time)}` : `${days} · varies by day`
}

// Editing a schedule.
const editing = ref<WorkScheduleRow | null>(null)
const drawer = ref(false)
const saving = ref(false)
const form = reactive({ name: '', description: '', grace_minutes: 10, early_leave_grace_minutes: 10, is_default: false, is_active: true, days: [] as WorkScheduleDayRow[] })

function open(s: WorkScheduleRow | null) {
  editing.value = s
  Object.assign(form, {
    name: s?.name ?? '',
    description: s?.description ?? '',
    grace_minutes: s?.grace_minutes ?? 10,
    early_leave_grace_minutes: s?.early_leave_grace_minutes ?? 10,
    is_default: s?.is_default ?? false,
    is_active: s?.is_active ?? true,
    days: [1, 2, 3, 4, 5, 6, 7].map((w) => {
      const d = s?.days.find((x) => x.weekday === w)
      return { weekday: w, is_working: d?.is_working ?? w <= 5, start_time: hhmm(d?.start_time ?? null) || '09:00', end_time: hhmm(d?.end_time ?? null) || '17:00', break_minutes: d?.break_minutes ?? 60 }
    }),
  })
  drawer.value = true
}

async function save() {
  saving.value = true
  try {
    const body = { ...form, days: form.days.map((d) => ({ ...d, start_time: d.is_working ? d.start_time : null, end_time: d.is_working ? d.end_time : null })) }
    const { data } = await api.post<ApiEnvelope<unknown>>(`/attendance/schedules/${editing.value ? 'update' : 'create'}`, editing.value ? { id: editing.value.id, ...body } : body)
    message.success(data.message)
    drawer.value = false
    await load()
  } catch (e) {
    message.error(errorMessage(e))
  } finally {
    saving.value = false
  }
}

function remove(s: WorkScheduleRow) {
  dialog.warning({
    title: `Delete ${s.name}?`,
    content: 'A schedule that has been used can only be switched off.',
    positiveText: 'Delete',
    negativeText: 'Cancel',
    onPositiveClick: async () => {
      try {
        await api.post('/attendance/schedules/delete', { id: s.id })
        message.success('Deleted.')
        await load()
      } catch (e) {
        message.error(errorMessage(e))
      }
    },
  })
}

// People: assign a schedule, or change how they're tracked.
const checked = ref<DataTableRowKey[]>([])
const assigning = ref(false)
const assignForm = reactive({ work_schedule_id: null as number | null, starts_on: null as string | null })

function openAssign() {
  const d = new Date()
  assignForm.starts_on = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
  assignForm.work_schedule_id = null
  assigning.value = true
}

async function assign() {
  saving.value = true
  try {
    const { data } = await api.post<ApiEnvelope<unknown>>('/attendance/schedules/assign', { ...assignForm, user_ids: checked.value })
    message.success(data.message)
    assigning.value = false
    checked.value = []
    await load()
  } catch (e) {
    message.error(errorMessage(e))
  } finally {
    saving.value = false
  }
}

async function setMode(mode: 'schedule' | 'visits' | 'not_tracked') {
  try {
    await api.post('/attendance/staff-mode', { user_ids: checked.value, attendance_mode: mode })
    message.success(`${checked.value.length} person(s) now: ${MODE_LABEL[mode].toLowerCase()}.`)
    checked.value = []
    await load()
  } catch (e) {
    message.error(errorMessage(e))
  }
}

const activeSchedules = computed(() => schedules.value.filter((s) => s.is_active))

const scheduleColumns: DataTableColumns<WorkScheduleRow> = [
  {
    title: 'Schedule',
    key: 'name',
    render: (s) =>
      h('div', [
        h(NSpace, { size: 6, align: 'center' }, { default: () => [h('strong', { style: s.is_active ? '' : 'opacity:.5' }, s.name), s.is_default ? h(NTag, { size: 'tiny', round: true, type: 'primary' }, { default: () => 'Default' }) : null, !s.is_active ? h(NTag, { size: 'tiny', round: true }, { default: () => 'Off' }) : null] }),
        h('small', summary(s)),
      ]),
  },
  { title: 'Grace', key: 'grace', render: (s) => `${s.grace_minutes} min` },
  { title: 'People', key: 'staff_count', render: (s) => (s.is_default ? (s.staff_count ? `${s.staff_count} + everyone else` : 'Everyone') : String(s.staff_count)) },
  {
    title: '',
    key: 'actions',
    align: 'right',
    render: (s) =>
      h(NSpace, { justify: 'end', size: 4 }, {
        default: () => [
          h(NButton, { size: 'small', onClick: () => open(s) }, { default: () => 'Edit' }),
          s.is_default ? null : h(NButton, { size: 'small', quaternary: true, type: 'error', onClick: () => remove(s) }, { default: () => 'Delete' }),
        ],
      }),
  },
]

const peopleColumns: DataTableColumns<ScheduledPerson> = [
  { type: 'selection' },
  { title: 'Person', key: 'name', render: (p) => h('div', [h('strong', p.name), h('small', p.designation ?? '')]) },
  {
    title: 'Tracked by',
    key: 'mode',
    render: (p) => h(NTag, { size: 'small', round: true, type: p.attendance_mode === 'not_tracked' ? 'default' : p.attendance_mode === 'visits' ? 'info' : 'success' }, { default: () => MODE_LABEL[p.attendance_mode] + (p.is_remote && p.attendance_mode === 'schedule' ? ' · remote' : '') }),
  },
  {
    title: 'Schedule',
    key: 'schedule',
    render: (p) => (p.attendance_mode !== 'schedule' ? '—' : h('span', [p.schedule?.name ?? 'None', p.on_default ? h('small', 'the default') : p.since ? h('small', `since ${formatDate(p.since)}`) : null])),
  },
]

onMounted(load)
</script>

<template>
  <div class="page wide">
    <div class="page-header">
      <div>
        <h1>Working hours</h1>
        <p class="muted subtitle">When people work. Anyone not put on a schedule follows the default.</p>
      </div>
      <NButton type="primary" @click="open(null)">
        <template #icon><NIcon><AddOutline /></NIcon></template>
        New schedule
      </NButton>
    </div>

    <NAlert v-if="error" type="error" :show-icon="false">{{ error }}</NAlert>

    <template v-else>
      <NCard title="Schedules" class="gap">
        <NDataTable :columns="scheduleColumns" :data="schedules" :loading="loading" :bordered="false" size="small" :row-key="(s: WorkScheduleRow) => s.id" />
      </NCard>

      <NCard title="People">
        <template #header-extra>
          <NSpace v-if="checked.length">
            <NButton size="small" type="primary" @click="openAssign">Put {{ checked.length }} on a schedule</NButton>
            <NButton size="small" @click="setMode('schedule')">Track by working hours</NButton>
            <NButton size="small" @click="setMode('visits')">Track by client visits</NButton>
            <NButton size="small" quaternary @click="setMode('not_tracked')">Don’t track</NButton>
          </NSpace>
          <span v-else class="muted small">Tick people to change their schedule or how they’re tracked.</span>
        </template>
        <NDataTable v-model:checked-row-keys="checked" :columns="peopleColumns" :data="people" :loading="loading" :bordered="false" size="small" :row-key="(p: ScheduledPerson) => p.id" :max-height="480" />
      </NCard>
    </template>

    <NDrawer v-model:show="drawer" :width="640" placement="right" :trap-focus="false">
      <NDrawerContent :title="editing ? `Edit ${editing.name}` : 'New schedule'" closable>
        <NForm @submit.prevent="save">
          <NFormItem label="Name"><NInput v-model:value="form.name" placeholder="e.g. Night shift" /></NFormItem>
          <NSpace>
            <NFormItem label="Late after (minutes’ grace)"><NInputNumber v-model:value="form.grace_minutes" :min="0" :max="240" style="width: 140px" /></NFormItem>
            <NFormItem label="Left early before (grace)"><NInputNumber v-model:value="form.early_leave_grace_minutes" :min="0" :max="240" style="width: 140px" /></NFormItem>
          </NSpace>

          <div v-for="d in form.days" :key="d.weekday" class="day">
            <NCheckbox v-model:checked="d.is_working" class="day-name">{{ WEEKDAYS[d.weekday - 1] }}</NCheckbox>
            <template v-if="d.is_working">
              <NTimePicker v-model:formatted-value="d.start_time" value-format="HH:mm" format="HH:mm" size="small" style="width: 105px" />
              <span>–</span>
              <NTimePicker v-model:formatted-value="d.end_time" value-format="HH:mm" format="HH:mm" size="small" style="width: 105px" />
              <NInputNumber v-model:value="d.break_minutes" :min="0" :max="600" :show-button="false" size="small" style="width: 120px"><template #prefix>Break</template><template #suffix>min</template></NInputNumber>
              <span class="muted small">{{ formatMinutes(minutesOf(d)) }}{{ d.end_time && d.start_time && d.end_time <= d.start_time ? ' · ends next day' : '' }}</span>
            </template>
            <span v-else class="muted small">Day off</span>
          </div>

          <NSpace class="flags">
            <NCheckbox v-model:checked="form.is_default">The default for everyone not put on a schedule</NCheckbox>
            <NCheckbox v-model:checked="form.is_active" :disabled="editing?.is_default">In use</NCheckbox>
          </NSpace>
        </NForm>
        <template #footer>
          <NSpace>
            <NButton @click="drawer = false">Cancel</NButton>
            <NButton type="primary" :loading="saving" :disabled="!form.name.trim()" @click="save">Save schedule</NButton>
          </NSpace>
        </template>
      </NDrawerContent>
    </NDrawer>

    <NModal v-model:show="assigning" preset="card" :title="`Put ${checked.length} person(s) on a schedule`" style="max-width: 440px">
      <NForm @submit.prevent="assign">
        <NFormItem label="Schedule"><NSelect v-model:value="assignForm.work_schedule_id" :options="activeSchedules.map((s) => ({ label: s.name, value: s.id }))" /></NFormItem>
        <NFormItem label="From"><NDatePicker v-model:formatted-value="assignForm.starts_on" value-format="yyyy-MM-dd" type="date" style="width: 100%" /></NFormItem>
        <p class="muted small">Their current schedule ends the day before. Days already settled keep their times.</p>
        <NSpace justify="end">
          <NButton @click="assigning = false">Cancel</NButton>
          <NButton type="primary" attr-type="submit" :loading="saving" :disabled="!assignForm.work_schedule_id || !assignForm.starts_on">Save</NButton>
        </NSpace>
      </NForm>
    </NModal>
  </div>
</template>

<style scoped>
.wide {
  max-width: 1180px;
}

.subtitle {
  margin: 6px 0 0;
}

.gap {
  margin-bottom: 16px;
}

.small {
  font-size: 0.88rem;
}

.day {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
  padding: 8px 0;
  border-top: 1px solid rgba(127, 127, 127, 0.18);
}

.day-name {
  width: 120px;
}

.flags {
  margin-top: 16px;
}

:deep(small) {
  display: block;
  opacity: 0.6;
}
</style>
