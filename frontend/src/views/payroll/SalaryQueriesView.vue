<script setup lang="ts">
import { NAlert, NButton, NCard, NEmpty, NForm, NFormItem, NInput, NInputNumber, NModal, NRadioButton, NRadioGroup, NSkeleton, NSpace, NTag, useMessage } from 'naive-ui'
import { onMounted, reactive, ref, watch } from 'vue'
import { api, errorMessage } from '@/api/client'
import type { ApiEnvelope, SalaryClaim } from '@/api/types'
import { formatMoney } from '@/lib/money'
import { periodLabel } from '@/lib/payroll'
import { formatDate } from '@/lib/staff'

/**
 * Staff questions about an approved payslip. Accepting one can add a credit
 * or deduction to the person's next payslip — approved pay is never
 * rewritten after the fact.
 */
const message = useMessage()
const status = ref<'open' | 'approved' | 'rejected'>('open')
const claims = ref<SalaryClaim[] | null>(null)
const error = ref('')

async function load() {
  claims.value = null
  try {
    const { data } = await api.get<ApiEnvelope<SalaryClaim[]>>('/payroll/claims/list', { params: { status: status.value } })
    claims.value = data.data
  } catch (e) {
    error.value = errorMessage(e)
  }
}

// Answer a query.
const answering = ref<SalaryClaim | null>(null)
const decision = ref<'approve' | 'reject'>('approve')
const saving = ref(false)
const form = reactive({ response: '', adjustment_type: 'credit' as 'credit' | 'debit', adjustment_amount: null as number | null })

function answer(claim: SalaryClaim, how: 'approve' | 'reject') {
  answering.value = claim
  decision.value = how
  Object.assign(form, { response: '', adjustment_type: 'credit', adjustment_amount: null })
}

async function submit() {
  saving.value = true
  try {
    const body =
      decision.value === 'approve'
        ? { id: answering.value!.id, response: form.response, ...(form.adjustment_amount ? { adjustment_type: form.adjustment_type, adjustment_amount: form.adjustment_amount } : {}) }
        : { id: answering.value!.id, response: form.response }
    const { data } = await api.post<ApiEnvelope<unknown>>(`/payroll/claims/${decision.value}`, body)
    message.success(data.message)
    answering.value = null
    await load()
  } catch (e) {
    message.error(errorMessage(e))
  } finally {
    saving.value = false
  }
}

watch(status, load)
onMounted(load)
</script>

<template>
  <div class="page">
    <div class="page-header">
      <div>
        <h1>Salary queries</h1>
        <p class="muted subtitle">Questions from staff about their payslips.</p>
      </div>
      <NRadioGroup v-model:value="status" size="small">
        <NRadioButton value="open">Open</NRadioButton>
        <NRadioButton value="approved">Accepted</NRadioButton>
        <NRadioButton value="rejected">Declined</NRadioButton>
      </NRadioGroup>
    </div>

    <NAlert v-if="error" type="error" :show-icon="false">{{ error }}</NAlert>
    <NSkeleton v-else-if="!claims" height="200px" :sharp="false" />
    <NCard v-else-if="!claims.length"><NEmpty :description="status === 'open' ? 'No open queries.' : 'Nothing here yet.'" /></NCard>

    <NCard v-for="c in claims ?? []" :key="c.id" class="claim">
      <div class="claim-head">
        <div>
          <strong>{{ c.staff?.name }}</strong>
          <span v-if="c.payslip" class="muted">
            ·
            <RouterLink :to="{ name: 'payslip', params: { id: c.payslip.id } }" class="link">{{ periodLabel(c.payslip) }} payslip</RouterLink>
            ({{ formatMoney(c.payslip.net_pay, c.payslip.currency) }})
          </span>
        </div>
        <span class="muted">{{ formatDate(c.created_at) }}</span>
      </div>
      <p class="reason">{{ c.reason }}</p>
      <p v-if="c.response" class="response">
        <NTag size="small" round :type="c.status === 'approved' ? 'success' : 'default'">{{ c.status === 'approved' ? 'Accepted' : 'Declined' }}</NTag>
        {{ c.response }}
        <span v-if="c.resolved_by" class="muted"> — {{ c.resolved_by.name }}, {{ formatDate(c.resolved_at) }}</span>
        <span v-if="c.salary_adjustment_id" class="muted"> · settled on the next payslip</span>
      </p>
      <NSpace v-if="c.status === 'open'">
        <NButton type="primary" size="small" @click="answer(c, 'approve')">Accept</NButton>
        <NButton size="small" @click="answer(c, 'reject')">Decline</NButton>
      </NSpace>
    </NCard>

    <NModal :show="!!answering" preset="card" :title="decision === 'approve' ? 'Accept this query' : 'Decline this query'" style="max-width: 500px" @update:show="(v: boolean) => !v && (answering = null)">
      <NForm @submit.prevent="submit">
        <NFormItem label="Reply to the employee">
          <NInput v-model:value="form.response" type="textarea" :autosize="{ minRows: 3 }" :placeholder="decision === 'approve' ? 'e.g. You’re right — the overtime will be on your next payslip.' : 'e.g. The overtime was paid on the previous payslip.'" />
        </NFormItem>
        <template v-if="decision === 'approve'">
          <NFormItem label="Put it right on the next payslip (optional)">
            <NSpace>
              <NRadioGroup v-model:value="form.adjustment_type" size="small">
                <NRadioButton value="credit">Pay extra</NRadioButton>
                <NRadioButton value="debit">Deduct</NRadioButton>
              </NRadioGroup>
              <NInputNumber v-model:value="form.adjustment_amount" :min="0" :precision="3" placeholder="Amount" clearable style="width: 160px" />
            </NSpace>
          </NFormItem>
        </template>
        <NSpace justify="end">
          <NButton @click="answering = null">Cancel</NButton>
          <NButton type="primary" attr-type="submit" :disabled="!form.response.trim()" :loading="saving">{{ decision === 'approve' ? 'Accept' : 'Decline' }}</NButton>
        </NSpace>
      </NForm>
    </NModal>
  </div>
</template>

<style scoped>
.subtitle {
  margin: 6px 0 0;
}

.claim + .claim {
  margin-top: 12px;
}

.claim-head {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
}

.link {
  color: inherit;
}

.reason {
  margin: 8px 0 12px;
}

.response {
  margin: 0 0 4px;
}
</style>
