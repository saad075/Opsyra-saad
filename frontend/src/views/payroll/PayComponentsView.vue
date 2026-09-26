<script setup lang="ts">
import { AddOutline } from '@vicons/ionicons5'
import { NAlert, NButton, NCard, NCheckbox, NDataTable, NEmpty, NForm, NFormItem, NGrid, NGridItem, NIcon, NInput, NModal, NRadio, NRadioGroup, NSpace, NSwitch, NTag, useDialog, useMessage, type DataTableColumns } from 'naive-ui'
import { computed, h, onMounted, reactive, ref } from 'vue'
import { api, errorMessage, fieldErrors } from '@/api/client'
import type { ApiEnvelope, PayCalculation, PayComponent } from '@/api/types'
import { CALCULATION_LABEL } from '@/lib/payroll'

/**
 * The earnings and deductions a salary is built from. Pre-filled per
 * country; a component's type and calculation are fixed once created
 * (salaries and payslips depend on them) — only its name and settings change.
 */
const message = useMessage()
const dialog = useDialog()

const components = ref<PayComponent[]>([])
const loading = ref(true)
const loadError = ref('')

async function load() {
  try {
    const { data } = await api.get<ApiEnvelope<PayComponent[]>>('/payroll/pay-components/list', { params: { include_inactive: 1 } })
    components.value = data.data
    loadError.value = ''
  } catch (e) {
    loadError.value = errorMessage(e)
  } finally {
    loading.value = false
  }
}

const earnings = computed(() => components.value.filter((c) => c.type === 'earning'))
const deductions = computed(() => components.value.filter((c) => c.type === 'deduction'))

const editing = ref<PayComponent | null>(null)
const modalOpen = ref(false)
const saving = ref(false)
const errors = ref<Record<string, string>>({})
const form = reactive({ name: '', type: 'earning' as 'earning' | 'deduction', calculation: 'fixed' as PayCalculation, prorate: true })

function open(component: PayComponent | null, type: 'earning' | 'deduction' = 'earning') {
  editing.value = component
  errors.value = {}
  Object.assign(form, { name: component?.name ?? '', type: component?.type ?? type, calculation: component?.calculation ?? 'fixed', prorate: component?.prorate ?? true })
  modalOpen.value = true
}

// % of gross only makes sense for deductions (gross isn't known while earnings are added up).
const calculations = computed<PayCalculation[]>(() => (form.type === 'earning' ? ['fixed', 'percent_of_basic'] : ['fixed', 'percent_of_basic', 'percent_of_gross']))

async function save() {
  saving.value = true
  errors.value = {}
  try {
    if (editing.value) await api.post('/payroll/pay-components/update', { id: editing.value.id, name: form.name, prorate: form.prorate })
    else await api.post('/payroll/pay-components/create', form)
    message.success(editing.value ? 'Saved.' : 'Component added.')
    modalOpen.value = false
    await load()
  } catch (e) {
    errors.value = fieldErrors(e)
    if (!Object.keys(errors.value).length) message.error(errorMessage(e))
  } finally {
    saving.value = false
  }
}

async function toggle(c: PayComponent, active: boolean) {
  try {
    await api.post('/payroll/pay-components/update', { id: c.id, name: c.name, is_active: active })
    c.is_active = active
  } catch (e) {
    message.error(errorMessage(e))
  }
}

function remove(c: PayComponent) {
  dialog.warning({
    title: `Delete ${c.name}?`,
    content: 'If it is on anyone’s salary it can only be switched off.',
    positiveText: 'Delete',
    negativeText: 'Cancel',
    onPositiveClick: async () => {
      try {
        await api.post('/payroll/pay-components/delete', { id: c.id })
        message.success('Deleted.')
        await load()
      } catch (e) {
        message.error(errorMessage(e))
      }
    },
  })
}

const columns: DataTableColumns<PayComponent> = [
  {
    title: 'Component',
    key: 'name',
    render: (c) =>
      h('div', [
        h(NSpace, { size: 6, align: 'center' }, { default: () => [h('strong', { style: c.is_active ? '' : 'opacity:.5' }, c.name), c.is_basic ? h(NTag, { size: 'tiny', round: true, type: 'primary' }, { default: () => 'Basic pay' }) : null] }),
        h('small', CALCULATION_LABEL[c.calculation] + (c.calculation === 'fixed' && !c.prorate ? ' · not reduced for part months' : '')),
      ]),
  },
  { title: 'In use', key: 'is_active', width: 80, render: (c) => h(NSwitch, { size: 'small', value: c.is_active, disabled: c.is_basic, 'onUpdate:value': (v: boolean) => toggle(c, v) }) },
  {
    title: '',
    key: 'actions',
    align: 'right',
    width: 140,
    render: (c) =>
      h(NSpace, { justify: 'end', size: 4 }, {
        default: () => [
          h(NButton, { size: 'small', onClick: () => open(c) }, { default: () => 'Edit' }),
          c.is_system ? null : h(NButton, { size: 'small', quaternary: true, type: 'error', onClick: () => remove(c) }, { default: () => 'Delete' }),
        ],
      }),
  },
]

onMounted(load)
</script>

<template>
  <div class="page wide">
    <div class="page-header">
      <div>
        <h1>Pay components</h1>
        <p class="muted subtitle">The earnings and deductions each salary is built from. Pre-filled for your country — add what your company uses.</p>
      </div>
    </div>

    <NAlert v-if="loadError" type="error" :show-icon="false">{{ loadError }}</NAlert>
    <NGrid v-else :cols="2" :x-gap="16" :y-gap="16" item-responsive responsive="screen">
      <NGridItem v-for="g in [{ type: 'earning' as const, title: 'Earnings', list: earnings }, { type: 'deduction' as const, title: 'Deductions', list: deductions }]" :key="g.type" span="2 l:1">
        <NCard :title="g.title">
          <template #header-extra>
            <NButton size="small" type="primary" @click="open(null, g.type)">
              <template #icon><NIcon><AddOutline /></NIcon></template>
              Add
            </NButton>
          </template>
          <NDataTable :columns="columns" :data="g.list" :loading="loading" :bordered="false" size="small" :row-key="(c: PayComponent) => c.id">
            <template #empty><NEmpty :description="`No ${g.title.toLowerCase()} yet.`" /></template>
          </NDataTable>
        </NCard>
      </NGridItem>
    </NGrid>

    <NModal v-model:show="modalOpen" preset="card" :title="editing ? `Edit ${editing.name}` : `Add ${form.type === 'earning' ? 'an earning' : 'a deduction'}`" style="max-width: 480px">
      <NForm @submit.prevent="save">
        <NFormItem label="Name" :feedback="errors.name" :validation-status="errors.name ? 'error' : undefined">
          <NInput v-model:value="form.name" :placeholder="form.type === 'earning' ? 'e.g. Transport allowance' : 'e.g. Pension contribution'" />
        </NFormItem>
        <NFormItem label="How it’s worked out" :feedback="editing ? 'Fixed once created — salaries depend on it.' : errors.calculation" :validation-status="errors.calculation ? 'error' : undefined">
          <NRadioGroup v-model:value="form.calculation" :disabled="!!editing">
            <NSpace vertical>
              <NRadio v-for="c in calculations" :key="c" :value="c">{{ CALCULATION_LABEL[c] }}</NRadio>
            </NSpace>
          </NRadioGroup>
        </NFormItem>
        <NFormItem v-if="form.calculation === 'fixed'" :show-label="false">
          <NCheckbox v-model:checked="form.prorate">Reduce it for someone who joins or leaves part-way through a pay period</NCheckbox>
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

:deep(small) {
  display: block;
  opacity: 0.6;
}
</style>
