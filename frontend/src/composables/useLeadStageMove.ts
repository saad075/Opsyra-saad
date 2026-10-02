import { ref } from 'vue'
import { api, errorMessage } from '@/api/client'
import type { ApiEnvelope } from '@/api/types'
import type { LeadPipelineStage, LeadRow } from '@/api/crmLeadsTypes'

/**
 * The move-a-lead-onto-a-stage flow, shared by the Kanban board (drag) and
 * the lead detail page (clicking a step): moving onto a 'lost' stage always
 * prompts for a reason first (the API refuses without one — see
 * LeadController::move); everything else commits immediately.
 *
 * `onCancelled` lets a caller undo whatever it did optimistically before
 * this ran (the Kanban board puts the dragged card back in its original
 * column); the detail page has nothing to undo, so it just omits it.
 */
export function useLeadStageMove(message: { error: (msg: string) => void }, onMoved?: () => void) {
  const lostModalOpen = ref(false)
  const lostReasonDraft = ref('')
  const moving = ref(false)

  let pending: { lead: LeadRow; stage: LeadPipelineStage; onCancelled?: () => void } | null = null

  async function commit(lead: LeadRow, stage: LeadPipelineStage, lostReason?: string): Promise<LeadRow | null> {
    try {
      const { data } = await api.post<ApiEnvelope<LeadRow>>('/leads/move', {
        id: lead.id,
        lead_pipeline_stage_id: stage.id,
        lost_reason: lostReason,
      })
      Object.assign(lead, data.data)
      // The move response is the bare lead — no eager-loaded relations, so
      // a page relying on those (the detail page's changelog) needs to
      // reload rather than trust this object alone.
      onMoved?.()
      return data.data
    } catch (e) {
      message.error(errorMessage(e))
      return null
    }
  }

  async function requestMove(lead: LeadRow, stage: LeadPipelineStage, onCancelled?: () => void): Promise<void> {
    if (stage.code === 'lost') {
      pending = { lead, stage, onCancelled }
      lostReasonDraft.value = ''
      lostModalOpen.value = true
      return
    }

    await commit(lead, stage)
  }

  async function confirmLostMove(): Promise<void> {
    if (! pending) return

    moving.value = true
    try {
      const result = await commit(pending.lead, pending.stage, lostReasonDraft.value)
      if (result) {
        lostModalOpen.value = false
        pending = null
      }
    } finally {
      moving.value = false
    }
  }

  function cancelLostMove(): void {
    pending?.onCancelled?.()
    lostModalOpen.value = false
    pending = null
  }

  return { lostModalOpen, lostReasonDraft, moving, requestMove, confirmLostMove, cancelLostMove }
}
