<script setup lang="ts">
import { AddOutline, TrashOutline } from '@vicons/ionicons5'
import { NButton, NCheckbox, NDatePicker, NEmpty, NForm, NFormItem, NIcon, NInput, NModal, NSelect, NSpace, NTag, useDialog, useMessage } from 'naive-ui'
import { reactive, ref } from 'vue'
import { api, errorMessage } from '@/api/client'
import type { ApiEnvelope } from '@/api/types'
import type { LeadActivity, LeadActivityType } from '@/api/crmLeadsTypes'
import { formatDate } from '@/lib/staff'

const props = defineProps<{ leadId: number; activities: LeadActivity[] }>()
const emit = defineEmits<{ changed: [] }>()

const message = useMessage()
const dialog = useDialog()

const TYPE_LABEL: Record<LeadActivityType, string> = { call: 'Call', meeting: 'Meeting', lunch: 'Lunch', deadline: 'Deadline', other: 'Other' }
const typeOptions = Object.entries(TYPE_LABEL).map(([value, label]) => ({ label, value }))

const modalOpen = ref(false)
const saving = ref(false)
const scheduledAtMs = ref<number | null>(null)
const form = reactive({ type: 'call' as LeadActivityType, title: '', comment: '' })

function openCreate() {
  Object.assign(form, { type: 'call', title: '', comment: '' })
  scheduledAtMs.value = null
  modalOpen.value = true
}

async function save() {
  saving.value = true
  try {
    await api.post('/lead-activities/create', {
      lead_id: props.leadId,
      type: form.type,
      title: form.title,
      comment: form.comment || null,
      scheduled_at: scheduledAtMs.value ? new Date(scheduledAtMs.value).toISOString().slice(0, 19).replace('T', ' ') : null,
    })
    modalOpen.value = false
    emit('changed')
  } catch (e) {
    message.error(errorMessage(e))
  } finally {
    saving.value = false
  }
}

async function toggleDone(activity: LeadActivity) {
  try {
    await api.post<ApiEnvelope<LeadActivity>>('/lead-activities/update', {
      id: activity.id, type: activity.type, title: activity.title, comment: activity.comment, scheduled_at: activity.scheduled_at,
      is_done: ! activity.is_done,
    })
    emit('changed')
  } catch (e) {
    message.error(errorMessage(e))
  }
}

function remove(activity: LeadActivity) {
  dialog.warning({
    title: 'Delete this activity?',
    content: 'This can’t be undone.',
    positiveText: 'Delete',
    negativeText: 'Cancel',
    onPositiveClick: async () => {
      try {
        await api.post('/lead-activities/delete', { id: activity.id })
        emit('changed')
      } catch (e) {
        message.error(errorMessage(e))
      }
    },
  })
}
</script>

<template>
  <div>
    <div class="head">
      <NButton size="small" @click="openCreate">
        <template #icon><NIcon><AddOutline /></NIcon></template>
        Log activity
      </NButton>
    </div>

    <NEmpty v-if="! activities.length" description="No activities logged yet." />
    <div v-for="activity in activities" :key="activity.id" class="row">
      <NCheckbox :checked="activity.is_done" @update:checked="toggleDone(activity)" />
      <div class="body" :class="{ done: activity.is_done }">
        <div class="title-row">
          <NTag size="small" round>{{ TYPE_LABEL[activity.type] }}</NTag>
          <strong>{{ activity.title }}</strong>
        </div>
        <p v-if="activity.comment" class="comment">{{ activity.comment }}</p>
        <span class="muted small">
          {{ activity.scheduled_at ? `Scheduled ${formatDate(activity.scheduled_at)}` : '' }}
          {{ activity.author ? ` · logged by ${activity.author.name}` : '' }}
        </span>
      </div>
      <NButton size="tiny" quaternary type="error" @click="remove(activity)">
        <template #icon><NIcon><TrashOutline /></NIcon></template>
      </NButton>
    </div>

    <NModal v-model:show="modalOpen" preset="card" title="Log activity" style="max-width: 460px" :mask-closable="!saving">
      <NForm @submit.prevent="save">
        <NFormItem label="Type"><NSelect v-model:value="form.type" :options="typeOptions" /></NFormItem>
        <NFormItem label="Title"><NInput v-model:value="form.title" placeholder="e.g. Discovery call" /></NFormItem>
        <NFormItem label="When"><NDatePicker v-model:value="scheduledAtMs" type="datetime" clearable style="width: 100%" /></NFormItem>
        <NFormItem label="Comment"><NInput v-model:value="form.comment" type="textarea" :autosize="{ minRows: 2, maxRows: 5 }" /></NFormItem>
        <NSpace justify="end">
          <NButton :disabled="saving" @click="modalOpen = false">Cancel</NButton>
          <NButton type="primary" attr-type="submit" :loading="saving" :disabled="! form.title.trim()">Log activity</NButton>
        </NSpace>
      </NForm>
    </NModal>
  </div>
</template>

<style scoped>
.head {
  margin-bottom: 10px;
}

.row {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 10px 0;
  border-top: 1px solid var(--n-border-color, #eee);
}

.row:first-of-type {
  border-top: none;
}

.body {
  flex: 1;
}

.body.done strong {
  text-decoration: line-through;
  opacity: 0.6;
}

.title-row {
  display: flex;
  align-items: center;
  gap: 8px;
}

.comment {
  margin: 4px 0;
  white-space: pre-wrap;
}

.small {
  font-size: 0.85em;
}
</style>
