<script setup lang="ts">
import { NButton, NDatePicker, NForm, NFormItem, NInput, NInputNumber, NModal, NSelect, useDialog, useMessage } from 'naive-ui'
import { reactive, ref, watch } from 'vue'
import { api, errorMessage, fieldErrors } from '@/api/client'
import type { ApiEnvelope, PersonRef } from '@/api/types'
import type { LeadForm, LeadRow, LeadSource, LeadType } from '@/api/crmLeadsTypes'

const props = defineProps<{
  show: boolean
  lead: LeadRow | null
  /** Only used when creating — which pipeline the new lead lands in. */
  pipelineId: number | null
}>()

const emit = defineEmits<{
  'update:show': [boolean]
  saved: [LeadRow]
  deleted: []
}>()

const message = useMessage()
const dialog = useDialog()

const saving = ref(false)
const errors = ref<Record<string, string>>({})
const form = reactive<LeadForm>({
  title: '',
  description: null,
  lead_value: null,
  expected_close_date: null,
  contact_name: null,
  contact_email: null,
  contact_phone: null,
  lead_source_id: null,
  lead_type_id: null,
  owner_id: null,
})
// NDatePicker wants a timestamp, not the yyyy-MM-dd string the API expects.
const expectedCloseDateMs = ref<number | null>(null)

const sources = ref<LeadSource[]>([])
const types = ref<LeadType[]>([])
const owners = ref<PersonRef[]>([])
let pickersLoaded = false

async function loadPickers() {
  if (pickersLoaded) return
  pickersLoaded = true

  sources.value = (await api.get<ApiEnvelope<LeadSource[]>>('/lead-sources/list')).data.data
  types.value = (await api.get<ApiEnvelope<LeadType[]>>('/lead-types/list')).data.data
  owners.value = (await api.get<ApiEnvelope<PersonRef[]>>('/staff/eligible-managers').catch(() => ({ data: { data: [] } }))).data.data
}

watch(
  () => props.show,
  async (open) => {
    if (! open) return

    errors.value = {}
    const lead = props.lead
    Object.assign(form, {
      title: lead?.title ?? '',
      description: lead?.description ?? null,
      lead_value: lead?.lead_value ? Number(lead.lead_value) : null,
      expected_close_date: lead?.expected_close_date ?? null,
      contact_name: lead?.contact_name ?? null,
      contact_email: lead?.contact_email ?? null,
      contact_phone: lead?.contact_phone ?? null,
      lead_source_id: lead?.lead_source_id ?? null,
      lead_type_id: lead?.lead_type_id ?? null,
      owner_id: lead?.owner_id ?? null,
    })
    expectedCloseDateMs.value = lead?.expected_close_date ? new Date(lead.expected_close_date).getTime() : null

    await loadPickers()
  },
)

async function save() {
  saving.value = true
  errors.value = {}
  const payload = {
    ...form,
    expected_close_date: expectedCloseDateMs.value ? new Date(expectedCloseDateMs.value).toISOString().slice(0, 10) : null,
  }
  try {
    const { data } = props.lead
      ? await api.post<ApiEnvelope<LeadRow>>('/leads/update', { id: props.lead.id, ...payload })
      : await api.post<ApiEnvelope<LeadRow>>('/leads/create', { ...payload, lead_pipeline_id: props.pipelineId })

    message.success(props.lead ? 'Lead updated.' : 'Lead created.')
    emit('update:show', false)
    emit('saved', data.data)
  } catch (e) {
    errors.value = fieldErrors(e)
    if (! Object.keys(errors.value).length) message.error(errorMessage(e))
  } finally {
    saving.value = false
  }
}

function remove() {
  if (! props.lead) return
  const lead = props.lead

  dialog.warning({
    title: `Delete "${lead.title}"?`,
    content: 'This can’t be undone.',
    positiveText: 'Delete',
    negativeText: 'Cancel',
    onPositiveClick: async () => {
      try {
        await api.post('/leads/delete', { id: lead.id })
        message.success('Lead deleted.')
        emit('update:show', false)
        emit('deleted')
      } catch (e) {
        message.error(errorMessage(e))
      }
    },
  })
}
</script>

<template>
  <NModal
    :show="show"
    preset="card"
    :title="lead ? 'Edit lead' : 'Add lead'"
    style="max-width: 520px"
    :mask-closable="!saving"
    @update:show="(v: boolean) => emit('update:show', v)"
  >
    <NForm @submit.prevent="save">
      <NFormItem label="Title" :feedback="errors.title" :validation-status="errors.title ? 'error' : undefined">
        <NInput v-model:value="form.title" placeholder="e.g. Acme Corp — platform migration" />
      </NFormItem>
      <NFormItem label="Value"><NInputNumber v-model:value="form.lead_value" :min="0" style="width: 100%" /></NFormItem>
      <NFormItem label="Expected close date"><NDatePicker v-model:value="expectedCloseDateMs" type="date" clearable style="width: 100%" /></NFormItem>
      <NFormItem label="Contact name"><NInput v-model:value="form.contact_name" /></NFormItem>
      <NFormItem label="Contact email" :feedback="errors.contact_email" :validation-status="errors.contact_email ? 'error' : undefined">
        <NInput v-model:value="form.contact_email" />
      </NFormItem>
      <NFormItem label="Contact phone"><NInput v-model:value="form.contact_phone" /></NFormItem>
      <NFormItem label="Source">
        <NSelect v-model:value="form.lead_source_id" :options="sources.map((s) => ({ label: s.name, value: s.id }))" filterable clearable placeholder="None" />
      </NFormItem>
      <NFormItem label="Type">
        <NSelect v-model:value="form.lead_type_id" :options="types.map((t) => ({ label: t.name, value: t.id }))" filterable clearable placeholder="None" />
      </NFormItem>
      <NFormItem label="Owner">
        <NSelect v-model:value="form.owner_id" :options="owners.map((o) => ({ label: o.name, value: o.id }))" filterable clearable placeholder="Unassigned" />
      </NFormItem>
      <NFormItem label="Description"><NInput v-model:value="form.description" type="textarea" :autosize="{ minRows: 2, maxRows: 5 }" /></NFormItem>
      <div class="actions">
        <NButton v-if="lead" type="error" ghost :disabled="saving" @click="remove">Delete</NButton>
        <div class="spacer" />
        <NButton :disabled="saving" @click="emit('update:show', false)">Cancel</NButton>
        <NButton type="primary" attr-type="submit" :loading="saving">{{ lead ? 'Save' : 'Add lead' }}</NButton>
      </div>
    </NForm>
  </NModal>
</template>

<style scoped>
.actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
}

.spacer {
  flex: 1;
}
</style>
