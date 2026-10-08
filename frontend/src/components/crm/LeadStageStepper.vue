<script setup lang="ts">
import { ChevronDownOutline } from '@vicons/ionicons5'
import { NDropdown, NIcon } from 'naive-ui'
import { computed } from 'vue'
import type { LeadPipelineStage } from '@/api/crmLeadsTypes'

const props = defineProps<{
  stages: LeadPipelineStage[]
  currentStageId: number | null
  disabled?: boolean
}>()

const emit = defineEmits<{ select: [LeadPipelineStage] }>()

/** The open-pipeline steps shown as connected arrows — won/lost are a separate dropdown segment, not steps in the flow. */
const flowStages = computed(() => props.stages.filter((s) => s.code !== 'won' && s.code !== 'lost').sort((a, b) => a.sort_order - b.sort_order))
const wonStage = computed(() => props.stages.find((s) => s.code === 'won') ?? null)
const lostStage = computed(() => props.stages.find((s) => s.code === 'lost') ?? null)

const currentFlowIndex = computed(() => flowStages.value.findIndex((s) => s.id === props.currentStageId))
const isClosed = computed(() => props.currentStageId === wonStage.value?.id || props.currentStageId === lostStage.value?.id)

function statusOf(index: number): 'current' | 'done' | 'upcoming' {
  if (isClosed.value) return 'done' // the whole flow reads as completed once the lead is won or lost
  if (index === currentFlowIndex.value) return 'current'
  return index < currentFlowIndex.value ? 'done' : 'upcoming'
}

const closedLabel = computed(() => {
  if (props.currentStageId === wonStage.value?.id) return 'Won'
  if (props.currentStageId === lostStage.value?.id) return 'Lost'
  return 'Won/Lost'
})

const closedOptions = computed(() => [
  ...(wonStage.value ? [{ label: 'Won', key: 'won' }] : []),
  ...(lostStage.value ? [{ label: 'Lost', key: 'lost' }] : []),
])

function onClosedSelect(key: string) {
  const stage = key === 'won' ? wonStage.value : lostStage.value
  if (stage) emit('select', stage)
}
</script>

<template>
  <div class="stepper">
    <button
      v-for="(stage, index) in flowStages"
      :key="stage.id"
      type="button"
      class="arrow"
      :class="statusOf(index)"
      :disabled="disabled || isClosed"
      @click="emit('select', stage)"
    >
      {{ stage.name }}
    </button>

    <NDropdown v-if="closedOptions.length" trigger="click" :options="closedOptions" :disabled="disabled" @select="onClosedSelect">
      <button type="button" class="closed-pill" :class="{ won: currentStageId === wonStage?.id, lost: currentStageId === lostStage?.id }" :disabled="disabled">
        {{ closedLabel }}
        <NIcon size="14"><ChevronDownOutline /></NIcon>
      </button>
    </NDropdown>
  </div>
</template>

<style scoped>
.stepper {
  display: flex;
  align-items: stretch;
  flex-wrap: wrap;
  row-gap: 8px;
  margin-bottom: 14px;
}

.arrow {
  position: relative;
  border: none;
  background: #ebebeb;
  color: #333;
  padding: 8px 20px 8px 24px;
  font-size: 0.85em;
  cursor: pointer;
  clip-path: polygon(0 0, calc(100% - 10px) 0, 100% 50%, calc(100% - 10px) 100%, 0 100%, 10px 50%);
  margin-right: -8px;
}

.arrow:first-child {
  clip-path: polygon(0 0, calc(100% - 10px) 0, 100% 50%, calc(100% - 10px) 100%, 0 100%);
  padding-left: 16px;
}

.arrow:disabled {
  cursor: default;
}

.arrow.current {
  background: #2080f0;
  color: #fff;
  font-weight: 600;
}

.arrow.done {
  background: #d4ecdf;
  color: #18a058;
}

.arrow.upcoming {
  background: #ebebeb;
  color: #666;
}

.closed-pill {
  display: flex;
  align-items: center;
  gap: 4px;
  border: 1px solid var(--n-border-color, #d9d9d9);
  background: var(--n-color, #fff);
  border-radius: 16px;
  padding: 7px 14px;
  font-size: 0.85em;
  cursor: pointer;
  margin-left: 10px;
}

.closed-pill:disabled {
  cursor: default;
}

.closed-pill.won {
  background: #18a058;
  border-color: transparent;
  color: #fff;
  font-weight: 600;
}

.closed-pill.lost {
  background: #d03050;
  border-color: transparent;
  color: #fff;
  font-weight: 600;
}
</style>
