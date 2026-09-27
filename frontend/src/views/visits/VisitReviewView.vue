<script setup lang="ts">
import { NAlert, NCard, NEmpty, NSkeleton, NTag } from 'naive-ui'
import { onMounted, ref } from 'vue'
import { api, errorMessage } from '@/api/client'
import type { ApiEnvelope } from '@/api/types'
import type { VisitRow } from '@/api/visitTypes'
import VisitDrawer from '@/components/VisitDrawer.vue'
import { formatMinutes, localTime } from '@/lib/attendance'
import { formatDate } from '@/lib/staff'
import { visitFlags } from '@/lib/visits'

/**
 * Visits waiting for a coordinator: checked in or out away from the
 * address, without a location, or never checked out. Their time doesn't
 * count — or get paid — until accepted.
 */
const visits = ref<VisitRow[] | null>(null)
const caregivers = ref<{ id: number; name: string }[]>([])
const error = ref('')
const opened = ref<VisitRow | null>(null)

async function load() {
  try {
    visits.value = (await api.get<ApiEnvelope<VisitRow[]>>('/visits/review-queue')).data.data
  } catch (e) {
    error.value = errorMessage(e)
  }
}

function changed(v: VisitRow) {
  opened.value = v.review_status === 'needs_review' ? v : null
  load()
}

onMounted(async () => {
  await load()
  caregivers.value = (await api.get<ApiEnvelope<{ id: number; name: string }[]>>('/visits/caregivers').catch(() => ({ data: { data: [] } }))).data.data
})
</script>

<template>
  <div class="page">
    <div class="page-header">
      <div>
        <h1>Visits to review</h1>
        <p class="muted subtitle">Their time doesn’t count, or get paid, until you accept it.</p>
      </div>
    </div>

    <NAlert v-if="error" type="error" :show-icon="false">{{ error }}</NAlert>
    <NSkeleton v-else-if="!visits" height="200px" :sharp="false" />
    <NCard v-else-if="!visits.length"><NEmpty description="Nothing to review." /></NCard>

    <NCard v-for="v in visits ?? []" :key="v.id" class="row" hoverable @click="opened = v">
      <div class="head">
        <div>
          <strong>{{ v.location?.name }}</strong>
          <span class="muted"> · {{ v.staff?.name }} · {{ formatDate(v.date) }}, {{ localTime(v.planned_start) }}–{{ localTime(v.planned_end) }}</span>
        </div>
        <span class="muted">{{ formatMinutes(v.worked_minutes) }}</span>
      </div>
      <div class="flags">
        <NTag v-for="f in visitFlags(v).filter((f) => f !== 'To review')" :key="f" size="small" round type="warning">{{ f }}</NTag>
      </div>
    </NCard>

    <VisitDrawer :visit="opened" :caregivers="caregivers" @close="opened = null" @changed="changed" />
  </div>
</template>

<style scoped>
.subtitle {
  margin: 6px 0 0;
}

.row {
  cursor: pointer;
}

.row + .row {
  margin-top: 10px;
}

.head {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
}

.flags {
  display: flex;
  gap: 6px;
  margin-top: 8px;
}
</style>
