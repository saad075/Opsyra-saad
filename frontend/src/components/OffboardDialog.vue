<script setup lang="ts">
import { NAlert, NButton, NDatePicker, NForm, NFormItem, NInput, NModal, NSelect, useMessage } from 'naive-ui'
import { computed, onMounted, reactive, ref } from 'vue'
import { api, errorMessage, fieldErrors } from '@/api/client'
import type { ApiEnvelope, PersonRef } from '@/api/types'

/**
 * Starting someone's exit. If they manage people, a new manager is required
 * (the API enforces it too). Without level-2 approval rights this becomes a
 * request an approver signs off.
 */
const props = defineProps<{ staffId: number; staffName: string; directReports: number }>()
const emit = defineEmits<{ done: [message: string] }>()
const show = defineModel<boolean>('show', { required: true })

const message = useMessage()
const reasons = ref<{ id: number; name: string; requires_note: boolean }[]>([])
const managers = ref<PersonRef[]>([])
const saving = ref(false)
const errors = ref<Record<string, string>>({})
const form = reactive({
  last_working_day: null as string | null,
  departure_reason_id: null as number | null,
  note: '',
  new_manager_id: null as number | null,
})

const noteRequired = computed(() => reasons.value.find((r) => r.id === form.departure_reason_id)?.requires_note ?? false)

onMounted(async () => {
  const [r, m] = await Promise.all([
    api.get<ApiEnvelope<typeof reasons.value>>('/departure-reasons/list'),
    api.get<ApiEnvelope<PersonRef[]>>('/staff/eligible-managers', { params: { exclude_id: props.staffId } }),
  ])
  reasons.value = r.data.data
  managers.value = m.data.data
})

async function submit() {
  saving.value = true
  errors.value = {}
  try {
    const { data } = await api.post('/staff/offboard', { id: props.staffId, ...form, note: form.note || null })
    show.value = false
    emit('done', data.message)
  } catch (e) {
    errors.value = fieldErrors(e)
    if (!Object.keys(errors.value).length) message.error(errorMessage(e))
  } finally {
    saving.value = false
  }
}

const err = (field: string) => ({ feedback: errors.value[field], validationStatus: errors.value[field] ? ('error' as const) : undefined })
</script>

<template>
  <NModal v-model:show="show" preset="card" :title="`Offboard ${staffName}`" style="max-width: 520px" :mask-closable="!saving">
    <NForm @submit.prevent="submit">
      <NFormItem label="Last working day" v-bind="err('last_working_day')">
        <NDatePicker v-model:formatted-value="form.last_working_day" value-format="yyyy-MM-dd" type="date" style="width: 100%" />
      </NFormItem>
      <NFormItem label="Reason for leaving" v-bind="err('departure_reason_id')">
        <NSelect v-model:value="form.departure_reason_id" :options="reasons.map((r) => ({ label: r.name, value: r.id }))" />
      </NFormItem>
      <NFormItem :label="noteRequired ? 'Note (required for this reason)' : 'Note (optional)'" v-bind="err('note')">
        <NInput v-model:value="form.note" type="textarea" :autosize="{ minRows: 2 }" />
      </NFormItem>
      <template v-if="directReports > 0">
        <NAlert type="info" :show-icon="false" class="gap">
          {{ staffName }} manages {{ directReports }} {{ directReports === 1 ? 'person' : 'people' }}. Choose who they’ll report to instead.
        </NAlert>
        <NFormItem label="New manager for their team" v-bind="err('new_manager_id')">
          <NSelect v-model:value="form.new_manager_id" filterable :options="managers.map((m) => ({ label: m.designation ? `${m.name} — ${m.designation}` : m.name, value: m.id }))" />
        </NFormItem>
      </template>
      <div class="actions">
        <NButton :disabled="saving" @click="show = false">Cancel</NButton>
        <NButton type="error" attr-type="submit" :loading="saving" :disabled="!form.last_working_day || !form.departure_reason_id">Offboard</NButton>
      </div>
    </NForm>
  </NModal>
</template>

<style scoped>
.gap {
  margin-bottom: 12px;
}

.actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
}
</style>
