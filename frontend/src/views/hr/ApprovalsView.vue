<script setup lang="ts">
import { NAlert, NButton, NCard, NEmpty, NForm, NFormItem, NInput, NModal, NSpace, NTabPane, NTabs, NTag, useMessage } from 'naive-ui'
import { computed, onMounted, ref } from 'vue'
import { api, errorMessage } from '@/api/client'
import type { ApiEnvelope, OffboardRequest, OnboardingRequest } from '@/api/types'
import { formatDate } from '@/lib/staff'
import { useAuthStore } from '@/stores/auth'

/**
 * Hires and leavers waiting for sign-off (level-2 approvers), and rejected
 * hires HR still needs to tell (HR). Each tab shows only if you can act on it.
 */
const auth = useAuthStore()
const message = useMessage()

const canApprove = computed(() => auth.can('HRMS-Approval-L2'))
const isHr = computed(() => auth.can('HRMS-StaffManagement'))

const joining = ref<OnboardingRequest[]>([])
const leaving = ref<OffboardRequest[]>([])
const toNotify = ref<OnboardingRequest[]>([])
const error = ref('')
const busy = ref<string | null>(null)

const rejecting = ref<{ kind: 'onboard' | 'offboard'; id: number; name: string } | null>(null)
const rejectReason = ref('')

/**
 * Each list loads on its own: one slow or failing request shows its own
 * error instead of leaving the whole page empty.
 */
async function load() {
  error.value = ''

  const fill = async <T,>(enabled: boolean, url: string, target: { value: T[] }) => {
    if (!enabled) return
    try {
      target.value = (await api.get<ApiEnvelope<T[]>>(url)).data.data
    } catch (e) {
      error.value = errorMessage(e)
    }
  }

  await Promise.all([
    fill<OnboardingRequest>(canApprove.value, '/staff/onboard/pending-approvals', joining),
    fill<OffboardRequest>(canApprove.value, '/staff/offboard/pending-approvals', leaving),
    fill<OnboardingRequest>(isHr.value, '/staff/onboard/rejected-awaiting-notification', toNotify),
  ])
}

async function run(key: string, request: () => Promise<{ data: { message: string } }>) {
  busy.value = key
  try {
    const { data } = await request()
    message.success(data.message)
    await load()
  } catch (e) {
    message.error(errorMessage(e))
  } finally {
    busy.value = null
  }
}

const approve = (kind: 'onboard' | 'offboard', id: number) => run(`${kind}-${id}`, () => api.post(`/staff/${kind}/approve`, { id }))

function startReject(kind: 'onboard' | 'offboard', id: number, name: string) {
  rejecting.value = { kind, id, name }
  rejectReason.value = ''
}

async function confirmReject() {
  const r = rejecting.value!
  await run(`${r.kind}-${r.id}`, () => api.post(`/staff/${r.kind}/reject`, { id: r.id, reason: rejectReason.value }))
  rejecting.value = null
}

const notified = (id: number) => run(`notify-${id}`, () => api.post('/staff/onboard/complete-rejected', { id }))

onMounted(load)
</script>

<template>
  <div class="page">
    <div class="page-header">
      <div>
        <h1>Approvals</h1>
        <p class="muted subtitle">New hires and leavers waiting for sign-off.</p>
      </div>
    </div>

    <NAlert v-if="error" type="error" :show-icon="false" class="error">{{ error }}</NAlert>
    <NAlert v-if="!canApprove && !isHr" type="info" :show-icon="false">You don’t approve hires or leavers.</NAlert>

    <NTabs v-else type="line" animated>
      <NTabPane v-if="canApprove" name="joining" :tab="`Joining (${joining.length})`">
        <NEmpty v-if="!joining.length" description="No hires waiting for approval." class="empty" />
        <NCard v-for="r in joining" :key="r.id" size="small" class="item">
          <div class="row">
            <div>
              <RouterLink :to="{ name: 'staff-profile', params: { id: r.staff!.id } }" class="name">{{ r.staff?.name }}</RouterLink>
              <p class="muted meta">
                {{ r.staff?.designation ?? 'No job title' }} · starts {{ formatDate(r.staff?.joined_at) }} · requested by {{ r.requested_by?.name ?? '—' }}
                {{ formatDate(r.created_at) }}
              </p>
            </div>
            <NSpace>
              <NButton size="small" :loading="busy === `onboard-${r.id}`" @click="startReject('onboard', r.id, r.staff?.name ?? '')">Reject</NButton>
              <NButton size="small" type="primary" :loading="busy === `onboard-${r.id}`" @click="approve('onboard', r.id)">Approve</NButton>
            </NSpace>
          </div>
        </NCard>
      </NTabPane>

      <NTabPane v-if="canApprove" name="leaving" :tab="`Leaving (${leaving.length})`">
        <NEmpty v-if="!leaving.length" description="No leavers waiting for approval." class="empty" />
        <NCard v-for="r in leaving" :key="r.id" size="small" class="item">
          <div class="row">
            <div>
              <RouterLink :to="{ name: 'staff-profile', params: { id: r.staff!.id } }" class="name">{{ r.staff?.name }}</RouterLink>
              <NTag size="small" round class="tag">{{ r.departure_reason?.name }}</NTag>
              <p class="muted meta">
                Last day {{ formatDate(r.last_working_day) }} · requested by {{ r.requested_by?.name ?? '—' }}
                <template v-if="r.direct_reports_count"> · {{ r.direct_reports_count }} reports move to {{ r.new_manager?.name }}</template>
              </p>
              <p v-if="r.note" class="note">“{{ r.note }}”</p>
            </div>
            <NSpace>
              <NButton size="small" :loading="busy === `offboard-${r.id}`" @click="startReject('offboard', r.id, r.staff?.name ?? '')">Reject</NButton>
              <NButton size="small" type="primary" :loading="busy === `offboard-${r.id}`" @click="approve('offboard', r.id)">Approve</NButton>
            </NSpace>
          </div>
        </NCard>
      </NTabPane>

      <NTabPane v-if="isHr" name="notify" :tab="`Rejected hires to notify (${toNotify.length})`">
        <NEmpty v-if="!toNotify.length" description="Nobody waiting to be told." class="empty" />
        <NCard v-for="r in toNotify" :key="r.id" size="small" class="item">
          <div class="row">
            <div>
              <strong>{{ r.staff?.name }}</strong>
              <p class="muted meta">Rejected by {{ r.reviewed_by?.name ?? '—' }} {{ formatDate(r.reviewed_at) }}: {{ r.rejection_reason }}</p>
            </div>
            <NButton size="small" :loading="busy === `notify-${r.id}`" @click="notified(r.id)">They’ve been told</NButton>
          </div>
        </NCard>
      </NTabPane>
    </NTabs>

    <NModal :show="rejecting !== null" preset="card" :title="`Reject ${rejecting?.name}?`" style="max-width: 480px" @update:show="(v: boolean) => { if (!v) rejecting = null }">
      <NForm @submit.prevent="confirmReject">
        <NFormItem label="Reason (shared with HR)">
          <NInput v-model:value="rejectReason" type="textarea" :autosize="{ minRows: 3 }" />
        </NFormItem>
        <NSpace justify="end">
          <NButton @click="rejecting = null">Cancel</NButton>
          <NButton type="error" attr-type="submit" :disabled="!rejectReason.trim()" :loading="busy !== null">Reject</NButton>
        </NSpace>
      </NForm>
    </NModal>
  </div>
</template>

<style scoped>
.subtitle {
  margin: 6px 0 0;
}

.empty {
  padding: 32px 0;
}

.item {
  margin-top: 10px;
}

.row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  flex-wrap: wrap;
}

.name {
  font-weight: 700;
  color: inherit;
}

.tag {
  margin-left: 8px;
}

.meta {
  margin: 4px 0 0;
  font-size: 0.9rem;
}

.note {
  margin: 6px 0 0;
  font-style: italic;
}
</style>
