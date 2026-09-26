<script setup lang="ts">
import { NAlert, NButton, NDataTable, NEmpty, NInput, NRadioButton, NRadioGroup, NSpace, NTag, type DataTableColumns } from 'naive-ui'
import { computed, h, onMounted, ref } from 'vue'
import { api, errorMessage } from '@/api/client'
import type { ApiEnvelope, PayComponent, SalaryOverviewRow } from '@/api/types'
import SalaryDrawer from '@/components/SalaryDrawer.vue'
import { formatMoney } from '@/lib/money'
import { FREQUENCY_LABEL } from '@/lib/payroll'
import { formatDate } from '@/lib/staff'

/**
 * Everyone who should be paid, what they're on, and who still needs a
 * salary or bank details — the Setup wizard's Salaries and Bank details
 * steps are done here.
 */
const rows = ref<SalaryOverviewRow[]>([])
const components = ref<PayComponent[]>([])
const loading = ref(true)
const loadError = ref('')
const filter = ref<'all' | 'no_salary' | 'no_bank'>('all')
const search = ref('')
const opened = ref<SalaryOverviewRow | null>(null)

async function load() {
  try {
    const { data } = await api.get<ApiEnvelope<SalaryOverviewRow[]>>('/payroll/salaries/overview')
    rows.value = data.data
    if (opened.value) opened.value = rows.value.find((r) => r.id === opened.value!.id) ?? null
    loadError.value = ''
  } catch (e) {
    loadError.value = errorMessage(e)
  } finally {
    loading.value = false
  }
}

const counts = computed(() => ({ noSalary: rows.value.filter((r) => !r.salary).length, noBank: rows.value.filter((r) => !r.has_bank_account).length }))
const shown = computed(() => {
  const q = search.value.trim().toLowerCase()
  return rows.value
    .filter((r) => (filter.value === 'no_salary' ? !r.salary : filter.value === 'no_bank' ? !r.has_bank_account : true))
    .filter((r) => !q || r.name.toLowerCase().includes(q) || (r.designation ?? '').toLowerCase().includes(q))
})

const columns: DataTableColumns<SalaryOverviewRow> = [
  { title: 'Person', key: 'name', render: (r) => h('div', [h('strong', r.name), h('small', [r.designation, r.status === 'pending' ? 'starting soon' : null].filter(Boolean).join(' · '))]) },
  {
    title: 'Salary',
    key: 'salary',
    render: (r) =>
      r.salary
        ? h('div', [h('span', `${formatMoney(r.salary.net, r.salary.currency)} take-home`), h('small', `${FREQUENCY_LABEL[r.salary.pay_frequency]} · gross ${formatMoney(r.salary.gross, r.salary.currency)} · from ${formatDate(r.salary.effective_from)}`)])
        : h(NTag, { size: 'small', round: true, type: 'warning' }, { default: () => 'Not set' }),
  },
  {
    title: 'Paid by',
    key: 'bank',
    render: (r) => (r.has_bank_account ? 'Bank transfer' : h(NTag, { size: 'small', round: true }, { default: () => 'Cheque (no bank details)' })),
  },
  { title: '', key: 'actions', align: 'right', render: (r) => h(NButton, { size: 'small', type: r.salary ? 'default' : 'primary', onClick: (e: MouseEvent) => (e.stopPropagation(), (opened.value = r)) }, { default: () => (r.salary ? 'Change' : 'Set salary') }) },
]

onMounted(async () => {
  await load()
  components.value = (await api.get<ApiEnvelope<PayComponent[]>>('/payroll/pay-components/list').catch(() => ({ data: { data: [] } }))).data.data
})
</script>

<template>
  <div class="page wide">
    <div class="page-header">
      <div>
        <h1>Salaries</h1>
        <p class="muted subtitle">
          <template v-if="!loading">{{ rows.length - counts.noSalary }} of {{ rows.length }} people have a salary · {{ rows.length - counts.noBank }} have bank details</template>
        </p>
      </div>
      <NSpace align="center">
        <NRadioGroup v-model:value="filter" size="small">
          <NRadioButton value="all">Everyone</NRadioButton>
          <NRadioButton value="no_salary">No salary ({{ counts.noSalary }})</NRadioButton>
          <NRadioButton value="no_bank">No bank details ({{ counts.noBank }})</NRadioButton>
        </NRadioGroup>
        <NInput v-model:value="search" size="small" placeholder="Find someone" clearable style="width: 180px" />
      </NSpace>
    </div>

    <NAlert v-if="loadError" type="error" :show-icon="false">{{ loadError }}</NAlert>
    <NDataTable
      v-else
      :columns="columns"
      :data="shown"
      :loading="loading"
      :bordered="false"
      :row-key="(r: SalaryOverviewRow) => r.id"
      :row-props="(r: SalaryOverviewRow) => ({ style: 'cursor: pointer', onClick: () => (opened = r) })"
    >
      <template #empty><NEmpty description="Nobody here." /></template>
    </NDataTable>

    <SalaryDrawer :person="opened" :components="components" @close="opened = null" @saved="load" />
  </div>
</template>

<style scoped>
.wide {
  max-width: 1180px;
}

.subtitle {
  margin: 6px 0 0;
  min-height: 1.2em;
}

:deep(small) {
  display: block;
  opacity: 0.6;
}
</style>
