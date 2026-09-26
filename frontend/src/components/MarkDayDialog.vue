<script setup lang="ts">
import { NAlert, NButton, NForm, NFormItem, NInput, NModal, NRadioButton, NRadioGroup, NSelect, NSpace, NSwitch, NTimePicker, useMessage } from 'naive-ui'
import { reactive, ref, watch } from 'vue'
import { api, errorMessage } from '@/api/client'
import type { AttendanceDayRecord } from '@/api/attendanceTypes'
import { localTime } from '@/lib/attendance'
import { formatDate } from '@/lib/staff'

/**
 * HR marking or fixing one person's day: set the clock times, set a status
 * (e.g. paid or unpaid leave), or hand the day back to the clock.
 */
const props = defineProps<{ target: { userId: number; name: string; date: string; day: AttendanceDayRecord | null } | null }>()
const emit = defineEmits<{ close: []; saved: [] }>()
const message = useMessage()

const saving = ref(false)
const form = reactive({ action: 'times' as 'times' | 'status', in: null as string | null, out: null as string | null, status: 'on_leave', leave_paid: true, note: '' })

watch(
  () => props.target,
  (t) => {
    if (!t) return
    Object.assign(form, {
      action: 'times',
      in: localTime(t.day?.first_in_at) || '09:00',
      out: localTime(t.day?.last_out_at) || '17:00',
      status: t.day?.is_manual ? t.day.status : 'on_leave',
      leave_paid: t.day?.leave_paid ?? true,
      note: t.day?.note ?? '',
    })
  },
)

const statuses = [
  { label: 'On leave', value: 'on_leave' },
  { label: 'Working remotely', value: 'remote' },
  { label: 'Present', value: 'present' },
  { label: 'Half day', value: 'half_day' },
  { label: 'Absent', value: 'absent' },
  { label: 'Day off', value: 'day_off' },
  { label: 'Holiday', value: 'holiday' },
]

async function post(body: object, done: string) {
  saving.value = true
  try {
    await api.post('/attendance/mark', { user_id: props.target!.userId, date: props.target!.date, ...body })
    message.success(done)
    emit('saved')
    emit('close')
  } catch (e) {
    message.error(errorMessage(e))
  } finally {
    saving.value = false
  }
}

function save() {
  if (form.action === 'times') post({ action: 'times', in: form.in, out: form.out }, 'Times saved.')
  else post({ action: 'status', status: form.status, leave_paid: form.status === 'on_leave' ? form.leave_paid : null, note: form.note || null }, 'Day updated.')
}
</script>

<template>
  <NModal :show="!!target" preset="card" :title="target ? `${target.name} · ${formatDate(target.date)}` : ''" style="max-width: 480px" @update:show="(v: boolean) => !v && emit('close')">
    <NForm v-if="target" @submit.prevent="save">
      <NFormItem :show-label="false">
        <NRadioGroup v-model:value="form.action">
          <NRadioButton value="times">Set clock times</NRadioButton>
          <NRadioButton value="status">Set the day</NRadioButton>
        </NRadioGroup>
      </NFormItem>

      <template v-if="form.action === 'times'">
        <NSpace>
          <NFormItem label="In"><NTimePicker v-model:formatted-value="form.in" value-format="HH:mm" format="HH:mm" /></NFormItem>
          <NFormItem label="Out"><NTimePicker v-model:formatted-value="form.out" value-format="HH:mm" format="HH:mm" clearable /></NFormItem>
        </NSpace>
        <p class="muted small">Replaces what was clocked (the original is kept for the record). An out-time before the in-time is the next morning.</p>
      </template>

      <template v-else>
        <NFormItem label="The day was">
          <NSelect v-model:value="form.status" :options="statuses" />
        </NFormItem>
        <NFormItem v-if="form.status === 'on_leave'" label="Paid leave">
          <NSwitch v-model:value="form.leave_paid" />
        </NFormItem>
        <NFormItem label="Note (optional)"><NInput v-model:value="form.note" placeholder="e.g. Doctor’s appointment" /></NFormItem>
        <NAlert type="info" :show-icon="false" class="small">Clocking won’t change this day until you hand it back to the clock.</NAlert>
      </template>

      <NSpace justify="space-between" class="actions">
        <NButton v-if="target.day?.is_manual" quaternary :loading="saving" @click="post({ action: 'reset' }, 'Handed back to the clock.')">Hand back to the clock</NButton>
        <span v-else />
        <NSpace>
          <NButton @click="emit('close')">Cancel</NButton>
          <NButton type="primary" attr-type="submit" :loading="saving" :disabled="form.action === 'times' && !form.in">Save</NButton>
        </NSpace>
      </NSpace>
    </NForm>
  </NModal>
</template>

<style scoped>
.small {
  font-size: 0.88rem;
  margin: 0 0 12px;
}

.actions {
  margin-top: 12px;
}
</style>
