<script setup lang="ts">
import { NAlert, NButton, NCard, NForm, NFormItem, NGrid, NGridItem, NInput, NSelect, NSkeleton, useMessage } from 'naive-ui'
import { onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { api, errorMessage, fieldErrors } from '@/api/client'
import type { ApiEnvelope, CompanySettings } from '@/api/types'

const router = useRouter()
const message = useMessage()

const loaded = ref(false)
const loadError = ref('')
const saving = ref(false)
const errors = ref<Record<string, string>>({})
const country = ref('')

const form = reactive({ company_name: '', address: '', city: '', phone: '', email: '', timezone: '', currency: '' })

const timezones = Intl.supportedValuesOf('timeZone').map((tz) => ({ label: tz.replace(/_/g, ' '), value: tz }))
const currencies = ['GBP', 'USD', 'OMR', 'PKR', 'EUR', 'AED', 'SAR'].map((c) => ({ label: c, value: c }))

onMounted(async () => {
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
  </div>
</template>

<style scoped>
.narrow {
  max-width: 760px;
}

.subtitle {
  margin: 6px 0 0;
}

.actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  margin-top: 8px;
}
</style>
