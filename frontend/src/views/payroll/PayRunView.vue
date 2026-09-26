<script setup lang="ts">
import { ArrowBackOutline } from '@vicons/ionicons5'
import { NAlert, NButton, NCard, NDataTable, NEmpty, NGrid, NGridItem, NIcon, NRadioButton, NRadioGroup, NSkeleton, NSpace, NTag, useDialog, useMessage, type DataTableColumns, type DataTableRowKey } from 'naive-ui'
import { computed, h, onMounted, onUnmounted, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { api, errorMessage } from '@/api/client'
import type { ApiEnvelope, PayRunDetail, Payslip, PayslipStatus } from '@/api/types'
import StatCard from '@/components/StatCard.vue'
import { formatMoney } from '@/lib/money'
import { canApprove, isUnpaid, PAYROLL_MANAGERS, PAYSLIP_STATUS, periodLabel } from '@/lib/payroll'
import { formatDate } from '@/lib/staff'
import { useAuthStore } from '@/stores/auth'

/**
 * One pay run: totals, anyone whose payslip couldn't be made, and every
 * payslip with bulk approve / regenerate / carry forward.
 */
const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const message = useMessage()
const dialog = useDialog()

const run = ref<PayRunDetail | null>(null)
const payslips = ref<Payslip[]>([])
const error = ref('')
const busy = ref(false)
const filter = ref<'' | PayslipStatus>('')
const checked = ref<DataTableRowKey[]>([])
let poll: ReturnType<typeof setTimeout> | undefined

const canManage = computed(() => auth.can(PAYROLL_MANAGERS))
const id = computed(() => Number(route.params.id))

async function load() {
  try {
    const [r, p] = await Promise.all([
      api.post<ApiEnvelope<PayRunDetail>>('/payroll/pay-runs/show', { id: id.value }),
      api.post<ApiEnvelope<Payslip[]>>('/payroll/payslips/list', { pay_run_id: id.value }),
    ])
    run.value = r.data.data
    payslips.value = p.data.data
    checked.value = checked.value.filter((k) => payslips.value.some((s) => s.id === k))
    clearTimeout(poll)
    if (run.value.status === 'processing') poll = setTimeout(load, 3000)
  } catch (e) {
    error.value = errorMessage(e)
  }
}

const shown = computed(() => (filter.value ? payslips.value.filter((s) => s.status === filter.value) : payslips.value))
const selected = computed(() => payslips.value.filter((s) => checked.value.includes(s.id)))
const approvable = computed(() => selected.value.filter((s) => canApprove(s, auth.me)))
const drafts = computed(() => selected.value.filter((s) => s.status === 'draft'))
const unpaid = computed(() => selected.value.filter(isUnpaid))
const waitingForMe = computed(() => payslips.value.filter((s) => canApprove(s, auth.me)))

const columns = computed<DataTableColumns<Payslip>>(() => [
  { type: 'selection' },
  {
    title: 'Person',
    key: 'staff',
    render: (s) => h('div', [h(RouterLink, { to: { name: 'payslip', params: { id: s.id } }, class: 'name' }, { default: () => s.staff?.name }), h('small', s.staff?.designation ?? '')]),
  },
  { title: 'Days paid', key: 'payment_days', render: (s) => (s.payment_days === s.period_days ? 'Full period' : `${s.payment_days} of ${s.period_days}`) },
  { title: 'Gross', key: 'gross_earnings', align: 'right', render: (s) => formatMoney(s.gross_earnings, s.currency) },
  { title: 'Deductions', key: 'total_deductions', align: 'right', render: (s) => formatMoney(s.total_deductions, s.currency) },
  { title: 'Net pay', key: 'net_pay', align: 'right', render: (s) => h('strong', formatMoney(s.net_pay, s.currency)) },
  {
    title: 'Status',
    key: 'status',
    render: (s) =>
      h(NSpace, { size: 4 }, {
        default: () => [
          h(NTag, { size: 'small', round: true, type: PAYSLIP_STATUS[s.status].type }, { default: () => PAYSLIP_STATUS[s.status].label }),
          s.payment_method === 'cheque' && !s.cheque_number && s.status === 'approved' ? h(NTag, { size: 'small', round: true, type: 'warning' }, { default: () => 'Cheque no. needed' }) : null,
        ],
      }),
  },
])

async function act(url: string, ids: number[], done?: (msg: string) => void) {
  busy.value = true
  try {
    const { data } = await api.post<ApiEnvelope<unknown>>(url, { ids })
    ;(done ?? message.success)(data.message)
    checked.value = []
    await load()
  } catch (e) {
    message.error(errorMessage(e))
  } finally {
    busy.value = false
  }
}

const approve = () => act('/payroll/payslips/approve', approvable.value.map((s) => s.id), (msg) => (msg.includes(' 0 skipped') ? message.success(msg) : message.warning(msg)))
const regenerate = () => act('/payroll/payslips/regenerate', drafts.value.map((s) => s.id))

function carryForward() {
  dialog.warning({
    title: `Carry forward ${unpaid.value.length} payslip(s)?`,
    content: 'They won’t be paid this period. Their net pay is added as a credit to the next payslip.',
    positiveText: 'Carry forward',
    negativeText: 'Cancel',
    onPositiveClick: () => act('/payroll/payslips/carry-forward', unpaid.value.map((s) => s.id)),
  })
}

async function retryFailures() {
  busy.value = true
  try {
    const ids = run.value!.failures.map((f) => f.user_id).filter((x): x is number => !!x)
    const { data } = await api.post<ApiEnvelope<unknown>>('/payroll/pay-runs/generate', { pay_run_id: id.value, user_ids: ids.length ? ids : undefined })
    message.success(data.message)
    setTimeout(load, 1500)
  } catch (e) {
    message.error(errorMessage(e))
  } finally {
    busy.value = false
  }
}

onMounted(load)
onUnmounted(() => clearTimeout(poll))
</script>

<template>
  <div class="page wide">
    <NButton text class="back" @click="router.push({ name: 'payroll-runs' })">
      <template #icon><NIcon><ArrowBackOutline /></NIcon></template>
      Pay runs
    </NButton>

    <NAlert v-if="error" type="error" :show-icon="false">{{ error }}</NAlert>
    <NSkeleton v-else-if="!run" height="360px" :sharp="false" />

    <template v-else>
      <div class="page-header">
        <div>
          <h1>{{ periodLabel(run) }}</h1>
          <p class="muted subtitle">
            {{ run.frequency === 'monthly' ? 'Monthly' : 'Every two weeks' }} ·
            {{ formatDate(run.period_start) }} – {{ formatDate(run.period_end) }}
            <template v-if="run.pay_date"> · paid {{ formatDate(run.pay_date) }}</template>
            <template v-if="run.created_by"> · started by {{ run.created_by.name }}</template>
          </p>
        </div>
      </div>

      <NAlert v-if="run.status === 'processing'" type="info" class="gap">Payslips are being generated — this page updates by itself.</NAlert>

      <NAlert v-if="run.failures.length" type="error" title="Some payslips couldn’t be made" class="gap">
        <ul class="failures">
          <li v-for="(f, i) in run.failures" :key="i"><strong>{{ f.name ?? 'Unknown person' }}</strong> — {{ f.message }}</li>
        </ul>
        <NButton v-if="canManage" size="small" :loading="busy" @click="retryFailures">Fix the cause, then try again</NButton>
      </NAlert>

      <NGrid :cols="4" :x-gap="16" :y-gap="16" item-responsive responsive="screen" class="gap">
        <NGridItem span="4 s:2 l:1"><StatCard label="Payslips" :value="run.payslips_count" /></NGridItem>
        <NGridItem span="4 s:2 l:1"><StatCard label="Waiting for you" :value="waitingForMe.length" :tone="waitingForMe.length ? 'warning' : 'default'" hint="You can approve these now" /></NGridItem>
        <NGridItem v-for="t in run.totals" :key="t.currency" span="4 s:2 l:1">
          <StatCard :label="`Net pay (${t.currency})`" :value="formatMoney(t.net, t.currency)" :hint="`Gross ${formatMoney(t.gross, t.currency)} · deductions ${formatMoney(t.deductions, t.currency)}`" />
        </NGridItem>
      </NGrid>

      <NCard>
        <div class="toolbar">
          <NRadioGroup v-model:value="filter" size="small">
            <NRadioButton value="">All ({{ payslips.length }})</NRadioButton>
            <NRadioButton value="draft">Draft ({{ run.draft_count }})</NRadioButton>
            <NRadioButton value="l1_approved">Level 1 ({{ run.l1_approved_count }})</NRadioButton>
            <NRadioButton value="approved">Approved ({{ run.approved_count }})</NRadioButton>
            <NRadioButton v-if="run.carried_forward_count" value="carried_forward">Carried forward ({{ run.carried_forward_count }})</NRadioButton>
          </NRadioGroup>
          <NSpace v-if="selected.length">
            <NButton v-if="approvable.length" type="primary" :loading="busy" @click="approve">Approve {{ approvable.length }}</NButton>
            <NButton v-if="canManage && drafts.length" :loading="busy" @click="regenerate">Regenerate {{ drafts.length }}</NButton>
            <NButton v-if="canManage && unpaid.length" quaternary type="warning" :loading="busy" @click="carryForward">Carry forward {{ unpaid.length }}</NButton>
          </NSpace>
          <span v-else class="muted small">Tick payslips to approve them in bulk.</span>
        </div>

        <NDataTable v-model:checked-row-keys="checked" :columns="columns" :data="shown" :bordered="false" :row-key="(s: Payslip) => s.id" size="small">
          <template #empty><NEmpty description="No payslips here." /></template>
        </NDataTable>
      </NCard>
    </template>
  </div>
</template>

<style scoped>
.wide {
  max-width: 1180px;
}

.back {
  margin-bottom: 14px;
}

.subtitle {
  margin: 6px 0 0;
}

.gap {
  margin-bottom: 16px;
}

.failures {
  margin: 4px 0 10px;
  padding-left: 18px;
}

.toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
  margin-bottom: 12px;
}

.small {
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
