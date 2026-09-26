<script setup lang="ts">
import { NAlert, NButton, NCard, NEmpty, NForm, NFormItem, NInput, NModal, NRadioButton, NRadioGroup, NSkeleton, NSpace, NSwitch, NTag, useMessage } from 'naive-ui'
import { onMounted, reactive, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import { api, errorMessage } from '@/api/client'
import type { AttendanceCorrectionRow } from '@/api/attendanceTypes'
import type { ApiEnvelope } from '@/api/types'
import { formatDate } from '@/lib/staff'

/**
 * Staff asking for a day to be put right. HR sees everyone's; a manager
 * sees their own team's (the API decides). Approving updates the day.
 */
const message = useMessage()
const status = ref<'open' | 'approved' | 'rejected'>('open')
const rows = ref<AttendanceCorrectionRow[] | null>(null)
const error = ref('')

async function load() {
  rows.value = null
  try {
    const { data } = await api.get<ApiEnvelope<AttendanceCorrectionRow[]>>('/attendance/corrections/list', { params: { status: status.value } })
    rows.value = data.data
  } catch (e) {
    error.value = errorMessage(e)
  }
}

const describe = (r: AttendanceCorrectionRow) =>
  r.requested_status === 'remote' ? 'Was working remotely' : r.requested_status === 'on_leave' ? 'Was on leave' : `Clocked ${r.requested_in?.slice(0, 5) ?? '?'}–${r.requested_out?.slice(0, 5) ?? '(no out)'}`

const deciding = ref<{ row: AttendanceCorrectionRow; approve: boolean } | null>(null)
const saving = ref(false)
const form = reactive({ response: '', leave_paid: true })

function decide(row: AttendanceCorrectionRow, approve: boolean) {
  deciding.value = { row, approve }
  Object.assign(form, { response: '', leave_paid: true })
}

async function submit() {
  const { row, approve } = deciding.value!
  saving.value = true
  try {
    const { data } = await api.post<ApiEnvelope<unknown>>(`/attendance/corrections/${approve ? 'approve' : 'reject'}`, {
      id: row.id,
      response: form.response || null,
      ...(approve && row.requested_status === 'on_leave' ? { leave_paid: form.leave_paid } : {}),
    })
    message.success(data.message)
    deciding.value = null
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
        <h1>Attendance requests</h1>
        <p class="muted subtitle">People asking for a day to be put right.</p>
      </div>
      <NRadioGroup v-model:value="status" size="small">
        <NRadioButton value="open">Waiting</NRadioButton>
        <NRadioButton value="approved">Approved</NRadioButton>
        <NRadioButton value="rejected">Declined</NRadioButton>
      </NRadioGroup>
    </div>

    <NAlert v-if="error" type="error" :show-icon="false">{{ error }}</NAlert>
    <NSkeleton v-else-if="!rows" height="160px" :sharp="false" />
    <NCard v-else-if="!rows.length"><NEmpty :description="status === 'open' ? 'Nothing waiting.' : 'Nothing here yet.'" /></NCard>

    <NCard v-for="r in rows ?? []" :key="r.id" class="row">
      <div class="head">
        <div>
          <RouterLink v-if="r.staff" :to="{ name: 'attendance-person', params: { id: r.staff.id }, query: { month: r.date.slice(0, 7) } }" class="name">{{ r.staff.name }}</RouterLink>
          <span class="muted"> · {{ formatDate(r.date) }}</span>
        </div>
        <NTag size="small" round>{{ describe(r) }}</NTag>
      </div>
      <p class="reason">{{ r.reason }}</p>
      <p v-if="r.response" class="muted">Reply: {{ r.response }}<template v-if="r.resolved_by"> — {{ r.resolved_by.name }}</template></p>
      <NSpace v-if="r.status === 'open'">
        <NButton size="small" type="primary" @click="decide(r, true)">Approve</NButton>
        <NButton size="small" @click="decide(r, false)">Decline</NButton>
      </NSpace>
    </NCard>

    <NModal :show="!!deciding" preset="card" :title="deciding?.approve ? 'Approve this request' : 'Decline this request'" style="max-width: 460px" @update:show="(v: boolean) => !v && (deciding = null)">
      <NForm @submit.prevent="submit">
        <NFormItem v-if="deciding?.approve && deciding.row.requested_status === 'on_leave'" label="Paid leave">
          <NSwitch v-model:value="form.leave_paid" />
        </NFormItem>
        <NFormItem :label="deciding?.approve ? 'Reply (optional)' : 'Reply'">
          <NInput v-model:value="form.response" type="textarea" :autosize="{ minRows: 2 }" />
        </NFormItem>
        <NSpace justify="end">
          <NButton @click="deciding = null">Cancel</NButton>
          <NButton type="primary" attr-type="submit" :loading="saving" :disabled="!deciding?.approve && !form.response.trim()">{{ deciding?.approve ? 'Approve' : 'Decline' }}</NButton>
        </NSpace>
      </NForm>
    </NModal>
  </div>
</template>

<style scoped>
.subtitle {
  margin: 6px 0 0;
}

.row + .row {
  margin-top: 12px;
}

.head {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
}

.name {
  font-weight: 600;
  color: inherit;
}

.reason {
  margin: 8px 0 12px;
}
</style>
