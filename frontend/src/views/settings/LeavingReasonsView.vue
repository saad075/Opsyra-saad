<script setup lang="ts">
import { AddOutline } from '@vicons/ionicons5'
import { NAlert, NButton, NCheckbox, NDataTable, NEmpty, NForm, NFormItem, NIcon, NInput, NModal, NSpace, NSwitch, NTag, useDialog, useMessage, type DataTableColumns } from 'naive-ui'
import { h, onMounted, reactive, ref } from 'vue'
import { api, errorMessage, fieldErrors } from '@/api/client'
import type { ApiEnvelope, DepartureReason } from '@/api/types'

/**
 * Reasons recorded when someone leaves. Pre-filled per country; built-in
 * and already-used reasons can be deactivated but not deleted.
 */
const message = useMessage()
const dialog = useDialog()

const reasons = ref<DepartureReason[]>([])
const loading = ref(true)
const loadError = ref('')

async function load() {
  try {
    const { data } = await api.get<ApiEnvelope<DepartureReason[]>>('/departure-reasons/list', { params: { include_inactive: 1 } })
    reasons.value = data.data
    loadError.value = ''
  } catch (e) {
    loadError.value = errorMessage(e)
  } finally {
    loading.value = false
  }
}

const editing = ref<DepartureReason | null>(null)
const modalOpen = ref(false)
const saving = ref(false)
const errors = ref<Record<string, string>>({})
const form = reactive({ name: '', requires_note: false })

function open(reason: DepartureReason | null) {
  editing.value = reason
  errors.value = {}
  Object.assign(form, { name: reason?.name ?? '', requires_note: reason?.requires_note ?? false })
  modalOpen.value = true
}

async function save() {
  saving.value = true
  errors.value = {}
  try {
    if (editing.value) await api.post('/departure-reasons/update', { id: editing.value.id, ...form })
    else await api.post('/departure-reasons/create', form)
    message.success(editing.value ? 'Saved.' : 'Reason added.')
    modalOpen.value = false
    await load()
  } catch (e) {
    errors.value = fieldErrors(e)
    if (!Object.keys(errors.value).length) message.error(errorMessage(e))
  } finally {
    saving.value = false
  }
}

async function toggle(reason: DepartureReason, active: boolean) {
  try {
    await api.post('/departure-reasons/update', { id: reason.id, name: reason.name, is_active: active })
    reason.is_active = active
  } catch (e) {
    message.error(errorMessage(e))
  }
}

function remove(reason: DepartureReason) {
  dialog.warning({
    title: `Delete “${reason.name}”?`,
    content: 'If it has been used for someone who left, it can only be deactivated.',
    positiveText: 'Delete',
    negativeText: 'Cancel',
    onPositiveClick: async () => {
      try {
        await api.post('/departure-reasons/delete', { id: reason.id })
        message.success('Deleted.')
        await load()
      } catch (e) {
        message.error(errorMessage(e))
      }
    },
  })
}

const columns: DataTableColumns<DepartureReason> = [
  {
    title: 'Reason',
    key: 'name',
    render: (r) => h(NSpace, { size: 6, align: 'center' }, { default: () => [h('strong', { style: r.is_active ? '' : 'opacity:.5' }, r.name), r.is_system ? h(NTag, { size: 'tiny', round: true }, { default: () => 'Built in' }) : null] }),
  },
  { title: 'Needs a note', key: 'requires_note', render: (r) => (r.requires_note ? 'Yes' : '—') },
  { title: 'In use', key: 'is_active', render: (r) => h(NSwitch, { size: 'small', value: r.is_active, 'onUpdate:value': (v: boolean) => toggle(r, v) }) },
  {
    title: '',
    key: 'actions',
    align: 'right',
    render: (r) =>
      h(NSpace, { justify: 'end', size: 4 }, {
        default: () => [
          h(NButton, { size: 'small', onClick: () => open(r) }, { default: () => 'Edit' }),
          r.is_system ? null : h(NButton, { size: 'small', quaternary: true, type: 'error', onClick: () => remove(r) }, { default: () => 'Delete' }),
        ],
      }),
  },
]

onMounted(load)
</script>

<template>
  <div class="page">
    <div class="page-header">
      <div>
        <h1>Leaving reasons</h1>
        <p class="muted subtitle">Chosen when someone leaves. Switch off any you don’t use.</p>
      </div>
      <NButton type="primary" @click="open(null)">
        <template #icon><NIcon><AddOutline /></NIcon></template>
        Add reason
      </NButton>
    </div>

    <NAlert v-if="loadError" type="error" :show-icon="false">{{ loadError }}</NAlert>
    <NDataTable v-else :columns="columns" :data="reasons" :loading="loading" :bordered="false" :row-key="(r: DepartureReason) => r.id">
      <template #empty><NEmpty description="No leaving reasons yet." /></template>
    </NDataTable>

    <NModal v-model:show="modalOpen" preset="card" :title="editing ? 'Edit reason' : 'Add a reason'" style="max-width: 460px">
      <NForm @submit.prevent="save">
        <NFormItem label="Reason" :feedback="errors.name" :validation-status="errors.name ? 'error' : undefined">
          <NInput v-model:value="form.name" placeholder="e.g. Relocation" />
        </NFormItem>
        <NFormItem :show-label="false">
          <NCheckbox v-model:checked="form.requires_note">Ask for a note when this reason is chosen</NCheckbox>
        </NFormItem>
        <NSpace justify="end">
          <NButton @click="modalOpen = false">Cancel</NButton>
          <NButton type="primary" attr-type="submit" :loading="saving" :disabled="!form.name.trim()">Save</NButton>
        </NSpace>
      </NForm>
    </NModal>
  </div>
</template>

<style scoped>
.subtitle {
  margin: 6px 0 0;
}
</style>
