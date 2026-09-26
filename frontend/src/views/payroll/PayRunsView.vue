<script setup lang="ts">
import { AddOutline } from '@vicons/ionicons5'
import { NAlert, NButton, NDataTable, NDatePicker, NEmpty, NForm, NFormItem, NIcon, NModal, NRadioButton, NRadioGroup, NSpace, NTag, useMessage, type DataTableColumns } from 'naive-ui'
import { computed, h, onMounted, onUnmounted, reactive, ref, type VNode } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { api, errorMessage } from '@/api/client'
import type { ApiEnvelope, PayRun } from '@/api/types'
import { PAYROLL_MANAGERS, periodLabel } from '@/lib/payroll'
import { formatDate } from '@/lib/staff'
import { useAuthStore } from '@/stores/auth'

/** Every pay run, newest first, with how far each has got through approval. */
const auth = useAuthStore()
const router = useRouter()
const message = useMessage()
const canManage = computed(() => auth.can(PAYROLL_MANAGERS))

const runs = ref<PayRun[]>([])
const loading = ref(true)
const error = ref('')
let poll: ReturnType<typeof setTimeout> | undefined

async function load() {
  try {
    const { data } = await api.get<ApiEnvelope<PayRun[]>>('/payroll/pay-runs/list')
    runs.value = data.data
    error.value = ''
    // Payslips are generated in the background; check again until done.
    clearTimeout(poll)
    if (runs.value.some((r) => r.status === 'processing')) poll = setTimeout(load, 3000)
  } catch (e) {
    error.value = errorMessage(e)
  } finally {
    loading.value = false
  }
}

const columns: DataTableColumns<PayRun> = [
  {
    title: 'Period',
    key: 'period',
    render: (r) => h('div', [h(RouterLink, { to: { name: 'payroll-run', params: { id: r.id } }, class: 'name' }, { default: () => periodLabel(r) }), h('small', r.frequency === 'monthly' ? 'Monthly' : 'Every two weeks')]),
  },
  { title: 'Pay date', key: 'pay_date', render: (r) => formatDate(r.pay_date) || '—' },
  { title: 'Payslips', key: 'payslips_count', render: (r) => (r.status === 'processing' ? h(NTag, { size: 'small', round: true, type: 'info' }, { default: () => 'Generating…' }) : String(r.payslips_count)) },
  {
    title: 'Approval',
    key: 'approval',
    render: (r) => {
      if (r.status === 'processing') return '—'
      const waiting = r.draft_count + r.l1_approved_count
      const tags: VNode[] = []
      if (r.draft_count) tags.push(h(NTag, { size: 'small', round: true }, { default: () => `${r.draft_count} draft` }))
      if (r.l1_approved_count) tags.push(h(NTag, { size: 'small', round: true, type: 'info' }, { default: () => `${r.l1_approved_count} at level 1` }))
      if (!waiting && r.payslips_count) tags.push(h(NTag, { size: 'small', round: true, type: 'success' }, { default: () => 'All approved' }))
      return h(NSpace, { size: 4 }, { default: () => tags })
    },
  },
  { title: 'Problems', key: 'errors', render: (r) => (r.errors?.length ? h(NTag, { size: 'small', round: true, type: 'error' }, { default: () => `${r.errors!.length} not generated` }) : '—') },
]

// Start a run.
const creating = ref(false)
const saving = ref(false)
const form = reactive({ frequency: 'monthly' as 'monthly' | 'biweekly', period_start: null as string | null, pay_date: null as string | null })

function openCreate() {
  const now = new Date()
  const firstOfMonth = new Date(now.getFullYear(), now.getMonth(), 1)
  form.period_start = `${firstOfMonth.getFullYear()}-${String(firstOfMonth.getMonth() + 1).padStart(2, '0')}-01`
  creating.value = true
}

// A monthly run always starts on the 1st; the API refuses anything else.
const disableDate = (ts: number) => form.frequency === 'monthly' && new Date(ts).getDate() !== 1

async function create() {
  saving.value = true
  try {
    const { data } = await api.post<ApiEnvelope<PayRun>>('/payroll/pay-runs/create', form)
    message.success(data.message)
    creating.value = false
    router.push({ name: 'payroll-run', params: { id: data.data.id } })
  } catch (e) {
    message.error(errorMessage(e))
  } finally {
    saving.value = false
  }
}

onMounted(load)
onUnmounted(() => clearTimeout(poll))
</script>

<template>
  <div class="page wide">
    <div class="page-header">
      <div>
        <h1>Pay runs</h1>
        <p class="muted subtitle">Generate payslips for a period, then approve them.</p>
      </div>
      <NButton v-if="canManage" type="primary" @click="openCreate">
        <template #icon><NIcon><AddOutline /></NIcon></template>
        New pay run
      </NButton>
    </div>

    <NAlert v-if="error" type="error" :show-icon="false">{{ error }}</NAlert>
    <NDataTable
      v-else
      :columns="columns"
      :data="runs"
      :loading="loading"
      :bordered="false"
      :row-key="(r: PayRun) => r.id"
      :row-props="(r: PayRun) => ({ style: 'cursor: pointer', onClick: (e: MouseEvent) => { if (!(e.target as HTMLElement).closest('a')) router.push({ name: 'payroll-run', params: { id: r.id } }) } })"
    >
      <template #empty><NEmpty description="No pay runs yet." /></template>
    </NDataTable>

    <NModal v-model:show="creating" preset="card" title="New pay run" style="max-width: 460px">
      <NForm @submit.prevent="create">
        <NFormItem label="Pay frequency">
          <NRadioGroup v-model:value="form.frequency">
            <NRadioButton value="monthly">Monthly</NRadioButton>
            <NRadioButton value="biweekly">Every two weeks</NRadioButton>
          </NRadioGroup>
        </NFormItem>
        <NFormItem :label="form.frequency === 'monthly' ? 'Month (starts on the 1st)' : 'First day of the two weeks'">
          <NDatePicker v-model:formatted-value="form.period_start" value-format="yyyy-MM-dd" type="date" :is-date-disabled="disableDate" style="width: 100%" />
        </NFormItem>
        <NFormItem label="Pay date (optional)">
          <NDatePicker v-model:formatted-value="form.pay_date" value-format="yyyy-MM-dd" type="date" clearable style="width: 100%" />
        </NFormItem>
        <p class="muted hint">Payslips are made for everyone with a salary on this frequency. It takes a few seconds; you can leave the page.</p>
        <NSpace justify="end">
          <NButton @click="creating = false">Cancel</NButton>
          <NButton type="primary" attr-type="submit" :loading="saving" :disabled="!form.period_start">Start pay run</NButton>
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

.hint {
  margin: 0 0 16px;
  font-size: 0.9rem;
}

:deep(.name) {
  font-weight: 600;
  color: inherit;
}

:deep(small) {
  display: block;
  opacity: 0.6;
}
</style>
