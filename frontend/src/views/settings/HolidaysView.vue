<script setup lang="ts">
import { AddOutline, ChevronBack, ChevronForward } from '@vicons/ionicons5'
import { NAlert, NButton, NDataTable, NDatePicker, NEmpty, NForm, NFormItem, NIcon, NInput, NModal, NSelect, NSpace, useDialog, useMessage, type DataTableColumns } from 'naive-ui'
import { h, onMounted, reactive, ref, watch } from 'vue'
import { api, errorMessage, fieldErrors } from '@/api/client'
import type { HolidayRow } from '@/api/attendanceTypes'
import type { ApiEnvelope, Branch } from '@/api/types'
import { formatDate } from '@/lib/staff'

/** Public and company holidays — for every branch, or one. */
const message = useMessage()
const dialog = useDialog()

const year = ref(new Date().getFullYear())
const holidays = ref<HolidayRow[]>([])
const branches = ref<Branch[]>([])
const loading = ref(true)
const error = ref('')

async function load() {
  loading.value = true
  try {
    const { data } = await api.get<ApiEnvelope<HolidayRow[]>>('/attendance/holidays/list', { params: { year: year.value } })
    holidays.value = data.data
    error.value = ''
  } catch (e) {
    error.value = errorMessage(e)
  } finally {
    loading.value = false
  }
}

const editing = ref<HolidayRow | null>(null)
const modal = ref(false)
const saving = ref(false)
const errors = ref<Record<string, string>>({})
const form = reactive({ date: null as string | null, name: '', branch_id: null as number | null })

function open(hol: HolidayRow | null) {
  editing.value = hol
  errors.value = {}
  Object.assign(form, { date: hol?.date.slice(0, 10) ?? null, name: hol?.name ?? '', branch_id: hol?.branch_id ?? null })
  modal.value = true
}

async function save() {
  saving.value = true
  errors.value = {}
  try {
    await api.post(`/attendance/holidays/${editing.value ? 'update' : 'create'}`, editing.value ? { id: editing.value.id, ...form } : form)
    message.success(editing.value ? 'Saved.' : 'Holiday added.')
    modal.value = false
    await load()
  } catch (e) {
    errors.value = fieldErrors(e)
    if (!Object.keys(errors.value).length) message.error(errorMessage(e))
  } finally {
    saving.value = false
  }
}

function remove(hol: HolidayRow) {
  dialog.warning({
    title: `Remove ${hol.name}?`,
    content: 'Days already settled as a holiday stay as they are.',
    positiveText: 'Remove',
    negativeText: 'Cancel',
    onPositiveClick: async () => {
      try {
        await api.post('/attendance/holidays/delete', { id: hol.id })
        message.success('Removed.')
        await load()
      } catch (e) {
        message.error(errorMessage(e))
      }
    },
  })
}

const columns: DataTableColumns<HolidayRow> = [
  { title: 'Date', key: 'date', render: (r) => h('div', [h('strong', formatDate(r.date)), h('small', new Date(`${r.date.slice(0, 10)}T00:00:00`).toLocaleDateString(undefined, { weekday: 'long' }))]) },
  { title: 'Holiday', key: 'name' },
  { title: 'Where', key: 'branch', render: (r) => r.branch?.name ?? 'Every branch' },
  {
    title: '',
    key: 'actions',
    align: 'right',
    render: (r) =>
      h(NSpace, { justify: 'end', size: 4 }, {
        default: () => [
          h(NButton, { size: 'small', onClick: () => open(r) }, { default: () => 'Edit' }),
          h(NButton, { size: 'small', quaternary: true, type: 'error', onClick: () => remove(r) }, { default: () => 'Remove' }),
        ],
      }),
  },
]

watch(year, load)
onMounted(async () => {
  await load()
  branches.value = (await api.get<ApiEnvelope<Branch[]>>('/branches/list').catch(() => ({ data: { data: [] } }))).data.data
})
</script>

<template>
  <div class="page">
    <div class="page-header">
      <div>
        <h1>Public holidays</h1>
        <p class="muted subtitle">Days off for everyone, or one branch. Nobody is marked absent on them.</p>
      </div>
      <NSpace align="center">
        <NButton quaternary circle aria-label="Previous year" @click="year--"><template #icon><NIcon><ChevronBack /></NIcon></template></NButton>
        <strong>{{ year }}</strong>
        <NButton quaternary circle aria-label="Next year" @click="year++"><template #icon><NIcon><ChevronForward /></NIcon></template></NButton>
        <NButton type="primary" @click="open(null)">
          <template #icon><NIcon><AddOutline /></NIcon></template>
          Add holiday
        </NButton>
      </NSpace>
    </div>

    <NAlert v-if="error" type="error" :show-icon="false">{{ error }}</NAlert>
    <NDataTable v-else :columns="columns" :data="holidays" :loading="loading" :bordered="false" :row-key="(r: HolidayRow) => r.id">
      <template #empty><NEmpty :description="`No holidays in ${year} yet.`" /></template>
    </NDataTable>

    <NModal v-model:show="modal" preset="card" :title="editing ? 'Edit holiday' : 'Add a holiday'" style="max-width: 440px">
      <NForm @submit.prevent="save">
        <NFormItem label="Date" :feedback="errors.date" :validation-status="errors.date ? 'error' : undefined">
          <NDatePicker v-model:formatted-value="form.date" value-format="yyyy-MM-dd" type="date" style="width: 100%" />
        </NFormItem>
        <NFormItem label="Name" :feedback="errors.name" :validation-status="errors.name ? 'error' : undefined">
          <NInput v-model:value="form.name" placeholder="e.g. Independence Day" />
        </NFormItem>
        <NFormItem label="Where">
          <NSelect v-model:value="form.branch_id" :options="branches.map((b) => ({ label: b.name, value: b.id }))" placeholder="Every branch" clearable />
        </NFormItem>
        <NSpace justify="end">
          <NButton @click="modal = false">Cancel</NButton>
          <NButton type="primary" attr-type="submit" :loading="saving" :disabled="!form.date || !form.name.trim()">Save</NButton>
        </NSpace>
      </NForm>
    </NModal>
  </div>
</template>

<style scoped>
.subtitle {
  margin: 6px 0 0;
}

:deep(small) {
  display: block;
  opacity: 0.6;
}
</style>
