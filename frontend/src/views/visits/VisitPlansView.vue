<script setup lang="ts">
import { AddOutline } from '@vicons/ionicons5'
import { NAlert, NButton, NCheckbox, NCheckboxGroup, NDataTable, NDatePicker, NEmpty, NForm, NFormItem, NIcon, NInput, NModal, NSelect, NSpace, NTimePicker, useDialog, useMessage, type DataTableColumns } from 'naive-ui'
import { computed, h, onMounted, reactive, ref } from 'vue'
import { api, errorMessage } from '@/api/client'
import type { ApiEnvelope } from '@/api/types'
import type { VisitLocationRow, VisitPlanRow } from '@/api/visitTypes'
import { formatDate } from '@/lib/staff'
import { weekdaysLabel } from '@/lib/visits'

/**
 * Repeating visits — "Mrs Patel, Mon/Wed/Fri 08:00–09:00, Ama" — which
 * create the actual visits two weeks ahead. Changing a plan updates its
 * upcoming visits, except ones moved or reassigned by hand.
 */
const message = useMessage()
const dialog = useDialog()

type Person = { id: number; name: string; designation: string | null; attendance_mode: string }

const plans = ref<VisitPlanRow[]>([])
const clients = ref<VisitLocationRow[]>([])
const people = ref<Person[]>([])
const loading = ref(true)
const error = ref('')
const clientFilter = ref<number | null>(null)
const personFilter = ref<number | null>(null)

async function load() {
  try {
    const { data } = await api.get<ApiEnvelope<VisitPlanRow[]>>('/visits/plans/list', { params: { visit_location_id: clientFilter.value ?? undefined, user_id: personFilter.value ?? undefined } })
    plans.value = data.data
    error.value = ''
  } catch (e) {
    error.value = errorMessage(e)
  } finally {
    loading.value = false
  }
}

const clientOptions = computed(() => clients.value.map((c) => ({ label: c.name, value: c.id })))
const personOptions = computed(() => people.value.map((p) => ({ label: p.name + (p.attendance_mode === 'visits' ? '' : ' (office)'), value: p.id })))

const editing = ref<VisitPlanRow | null>(null)
const modal = ref(false)
const saving = ref(false)
const form = reactive({ visit_location_id: null as number | null, user_id: null as number | null, weekdays: [] as number[], start_time: '09:00' as string | null, end_time: '10:00' as string | null, starts_on: null as string | null, ends_on: null as string | null, notes: '' })

const today = () => {
  const d = new Date()
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
}

function open(p: VisitPlanRow | null) {
  editing.value = p
  Object.assign(form, {
    visit_location_id: p?.visit_location_id ?? clientFilter.value,
    user_id: p?.user_id ?? personFilter.value,
    weekdays: p ? p.weekdays.map(Number) : [1, 2, 3, 4, 5],
    start_time: p?.start_time.slice(0, 5) ?? '09:00',
    end_time: p?.end_time.slice(0, 5) ?? '10:00',
    starts_on: p?.starts_on.slice(0, 10) ?? today(),
    ends_on: p?.ends_on?.slice(0, 10) ?? null,
    notes: p?.notes ?? '',
  })
  modal.value = true
}

async function save() {
  saving.value = true
  try {
    const body = { ...form, notes: form.notes || null }
    const { data } = await api.post<ApiEnvelope<unknown>>(`/visits/plans/${editing.value ? 'update' : 'create'}`, editing.value ? { id: editing.value.id, ...body } : body)
    message.success(data.message)
    modal.value = false
    await load()
  } catch (e) {
    message.error(errorMessage(e))
  } finally {
    saving.value = false
  }
}

function end(p: VisitPlanRow) {
  dialog.warning({
    title: 'End this plan?',
    content: 'Its upcoming visits are removed. Visits already done stay.',
    positiveText: 'End plan',
    negativeText: 'Keep it',
    onPositiveClick: async () => {
      try {
        await api.post('/visits/plans/delete', { id: p.id })
        message.success('Plan ended.')
        await load()
      } catch (e) {
        message.error(errorMessage(e))
      }
    },
  })
}

const columns: DataTableColumns<VisitPlanRow> = [
  { title: 'Client', key: 'client', render: (p) => h('div', [h('strong', p.location?.name ?? ''), h('small', p.location?.address ?? '')]) },
  { title: 'Who', key: 'staff', render: (p) => p.staff?.name ?? '—' },
  { title: 'When', key: 'when', render: (p) => h('div', [h('span', `${weekdaysLabel(p.weekdays)} · ${p.start_time.slice(0, 5)}–${p.end_time.slice(0, 5)}`), h('small', `From ${formatDate(p.starts_on)}${p.ends_on ? ` until ${formatDate(p.ends_on)}` : ''}`)]) },
  { title: 'Notes', key: 'notes', render: (p) => p.notes ?? '' },
  {
    title: '',
    key: 'actions',
    align: 'right',
    render: (p) =>
      h(NSpace, { justify: 'end', size: 4 }, {
        default: () => [
          h(NButton, { size: 'small', onClick: () => open(p) }, { default: () => 'Edit' }),
          h(NButton, { size: 'small', quaternary: true, type: 'error', onClick: () => end(p) }, { default: () => 'End' }),
        ],
      }),
  },
]

onMounted(async () => {
  await load()
  const [c, p] = await Promise.all([
    api.get<ApiEnvelope<VisitLocationRow[]>>('/visits/locations/list').catch(() => ({ data: { data: [] } })),
    api.get<ApiEnvelope<Person[]>>('/visits/caregivers').catch(() => ({ data: { data: [] } })),
  ])
  clients.value = c.data.data
  people.value = p.data.data
})
</script>

<template>
  <div class="page wide">
    <div class="page-header">
      <div>
        <h1>Visit plans</h1>
        <p class="muted subtitle">Repeating visits. They create the actual visits two weeks ahead, which you can still change one by one.</p>
      </div>
      <NSpace align="center">
        <NSelect v-model:value="clientFilter" :options="clientOptions" placeholder="Any client" clearable filterable style="width: 190px" @update:value="load" />
        <NSelect v-model:value="personFilter" :options="personOptions" placeholder="Anyone" clearable filterable style="width: 180px" @update:value="load" />
        <NButton type="primary" @click="open(null)">
          <template #icon><NIcon><AddOutline /></NIcon></template>
          New plan
        </NButton>
      </NSpace>
    </div>

    <NAlert v-if="error" type="error" :show-icon="false">{{ error }}</NAlert>
    <NDataTable v-else :columns="columns" :data="plans" :loading="loading" :bordered="false" :row-key="(p: VisitPlanRow) => p.id">
      <template #empty><NEmpty description="No visit plans yet." /></template>
    </NDataTable>

    <NModal v-model:show="modal" preset="card" :title="editing ? 'Edit plan' : 'New visit plan'" style="max-width: 540px">
      <NForm @submit.prevent="save">
        <NFormItem label="Client"><NSelect v-model:value="form.visit_location_id" :options="clientOptions" filterable /></NFormItem>
        <NFormItem label="Who visits"><NSelect v-model:value="form.user_id" :options="personOptions" filterable /></NFormItem>
        <NFormItem label="On">
          <NCheckboxGroup v-model:value="form.weekdays">
            <NSpace>
              <NCheckbox v-for="(d, i) in ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']" :key="d" :value="i + 1" :label="d" />
            </NSpace>
          </NCheckboxGroup>
        </NFormItem>
        <NSpace>
          <NFormItem label="From"><NTimePicker v-model:formatted-value="form.start_time" value-format="HH:mm" format="HH:mm" /></NFormItem>
          <NFormItem label="To"><NTimePicker v-model:formatted-value="form.end_time" value-format="HH:mm" format="HH:mm" /></NFormItem>
        </NSpace>
        <NSpace>
          <NFormItem label="Starting"><NDatePicker v-model:formatted-value="form.starts_on" value-format="yyyy-MM-dd" type="date" /></NFormItem>
          <NFormItem label="Until (optional)"><NDatePicker v-model:formatted-value="form.ends_on" value-format="yyyy-MM-dd" type="date" clearable /></NFormItem>
        </NSpace>
        <NFormItem label="Notes (optional)"><NInput v-model:value="form.notes" placeholder="e.g. Covers Ben’s round until March" /></NFormItem>
        <NSpace justify="end">
          <NButton @click="modal = false">Cancel</NButton>
          <NButton type="primary" attr-type="submit" :loading="saving" :disabled="!form.visit_location_id || !form.user_id || !form.weekdays.length || !form.start_time || !form.end_time || !form.starts_on">Save plan</NButton>
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

:deep(small) {
  display: block;
  opacity: 0.6;
}
</style>
