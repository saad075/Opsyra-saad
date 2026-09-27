<script setup lang="ts">
import { NAlert, NButton, NDatePicker, NDescriptions, NDescriptionsItem, NDivider, NDrawer, NDrawerContent, NInput, NSelect, NSpace, NTag, NTimePicker, useDialog, useMessage } from 'naive-ui'
import { computed, reactive, ref, watch } from 'vue'
import { api, errorMessage } from '@/api/client'
import type { ApiEnvelope } from '@/api/types'
import type { VisitRow } from '@/api/visitTypes'
import OMap, { type MapPoint } from '@/components/OMap.vue'
import { formatMinutes, localTime } from '@/lib/attendance'
import { formatDate } from '@/lib/staff'
import { VISIT_STATUS, visitFlags } from '@/lib/visits'

/**
 * One visit, for a coordinator: where the check-in and check-out happened
 * against the client's address, and what can be done — move or reassign
 * (before it starts), cancel, set the real times, accept or reject a
 * flagged check-in.
 */
const props = defineProps<{ visit: VisitRow | null; caregivers: { id: number; name: string }[] }>()
const emit = defineEmits<{ close: []; changed: [visit: VisitRow] }>()
const message = useMessage()
const dialog = useDialog()

const busy = ref(false)
const move = reactive({ user_id: null as number | null, date: null as string | null, start_time: null as string | null, end_time: null as string | null })
const times = reactive({ in: null as string | null, out: null as string | null, note: '' })
const reviewNote = ref('')

watch(
  () => props.visit,
  (v) => {
    if (!v) return
    Object.assign(move, { user_id: v.staff?.id ?? null, date: v.date, start_time: localTime(v.planned_start), end_time: localTime(v.planned_end) })
    Object.assign(times, { in: localTime(v.checked_in_at) || localTime(v.planned_start), out: localTime(v.checked_out_at) || localTime(v.planned_end), note: '' })
    reviewNote.value = ''
  },
)

// Where the phone was at check-in and check-out, against the address.
const points = computed<MapPoint[]>(() => {
  const v = props.visit
  if (!v) return []
  const out: MapPoint[] = []
  if (v.check_in_latitude !== null && v.check_in_longitude !== null) {
    out.push({ latitude: v.check_in_latitude, longitude: v.check_in_longitude, label: `Checked in ${localTime(v.checked_in_at)}`, tone: v.check_in_within_range === false ? 'far' : 'ok' })
  }
  if (v.check_out_latitude !== null && v.check_out_longitude !== null) {
    out.push({ latitude: v.check_out_latitude, longitude: v.check_out_longitude, label: `Checked out ${localTime(v.checked_out_at)}`, tone: v.check_out_within_range === false ? 'far' : 'neutral' })
  }
  return out
})

async function act(url: string, body: object) {
  busy.value = true
  try {
    const { data } = await api.post<ApiEnvelope<VisitRow>>(url, { id: props.visit!.id, ...body })
    message.success(data.message)
    emit('changed', data.data)
  } catch (e) {
    message.error(errorMessage(e))
  } finally {
    busy.value = false
  }
}

function cancel() {
  dialog.warning({
    title: 'Cancel this visit?',
    content: 'The caregiver won’t see it any more.',
    positiveText: 'Cancel visit',
    negativeText: 'Keep it',
    onPositiveClick: () => act('/visits/cancel', { reason: null }),
  })
}
</script>

<template>
  <NDrawer :show="!!visit" :width="560" placement="right" :trap-focus="false" @update:show="(v: boolean) => !v && emit('close')">
    <NDrawerContent v-if="visit" :title="visit.location?.name ?? 'Visit'" closable>
      <p class="muted sub">{{ visit.location?.address }}<template v-if="visit.location?.city">, {{ visit.location.city }}</template></p>

      <NSpace class="tags">
        <NTag round :type="VISIT_STATUS[visit.status].type">{{ VISIT_STATUS[visit.status].label }}</NTag>
        <NTag v-for="f in visitFlags(visit)" :key="f" round type="warning">{{ f }}</NTag>
      </NSpace>

      <NDescriptions :column="2" label-placement="top" size="small" class="facts">
        <NDescriptionsItem label="Planned">{{ formatDate(visit.date) }}, {{ localTime(visit.planned_start) }}–{{ localTime(visit.planned_end) }}</NDescriptionsItem>
        <NDescriptionsItem label="Caregiver">{{ visit.staff?.name ?? '—' }}</NDescriptionsItem>
        <NDescriptionsItem label="Checked in">
          <template v-if="visit.checked_in_at">{{ localTime(visit.checked_in_at) }}<span v-if="visit.check_in_distance_m !== null" class="muted"> · {{ visit.check_in_distance_m }} m from the address</span></template>
          <template v-else>—</template>
        </NDescriptionsItem>
        <NDescriptionsItem label="Checked out">
          <template v-if="visit.checked_out_at">{{ localTime(visit.checked_out_at) }}<span v-if="visit.check_out_distance_m !== null" class="muted"> · {{ visit.check_out_distance_m }} m away</span></template>
          <template v-else>—</template>
        </NDescriptionsItem>
        <NDescriptionsItem label="Time">{{ formatMinutes(visit.worked_minutes) }} <span v-if="visit.countable_minutes !== visit.worked_minutes" class="muted">({{ formatMinutes(visit.countable_minutes) }} counts)</span></NDescriptionsItem>
        <NDescriptionsItem label="From">{{ visit.from_plan ? 'A visit plan' : 'Added by hand' }}</NDescriptionsItem>
      </NDescriptions>

      <p v-if="visit.caregiver_note" class="note"><strong>Caregiver’s note:</strong> {{ visit.caregiver_note }}</p>
      <p v-if="visit.review_note" class="note"><strong>Review:</strong> {{ visit.review_note }}</p>

      <OMap
        v-if="visit.location?.latitude != null && visit.location?.longitude != null"
        :place="{ latitude: visit.location.latitude, longitude: visit.location.longitude }"
        :radius="visit.location.geofence_radius_m"
        :points="points"
        :height="200"
      />
      <NAlert v-else type="warning" :show-icon="false">This client has no map point, so check-ins here can’t be verified. Add one on the Clients page.</NAlert>

      <!-- Review a flagged visit. -->
      <template v-if="visit.review_status === 'needs_review'">
        <NDivider title-placement="left">Review</NDivider>
        <p class="muted small">
          {{ visit.missed_checkout ? 'The caregiver never checked out, so the time is unknown — set the real times below.' : 'The check-in or check-out was away from the address, or had no location. Accept it to count the time, or reject it.' }}
        </p>
        <NInput v-model:value="reviewNote" placeholder="Note (needed to reject)" class="gap" />
        <NSpace v-if="!visit.missed_checkout">
          <NButton type="primary" :loading="busy" @click="act('/visits/review', { decision: 'accept', note: reviewNote || null })">Accept</NButton>
          <NButton :loading="busy" :disabled="!reviewNote.trim()" @click="act('/visits/review', { decision: 'reject', note: reviewNote })">Reject</NButton>
        </NSpace>
      </template>

      <!-- Move / reassign / cancel before it starts. -->
      <template v-if="visit.status === 'planned'">
        <NDivider title-placement="left">Change this visit</NDivider>
        <NSpace vertical>
          <NSelect v-model:value="move.user_id" :options="caregivers.map((c) => ({ label: c.name, value: c.id }))" filterable />
          <NSpace>
            <NDatePicker v-model:formatted-value="move.date" value-format="yyyy-MM-dd" type="date" style="width: 150px" />
            <NTimePicker v-model:formatted-value="move.start_time" value-format="HH:mm" format="HH:mm" style="width: 110px" />
            <NTimePicker v-model:formatted-value="move.end_time" value-format="HH:mm" format="HH:mm" style="width: 110px" />
          </NSpace>
          <NSpace>
            <NButton type="primary" :loading="busy" @click="act('/visits/update', move)">Save changes</NButton>
            <NButton quaternary type="error" :loading="busy" @click="cancel">Cancel visit</NButton>
          </NSpace>
        </NSpace>
      </template>

      <!-- Set the real times (missed check-out, flat phone, a visit done without the app). -->
      <template v-if="visit.status !== 'cancelled' && visit.status !== 'in_progress'">
        <NDivider title-placement="left">Set the real times</NDivider>
        <NSpace align="center">
          <NTimePicker v-model:formatted-value="times.in" value-format="HH:mm" format="HH:mm" style="width: 110px" />
          <span>–</span>
          <NTimePicker v-model:formatted-value="times.out" value-format="HH:mm" format="HH:mm" style="width: 110px" />
        </NSpace>
        <NInput v-model:value="times.note" placeholder="How you know (e.g. confirmed with the family)" class="gap" />
        <NButton :loading="busy" :disabled="!times.in || !times.out" @click="act('/visits/set-times', { in: times.in, out: times.out, note: times.note || null })">Save times — counts as reviewed</NButton>
      </template>
    </NDrawerContent>
  </NDrawer>
</template>

<style scoped>
.sub {
  margin: -8px 0 10px;
}

.tags {
  margin-bottom: 12px;
}

.facts {
  margin-bottom: 12px;
}

.note {
  margin: 0 0 10px;
}

.small {
  font-size: 0.9rem;
  margin: 0 0 8px;
}

.gap {
  margin: 8px 0;
}
</style>
