<script setup lang="ts">
import { NAlert, NButton, NCard, NForm, NFormItem, NInputNumber, NRadioButton, NRadioGroup, NSkeleton, NSwitch, useMessage } from 'naive-ui'
import { onMounted, ref } from 'vue'
import { api, errorMessage } from '@/api/client'
import type { AttendancePolicyValues } from '@/api/attendanceTypes'
import type { ApiEnvelope } from '@/api/types'

/**
 * The company's attendance rules, in two parts: how a short day is judged,
 * and — only if switched on — what attendance costs in pay. Pay rules are
 * applied when a payslip is made, so changing them never rewrites the past.
 */
const message = useMessage()
const policy = ref<AttendancePolicyValues | null>(null)
const saving = ref(false)
const error = ref('')

onMounted(async () => {
  try {
    policy.value = (await api.get<ApiEnvelope<AttendancePolicyValues>>('/attendance/policy')).data.data
  } catch (e) {
    error.value = errorMessage(e)
  }
})

async function save() {
  saving.value = true
  try {
    const { data } = await api.post<ApiEnvelope<AttendancePolicyValues>>('/attendance/policy/update', policy.value)
    policy.value = data.data
    message.success(data.message)
  } catch (e) {
    message.error(errorMessage(e))
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="page">
    <div class="page-header">
      <div>
        <h1>Attendance rules</h1>
        <p class="muted subtitle">How short days are judged, and whether attendance affects pay.</p>
      </div>
    </div>

    <NAlert v-if="error" type="error" :show-icon="false">{{ error }}</NAlert>
    <NSkeleton v-else-if="!policy" height="400px" :sharp="false" />

    <NForm v-else @submit.prevent="save">
      <NCard title="Judging a day" class="gap">
        <p class="muted small">As a share of the day’s scheduled hours.</p>
        <div class="rule">
          <span>Worked less than</span>
          <NInputNumber v-model:value="policy.half_day_below_percent" :min="0" :max="100" size="small" style="width: 110px"><template #suffix>%</template></NInputNumber>
          <span>is a <strong>half day</strong>; less than</span>
          <NInputNumber v-model:value="policy.absent_below_percent" :min="0" :max="100" size="small" style="width: 110px"><template #suffix>%</template></NInputNumber>
          <span>is <strong>absent</strong>.</span>
        </div>
      </NCard>

      <NCard title="Pay" class="gap">
        <NFormItem label="Attendance affects pay" label-placement="left">
          <NSwitch v-model:value="policy.deductions_enabled" />
        </NFormItem>
        <NAlert v-if="!policy.deductions_enabled" type="info" :show-icon="false">Off: payslips ignore attendance. Hourly pay always follows the hours worked.</NAlert>

        <template v-else>
          <p class="muted small">A day’s pay is the salary (basic plus allowances) divided by the days in the pay period. Each deduction appears as its own line on the payslip.</p>
          <div class="rule"><span>Each <strong>absent</strong> day costs</span><NInputNumber v-model:value="policy.absent_deduction_days" :min="0" :max="3" :step="0.5" size="small" style="width: 110px" /><span>day’s pay.</span></div>
          <div class="rule"><span>Each <strong>half day</strong> costs</span><NInputNumber v-model:value="policy.half_day_deduction_days" :min="0" :max="1" :step="0.25" size="small" style="width: 110px" /><span>day’s pay.</span></div>
          <div class="rule"><span>Each day of <strong>unpaid leave</strong> costs</span><NInputNumber v-model:value="policy.unpaid_leave_deduction_days" :min="0" :max="3" :step="0.5" size="small" style="width: 110px" /><span>day’s pay.</span></div>
          <div class="rule">
            <span>The first</span><NInputNumber v-model:value="policy.free_lates_per_month" :min="0" :max="31" size="small" style="width: 90px" />
            <span><strong>late arrivals</strong> a month are free; after that each costs</span>
            <NInputNumber v-model:value="policy.late_deduction_percent" :min="0" :max="100" size="small" style="width: 110px"><template #suffix>%</template></NInputNumber><span>of a day’s pay.</span>
          </div>
          <div class="rule">
            <span>The first</span><NInputNumber v-model:value="policy.free_early_leaves_per_month" :min="0" :max="31" size="small" style="width: 90px" />
            <span><strong>early leaves</strong> a month are free; after that each costs</span>
            <NInputNumber v-model:value="policy.early_leave_deduction_percent" :min="0" :max="100" size="small" style="width: 110px"><template #suffix>%</template></NInputNumber><span>of a day’s pay.</span>
          </div>
          <div class="rule">
            <span>A <strong>missed check-out</strong> counts as</span>
            <NRadioGroup v-model:value="policy.missed_checkout_counts_as" size="small">
              <NRadioButton value="present">Present</NRadioButton>
              <NRadioButton value="half_day">Half day</NRadioButton>
              <NRadioButton value="absent">Absent</NRadioButton>
            </NRadioGroup>
            <span class="muted small">until HR corrects the day.</span>
          </div>
        </template>
      </NCard>

      <NButton type="primary" attr-type="submit" :loading="saving">Save rules</NButton>
    </NForm>
  </div>
</template>

<style scoped>
.subtitle {
  margin: 6px 0 0;
}

.gap {
  margin-bottom: 16px;
}

.small {
  font-size: 0.9rem;
  margin: 0 0 12px;
}

.rule {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
  padding: 8px 0;
}
</style>
