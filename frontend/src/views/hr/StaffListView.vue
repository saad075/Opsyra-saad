<script setup lang="ts">
import { AddOutline, SearchOutline } from '@vicons/ionicons5'
import { NAlert, NAvatar, NButton, NDataTable, NEmpty, NIcon, NInput, NRadioButton, NRadioGroup, NSpace, NTag, type DataTableColumns } from 'naive-ui'
import { computed, h, onMounted, ref, watch } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { api, errorMessage } from '@/api/client'
import type { ApiEnvelope, StaffMember } from '@/api/types'
import { formatDate, initials, statusOf } from '@/lib/staff'
import { useBranchesStore } from '@/stores/branches'

const router = useRouter()
const branches = useBranchesStore()

const staff = ref<StaffMember[]>([])
const loading = ref(true)
const error = ref('')
const search = ref('')
const filter = ref<'current' | 'pending_approval' | 'offboarded' | 'all'>('current')

async function load() {
  loading.value = true
  try {
    const { data } = await api.post<ApiEnvelope<StaffMember[]>>('/staff/list')
    staff.value = data.data
    error.value = ''
  } catch (e) {
    error.value = errorMessage(e)
  } finally {
    loading.value = false
  }
}

const counts = computed(() => ({
  current: staff.value.filter((s) => s.status === 'active' || s.status === 'pending').length,
  pending_approval: staff.value.filter((s) => s.status === 'pending_approval').length,
  offboarded: staff.value.filter((s) => s.status === 'offboarded').length,
  all: staff.value.length,
}))

const shown = computed(() => {
  const q = search.value.trim().toLowerCase()
  return staff.value
    .filter((s) => {
      if (filter.value === 'current') return s.status === 'active' || s.status === 'pending'
      if (filter.value === 'all') return true
      return s.status === filter.value
    })
    .filter((s) => !q || [s.name, s.email, s.designation ?? ''].some((v) => v.toLowerCase().includes(q)))
})

const columns: DataTableColumns<StaffMember> = [
  {
    title: 'Name',
    key: 'name',
    sorter: (a, b) => a.name.localeCompare(b.name),
    render: (s) =>
      h('div', { class: 'person' }, [
        h(NAvatar, { round: true, size: 34, class: 'avatar', src: s.avatar ?? undefined }, { default: () => initials(s.name) }),
        h('div', [h(RouterLink, { to: { name: 'staff-profile', params: { id: s.id } }, class: 'name' }, { default: () => s.name }), h('small', s.email)]),
      ]),
  },
  { title: 'Job title', key: 'designation', render: (s) => s.designation ?? '—' },
  {
    title: 'Branch',
    key: 'branch',
    render: (s) => s.branches.map((b) => b.branch?.name).filter(Boolean).join(', ') || '—',
  },
  {
    title: 'Joined',
    key: 'joined_at',
    sorter: (a, b) => (a.joined_at ?? '').localeCompare(b.joined_at ?? ''),
    render: (s) => formatDate(s.joined_at) || '—',
  },
  {
    title: 'Status',
    key: 'status',
    render: (s) => h(NTag, { size: 'small', round: true, type: statusOf(s.status).type }, { default: () => statusOf(s.status).label }),
  },
]

watch(() => branches.version, load)
onMounted(load)
</script>

<template>
  <div class="page wide">
    <div class="page-header">
      <div>
        <h1>Staff</h1>
        <p class="muted subtitle">Everyone in {{ branches.label === 'All my branches' ? 'your branches' : branches.label }}.</p>
      </div>
      <NButton type="primary" @click="router.push({ name: 'staff-new' })">
        <template #icon><NIcon><AddOutline /></NIcon></template>
        Add staff
      </NButton>
    </div>

    <NSpace justify="space-between" align="center" class="toolbar" :wrap="true">
      <NRadioGroup v-model:value="filter" size="small">
        <NRadioButton value="current">Current ({{ counts.current }})</NRadioButton>
        <NRadioButton value="pending_approval">Awaiting approval ({{ counts.pending_approval }})</NRadioButton>
        <NRadioButton value="offboarded">Left ({{ counts.offboarded }})</NRadioButton>
        <NRadioButton value="all">All ({{ counts.all }})</NRadioButton>
      </NRadioGroup>
      <NInput v-model:value="search" clearable placeholder="Search name, email or job title" class="search">
        <template #prefix><NIcon><SearchOutline /></NIcon></template>
      </NInput>
    </NSpace>

    <NAlert v-if="error" type="error" :show-icon="false">{{ error }}</NAlert>

    <NDataTable
      v-else
      :columns="columns"
      :data="shown"
      :loading="loading"
      :bordered="false"
      :row-key="(s: StaffMember) => s.id"
      :pagination="{ pageSize: 25 }"
      :row-props="(s: StaffMember) => ({ style: 'cursor: pointer', onClick: (e: MouseEvent) => { if (!(e.target as HTMLElement).closest('a')) router.push({ name: 'staff-profile', params: { id: s.id } }) } })"
    >
      <template #empty>
        <NEmpty :description="search ? 'No one matches that search.' : 'No staff here yet.'" />
      </template>
    </NDataTable>
  </div>
</template>

<style scoped>
.wide {
  max-width: 1280px;
}

.subtitle {
  margin: 6px 0 0;
}

.toolbar {
  margin-bottom: 14px;
}

.search {
  width: 300px;
  max-width: 100%;
}

:deep(.person) {
  display: flex;
  align-items: center;
  gap: 12px;
}

:deep(.avatar) {
  background: #0e6e62;
  color: #fff;
  font-size: 12px;
  flex-shrink: 0;
}

:deep(.name) {
  font-weight: 600;
  color: inherit;
  text-decoration: none;
}

:deep(.name:hover) {
  text-decoration: underline;
}

:deep(small) {
  display: block;
  opacity: 0.6;
}
</style>
