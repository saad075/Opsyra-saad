<script setup lang="ts">
import { AddOutline } from '@vicons/ionicons5'
import { NAlert, NButton, NCard, NDataTable, NEmpty, NForm, NFormItem, NGrid, NGridItem, NIcon, NInput, NModal, NSelect, NSpace, useDialog, useMessage, type DataTableColumns } from 'naive-ui'
import { computed, h, onMounted, reactive, ref } from 'vue'
import { api, errorMessage, fieldErrors } from '@/api/client'
import type { ApiEnvelope, Department, Designation } from '@/api/types'

/**
 * Departments and the job titles in each — two short lists side by side.
 * Picking a department narrows the job titles to it.
 */
const message = useMessage()
const dialog = useDialog()

const departments = ref<Department[]>([])
const designations = ref<Designation[]>([])
const loading = ref(true)
const loadError = ref('')
const selected = ref<number | null>(null)

async function load() {
  try {
    const [d, t] = await Promise.all([
      api.get<ApiEnvelope<Department[]>>('/departments/list'),
      api.get<ApiEnvelope<Designation[]>>('/designations-management/list'),
    ])
    departments.value = d.data.data
    designations.value = t.data.data
    loadError.value = ''
  } catch (e) {
    loadError.value = errorMessage(e)
  } finally {
    loading.value = false
  }
}

const shownTitles = computed(() => (selected.value ? designations.value.filter((t) => t.department_id === selected.value) : designations.value))
const departmentOptions = computed(() => departments.value.map((d) => ({ label: d.name, value: d.id })))

// One dialog for both kinds.
type Kind = 'department' | 'title'
const kind = ref<Kind>('department')
const editingId = ref<number | null>(null)
const modalOpen = ref(false)
const saving = ref(false)
const errors = ref<Record<string, string>>({})
const form = reactive({ name: '', description: '', department_id: null as number | null })

function open(k: Kind, item: Department | Designation | null) {
  kind.value = k
  editingId.value = item?.id ?? null
  errors.value = {}
  Object.assign(form, {
    name: item?.name ?? '',
    description: (item as Department | null)?.description ?? '',
    department_id: k === 'title' ? ((item as Designation | null)?.department_id ?? selected.value) : null,
  })
  modalOpen.value = true
}

const base = computed(() => (kind.value === 'department' ? '/departments' : '/designations-management'))

async function save() {
  saving.value = true
  errors.value = {}
  const body = kind.value === 'department' ? { name: form.name, description: form.description || null } : { name: form.name, department_id: form.department_id }
  try {
    await api.post(`${base.value}/${editingId.value ? 'update' : 'create'}`, editingId.value ? { id: editingId.value, ...body } : body)
    message.success(editingId.value ? 'Saved.' : kind.value === 'department' ? 'Department added.' : 'Job title added.')
    modalOpen.value = false
    await load()
  } catch (e) {
    errors.value = fieldErrors(e)
    if (!Object.keys(errors.value).length) message.error(errorMessage(e))
  } finally {
    saving.value = false
  }
}

function remove(k: Kind, item: { id: number; name: string }) {
  dialog.warning({
    title: `Delete ${item.name}?`,
    content: k === 'department' ? 'A department with job titles in it can’t be deleted — move or delete those first.' : 'Staff who already have this job title keep it on their profile.',
    positiveText: 'Delete',
    negativeText: 'Cancel',
    onPositiveClick: async () => {
      try {
        await api.post(`${k === 'department' ? '/departments' : '/designations-management'}/delete`, { id: item.id })
        message.success('Deleted.')
        if (k === 'department' && selected.value === item.id) selected.value = null
        await load()
      } catch (e) {
        message.error(errorMessage(e))
      }
    },
  })
}

const actions = (k: Kind, item: Department | Designation) =>
  h(NSpace, { justify: 'end', size: 4 }, {
    default: () => [
      h(NButton, { size: 'small', onClick: (e: MouseEvent) => (e.stopPropagation(), open(k, item)) }, { default: () => 'Edit' }),
      h(NButton, { size: 'small', quaternary: true, type: 'error', onClick: (e: MouseEvent) => (e.stopPropagation(), remove(k, item)) }, { default: () => 'Delete' }),
    ],
  })

const departmentColumns: DataTableColumns<Department> = [
  { title: 'Department', key: 'name', render: (d) => h('div', [h('strong', d.name), d.description ? h('small', d.description) : null]) },
  { title: 'Job titles', key: 'designations_count', align: 'right', width: 90 },
  { title: '', key: 'actions', align: 'right', render: (d) => actions('department', d) },
]

const titleColumns: DataTableColumns<Designation> = [
  { title: 'Job title', key: 'name', render: (t) => h('strong', t.name) },
  { title: 'Department', key: 'department', render: (t) => t.department?.name ?? '—' },
  { title: '', key: 'actions', align: 'right', render: (t) => actions('title', t) },
]

onMounted(load)
</script>

<template>
  <div class="page wide">
    <div class="page-header">
      <div>
        <h1>Departments and job titles</h1>
        <p class="muted subtitle">How your organisation is divided, and the job titles within each part.</p>
      </div>
    </div>

    <NAlert v-if="loadError" type="error" :show-icon="false">{{ loadError }}</NAlert>

    <NGrid v-else :cols="2" :x-gap="16" :y-gap="16" item-responsive responsive="screen">
      <NGridItem span="2 l:1">
        <NCard title="Departments">
          <template #header-extra>
            <NButton size="small" type="primary" @click="open('department', null)">
              <template #icon><NIcon><AddOutline /></NIcon></template>
              Add
            </NButton>
          </template>
          <NDataTable
            :columns="departmentColumns"
            :data="departments"
            :loading="loading"
            :bordered="false"
            size="small"
            :row-key="(d: Department) => d.id"
            :row-class-name="(d: Department) => (d.id === selected ? 'picked' : '')"
            :row-props="(d: Department) => ({ style: 'cursor: pointer', onClick: () => (selected = selected === d.id ? null : d.id) })"
          >
            <template #empty><NEmpty description="No departments yet — e.g. Finance, Operations, Sales." /></template>
          </NDataTable>
        </NCard>
      </NGridItem>

      <NGridItem span="2 l:1">
        <NCard>
          <template #header>
            Job titles
            <span v-if="selected" class="muted filter">
              in {{ departments.find((d) => d.id === selected)?.name }} ·
              <a href="#" @click.prevent="selected = null">show all</a>
            </span>
          </template>
          <template #header-extra>
            <NButton size="small" type="primary" @click="open('title', null)">
              <template #icon><NIcon><AddOutline /></NIcon></template>
              Add
            </NButton>
          </template>
          <NDataTable :columns="titleColumns" :data="shownTitles" :loading="loading" :bordered="false" size="small" :row-key="(t: Designation) => t.id">
            <template #empty><NEmpty description="No job titles here yet." /></template>
          </NDataTable>
        </NCard>
      </NGridItem>
    </NGrid>

    <NModal v-model:show="modalOpen" preset="card" :title="`${editingId ? 'Edit' : 'Add'} ${kind === 'department' ? 'department' : 'job title'}`" style="max-width: 460px">
      <NForm @submit.prevent="save">
        <NFormItem label="Name" :feedback="errors.name" :validation-status="errors.name ? 'error' : undefined">
          <NInput v-model:value="form.name" :placeholder="kind === 'department' ? 'e.g. Finance' : 'e.g. Accountant'" />
        </NFormItem>
        <NFormItem v-if="kind === 'department'" label="Description (optional)">
          <NInput v-model:value="form.description" type="textarea" :autosize="{ minRows: 2 }" />
        </NFormItem>
        <NFormItem v-else label="Department (optional)" :feedback="errors.department_id" :validation-status="errors.department_id ? 'error' : undefined">
          <NSelect v-model:value="form.department_id" :options="departmentOptions" clearable />
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
.wide {
  max-width: 1180px;
}

.subtitle {
  margin: 6px 0 0;
}

.filter {
  font-size: 0.9rem;
  font-weight: normal;
}

:deep(small) {
  display: block;
  opacity: 0.6;
}

:deep(.picked td) {
  background: rgba(14, 110, 98, 0.08) !important;
}
</style>
