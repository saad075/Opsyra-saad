<script setup lang="ts">
import { NAlert, NButton, NCard, NForm, NFormItem, NGrid, NGridItem, NInput, NModal, NSelect, NSkeleton, NSpace, useMessage } from 'naive-ui'
import { onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { api, errorMessage, fieldErrors } from '@/api/client'
import type { ApiEnvelope, CompanyOwnerInfo, CompanySettings, PersonRef } from '@/api/types'
import { formatDate } from '@/lib/staff'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const message = useMessage()
const auth = useAuthStore()

const loaded = ref(false)
const loadError = ref('')
const saving = ref(false)
const errors = ref<Record<string, string>>({})
const country = ref('')

const form = reactive({ company_name: '', address: '', city: '', phone: '', email: '', timezone: '', currency: '' })

const timezones = Intl.supportedValuesOf('timeZone').map((tz) => ({ label: tz.replace(/_/g, ' '), value: tz }))
const currencies = ['GBP', 'USD', 'OMR', 'PKR', 'EUR', 'AED', 'SAR'].map((c) => ({ label: c, value: c }))

// The company account owner: always an Administrator and can't be
// offboarded, so the company can't lock itself out. Only they hand it on.
const owner = ref<CompanyOwnerInfo | null>(null)
const transferOpen = ref(false)
const transferring = ref(false)
const transferErrors = ref<Record<string, string>>({})
const people = ref<PersonRef[]>([])
const transfer = reactive({ user_id: null as number | null, password: '', reason: '' })

async function loadOwner() {
  owner.value = (await api.get<ApiEnvelope<CompanyOwnerInfo>>('/company/owner/show').catch(() => null))?.data.data ?? null
}

async function openTransfer() {
  Object.assign(transfer, { user_id: null, password: '', reason: '' })
  transferErrors.value = {}
  transferOpen.value = true
  if (!people.value.length) {
    const { data } = await api.get<ApiEnvelope<PersonRef[]>>('/staff/eligible-managers', { params: { exclude_id: auth.me?.user.id } })
    people.value = data.data
  }
}

async function submitTransfer() {
  transferErrors.value = {}
  transferring.value = true
  try {
    const { data } = await api.post<{ message: string }>('/company/owner/transfer', transfer)
    message.success(data.message)
    transferOpen.value = false
    await Promise.all([loadOwner(), auth.fetchMe()])
  } catch (e) {
    transferErrors.value = fieldErrors(e)
    if (!Object.keys(transferErrors.value).length) message.error(errorMessage(e))
  } finally {
    transferring.value = false
  }
}

onMounted(async () => {
  void loadOwner()
  try {
    const { data } = await api.get<ApiEnvelope<CompanySettings>>('/company-settings/show')
    country.value = data.data.country.toUpperCase()
    for (const key of Object.keys(form) as (keyof typeof form)[]) form[key] = data.data[key] ?? ''
    if (!form.timezone) form.timezone = Intl.DateTimeFormat().resolvedOptions().timeZone
    loaded.value = true
  } catch (e) {
    loadError.value = errorMessage(e)
  }
})

async function save() {
  errors.value = {}
  saving.value = true
  try {
    await api.post('/company-settings/update', form)
    message.success('Company details saved.')
    await router.push({ name: 'setup' })
  } catch (e) {
    errors.value = fieldErrors(e)
    if (!Object.keys(errors.value).length) message.error(errorMessage(e))
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="page narrow">
    <div class="page-header">
      <div>
        <h1>Company details</h1>
        <p class="muted subtitle">Shown on payslips and documents. Country: {{ country || '…' }}.</p>
      </div>
    </div>

    <NAlert v-if="loadError" type="error" :show-icon="false">{{ loadError }}</NAlert>
    <NSkeleton v-else-if="!loaded" height="420px" :sharp="false" />

    <NCard v-else>
      <NForm @submit.prevent="save">
        <NGrid :cols="2" :x-gap="16" item-responsive responsive="screen">
          <NGridItem span="2">
            <NFormItem label="Company name" :feedback="errors.company_name" :validation-status="errors.company_name ? 'error' : undefined">
              <NInput v-model:value="form.company_name" />
            </NFormItem>
          </NGridItem>
          <NGridItem span="2">
            <NFormItem label="Address" :feedback="errors.address" :validation-status="errors.address ? 'error' : undefined">
              <NInput v-model:value="form.address" type="textarea" :autosize="{ minRows: 2 }" />
            </NFormItem>
          </NGridItem>
          <NGridItem span="2 m:1">
            <NFormItem label="City"><NInput v-model:value="form.city" /></NFormItem>
          </NGridItem>
          <NGridItem span="2 m:1">
            <NFormItem label="Phone"><NInput v-model:value="form.phone" /></NFormItem>
          </NGridItem>
          <NGridItem span="2 m:1">
            <NFormItem label="Email" :feedback="errors.email" :validation-status="errors.email ? 'error' : undefined">
              <NInput v-model:value="form.email" :input-props="{ type: 'email' }" />
            </NFormItem>
          </NGridItem>
          <NGridItem span="2 m:1">
            <NFormItem label="Currency" :feedback="errors.currency" :validation-status="errors.currency ? 'error' : undefined">
              <NSelect v-model:value="form.currency" :options="currencies" tag filterable />
            </NFormItem>
          </NGridItem>
          <NGridItem span="2">
            <NFormItem label="Timezone" :feedback="errors.timezone ?? 'Used for payroll dates and daily jobs.'" :validation-status="errors.timezone ? 'error' : undefined">
              <NSelect v-model:value="form.timezone" :options="timezones" filterable />
            </NFormItem>
          </NGridItem>
        </NGrid>
        <div class="actions">
          <RouterLink :to="{ name: 'setup' }"><NButton>Back to setup</NButton></RouterLink>
          <NButton type="primary" attr-type="submit" :loading="saving">Save</NButton>
        </div>
      </NForm>
    </NCard>

    <NCard v-if="owner" title="Account owner" class="owner-card">
      <p v-if="owner.owner" class="owner-name">
        <strong>{{ owner.owner.name }}</strong><span class="muted"> · {{ owner.owner.email }}</span>
        <span v-if="owner.is_me" class="muted"> (you)</span>
      </p>
      <p v-else class="muted">No owner is set. Contact Opsyra support.</p>
      <p class="muted small">The account owner always has the Administrator role and can’t be offboarded, so the company can never be locked out. Only the owner can hand ownership to someone else.</p>
      <NButton v-if="owner.is_me && !auth.me?.company.is_demo" @click="openTransfer">Hand ownership to someone else</NButton>

      <div v-if="owner.history.length" class="history">
        <p class="muted small">History</p>
        <ul>
          <li v-for="h in owner.history" :key="h.id">
            {{ formatDate(h.at) }}: {{ h.from ?? 'No one' }} → <strong>{{ h.to }}</strong>
            <span class="muted"> by {{ h.by ?? 'unknown' }}{{ h.reason ? ` — ${h.reason}` : '' }}</span>
          </li>
        </ul>
      </div>
    </NCard>

    <NModal v-model:show="transferOpen" preset="card" title="Hand ownership to someone else" style="max-width: 520px">
      <NForm @submit.prevent="submitTransfer">
        <p class="muted small">They become the account owner and an Administrator. You keep your Administrator role; the new owner can change it later.</p>
        <NFormItem label="New owner" :feedback="transferErrors.user_id" :validation-status="transferErrors.user_id ? 'error' : undefined">
          <NSelect
            v-model:value="transfer.user_id"
            filterable
            placeholder="Choose an active member of staff"
            :options="people.map((p) => ({ label: p.designation ? `${p.name} — ${p.designation}` : p.name, value: p.id }))"
          />
        </NFormItem>
        <NFormItem label="Reason (optional)">
          <NInput v-model:value="transfer.reason" placeholder="e.g. I’m leaving the company" />
        </NFormItem>
        <NFormItem label="Your password, to confirm" :feedback="transferErrors.password" :validation-status="transferErrors.password ? 'error' : undefined">
          <NInput v-model:value="transfer.password" type="password" show-password-on="click" :input-props="{ autocomplete: 'current-password' }" />
        </NFormItem>
        <NSpace justify="end">
          <NButton @click="transferOpen = false">Cancel</NButton>
          <NButton type="primary" attr-type="submit" :loading="transferring" :disabled="!transfer.user_id || !transfer.password">Hand over</NButton>
        </NSpace>
      </NForm>
    </NModal>
  </div>
</template>

<style scoped>
.narrow {
  max-width: 760px;
}

.subtitle {
  margin: 6px 0 0;
}

.owner-card {
  margin-top: 16px;
}

.owner-name {
  margin: 0 0 6px;
}

.small {
  font-size: 0.9rem;
}

.history ul {
  margin: 4px 0 0;
  padding-left: 18px;
  font-size: 0.9rem;
}

.actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  margin-top: 8px;
}
</style>
