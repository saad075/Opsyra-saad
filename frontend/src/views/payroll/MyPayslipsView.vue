<script setup lang="ts">
import { ChevronForward } from '@vicons/ionicons5'
import { NAlert, NCard, NEmpty, NIcon, NSkeleton, NTag } from 'naive-ui'
import { onMounted, ref } from 'vue'
import { api, errorMessage } from '@/api/client'
import type { ApiEnvelope, Payslip, SalaryClaim } from '@/api/types'
import { formatMoney } from '@/lib/money'
import { periodLabel } from '@/lib/payroll'

/** Everyone's own approved payslips, and any questions they've raised. */
const payslips = ref<Payslip[] | null>(null)
const claims = ref<SalaryClaim[]>([])
const error = ref('')

onMounted(async () => {
  try {
    const { data } = await api.get<ApiEnvelope<Payslip[]>>('/payroll/payslips/mine')
    payslips.value = data.data
  } catch (e) {
    error.value = errorMessage(e)
  }
  // Loaded separately: a slow or failed request here shouldn't hold the payslips.
  api
    .get<ApiEnvelope<SalaryClaim[]>>('/payroll/claims/mine')
    .then(({ data }) => (claims.value = data.data))
    .catch(() => {})
})
</script>

<template>
  <div class="page">
    <div class="page-header">
      <div>
        <h1>My payslips</h1>
        <p class="muted subtitle">Payslips appear here once they’re approved.</p>
      </div>
    </div>

    <NAlert v-if="error" type="error" :show-icon="false">{{ error }}</NAlert>
    <NSkeleton v-else-if="!payslips" height="200px" :sharp="false" />
    <NCard v-else-if="!payslips.length"><NEmpty description="No payslips yet." /></NCard>
    <NCard v-else>
      <RouterLink v-for="p in payslips" :key="p.id" :to="{ name: 'payslip', params: { id: p.id } }" class="row">
        <span class="period">
          <strong>{{ periodLabel(p) }}</strong>
          <NTag v-if="p.status === 'carried_forward'" size="tiny" round type="warning">Carried forward</NTag>
        </span>
        <span class="net">{{ formatMoney(p.net_pay, p.currency) }}</span>
        <NIcon class="chev"><ChevronForward /></NIcon>
      </RouterLink>
    </NCard>

    <NCard v-if="claims.length" title="My questions" class="gap">
      <div v-for="c in claims" :key="c.id" class="claim">
        <NTag size="small" round :type="c.status === 'open' ? 'warning' : c.status === 'approved' ? 'success' : 'default'">
          {{ c.status === 'open' ? 'Waiting for HR' : c.status === 'approved' ? 'Accepted' : 'Declined' }}
        </NTag>
        <RouterLink v-if="c.payslip" :to="{ name: 'payslip', params: { id: c.payslip.id } }" class="link">{{ periodLabel(c.payslip) }}</RouterLink>
        <p>{{ c.reason }}</p>
        <p v-if="c.response" class="muted"><strong>HR:</strong> {{ c.response }}</p>
      </div>
    </NCard>
  </div>
</template>

<style scoped>
.subtitle {
  margin: 6px 0 0;
}

.row {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 4px;
  color: inherit;
  text-decoration: none;
  border-top: 1px solid rgba(127, 127, 127, 0.18);
}

.row:first-child {
  border-top: 0;
}

.period {
  flex: 1;
  display: flex;
  gap: 8px;
  align-items: center;
}

.net {
  font-weight: 700;
  font-variant-numeric: tabular-nums;
}

.chev {
  opacity: 0.5;
}

.gap {
  margin-top: 16px;
}

.claim + .claim {
  margin-top: 14px;
  padding-top: 14px;
  border-top: 1px solid rgba(127, 127, 127, 0.18);
}

.link {
  margin-left: 8px;
  color: inherit;
  font-weight: 600;
}

.claim p {
  margin: 6px 0 0;
}
</style>
