<script setup lang="ts">
import { AddOutline, ChevronBack, ChevronForward } from '@vicons/ionicons5'
import { NAlert, NButton, NCard, NDatePicker, NEmpty, NForm, NFormItem, NIcon, NModal, NSelect, NSkeleton, NSpace, NTag, NTimePicker, useMessage } from 'naive-ui'
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { api, errorMessage } from '@/api/client'
import type { ApiEnvelope } from '@/api/types'
import type { VisitBoard, VisitLocationRow, VisitRow } from '@/api/visitTypes'
import StatCard from '@/components/StatCard.vue'
import VisitDrawer from '@/components/VisitDrawer.vue'
import { localTime } from '@/lib/attendance'
import { VISIT_STATUS, visitFlags } from '@/lib/visits'

/** The coordinator's day: each caregiver's visits, in order, as they happen. */
const message = useMessage()

const localDate = (d = new Date()) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`

const date = ref(localDate())
const board = ref<VisitBoard | null>(null)
const clients = ref<VisitLocationRow[]>([])
const caregivers = ref<{ id: number; name: string }[]>([])
const error = ref('')
const opened = ref<VisitRow | null>(null)

async function load() {
  try {
    const { data } = await api.get<ApiEnvelope<VisitBoard>>('/visits/day', { params: { date: date.value } })
    board.value = data.data
    error.value = ''
  } catch (e) {
    error.value = errorMessage(e)
  }
}

function shift(by: number) {
  const d = new Date(`${date.value}T00:00:00`)
  d.setDate(d.getDate() + by)
  date.value = localDate(d)
}

const shown = computed(() => board.value?.caregivers ?? [])

function changed(v: VisitRow) {
  opened.value = v
  load()
}

// A one-off visit.
const adding = ref(false)
const saving = ref(false)
const form = reactive({ visit_location_id: null as number | null, user_id: null as number | null, start_time: '09:00' as string | null, end_time: '10:00' as string | null, date: null as string | null })

function openAdd(userId: number | null = null) {
  Object.assign(form, { visit_location_id: null, user_id: userId, start_time: '09:00', end_time: '10:00', date: date.value })
  adding.value = true
}

async function add() {
  saving.value = true
  try {
    await api.post('/visits/create', form)
    message.success('Visit added.')
    adding.value = false
    await load()
  } catch (e) {
    message.error(errorMessage(e))
  } finally {
    saving.value = false
  }
}

watch(date, load)
onMounted(async () => {
  await load()
  const [c, p] = await Promise.all([
    api.get<ApiEnvelope<VisitLocationRow[]>>('/visits/locations/list').catch(() => ({ data: { data: [] } })),
    api.get<ApiEnvelope<{ id: number; name: string }[]>>('/visits/caregivers').catch(() => ({ data: { data: [] } })),
  ])
  clients.value = c.data.data
  caregivers.value = p.data.data
})
</script>

<template>
  <div class="page wide">
    <div class="page-header">
      <div>
        <h1>Visits</h1>
        <p class="muted subtitle">Each caregiver’s visits for the day. Click a visit to see where they checked in, or to change it.</p>
      </div>
      <NSpace align="center">
        <NButton quaternary circle aria-label="Previous day" @click="shift(-1)"><template #icon><NIcon><ChevronBack /></NIcon></template></NButton>
        <NDatePicker v-model:formatted-value="date" value-format="yyyy-MM-dd" type="date" style="width: 150px" />
        <NButton quaternary circle aria-label="Next day" @click="shift(1)"><template #icon><NIcon><ChevronForward /></NIcon></template></NButton>
        <NButton type="primary" @click="openAdd()">
          <template #icon><NIcon><AddOutline /></NIcon></template>
          Add a visit
        </NButton>
      </NSpace>
    </div>

    <NAlert v-if="error" type="error" :show-icon="false">{{ error }}</NAlert>
    <NSkeleton v-else-if="!board" height="320px" :sharp="false" />

    <template v-else>
      <div class="stats">
        <StatCard label="Visits" :value="board.summary.planned" />
        <StatCard label="Done" :value="board.summary.completed" />
        <StatCard label="In progress" :value="board.summary.in_progress" />
        <StatCard label="Missed" :value="board.summary.missed" :tone="board.summary.missed ? 'warning' : 'default'" />
        <StatCard label="To review" :value="board.summary.needs_review" :tone="board.summary.needs_review ? 'warning' : 'default'" :to="{ name: 'visits-review' }" />
      </div>

      <NCard v-if="!shown.length"><NEmpty description="Nobody works by visits yet. Set it per person under Settings → Working hours." /></NCard>

      <NCard v-for="c in shown" :key="c.id" class="lane" size="small">
        <div class="lane-head">
          <div>
            <strong>{{ c.name }}</strong>
            <small>{{ c.designation }}</small>
          </div>
          <NButton size="tiny" quaternary @click="openAdd(c.id)">+ Visit</NButton>
        </div>
        <div v-if="c.visits.length" class="visits">
          <button v-for="v in c.visits" :key="v.id" type="button" class="visit" :class="[v.status, { flagged: visitFlags(v).length }]" @click="opened = v">
            <span class="time">{{ localTime(v.planned_start) }}–{{ localTime(v.planned_end) }}</span>
            <span class="client">{{ v.location?.name }}</span>
            <span class="meta">
              <NTag size="tiny" round :type="VISIT_STATUS[v.status].type">{{ VISIT_STATUS[v.status].label }}</NTag>
              <span v-if="v.checked_in_at" class="muted">in {{ localTime(v.checked_in_at) }}</span>
            </span>
            <span v-for="f in visitFlags(v)" :key="f" class="flag">{{ f }}</span>
          </button>
        </div>
        <p v-else class="muted none">No visits.</p>
      </NCard>
    </template>

    <VisitDrawer :visit="opened" :caregivers="caregivers" @close="opened = null" @changed="changed" />

    <NModal v-model:show="adding" preset="card" title="Add a visit" style="max-width: 460px">
      <NForm @submit.prevent="add">
        <NFormItem label="Client"><NSelect v-model:value="form.visit_location_id" :options="clients.map((c) => ({ label: c.name, value: c.id }))" filterable /></NFormItem>
        <NFormItem label="Who visits"><NSelect v-model:value="form.user_id" :options="caregivers.map((c) => ({ label: c.name, value: c.id }))" filterable /></NFormItem>
        <NFormItem label="Day"><NDatePicker v-model:formatted-value="form.date" value-format="yyyy-MM-dd" type="date" style="width: 100%" /></NFormItem>
        <NSpace>
          <NFormItem label="From"><NTimePicker v-model:formatted-value="form.start_time" value-format="HH:mm" format="HH:mm" /></NFormItem>
          <NFormItem label="To"><NTimePicker v-model:formatted-value="form.end_time" value-format="HH:mm" format="HH:mm" /></NFormItem>
        </NSpace>
        <p class="muted small">For a visit that repeats, use Visit plans instead.</p>
        <NSpace justify="end">
          <NButton @click="adding = false">Cancel</NButton>
          <NButton type="primary" attr-type="submit" :loading="saving" :disabled="!form.visit_location_id || !form.user_id || !form.date">Add visit</NButton>
        </NSpace>
      </NForm>
    </NModal>
  </div>
</template>

<style scoped>
.wide {
  max-width: 1280px;
}

.subtitle {
  margin: 6px 0 0;
}

.stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 12px;
  margin-bottom: 16px;
}

.lane + .lane {
  margin-top: 10px;
}

.lane-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.lane-head small {
  display: block;
  opacity: 0.6;
}

.visits {
  display: flex;
  gap: 8px;
  overflow-x: auto;
  padding-bottom: 4px;
}

.visit {
  flex: 0 0 190px;
  display: flex;
  flex-direction: column;
  gap: 3px;
  text-align: left;
  padding: 10px 12px;
  border-radius: 8px;
  border: 1px solid rgba(127, 127, 127, 0.25);
  border-left: 4px solid #9aa5b1;
  background: transparent;
  color: inherit;
  font: inherit;
  cursor: pointer;
}

.visit:hover {
  border-color: #0e6e62;
}

.visit.completed {
  border-left-color: #0e6e62;
}

.visit.in_progress {
  border-left-color: #2f6fb5;
}

.visit.missed {
  border-left-color: #a23b45;
}

.visit.cancelled {
  opacity: 0.5;
}

.visit.flagged {
  background: rgba(183, 121, 31, 0.08);
}

.time {
  font-weight: 700;
  font-variant-numeric: tabular-nums;
}

.client {
  font-size: 0.92rem;
}

.meta {
  display: flex;
  gap: 6px;
  align-items: center;
  font-size: 0.85rem;
}

.flag {
  font-size: 0.8rem;
  color: #b7791f;
  font-weight: 600;
}

.none {
  margin: 0;
  font-size: 0.9rem;
}

.small {
  font-size: 0.88rem;
  margin: 0 0 12px;
}
</style>
