<script setup lang="ts">
import { NAlert, NButton, NCard, NEmpty, NInput, NSkeleton, useMessage } from 'naive-ui'
import { computed, onMounted, reactive, ref } from 'vue'
import { api, errorMessage } from '@/api/client'
import type { ApiEnvelope, Payslip } from '@/api/types'
import { formatMoney } from '@/lib/money'
import { PAYROLL_MANAGERS, periodLabel } from '@/lib/payroll'
import { useAuthStore } from '@/stores/auth'

/**
 * Approved payslips paid by cheque (no bank details on file) that still
 * need the cheque number written down. Approval never waits for this.
 */
type Pending = Pick<Payslip, 'id' | 'user_id' | 'period_start' | 'period_end' | 'currency' | 'net_pay'> & { staff: { id: number; name: string } }

const auth = useAuthStore()
const message = useMessage()
const canManage = computed(() => auth.can(PAYROLL_MANAGERS))

const rows = ref<Pending[] | null>(null)
const error = ref('')
const numbers = reactive<Record<number, string>>({})
const saving = ref<number | null>(null)

async function load() {
  try {
    const { data } = await api.get<ApiEnvelope<Pending[]>>('/payroll/payslips/pending-cheques')
    rows.value = data.data
  } catch (e) {
    error.value = errorMessage(e)
  }
}

async function save(row: Pending) {
  saving.value = row.id
  try {
    await api.post('/payroll/payslips/cheque-number', { id: row.id, cheque_number: numbers[row.id].trim() })
    message.success(`Cheque number saved for ${row.staff.name}.`)
    rows.value = rows.value!.filter((r) => r.id !== row.id)
  } catch (e) {
    message.error(errorMessage(e))
  } finally {
    saving.value = null
  }
}

onMounted(load)
</script>

<template>
  <div class="page">
    <div class="page-header">
      <div>
        <h1>Cheques</h1>
        <p class="muted subtitle">Approved pay going out by cheque, waiting for its cheque number.</p>
      </div>
    </div>

    <NAlert v-if="error" type="error" :show-icon="false">{{ error }}</NAlert>
    <NSkeleton v-else-if="!rows" height="200px" :sharp="false" />
    <NCard v-else-if="!rows.length"><NEmpty description="No cheques waiting for a number." /></NCard>
    <NCard v-else>
      <div v-for="r in rows" :key="r.id" class="row">
        <div class="who">
          <RouterLink :to="{ name: 'payslip', params: { id: r.id } }" class="name">{{ r.staff.name }}</RouterLink>
          <small>{{ periodLabel(r) }}</small>
        </div>
        <strong class="amount">{{ formatMoney(r.net_pay, r.currency) }}</strong>
        <div v-if="canManage" class="entry">
          <NInput v-model:value="numbers[r.id]" size="small" placeholder="Cheque no." style="width: 150px" @keyup.enter="numbers[r.id]?.trim() && save(r)" />
          <NButton size="small" type="primary" :disabled="!numbers[r.id]?.trim()" :loading="saving === r.id" @click="save(r)">Save</NButton>
        </div>
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
  gap: 16px;
  flex-wrap: wrap;
  padding: 12px 0;
  border-top: 1px solid rgba(127, 127, 127, 0.18);
}

.row:first-child {
  border-top: 0;
}

.who {
  flex: 1 1 200px;
}

.name {
  font-weight: 600;
  color: inherit;
}

small {
  display: block;
  opacity: 0.6;
}

.amount {
  font-variant-numeric: tabular-nums;
}

.entry {
  display: flex;
  gap: 8px;
}
</style>
