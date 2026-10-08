<script setup lang="ts">
import { AddOutline, AlertCircleOutline, GridOutline, ListOutline } from '@vicons/ionicons5'
import { NAlert, NButton, NButtonGroup, NDataTable, NForm, NFormItem, NIcon, NInput, NModal, NSelect, NSkeleton, NTag, NTooltip, useMessage, type DataTableColumns } from 'naive-ui'
import { computed, h, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import draggable from 'vuedraggable'
import { api, errorMessage } from '@/api/client'
import type { ApiEnvelope } from '@/api/types'
import type { LeadPipeline, LeadPipelineStage, LeadRow } from '@/api/crmLeadsTypes'
import LeadCard from '@/components/crm/LeadCard.vue'
import LeadFormModal from '@/components/crm/LeadFormModal.vue'
import { useLeadStageMove } from '@/composables/useLeadStageMove'
import { formatDate } from '@/lib/staff'
import { formatMoney } from '@/lib/money'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const message = useMessage()
const router = useRouter()

const pipelines = ref<LeadPipeline[]>([])
const selectedPipelineId = ref<number | null>(null)
const leads = ref<LeadRow[]>([])
const boardColumns = ref<Record<number, LeadRow[]>>({})
const loading = ref(true)
const error = ref('')

const currentPipeline = computed(() => pipelines.value.find((p) => p.id === selectedPipelineId.value) ?? null)
const pipelineOptions = computed(() => pipelines.value.map((p) => ({ label: p.name, value: p.id })))

async function loadPipelines() {
  const { data } = await api.get<ApiEnvelope<LeadPipeline[]>>('/lead-pipelines/list')
  pipelines.value = data.data
  if (! selectedPipelineId.value) {
    selectedPipelineId.value = (data.data.find((p) => p.is_default) ?? data.data[0])?.id ?? null
  }
}

async function loadLeads() {
  if (! selectedPipelineId.value) return

  loading.value = true
  try {
    const { data } = await api.get<ApiEnvelope<LeadRow[]>>('/leads/list', { params: { lead_pipeline_id: selectedPipelineId.value } })
    leads.value = data.data
    error.value = ''
  } catch (e) {
    error.value = errorMessage(e)
  } finally {
    loading.value = false
  }
  rebuildColumns()
}

function rebuildColumns() {
  const columns: Record<number, LeadRow[]> = {}
  for (const stage of currentPipeline.value?.stages ?? []) columns[stage.id] = []
  for (const lead of leads.value) {
    if (lead.lead_pipeline_stage_id && columns[lead.lead_pipeline_stage_id]) {
      columns[lead.lead_pipeline_stage_id].push(lead)
    }
  }
  boardColumns.value = columns
}

const STAGE_COLOR: Record<string, string> = { won: '#18a058', lost: '#d03050' }

const { lostModalOpen, lostReasonDraft, moving, requestMove, confirmLostMove, cancelLostMove } = useLeadStageMove(message)

async function onColumnChange(stage: LeadPipelineStage, event: { added?: { element: LeadRow } }) {
  if (! event.added) return

  const lead = event.added.element

  await requestMove(lead, stage, () => {
    // Dropped onto 'lost' but the reason prompt was cancelled — put the
    // card back where it was dragged from.
    const column = boardColumns.value[stage.id]
    const idx = column?.findIndex((l) => l.id === lead.id) ?? -1
    if (idx !== -1) column.splice(idx, 1)

    const originalStageId = lead.lead_pipeline_stage_id
    if (originalStageId && boardColumns.value[originalStageId]) {
      boardColumns.value[originalStageId].push(lead)
    }
  })
}

const modalOpen = ref(false)
const editing = ref<LeadRow | null>(null)

function openCreate() {
  editing.value = null
  modalOpen.value = true
}

function openLead(lead: LeadRow) {
  router.push({ name: 'crm-lead', params: { id: lead.id } })
}

const viewMode = ref<'board' | 'list'>('board')

const listColumns: DataTableColumns<LeadRow> = [
  {
    title: 'Title',
    key: 'title',
    render: (lead) =>
      h('span', { class: 'list-title', onClick: () => openLead(lead) }, [
        lead.title,
        lead.is_rotten
          ? h(
              NTooltip,
              { trigger: 'hover' },
              {
                trigger: () => h(NIcon, { class: 'list-rotten', size: 14 }, { default: () => h(AlertCircleOutline) }),
                default: () => 'No movement in a while — worth a follow-up.',
              },
            )
          : null,
      ]),
  },
  {
    title: 'Stage',
    key: 'stage',
    render: (lead) =>
      lead.stage
        ? h(
            NTag,
            { size: 'small', round: true, color: STAGE_COLOR[lead.stage.code] ? { color: STAGE_COLOR[lead.stage.code], textColor: '#fff', borderColor: 'transparent' } : undefined },
            { default: () => lead.stage!.name },
          )
        : '—',
  },
  { title: 'Value', key: 'lead_value', align: 'right', render: (lead) => (lead.lead_value ? formatMoney(Number(lead.lead_value), 'USD') : '—') },
  { title: 'Contact', key: 'contact_name', render: (lead) => lead.contact_name ?? '—' },
  { title: 'Source', key: 'source', render: (lead) => lead.source?.name ?? '—' },
  { title: 'Owner', key: 'owner', render: (lead) => lead.owner?.name ?? '—' },
  { title: 'Expected close', key: 'expected_close_date', render: (lead) => formatDate(lead.expected_close_date) || '—' },
]

watch(selectedPipelineId, loadLeads)

onMounted(async () => {
  await loadPipelines()
  await loadLeads()
})
</script>

<template>
  <div class="page wide">
    <div class="page-header">
      <div>
        <h1>Leads</h1>
        <p class="muted subtitle">Drag a card between stages to move it.</p>
      </div>
      <div class="header-actions">
        <NSelect v-if="pipelineOptions.length > 1" v-model:value="selectedPipelineId" :options="pipelineOptions" style="width: 200px" />
        <NButtonGroup>
          <NButton :type="viewMode === 'board' ? 'primary' : 'default'" ghost @click="viewMode = 'board'">
            <template #icon><NIcon><GridOutline /></NIcon></template>
          </NButton>
          <NButton :type="viewMode === 'list' ? 'primary' : 'default'" ghost @click="viewMode = 'list'">
            <template #icon><NIcon><ListOutline /></NIcon></template>
          </NButton>
        </NButtonGroup>
        <NButton v-if="auth.can('CRM-ManageLeads')" type="primary" @click="openCreate">
          <template #icon><NIcon><AddOutline /></NIcon></template>
          Add lead
        </NButton>
      </div>
    </div>

    <NAlert v-if="error" type="error" :show-icon="false">{{ error }}</NAlert>
    <NSkeleton v-else-if="loading" height="400px" :sharp="false" />

    <div v-else-if="viewMode === 'board'" class="board">
      <div v-for="stage in currentPipeline?.stages ?? []" :key="stage.id" class="column">
        <div class="column-head" :style="{ borderTopColor: STAGE_COLOR[stage.code] ?? '#d9d9d9' }">
          <strong>{{ stage.name }}</strong>
          <span class="muted small">{{ boardColumns[stage.id]?.length ?? 0 }} · {{ stage.probability }}%</span>
        </div>
        <draggable
          v-model="boardColumns[stage.id]"
          item-key="id"
          group="leads"
          class="column-body"
          :disabled="! auth.can('CRM-ManageLeads')"
          @change="(e: { added?: { element: LeadRow } }) => onColumnChange(stage, e)"
        >
          <template #item="{ element }">
            <LeadCard :lead="element" @click="openLead(element)" />
          </template>
        </draggable>
      </div>
    </div>

    <NDataTable
      v-else
      :columns="listColumns"
      :data="leads"
      :bordered="false"
      :row-key="(l: LeadRow) => l.id"
      :pagination="{ pageSize: 25 }"
    />

    <LeadFormModal v-model:show="modalOpen" :lead="editing" :pipeline-id="selectedPipelineId" @saved="loadLeads" @deleted="loadLeads" />

    <NModal v-model:show="lostModalOpen" preset="card" title="Mark as lost" style="max-width: 420px" :close-on-esc="false" :mask-closable="false">
      <NForm @submit.prevent="confirmLostMove">
        <NFormItem label="Why was it lost?">
          <NInput v-model:value="lostReasonDraft" type="textarea" :autosize="{ minRows: 2, maxRows: 5 }" placeholder="e.g. Went with a competitor" />
        </NFormItem>
        <div class="actions">
          <NButton :disabled="moving" @click="cancelLostMove">Cancel</NButton>
          <NButton type="error" attr-type="submit" :loading="moving" :disabled="! lostReasonDraft.trim()">Mark as lost</NButton>
        </div>
      </NForm>
    </NModal>
  </div>
</template>

<style scoped>
.wide {
  max-width: 100%;
}

.subtitle {
  margin: 6px 0 0;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 10px;
}

.board {
  display: flex;
  gap: 14px;
  overflow-x: auto;
  padding-bottom: 10px;
}

.column {
  flex: 0 0 260px;
  display: flex;
  flex-direction: column;
  background: var(--n-color-embedded, #f7f7f7);
  border-radius: 8px;
  max-height: calc(100vh - 220px);
}

.column-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 12px;
  border-top: 3px solid #d9d9d9;
  border-radius: 8px 8px 0 0;
}

.column-body {
  flex: 1;
  overflow-y: auto;
  padding: 8px;
  min-height: 60px;
}

.actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
}

:deep(.list-title) {
  cursor: pointer;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

:deep(.list-title:hover) {
  text-decoration: underline;
}

:deep(.list-rotten) {
  color: #f0a020;
}
</style>
