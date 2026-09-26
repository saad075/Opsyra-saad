<script setup lang="ts">
import { ArrowBackOutline, PrintOutline } from '@vicons/ionicons5'
import { NAlert, NButton, NCard, NForm, NFormItem, NGrid, NGridItem, NIcon, NInput, NModal, NSkeleton, NSpace, NTag, useMessage, useThemeVars } from 'naive-ui'
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api, errorMessage } from '@/api/client'
import type { ApiEnvelope, Payslip } from '@/api/types'
import { formatMoney } from '@/lib/money'
import { canApprove, PAYROLL_MANAGERS, PAYSLIP_STATUS, periodLabel } from '@/lib/payroll'
import { formatDate } from '@/lib/staff'
import { useAuthStore } from '@/stores/auth'

/**
 * One payslip, laid out like the paper one (earnings beside deductions,
 * net pay at the foot) and printable. Staff see their own once approved;
 * payroll staff see any, and act on it here.
 */
const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const message = useMessage()
const theme = useThemeVars()
const print = () => window.print()

const slip = ref<Payslip | null>(null)
const error = ref('')
const busy = ref(false)

const money = (n: number) => formatMoney(n, slip.value!.currency)
const earnings = computed(() => (slip.value?.lines ?? []).filter((l) => l.type === 'earning'))
const deductions = computed(() => (slip.value?.lines ?? []).filter((l) => l.type === 'deduction'))
const isMine = computed(() => slip.value?.user_id === auth.me?.user.id)
const canManage = computed(() => auth.can(PAYROLL_MANAGERS))
const needsCheque = computed(() => slip.value?.status === 'approved' && slip.value.payment_method === 'cheque' && !slip.value.cheque_number)
const openQuery = computed(() => slip.value?.claims?.find((c) => c.status === 'open'))

async function load() {
  try {
    const { data } = await api.post<ApiEnvelope<Payslip>>('/payroll/payslips/show', { id: Number(route.params.id) })
    slip.value = data.data
    error.value = ''
  } catch (e) {
    error.value = errorMessage(e)
  }
}

async function post(url: string, body: object, after?: () => void) {
  busy.value = true
  try {
    const { data } = await api.post<ApiEnvelope<unknown>>(url, body)
    message.success(data.message)
    after?.()
    await load()
  } catch (e) {
    message.error(errorMessage(e))
  } finally {
    busy.value = false
  }
}

const approve = () => post('/payroll/payslips/approve', { ids: [slip.value!.id] })

const cheque = ref('')
const saveCheque = () => post('/payroll/payslips/cheque-number', { id: slip.value!.id, cheque_number: cheque.value.trim() }, () => (cheque.value = ''))

const asking = ref(false)
const reason = ref('')
const sendQuery = () => post('/payroll/claims/create', { payslip_id: slip.value!.id, reason: reason.value.trim() }, () => ((asking.value = false), (reason.value = '')))

watch(() => route.params.id, load)
onMounted(load)
</script>

<template>
  <div class="page">
    <div class="no-print top">
      <NButton text @click="router.back()">
        <template #icon><NIcon><ArrowBackOutline /></NIcon></template>
        Back
      </NButton>
      <NSpace v-if="slip">
        <NButton v-if="isMine && slip.status === 'approved' && !openQuery" @click="asking = true">Question this payslip</NButton>
        <NButton v-if="canApprove(slip, auth.me)" type="primary" :loading="busy" @click="approve">Approve</NButton>
        <NButton @click="print">
          <template #icon><NIcon><PrintOutline /></NIcon></template>
          Print
        </NButton>
      </NSpace>
    </div>

    <NAlert v-if="error" type="error" :show-icon="false">{{ error }}</NAlert>
    <NSkeleton v-else-if="!slip" height="420px" :sharp="false" />

    <template v-else>
      <NCard class="slip">
        <div class="slip-head">
          <div>
            <p class="muted company">{{ auth.me?.company.name }} · Payslip</p>
            <h1>{{ slip.staff?.name }}</h1>
            <p class="muted">{{ slip.staff?.designation }}</p>
          </div>
          <div class="period">
            <strong>{{ periodLabel(slip) }}</strong>
            <span class="muted">{{ formatDate(slip.period_start) }} – {{ formatDate(slip.period_end) }}</span>
            <span class="muted">Days paid: {{ slip.payment_days }} of {{ slip.period_days }}</span>
            <NTag size="small" round :type="PAYSLIP_STATUS[slip.status].type">{{ PAYSLIP_STATUS[slip.status].label }}</NTag>
          </div>
        </div>

        <NGrid :cols="2" :x-gap="24" :y-gap="16" item-responsive responsive="screen" class="columns">
          <NGridItem span="2 m:1">
            <h3>Earnings</h3>
            <div v-for="l in earnings" :key="l.id" class="line">
              <span>{{ l.label }}</span><span>{{ money(l.amount) }}</span>
            </div>
            <div class="line total"><span>Gross earnings</span><span>{{ money(slip.gross_earnings) }}</span></div>
          </NGridItem>
          <NGridItem span="2 m:1">
            <h3>Deductions</h3>
            <p v-if="!deductions.length" class="muted">None this period.</p>
            <div v-for="l in deductions" :key="l.id" class="line">
              <span>{{ l.label }}</span><span>{{ money(l.amount) }}</span>
            </div>
            <div class="line total"><span>Total deductions</span><span>{{ money(slip.total_deductions) }}</span></div>
          </NGridItem>
        </NGrid>

        <div class="net">
          <span>Net pay</span>
          <strong>{{ money(slip.net_pay) }}</strong>
        </div>

        <div class="trail muted">
          <span>Generated {{ formatDate(slip.generated_at) }}</span>
          <span v-if="slip.l1_approver">Level 1: {{ slip.l1_approver.name }}, {{ formatDate(slip.l1_approved_at) }}</span>
          <span v-if="slip.approver">Approved: {{ slip.approver.name }}, {{ formatDate(slip.approved_at) }}</span>
          <span v-if="slip.payment_method">Paid by {{ slip.payment_method === 'transfer' ? 'bank transfer' : 'cheque' }}<template v-if="slip.cheque_number"> no. {{ slip.cheque_number }}</template></span>
          <span v-if="slip.pay_run?.pay_date">Pay date {{ formatDate(slip.pay_run.pay_date) }}</span>
        </div>
      </NCard>

      <NCard v-if="needsCheque && canManage" title="Cheque number" class="gap no-print">
        <p class="muted">No bank details were on file, so this payslip is paid by cheque.</p>
        <NSpace>
          <NInput v-model:value="cheque" placeholder="e.g. 004512" style="width: 220px" @keyup.enter="cheque.trim() && saveCheque()" />
          <NButton type="primary" :disabled="!cheque.trim()" :loading="busy" @click="saveCheque">Save</NButton>
        </NSpace>
      </NCard>

      <NCard v-if="slip.claims?.length" title="Questions about this payslip" class="gap no-print">
        <div v-for="c in slip.claims" :key="c.id" class="claim">
          <div class="claim-head">
            <NTag size="small" round :type="c.status === 'open' ? 'warning' : c.status === 'approved' ? 'success' : 'default'">
              {{ c.status === 'open' ? 'Waiting for HR' : c.status === 'approved' ? 'Accepted' : 'Declined' }}
            </NTag>
            <span class="muted">{{ formatDate(c.created_at) }}</span>
          </div>
          <p>{{ c.reason }}</p>
          <p v-if="c.response" class="response"><strong>HR:</strong> {{ c.response }}</p>
        </div>
      </NCard>

      <NModal v-model:show="asking" preset="card" title="Question this payslip" style="max-width: 480px">
        <NForm @submit.prevent="sendQuery">
          <NFormItem label="What looks wrong?">
            <NInput v-model:value="reason" type="textarea" :autosize="{ minRows: 3 }" placeholder="e.g. My overtime for the 14th is missing." />
          </NFormItem>
          <p class="muted small">HR will reply here. If they agree, the difference is paid on your next payslip.</p>
          <NSpace justify="end">
            <NButton @click="asking = false">Cancel</NButton>
            <NButton type="primary" attr-type="submit" :disabled="!reason.trim()" :loading="busy">Send to HR</NButton>
          </NSpace>
        </NForm>
      </NModal>
    </template>
  </div>
</template>

<style scoped>
.top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
  margin-bottom: 14px;
}

.slip-head {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
  padding-bottom: 16px;
  border-bottom: 1px solid rgba(127, 127, 127, 0.2);
}

.slip-head h1 {
  margin: 4px 0 2px;
}

.slip-head p {
  margin: 0;
}

.company {
  font-size: 0.85rem;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.period {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 4px;
}

.columns {
  margin-top: 16px;
}

h3 {
  margin: 0 0 8px;
  font-size: 1rem;
}

.line {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  padding: 6px 0;
  border-bottom: 1px dashed rgba(127, 127, 127, 0.2);
  font-variant-numeric: tabular-nums;
}

.line.total {
  border-bottom: 0;
  font-weight: 700;
}

.net {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  margin-top: 20px;
  padding: 14px 16px;
  border-radius: 8px;
  background: color-mix(in srgb, v-bind('theme.primaryColor') 10%, transparent);
  font-size: 1.1rem;
}

.net strong {
  font-size: 1.5rem;
  color: v-bind('theme.primaryColor');
  font-variant-numeric: tabular-nums;
}

.trail {
  display: flex;
  flex-wrap: wrap;
  gap: 6px 18px;
  margin-top: 14px;
  font-size: 0.88rem;
}

.gap {
  margin-top: 16px;
}

.claim + .claim {
  margin-top: 14px;
  padding-top: 14px;
  border-top: 1px solid rgba(127, 127, 127, 0.18);
}

.claim-head {
  display: flex;
  gap: 10px;
  align-items: center;
}

.claim p {
  margin: 6px 0 0;
}

.response {
  opacity: 0.85;
}

.small {
  font-size: 0.9rem;
  margin: 0 0 14px;
}

@media (max-width: 640px) {
  .period {
    align-items: flex-start;
  }
}
</style>
