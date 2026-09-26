<script setup lang="ts">
import { NButton, NCard, NSelect, NSkeleton, NTag, useMessage } from 'naive-ui'
import { computed, onMounted, ref } from 'vue'
import { api, errorMessage } from '@/api/client'
import type { MyToday } from '@/api/attendanceTypes'
import type { ApiEnvelope } from '@/api/types'
import { currentPosition, dayFlags, formatMinutes, localTime, statusLabel, DAY_STATUS } from '@/lib/attendance'
import { formatDate } from '@/lib/staff'

/**
 * Clock in and out from Opsyra in a browser — for anyone without the staff
 * app. Sends the browser's location if it's allowed; clocking never waits
 * on it (far away is flagged for HR, never refused).
 */
const emit = defineEmits<{ changed: [] }>()
const message = useMessage()

const today = ref<MyToday | null>(null)
const busy = ref(false)
const branchId = ref<number | null>(null)

async function load() {
  try {
    const { data } = await api.get<ApiEnvelope<MyToday>>('/attendance/today')
    today.value = data.data
    branchId.value ??= data.data.branches[0]?.id ?? null
  } catch {
    today.value = null
  }
}

const expectedText = computed(() => {
  const e = today.value?.expected
  if (!e) return ''
  if (e.kind === 'work') return `${localTime(e.start)}–${localTime(e.end)} · ${formatMinutes(e.required_minutes)}`
  if (e.kind === 'holiday') return e.holiday ?? 'Holiday'
  if (e.kind === 'day_off') return 'Day off'
  return ''
})

async function clock() {
  busy.value = true
  try {
    const where = await currentPosition()
    const { data } = await api.post<ApiEnvelope<unknown>>('/attendance/clock', {
      type: today.value?.next_action,
      branch_id: branchId.value,
      source: 'web',
      device: navigator.userAgent.slice(0, 255),
      ...(where ?? {}),
    })
    message.success(data.message)
    await load()
    emit('changed')
  } catch (e) {
    message.error(errorMessage(e))
  } finally {
    busy.value = false
  }
}

defineExpose({ load })
onMounted(load)
</script>

<template>
  <NCard v-if="today && today.attendance_mode === 'schedule'" class="clock" size="small">
    <div class="row">
      <div class="info">
        <p class="label">Today · {{ formatDate(today.date) }}</p>
        <p class="plan">{{ expectedText }}</p>
        <p v-if="today.day" class="state">
          <NTag size="small" round :type="DAY_STATUS[today.day.status].type">{{ statusLabel(today.day) }}</NTag>
          <span v-if="today.day.first_in_at">In {{ localTime(today.day.first_in_at) }}</span>
          <span v-if="today.day.last_out_at && today.day.status !== 'open'">· Out {{ localTime(today.day.last_out_at) }}</span>
          <span v-if="today.day.worked_minutes">· {{ formatMinutes(today.day.worked_minutes) }}</span>
          <span v-for="f in dayFlags(today.day)" :key="f" class="flag">· {{ f }}</span>
        </p>
        <p v-else class="muted state">Not clocked in yet.</p>
      </div>
      <div class="actions">
        <NSelect v-if="today.branches.length > 1 && today.next_action === 'in'" v-model:value="branchId" size="small" :options="today.branches.map((b) => ({ label: b.name, value: b.id }))" style="width: 170px" />
        <NButton :type="today.next_action === 'in' ? 'primary' : 'default'" size="large" :loading="busy" @click="clock">
          {{ today.next_action === 'in' ? 'Clock in' : 'Clock out' }}
        </NButton>
      </div>
    </div>
  </NCard>
  <NCard v-else-if="today && today.attendance_mode === 'visits'" size="small" class="clock">
    <p class="muted">You check in at each client visit in the Opsyra app.</p>
  </NCard>
  <NSkeleton v-else-if="today === null && busy" height="96px" :sharp="false" />
</template>

<style scoped>
.clock {
  border-left: 4px solid #0e6e62;
}

.row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  flex-wrap: wrap;
}

.info p {
  margin: 0;
}

.label {
  font-size: 0.85rem;
  opacity: 0.7;
}

.plan {
  font-size: 1.15rem;
  font-weight: 700;
  margin: 2px 0 6px !important;
}

.state {
  display: flex;
  gap: 6px;
  align-items: center;
  flex-wrap: wrap;
  font-size: 0.92rem;
}

.flag {
  color: #b7791f;
}

.actions {
  display: flex;
  gap: 8px;
  align-items: center;
}
</style>
