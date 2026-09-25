<script setup lang="ts">
import { NAlert, NCard, NEmpty, NGrid, NGridItem, NSkeleton, NTag } from 'naive-ui'
import { computed, onMounted, ref } from 'vue'
import { api, errorMessage } from '@/api/client'
import type { ApiEnvelope, PayrollDashboard } from '@/api/types'
import OChart from '@/components/charts/OChart.vue'
import StatCard from '@/components/StatCard.vue'
import { formatMoney } from '@/lib/money'

const data = ref<PayrollDashboard | null>(null)
const error = ref('')

const periodLabel = (start: string) => new Date(`${start}T00:00:00`).toLocaleDateString(undefined, { month: 'short', year: '2-digit' })

// One series per currency — never add different currencies together.
const currencies = computed(() => [...new Set((data.value?.runs ?? []).flatMap((r) => r.totals.map((t) => t.currency)))])
const trendSeries = computed(() =>
  currencies.value.map((currency) => ({
    label: `Net pay (${currency})`,
    data: (data.value?.runs ?? []).map((r) => r.totals.find((t) => t.currency === currency)?.net ?? 0),
  })),
)
const latest = computed(() => data.value?.runs.at(-1))
const earnings = computed(() => (data.value?.latest_breakdown ?? []).filter((l) => l.type === 'earning'))
const deductions = computed(() => (data.value?.latest_breakdown ?? []).filter((l) => l.type === 'deduction'))

onMounted(async () => {
  try {
    const { data: res } = await api.get<ApiEnvelope<PayrollDashboard>>('/payroll/dashboard')
    data.value = res.data
  } catch (e) {
    error.value = errorMessage(e)
  }
})
</script>

<template>
  <div class="page wide">
    <div class="page-header">
      <div>
        <h1>Payroll dashboard</h1>
        <p class="muted subtitle">Pay runs, approvals and what still needs doing.</p>
      </div>
    </div>

    <NAlert v-if="error" type="error" :show-icon="false">{{ error }}</NAlert>

    <template v-else-if="!data">
      <NGrid :cols="4" :x-gap="16" item-responsive responsive="screen">
        <NGridItem v-for="i in 4" :key="i" span="4 s:2 l:1"><NSkeleton height="104px" :sharp="false" /></NGridItem>
      </NGrid>
      <NSkeleton height="300px" :sharp="false" class="gap" />
    </template>

    <template v-else>
      <NGrid :cols="4" :x-gap="16" :y-gap="16" item-responsive responsive="screen">
        <NGridItem span="4 s:2 l:1">
          <StatCard label="Waiting for you" :value="data.awaiting.for_me" hint="Payslips you can approve now" :tone="data.awaiting.for_me ? 'warning' : 'default'" />
        </NGridItem>
        <NGridItem span="4 s:2 l:1">
          <StatCard label="Drafts" :value="data.awaiting.drafts" :hint="`${data.awaiting.level_2} awaiting level 2`" />
        </NGridItem>
        <NGridItem span="4 s:2 l:1">
          <StatCard label="Cheques to number" :value="data.awaiting.cheques" :tone="data.awaiting.cheques ? 'warning' : 'default'" />
        </NGridItem>
        <NGridItem span="4 s:2 l:1">
          <StatCard label="Open salary queries" :value="data.awaiting.claims" :tone="data.awaiting.claims ? 'warning' : 'default'" />
        </NGridItem>
      </NGrid>

      <NGrid :cols="3" :x-gap="16" :y-gap="16" item-responsive responsive="screen" class="gap">
        <NGridItem span="3 l:2">
          <NCard title="Net pay by pay run">
            <OChart v-if="data.runs.length" type="bar" :labels="data.runs.map((r) => periodLabel(r.period_start))" :series="trendSeries" />
            <NEmpty v-else description="No pay runs yet. Your first run will appear here." />
          </NCard>
        </NGridItem>
        <NGridItem span="3 l:1">
          <NCard title="Salary set-up">
            <ul class="facts">
              <li>
                <span>Active staff with a salary</span>
                <strong :class="{ warn: data.coverage.with_salary < data.coverage.active_staff }">
                  {{ data.coverage.with_salary }} / {{ data.coverage.active_staff }}
                </strong>
              </li>
              <li>
                <span>With bank details</span>
                <strong>{{ data.coverage.with_bank_details }} / {{ data.coverage.active_staff }}</strong>
              </li>
            </ul>
            <p class="muted small">Staff without bank details are paid by cheque.</p>
          </NCard>
        </NGridItem>
      </NGrid>

      <NGrid v-if="latest" :cols="2" :x-gap="16" :y-gap="16" item-responsive responsive="screen" class="gap">
        <NGridItem span="2 l:1">
          <NCard>
            <template #header>
              Latest run
              <NTag size="small" round class="tag">{{ latest.frequency }}</NTag>
            </template>
            <p class="muted">{{ latest.period_start }} to {{ latest.period_end }} · {{ latest.payslips }} payslips · {{ latest.approved }} approved</p>
            <NAlert v-if="latest.failures" type="warning" :show-icon="false" class="gap">
              {{ latest.failures }} {{ latest.failures === 1 ? 'person' : 'people' }} couldn’t be paid in this run — open the run to see why.
            </NAlert>
            <table class="totals">
              <thead><tr><th></th><th>Gross</th><th>Deductions</th><th>Net</th></tr></thead>
              <tbody>
                <tr v-for="t in latest.totals" :key="t.currency">
                  <th>{{ t.currency }}</th>
                  <td>{{ formatMoney(t.gross, t.currency) }}</td>
                  <td>{{ formatMoney(t.deductions, t.currency) }}</td>
                  <td><strong>{{ formatMoney(t.net, t.currency) }}</strong></td>
                </tr>
              </tbody>
            </table>
          </NCard>
        </NGridItem>
        <NGridItem span="2 l:1">
          <NCard title="What made up the latest run">
            <OChart
              v-if="earnings.length"
              type="doughnut"
              :height="220"
              :labels="[...earnings, ...deductions].map((l) => `${l.label}${l.type === 'deduction' ? ' (deduction)' : ''}`)"
              :series="[{ label: 'Amount', data: [...earnings, ...deductions].map((l) => l.amount) }]"
            />
          </NCard>
        </NGridItem>
      </NGrid>
    </template>
  </div>
</template>

<style scoped>
.wide {
  max-width: 1280px;
}

.subtitle {
  margin: 6px 0 0;
}

.gap {
  margin-top: 16px;
}

.small {
  font-size: 0.85rem;
  margin: 10px 0 0;
}

.tag {
  margin-left: 8px;
  vertical-align: middle;
}

.facts {
  list-style: none;
  margin: 0;
  padding: 0;
}

.facts li {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  padding: 9px 0;
  border-top: 1px solid rgba(127, 127, 127, 0.18);
}

.facts li:first-child {
  border-top: 0;
}

.facts strong {
  font-family: 'Plus Jakarta Sans', 'Segoe UI', sans-serif;
}

.facts strong.warn {
  color: #8a5a00;
}

.totals {
  width: 100%;
  margin-top: 14px;
  border-collapse: collapse;
  font-variant-numeric: tabular-nums;
}

.totals th,
.totals td {
  padding: 8px 6px;
  text-align: right;
  border-top: 1px solid rgba(127, 127, 127, 0.18);
}

.totals thead th {
  border-top: 0;
  font-weight: 600;
  opacity: 0.7;
}

.totals tbody th {
  text-align: left;
}
</style>
