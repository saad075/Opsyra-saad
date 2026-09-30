<script setup lang="ts">
import { ArrowBackOutline } from '@vicons/ionicons5'
import { NAlert, NAvatar, NButton, NCard, NDescriptions, NDescriptionsItem, NEmpty, NGrid, NGridItem, NIcon, NProgress, NSkeleton, NSpace, NTag, useDialog, useMessage } from 'naive-ui'
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api, errorMessage } from '@/api/client'
import type { ApiEnvelope, StaffProfile } from '@/api/types'
import OffboardDialog from '@/components/OffboardDialog.vue'
import { formatDate, initials, statusOf } from '@/lib/staff'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const message = useMessage()
const dialog = useDialog()

const profile = ref<StaffProfile | null>(null)
const error = ref('')
const offboardOpen = ref(false)

const id = computed(() => Number(route.params.id))
const s = computed(() => profile.value?.staff)
const isHr = computed(() => auth.can('HRMS-StaffManagement'))
const isSelf = computed(() => s.value?.id === auth.me?.user.id)

// A future last working day on an active person = a scheduled exit.
const scheduledExit = computed(() => profile.value?.offboarding?.status === 'approved' && s.value?.status !== 'offboarded' ? profile.value.offboarding : null)
const pendingExit = computed(() => (profile.value?.offboarding?.status === 'pending' ? profile.value.offboarding : null))

async function load() {
  error.value = ''
  try {
    const { data } = await api.post<ApiEnvelope<StaffProfile>>('/staff/show', { id: id.value })
    profile.value = data.data
  } catch (e) {
    error.value = errorMessage(e)
  }
}

function confirm(title: string, content: string, action: () => Promise<{ data: { message: string } }>) {
  dialog.warning({
    title,
    content,
    positiveText: 'Yes',
    negativeText: 'No',
    onPositiveClick: async () => {
      try {
        const { data } = await action()
        message.success(data.message)
        await load()
      } catch (e) {
        message.error(errorMessage(e))
      }
    },
  })
}

const cancelExit = () =>
  confirm('Cancel this exit?', `${s.value?.name} will stay on as normal.`, () => api.post('/staff/cancel-offboarding', { id: id.value }))

const rehire = () =>
  confirm('Rehire?', `${s.value?.name} will go through approval again before becoming active.`, () => api.post('/staff/status-update', { id: id.value }))

async function offboarded(msg: string) {
  message.success(msg)
  await load()
}

watch(id, load)
onMounted(load)
</script>

<template>
  <div class="page wide">
    <NButton text class="back" @click="router.push({ name: 'staff' })">
      <template #icon><NIcon><ArrowBackOutline /></NIcon></template>
      Staff
    </NButton>

    <NAlert v-if="error" type="error" :show-icon="false">{{ error }}</NAlert>
    <NSkeleton v-else-if="!profile" height="360px" :sharp="false" />

    <template v-else-if="s">
      <div class="page-header">
        <div class="identity">
          <NAvatar round :size="64" class="avatar" :src="s.avatar ?? undefined">{{ initials(s.name) }}</NAvatar>
          <div>
            <h1>{{ s.name }}</h1>
            <p class="muted sub">
              {{ s.designation ?? 'No job title' }}
              <NTag size="small" round :type="statusOf(s.status).type" class="tag">{{ statusOf(s.status).label }}</NTag>
              <NTag v-if="profile.is_owner" size="small" round type="info" class="tag">Account owner</NTag>
            </p>
          </div>
        </div>
        <NSpace v-if="isHr">
          <NButton v-if="s.status !== 'offboarded'" @click="router.push({ name: 'staff-edit', params: { id: s.id } })">Edit</NButton>
          <NButton v-if="(s.status === 'active' || s.status === 'pending') && !scheduledExit && !pendingExit && !isSelf && !profile.is_owner" type="error" ghost @click="offboardOpen = true">Offboard</NButton>
          <NButton v-if="scheduledExit || pendingExit" @click="cancelExit">Cancel exit</NButton>
          <NButton v-if="s.status === 'offboarded'" type="primary" @click="rehire">Rehire</NButton>
        </NSpace>
      </div>

      <NAlert v-if="s.status === 'pending_approval'" type="warning" :show-icon="false" class="gap">
        Waiting for level-2 approval to join{{ profile.onboarding?.requested_by ? ` — requested by ${profile.onboarding.requested_by.name}` : '' }}.
        <template v-if="profile.onboarding?.status === 'rejected'"> Rejected: {{ profile.onboarding.rejection_reason }}</template>
      </NAlert>
      <NAlert v-if="pendingExit" type="warning" :show-icon="false" class="gap">
        Leaving on {{ formatDate(pendingExit.last_working_day) }} ({{ pendingExit.departure_reason?.name }}) — waiting for level-2 approval.
      </NAlert>
      <NAlert v-if="scheduledExit" type="info" :show-icon="false" class="gap">
        Leaving on {{ formatDate(scheduledExit.last_working_day) }} — {{ scheduledExit.departure_reason?.name }}{{ scheduledExit.note ? `: ${scheduledExit.note}` : '' }}.
      </NAlert>
      <NAlert v-if="s.status === 'offboarded'" type="default" :show-icon="false" class="gap">
        Left on {{ formatDate(s.lefted_at) }}{{ s.left_reason ? ` — ${s.left_reason}` : '' }}.
      </NAlert>

      <NGrid :cols="3" :x-gap="16" :y-gap="16" item-responsive responsive="screen">
        <NGridItem span="3 l:2">
          <NCard title="Details">
            <NDescriptions :column="2" label-placement="top" size="small">
              <NDescriptionsItem label="Email">{{ s.email }}</NDescriptionsItem>
              <NDescriptionsItem label="Phone">{{ s.phone ?? '—' }}</NDescriptionsItem>
              <NDescriptionsItem label="Joined">{{ formatDate(s.joined_at) || '—' }}</NDescriptionsItem>
              <NDescriptionsItem label="Date of birth">{{ formatDate(s.dob) || '—' }}</NDescriptionsItem>
              <NDescriptionsItem label="Branches">{{ s.branches.map((b) => b.branch?.name).filter(Boolean).join(', ') || '—' }}</NDescriptionsItem>
              <NDescriptionsItem label="Works">{{ s.is_remote ? 'Remotely' : 'On site' }}</NDescriptionsItem>
              <NDescriptionsItem label="Roles">{{ s.roles.map((r) => r.name).join(', ') || '—' }}</NDescriptionsItem>
              <NDescriptionsItem label="City">{{ s.city ?? '—' }}</NDescriptionsItem>
            </NDescriptions>
          </NCard>

          <NCard title="Onboarding & offboarding checklists" class="gap-top">
            <NEmpty v-if="!profile.checklists.length" description="No checklists yet." />
            <RouterLink v-for="c in profile.checklists" :key="c.id" :to="{ name: 'checklist', params: { id: c.id } }" class="checklist">
              <div class="checklist-head">
                <strong>{{ c.type === 'onboarding' ? 'Onboarding' : 'Offboarding' }}</strong>
                <NTag size="small" round :type="c.status === 'completed' ? 'success' : c.status === 'cancelled' ? 'default' : 'info'">{{ c.status.replace('_', ' ') }}</NTag>
                <span class="muted">{{ c.closed_tasks_count }} of {{ c.tasks_count }} tasks done</span>
              </div>
              <NProgress type="line" :percentage="c.tasks_count ? Math.round((c.closed_tasks_count / c.tasks_count) * 100) : 0" :show-indicator="false" :height="8" />
            </RouterLink>
          </NCard>
        </NGridItem>

        <NGridItem span="3 l:1">
          <NCard title="Reports to">
            <RouterLink v-if="profile.manager" :to="{ name: 'staff-profile', params: { id: profile.manager.id } }" class="person-link">
              <NAvatar round size="small" class="avatar">{{ initials(profile.manager.name) }}</NAvatar>
              <span><strong>{{ profile.manager.name }}</strong><small>{{ profile.manager.designation }}</small></span>
            </RouterLink>
            <p v-else class="muted">No manager — top of the organisation.</p>
          </NCard>
          <NCard :title="`Team (${profile.direct_reports.length})`" class="gap-top">
            <p v-if="!profile.direct_reports.length" class="muted">No direct reports.</p>
            <RouterLink v-for="r in profile.direct_reports" :key="r.id" :to="{ name: 'staff-profile', params: { id: r.id } }" class="person-link">
              <NAvatar round size="small" class="avatar">{{ initials(r.name) }}</NAvatar>
              <span><strong>{{ r.name }}</strong><small>{{ r.designation }}</small></span>
            </RouterLink>
          </NCard>
        </NGridItem>
      </NGrid>

      <OffboardDialog v-if="offboardOpen" v-model:show="offboardOpen" :staff-id="s.id" :staff-name="s.name" :direct-reports="profile.direct_reports.length" @done="offboarded" />
    </template>
  </div>
</template>

<style scoped>
.wide {
  max-width: 1180px;
}

.back {
  margin-bottom: 14px;
}

.identity {
  display: flex;
  align-items: center;
  gap: 16px;
}

.avatar {
  background: #0e6e62;
  color: #fff;
  flex-shrink: 0;
}

.sub {
  margin: 6px 0 0;
  display: flex;
  align-items: center;
  gap: 8px;
}

.gap {
  margin-bottom: 14px;
}

.gap-top {
  margin-top: 16px;
}

.checklist {
  display: block;
  color: inherit;
  text-decoration: none;
}

.checklist + .checklist {
  margin-top: 16px;
}

.checklist-head {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 8px;
  flex-wrap: wrap;
}

.person-link {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 0;
  color: inherit;
  text-decoration: none;
}

.person-link small {
  display: block;
  opacity: 0.6;
}

.person-link:hover strong {
  text-decoration: underline;
}
</style>
