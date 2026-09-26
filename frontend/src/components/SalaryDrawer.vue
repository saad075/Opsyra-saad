<script setup lang="ts">
import { AddOutline, TrashOutline } from '@vicons/ionicons5'
import { NAlert, NButton, NDatePicker, NDivider, NDrawer, NDrawerContent, NForm, NFormItem, NGrid, NGridItem, NIcon, NInput, NInputNumber, NRadioButton, NRadioGroup, NSelect, NSkeleton, NSpace, NTabPane, NTabs, NTag, useMessage } from 'naive-ui'
import { computed, reactive, ref, watch } from 'vue'
import { api, errorMessage, fieldErrors } from '@/api/client'
import type { ApiEnvelope, BankAccount, PayComponent, SalaryOverviewRow, SalaryPeriod, StaffSalary } from '@/api/types'
import { formatMoney } from '@/lib/money'
import { CALCULATION_LABEL, FREQUENCY_LABEL } from '@/lib/payroll'
import { formatDate } from '@/lib/staff'

/**
 * One person's pay: their salary (effective-dated — a change applies from
 * a date and never rewrites past payslips) and their bank details.
 */
const props = defineProps<{ person: SalaryOverviewRow | null; components: PayComponent[] }>()
const emit = defineEmits<{ close: []; saved: [] }>()
const message = useMessage()

const tab = ref<'salary' | 'bank'>('salary')
const loading = ref(false)
const history = ref<StaffSalary[]>([])

type Line = { pay_component_id: number; value: number | null }
const form = reactive({ effective_from: null as string | null, pay_frequency: 'monthly' as 'monthly' | 'biweekly', lines: [] as Line[] })
const saving = ref(false)

const byId = computed(() => new Map(props.components.map((c) => [c.id, c])))
const basic = computed(() => props.components.find((c) => c.is_basic))
const unused = computed(() => props.components.filter((c) => c.is_active && !c.is_basic && !form.lines.some((l) => l.pay_component_id === c.id)))

// Local calendar date — toISOString() would give tomorrow's date late in the evening west of UTC.
const today = () => {
  const d = new Date()
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
}

async function load(person: SalaryOverviewRow) {
  tab.value = 'salary'
  loading.value = true
  preview.value = null
  bank.value = null
  try {
    const { data } = await api.post<ApiEnvelope<StaffSalary[]>>('/payroll/salaries/history', { user_id: person.id })
    history.value = data.data
    // Start from what they're on now (the usual change is a raise), else just basic pay.
    const current = data.data.find((s) => s.id === person.salary?.id)
    form.pay_frequency = current?.pay_frequency ?? 'monthly'
    form.effective_from = today()
    form.lines = current ? current.components.map((c) => ({ pay_component_id: c.pay_component_id, value: c.value })) : basic.value ? [{ pay_component_id: basic.value.id, value: null }] : []
  } catch (e) {
    message.error(errorMessage(e))
  } finally {
    loading.value = false
  }
}

// Only when a different person opens: the parent refreshes its row after
// every save, which mustn't reset the drawer (e.g. back to the Salary tab).
watch(
  () => props.person?.id,
  (id) => id && props.person && load(props.person),
)

// Live preview — the API's own calculation, so it can't disagree with the payslip.
const preview = ref<SalaryPeriod | null>(null)
const previewError = ref('')
let timer: ReturnType<typeof setTimeout> | undefined
watch(
  () => form.lines.map((l) => `${l.pay_component_id}:${l.value}`).join(','),
  () => {
    clearTimeout(timer)
    timer = setTimeout(async () => {
      const lines = form.lines.filter((l) => l.value !== null)
      if (!lines.length) return (preview.value = null)
      try {
        const { data } = await api.post<ApiEnvelope<SalaryPeriod>>('/payroll/salaries/preview', { components: lines })
        preview.value = data.data
        previewError.value = ''
      } catch (e) {
        previewError.value = errorMessage(e)
      }
    }, 350)
  },
)

const isPercent = (id: number) => byId.value.get(id)?.calculation !== 'fixed'
const invalid = computed(() => !form.effective_from || form.lines.some((l) => l.value === null) || !form.lines.some((l) => l.pay_component_id === basic.value?.id))

async function saveSalary() {
  saving.value = true
  try {
    await api.post('/payroll/salaries/set', { user_id: props.person!.id, effective_from: form.effective_from, pay_frequency: form.pay_frequency, components: form.lines })
    message.success(`Salary saved for ${props.person!.name}, from ${formatDate(form.effective_from)}.`)
    emit('saved')
    await load(props.person!)
  } catch (e) {
    message.error(errorMessage(e))
  } finally {
    saving.value = false
  }
}

// Bank details.
const bank = ref<BankAccount | null>(null)
const bankErrors = ref<Record<string, string>>({})
const bankLoaded = ref(false)

async function loadBank() {
  bankLoaded.value = false
  try {
    const { data } = await api.post<ApiEnvelope<BankAccount | null>>('/payroll/bank-accounts/show', { user_id: props.person!.id })
    bank.value = data.data ?? { account_holder_name: props.person!.name, bank_name: '', bank_country: '', account_number: null, iban: null, routing_number: null, tax_id: null, bank_code: null, swift_bic: null, account_currency: null }
  } catch (e) {
    message.error(errorMessage(e))
  } finally {
    bankLoaded.value = true
  }
}

watch(tab, (t) => t === 'bank' && props.person && loadBank())

async function saveBank() {
  saving.value = true
  bankErrors.value = {}
  try {
    await api.post('/payroll/bank-accounts/save', { user_id: props.person!.id, ...bank.value, bank_country: bank.value!.bank_country.toUpperCase() })
    message.success('Bank details saved. This change is logged.')
    emit('saved')
  } catch (e) {
    bankErrors.value = fieldErrors(e)
    if (!Object.keys(bankErrors.value).length) message.error(errorMessage(e))
  } finally {
    saving.value = false
  }
}

const err = (field: string) => ({ feedback: bankErrors.value[field], validationStatus: bankErrors.value[field] ? ('error' as const) : undefined })
</script>

<template>
  <NDrawer :show="!!person" :width="640" placement="right" :trap-focus="false" @update:show="(v: boolean) => !v && emit('close')">
    <NDrawerContent v-if="person" :title="person.name" closable>
      <p class="muted sub">{{ person.designation }}<template v-if="person.status === 'pending'"> · starting soon</template></p>

      <NTabs v-model:value="tab" type="line">
        <NTabPane name="salary" tab="Salary">
          <NSkeleton v-if="loading" height="260px" :sharp="false" />
          <NForm v-else @submit.prevent="saveSalary">
            <NGrid :cols="2" :x-gap="12">
              <NGridItem>
                <NFormItem label="Applies from">
                  <NDatePicker v-model:formatted-value="form.effective_from" value-format="yyyy-MM-dd" type="date" style="width: 100%" />
                </NFormItem>
              </NGridItem>
              <NGridItem>
                <NFormItem label="Paid">
                  <NRadioGroup v-model:value="form.pay_frequency">
                    <NRadioButton value="monthly">Monthly</NRadioButton>
                    <NRadioButton value="biweekly">Every 2 weeks</NRadioButton>
                  </NRadioGroup>
                </NFormItem>
              </NGridItem>
            </NGrid>

            <div v-for="(line, i) in form.lines" :key="line.pay_component_id" class="line">
              <div class="line-name">
                <strong>{{ byId.get(line.pay_component_id)?.name }}</strong>
                <small>{{ byId.get(line.pay_component_id)?.type === 'deduction' ? 'Deduction' : 'Earning' }} · {{ CALCULATION_LABEL[byId.get(line.pay_component_id)?.calculation ?? 'fixed'] }}</small>
              </div>
              <NInputNumber v-model:value="line.value" :min="0" :max="isPercent(line.pay_component_id) ? 100 : undefined" :precision="isPercent(line.pay_component_id) ? 2 : 3" :placeholder="isPercent(line.pay_component_id) ? '%' : 'Amount'" style="width: 160px">
                <template v-if="isPercent(line.pay_component_id)" #suffix>%</template>
              </NInputNumber>
              <NButton quaternary circle size="small" :disabled="byId.get(line.pay_component_id)?.is_basic" aria-label="Remove" @click="form.lines.splice(i, 1)">
                <template #icon><NIcon><TrashOutline /></NIcon></template>
              </NButton>
            </div>
            <NSelect
              v-if="unused.length"
              :value="null"
              :options="unused.map((c) => ({ label: `${c.name} (${c.type === 'earning' ? 'earning' : 'deduction'})`, value: c.id }))"
              placeholder="Add an allowance or deduction…"
              size="small"
              class="add"
              @update:value="(id: number) => form.lines.push({ pay_component_id: id, value: null })"
            >
              <template #arrow><NIcon><AddOutline /></NIcon></template>
            </NSelect>

            <NAlert v-if="previewError" type="warning" :show-icon="false" class="preview">{{ previewError }}</NAlert>
            <div v-else-if="preview" class="preview box">
              <p class="muted small">Each full {{ form.pay_frequency === 'monthly' ? 'month' : 'two weeks' }} comes to:</p>
              <div class="sum"><span>Gross</span><span>{{ formatMoney(preview.gross, preview.currency) }}</span></div>
              <div class="sum"><span>Deductions</span><span>− {{ formatMoney(preview.deductions, preview.currency) }}</span></div>
              <div class="sum net"><span>Take-home pay</span><span>{{ formatMoney(preview.net, preview.currency) }}</span></div>
            </div>

            <NSpace justify="end" class="actions">
              <NButton type="primary" attr-type="submit" :loading="saving" :disabled="invalid">Save salary</NButton>
            </NSpace>
            <p class="muted small">A salary applies from its date. Payslips already made aren’t changed.</p>
          </NForm>

          <template v-if="history.length">
            <NDivider title-placement="left">History</NDivider>
            <div v-for="s in history" :key="s.id" class="history">
              <span>From <strong>{{ formatDate(s.effective_from) }}</strong></span>
              <NTag v-if="s.id === person.salary?.id" size="tiny" round type="primary">Current</NTag>
              <span class="muted">{{ FREQUENCY_LABEL[s.pay_frequency] }}</span>
              <span class="muted">basic {{ formatMoney(s.components.find((c) => c.component.code === basic?.code)?.value ?? 0, s.currency) }}</span>
              <span v-if="s.created_by" class="muted">· {{ s.created_by.name }}</span>
            </div>
          </template>
        </NTabPane>

        <NTabPane name="bank" tab="Bank details">
          <NSkeleton v-if="!bankLoaded" height="260px" :sharp="false" />
          <NForm v-else-if="bank" @submit.prevent="saveBank">
            <p class="muted small">Without bank details, pay goes out by cheque. Every change here is logged.</p>
            <NGrid :cols="2" :x-gap="12">
              <NGridItem :span="2"><NFormItem label="Account holder" v-bind="err('account_holder_name')"><NInput v-model:value="bank.account_holder_name" /></NFormItem></NGridItem>
              <NGridItem><NFormItem label="Bank" v-bind="err('bank_name')"><NInput v-model:value="bank.bank_name" /></NFormItem></NGridItem>
              <NGridItem><NFormItem label="Country (2 letters)" v-bind="err('bank_country')"><NInput v-model:value="bank.bank_country" maxlength="2" placeholder="e.g. US" /></NFormItem></NGridItem>
              <NGridItem><NFormItem label="Account number" v-bind="err('account_number')"><NInput v-model:value="bank.account_number" /></NFormItem></NGridItem>
              <NGridItem><NFormItem label="IBAN" v-bind="err('iban')"><NInput v-model:value="bank.iban" /></NFormItem></NGridItem>
              <NGridItem><NFormItem label="Routing / sort code" v-bind="err('routing_number')"><NInput v-model:value="bank.routing_number" /></NFormItem></NGridItem>
              <NGridItem><NFormItem label="SWIFT / BIC" v-bind="err('swift_bic')"><NInput v-model:value="bank.swift_bic" /></NFormItem></NGridItem>
              <NGridItem><NFormItem label="Bank code (optional)"><NInput v-model:value="bank.bank_code" /></NFormItem></NGridItem>
              <NGridItem><NFormItem label="Tax ID (optional)"><NInput v-model:value="bank.tax_id" /></NFormItem></NGridItem>
            </NGrid>
            <NSpace justify="end">
              <NButton type="primary" attr-type="submit" :loading="saving" :disabled="!bank.account_holder_name || !bank.bank_name || !bank.bank_country">Save bank details</NButton>
            </NSpace>
          </NForm>
        </NTabPane>
      </NTabs>
    </NDrawerContent>
  </NDrawer>
</template>

<style scoped>
.sub {
  margin: -8px 0 8px;
}

.line {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 0;
  border-top: 1px solid rgba(127, 127, 127, 0.18);
}

.line-name {
  flex: 1;
  min-width: 0;
}

small {
  display: block;
  opacity: 0.6;
}

.add {
  margin-top: 8px;
}

.box {
  margin-top: 16px;
  padding: 12px 14px;
  border-radius: 8px;
  background: rgba(14, 110, 98, 0.08);
}

.preview {
  margin-top: 16px;
}

.sum {
  display: flex;
  justify-content: space-between;
  font-variant-numeric: tabular-nums;
  padding: 2px 0;
}

.sum.net {
  font-weight: 700;
  font-size: 1.05rem;
  margin-top: 4px;
}

.small {
  font-size: 0.88rem;
  margin: 0 0 8px;
}

.actions {
  margin: 16px 0 8px;
}

.history {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px;
  padding: 4px 0;
  font-size: 0.92rem;
}
</style>
