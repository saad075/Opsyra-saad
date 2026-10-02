<script setup lang="ts">
import { ArrowBackOutline, CheckmarkCircle } from '@vicons/ionicons5'
import { NAlert, NButton, NCard, NDescriptions, NDescriptionsItem, NEmpty, NForm, NFormItem, NGrid, NGridItem, NIcon, NInput, NModal, NSkeleton, NSpace, useMessage } from 'naive-ui'
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api, errorMessage } from '@/api/client'
import type { ApiEnvelope } from '@/api/types'
import type { LeadPipelineStage, LeadRow } from '@/api/crmLeadsTypes'
import LeadFormModal from '@/components/crm/LeadFormModal.vue'
import { useLeadStageMove } from '@/composables/useLeadStageMove'
import { formatDate } from '@/lib/staff'
import { formatMoney } from '@/lib/money'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const message = useMessage()

const id = computed(() => Number(route.params.id))
const lead = ref<LeadRow | null>(null)
const error = ref('')
const editOpen = ref(false)

async function loadLead() {
  error.value = ''
  try {
    const { data } = await api.post<ApiEnvelope<LeadRow>>('/leads/show', { id: id.value })
    lead.value = data.data
  } catch (e) {
    error.value = errorMessage(e)
  }
}

const { lostModalOpen, lostReasonDraft, moving, requestMove, confirmLostMove, cancelLostMove } = useLeadStageMove(message, loadLead)

// The pipeline's stages aren't on the lead response itself — fetched once
// from the pipeline list, same source the Kanban board uses.
const stages = ref<LeadPipelineStage[]>([])
async function loadStages() {
  if (! lead.value?.lead_pipeline_id) return
  const { data } = await api.get<ApiEnvelope<{ id: number; stages: LeadPipelineStage[] }[]>>('/lead-pipelines/list')
  stages.value = data.data.find((p) => p.id === lead.value!.lead_pipeline_id)?.stages ?? []
}

async function clickStage(stage: LeadPipelineStage) {
  if (! lead.value || ! auth.can('CRM-ManageLeads') || stage.id === lead.value.lead_pipeline_stage_id) return

  await requestMove(lead.value, stage)
}

const STAGE_COLOR: Record<string, string> = { won: '#18a058', lost: '#d03050' }

function stageStatus(stage: LeadPipelineStage): 'current' | 'done' | 'upcoming' {
  if (! lead.value) return 'upcoming'
  if (stage.id === lead.value.lead_pipeline_stage_id) return 'current'
  const currentOrder = stages.value.find((s) => s.id === lead.value!.lead_pipeline_stage_id)?.sort_order ?? 0
  return stage.sort_order < currentOrder ? 'done' : 'upcoming'
}

watch(id, async () => {
  await loadLead()
  await loadStages()
})

onMounted(async () => {
  await loadLead()
  await loadStages()
})
</script>

<template>
  <div class="page wide">
    <NButton text class="back" @click="router.push({ name: 'crm-leads' })">
      <template #icon><NIcon><ArrowBackOutline /></NIcon></template>
      Leads
    </NButton>

    <NAlert v-if="error" type="error" :show-icon="false">{{ error }}</NAlert>
    <NSkeleton v-else-if="! lead" height="300px" :sharp="false" />

    <template v-else>
      <div class="page-header">
        <h1>{{ lead.title }}</h1>
        <NButton v-if="auth.can('CRM-ManageLeads')" @click="editOpen = true">Edit</NButton>
      </div>

      <div class="stepper">
        <button
          v-for="stage in stages"
          :key="stage.id"
          type="button"
          class="step"
          :class="stageStatus(stage)"
          :style="stageStatus(stage) === 'current' ? { background: STAGE_COLOR[stage.code] ?? '#2080f0' } : {}"
          :disabled="! auth.can('CRM-ManageLeads') || moving"
          @click="clickStage(stage)"
        >
          <NIcon v-if="stageStatus(stage) === 'done'" size="14" class="step-icon"><CheckmarkCircle /></NIcon>
          {{ stage.name }}
        </button>
      </div>
      <NAlert v-if="lead.status === false" type="error" :show-icon="false" class="gap">Lost: {{ lead.lost_reason }}</NAlert>

      <NGrid :cols="3" :x-gap="16" :y-gap="16" item-responsive responsive="screen">
        <NGridItem span="3 l:1">
          <NCard title="About this lead">
            <NDescriptions :column="1" label-placement="left" size="small">
              <NDescriptionsItem label="Value">{{ lead.lead_value ? formatMoney(Number(lead.lead_value), 'USD') : '—' }}</NDescriptionsItem>
              <NDescriptionsItem label="Source">{{ lead.source?.name ?? '—' }}</NDescriptionsItem>
              <NDescriptionsItem label="Type">{{ lead.type?.name ?? '—' }}</NDescriptionsItem>
              <NDescriptionsItem label="Owner">{{ lead.owner?.name ?? 'Unassigned' }}</NDescriptionsItem>
              <NDescriptionsItem label="Expected close">{{ formatDate(lead.expected_close_date) || '—' }}</NDescriptionsItem>
            </NDescriptions>

            <div class="section-sep">
              <h4>Contact</h4>
              <p v-if="! lead.contact_name && ! lead.contact_email && ! lead.contact_phone" class="muted">No contact details.</p>
              <p v-if="lead.contact_name">{{ lead.contact_name }}</p>
              <p v-if="lead.contact_email" class="muted">{{ lead.contact_email }}</p>
              <p v-if="lead.contact_phone" class="muted">{{ lead.contact_phone }}</p>
            </div>

            <div v-if="lead.description" class="section-sep">
              <h4>Description</h4>
              <p class="description">{{ lead.description }}</p>
            </div>
          </NCard>
        </NGridItem>

        <NGridItem span="3 l:2">
          <NCard title="Changelog">
            <NEmpty v-if="! lead.stage_changes?.length" description="No stage changes yet." />
            <div v-for="change in lead.stage_changes" :key="change.id" class="change">
              <span class="change-text">
                Updated stage: <strong>{{ change.from_stage?.name ?? '—' }}</strong> → <strong>{{ change.to_stage?.name ?? '—' }}</strong>
              </span>
              <span class="muted small">{{ formatDate(change.created_at) }} · by {{ change.changed_by?.name ?? 'System' }}</span>
            </div>
          </NCard>
        </NGridItem>
      </NGrid>

      <LeadFormModal v-model:show="editOpen" :lead="lead" :pipeline-id="lead.lead_pipeline_id" @saved="loadLead" @deleted="router.push({ name: 'crm-leads' })" />

      <NModal v-model:show="lostModalOpen" preset="card" title="Mark as lost" style="max-width: 420px" :close-on-esc="false" :mask-closable="false">
        <NForm @submit.prevent="confirmLostMove">
          <NFormItem label="Why was it lost?">
            <NInput v-model:value="lostReasonDraft" type="textarea" :autosize="{ minRows: 2, maxRows: 5 }" placeholder="e.g. Went with a competitor" />
          </NFormItem>
          <NSpace justify="end">
            <NButton :disabled="moving" @click="cancelLostMove">Cancel</NButton>
            <NButton type="error" attr-type="submit" :loading="moving" :disabled="! lostReasonDraft.trim()">Mark as lost</NButton>
          </NSpace>
        </NForm>
      </NModal>
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

.stepper {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-bottom: 10px;
}

.step {
  border: 1px solid var(--n-border-color, #d9d9d9);
  background: var(--n-color, #fff);
  border-radius: 16px;
  padding: 6px 14px;
  font-size: 0.85em;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 4px;
}

.step:disabled {
  cursor: default;
}

.step.current {
  color: #fff;
  border-color: transparent;
  font-weight: 600;
}

.step.done {
  opacity: 0.75;
}

.step-icon {
  color: #18a058;
}

.gap {
  margin-bottom: 14px;
}

.section-sep {
  margin-top: 14px;
  padding-top: 14px;
  border-top: 1px solid var(--n-border-color, #eee);
}

.section-sep h4 {
  margin: 0 0 6px;
}

.section-sep p {
  margin: 2px 0;
}

.description {
  white-space: pre-wrap;
}

.change {
  display: flex;
  flex-direction: column;
  gap: 2px;
  padding: 10px 0;
  border-top: 1px solid var(--n-border-color, #eee);
}

.change:first-of-type {
  border-top: none;
}

.small {
  font-size: 0.85em;
}
</style>
