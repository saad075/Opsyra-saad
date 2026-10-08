<script setup lang="ts">
import { NButton, NForm, NFormItem, NInput, NModal, NSpace, useMessage } from 'naive-ui'
import { reactive, ref, watch } from 'vue'
import { api, errorMessage, fieldErrors } from '@/api/client'
import type { ApiEnvelope } from '@/api/types'
import type { LeadEmail } from '@/api/crmLeadsTypes'

const props = defineProps<{ show: boolean; leadId: number; defaultTo: string | null }>()
const emit = defineEmits<{ 'update:show': [boolean]; sent: [] }>()

const message = useMessage()
const saving = ref(false)
const errors = ref<Record<string, string>>({})
const form = reactive({ to_email: '', subject: '', body: '' })

watch(
  () => props.show,
  (open) => {
    if (! open) return
    errors.value = {}
    Object.assign(form, { to_email: props.defaultTo ?? '', subject: '', body: '' })
  },
)

async function send() {
  saving.value = true
  errors.value = {}
  try {
    await api.post<ApiEnvelope<LeadEmail>>('/lead-mail/send', { lead_id: props.leadId, ...form })
    message.success('Email sent.')
    emit('update:show', false)
    emit('sent')
  } catch (e) {
    errors.value = fieldErrors(e)
    if (! Object.keys(errors.value).length) message.error(errorMessage(e))
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <NModal :show="show" preset="card" title="Send email" style="max-width: 520px" :mask-closable="!saving" @update:show="(v: boolean) => emit('update:show', v)">
    <NForm @submit.prevent="send">
      <NFormItem label="To" :feedback="errors.to_email" :validation-status="errors.to_email ? 'error' : undefined">
        <NInput v-model:value="form.to_email" placeholder="contact@company.com" />
      </NFormItem>
      <NFormItem label="Subject" :feedback="errors.subject" :validation-status="errors.subject ? 'error' : undefined">
        <NInput v-model:value="form.subject" />
      </NFormItem>
      <NFormItem label="Message" :feedback="errors.body" :validation-status="errors.body ? 'error' : undefined">
        <NInput v-model:value="form.body" type="textarea" :autosize="{ minRows: 5, maxRows: 10 }" />
      </NFormItem>
      <NSpace justify="end">
        <NButton :disabled="saving" @click="emit('update:show', false)">Cancel</NButton>
        <NButton type="primary" attr-type="submit" :loading="saving" :disabled="! form.to_email.trim() || ! form.subject.trim() || ! form.body.trim()">Send</NButton>
      </NSpace>
    </NForm>
  </NModal>
</template>
