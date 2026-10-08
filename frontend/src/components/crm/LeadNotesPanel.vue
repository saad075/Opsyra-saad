<script setup lang="ts">
import { TrashOutline } from '@vicons/ionicons5'
import { NButton, NEmpty, NIcon, NInput, NSpace, useDialog, useMessage } from 'naive-ui'
import { ref } from 'vue'
import { api, errorMessage } from '@/api/client'
import type { LeadNote } from '@/api/crmLeadsTypes'
import { formatDate } from '@/lib/staff'

const props = defineProps<{ leadId: number; notes: LeadNote[] }>()
const emit = defineEmits<{ changed: [] }>()

const message = useMessage()
const dialog = useDialog()

const draft = ref('')
const saving = ref(false)
const editingId = ref<number | null>(null)
const editDraft = ref('')

async function add() {
  if (! draft.value.trim()) return

  saving.value = true
  try {
    await api.post('/lead-notes/create', { lead_id: props.leadId, body: draft.value })
    draft.value = ''
    emit('changed')
  } catch (e) {
    message.error(errorMessage(e))
  } finally {
    saving.value = false
  }
}

function startEdit(note: LeadNote) {
  editingId.value = note.id
  editDraft.value = note.body
}

async function saveEdit(note: LeadNote) {
  try {
    await api.post('/lead-notes/update', { id: note.id, body: editDraft.value })
    editingId.value = null
    emit('changed')
  } catch (e) {
    message.error(errorMessage(e))
  }
}

function remove(note: LeadNote) {
  dialog.warning({
    title: 'Delete this note?',
    content: 'This can’t be undone.',
    positiveText: 'Delete',
    negativeText: 'Cancel',
    onPositiveClick: async () => {
      try {
        await api.post('/lead-notes/delete', { id: note.id })
        emit('changed')
      } catch (e) {
        message.error(errorMessage(e))
      }
    },
  })
}
</script>

<template>
  <NSpace vertical size="large">
    <NSpace vertical>
      <NInput v-model:value="draft" type="textarea" placeholder="Add a note…" :autosize="{ minRows: 2, maxRows: 6 }" />
      <NButton size="small" type="primary" :disabled="! draft.trim()" :loading="saving" @click="add">Add note</NButton>
    </NSpace>

    <NEmpty v-if="! notes.length" description="No notes yet." />
    <div v-for="note in notes" :key="note.id" class="note">
      <template v-if="editingId === note.id">
        <NInput v-model:value="editDraft" type="textarea" :autosize="{ minRows: 2, maxRows: 6 }" />
        <NSpace class="note-actions" size="small">
          <NButton size="tiny" @click="editingId = null">Cancel</NButton>
          <NButton size="tiny" type="primary" @click="saveEdit(note)">Save</NButton>
        </NSpace>
      </template>
      <template v-else>
        <p class="body">{{ note.body }}</p>
        <div class="meta">
          <span class="muted">{{ note.author?.name ?? 'Unknown' }} · {{ formatDate(note.created_at) }}</span>
          <NSpace size="small">
            <NButton size="tiny" quaternary @click="startEdit(note)">Edit</NButton>
            <NButton size="tiny" quaternary type="error" @click="remove(note)">
              <template #icon><NIcon><TrashOutline /></NIcon></template>
            </NButton>
          </NSpace>
        </div>
      </template>
    </div>
  </NSpace>
</template>

<style scoped>
.note {
  padding: 10px 0;
  border-top: 1px solid var(--n-border-color, #eee);
}

.note:first-of-type {
  border-top: none;
}

.body {
  white-space: pre-wrap;
  margin: 0 0 6px;
}

.meta {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}

.note-actions {
  margin-top: 8px;
  justify-content: flex-end;
}
</style>
