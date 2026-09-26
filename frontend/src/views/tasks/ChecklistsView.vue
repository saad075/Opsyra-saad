<script setup lang="ts">
import { NAlert, NDataTable, NEmpty, NProgress, NRadioButton, NRadioGroup, NSpace, NTag, type DataTableColumns } from 'naive-ui'
import { h, onMounted, ref, watch } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { api, errorMessage } from '@/api/client'
import type { ApiEnvelope, Checklist } from '@/api/types'
import { formatDate } from '@/lib/staff'

/** HR overview of every onboarding and offboarding checklist. */
const router = useRouter()
const rows = ref<Checklist[]>([])
const loading = ref(true)
const error = ref('')
const status = ref<'in_progress' | 'completed' | 'cancelled'>('in_progress')
const type = ref<'' | 'onboarding' | 'offboarding'>('')

async function load() {
  loading.value = true
  try {
    const { data } = await api.get<ApiEnvelope<Checklist[]>>('/checklists/list', { params: { status: status.value, type: type.value || undefined } })
    rows.value = data.data
    error.value = ''
  } catch (e) {
    error.value = errorMessage(e)
  } finally {
    loading.value = false
  }
}

const columns: DataTableColumns<Checklist> = [
  {
    title: 'Person',
    key: 'staff',
    render: (c) => h('div', [h(RouterLink, { to: { name: 'checklist', params: { id: c.id } }, class: 'name' }, { default: () => c.staff.name }), h('small', c.staff.designation ?? '')]),
  },
  { title: 'Checklist', key: 'type', render: (c) => h(NTag, { size: 'small', round: true, type: c.type === 'onboarding' ? 'info' : 'default' }, { default: () => (c.type === 'onboarding' ? 'Onboarding' : 'Offboarding') }) },
  { title: 'Date', key: 'anchor_date', render: (c) => `${c.type === 'onboarding' ? 'Starts' : 'Leaves'} ${formatDate(c.anchor_date)}` },
  {
    title: 'Progress',
    key: 'progress',
    render: (c) =>
      h('div', { class: 'progress' }, [
        h(NProgress, { type: 'line', percentage: c.tasks_count ? Math.round(((c.closed_tasks_count ?? 0) / c.tasks_count) * 100) : 0, showIndicator: false, height: 8 }),
        h('small', `${c.closed_tasks_count} of ${c.tasks_count}`),
      ]),
  },
  {
    title: 'Overdue',
    key: 'overdue',
    render: (c) => (c.overdue_tasks_count ? h(NTag, { size: 'small', round: true, type: 'error' }, { default: () => `${c.overdue_tasks_count} overdue` }) : '—'),
  },
]

watch([status, type], load)
onMounted(load)
</script>

<template>
  <div class="page wide">
    <div class="page-header">
      <div>
        <h1>Checklists</h1>
        <p class="muted subtitle">Onboarding and offboarding across the company.</p>
      </div>
    </div>

    <NSpace class="toolbar">
      <NRadioGroup v-model:value="status" size="small">
        <NRadioButton value="in_progress">In progress</NRadioButton>
        <NRadioButton value="completed">Completed</NRadioButton>
        <NRadioButton value="cancelled">Cancelled</NRadioButton>
      </NRadioGroup>
      <NRadioGroup v-model:value="type" size="small">
        <NRadioButton value="">All</NRadioButton>
        <NRadioButton value="onboarding">Onboarding</NRadioButton>
        <NRadioButton value="offboarding">Offboarding</NRadioButton>
      </NRadioGroup>
    </NSpace>

    <NAlert v-if="error" type="error" :show-icon="false">{{ error }}</NAlert>
    <NDataTable
      v-else
      :columns="columns"
      :data="rows"
      :loading="loading"
      :bordered="false"
      :row-key="(c: Checklist) => c.id"
      :row-props="(c: Checklist) => ({ style: 'cursor: pointer', onClick: (e: MouseEvent) => { if (!(e.target as HTMLElement).closest('a')) router.push({ name: 'checklist', params: { id: c.id } }) } })"
    >
      <template #empty><NEmpty description="No checklists here." /></template>
    </NDataTable>
  </div>
</template>

<style scoped>
.wide {
  max-width: 1180px;
}

.subtitle {
  margin: 6px 0 0;
}

.toolbar {
  margin-bottom: 14px;
}

:deep(.name) {
  font-weight: 600;
  color: inherit;
}

:deep(small) {
  display: block;
  opacity: 0.6;
}

:deep(.progress) {
  min-width: 140px;
}
</style>
