<script setup lang="ts">
import { CloudUploadOutline, DownloadOutline, TrashOutline } from '@vicons/ionicons5'
import { NButton, NEmpty, NIcon, useDialog, useMessage } from 'naive-ui'
import { ref } from 'vue'
import { api, errorMessage } from '@/api/client'
import type { LeadFile } from '@/api/crmLeadsTypes'
import { formatDate } from '@/lib/staff'

const props = defineProps<{ leadId: number; files: LeadFile[] }>()
const emit = defineEmits<{ changed: [] }>()

const message = useMessage()
const dialog = useDialog()

const fileInput = ref<HTMLInputElement | null>(null)
const uploading = ref(false)

function formatSize(bytes: number): string {
  if (bytes < 1024) return `${bytes} B`
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
}

async function onFileChosen(event: Event) {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  input.value = '' // allow choosing the same file again later
  if (! file) return

  uploading.value = true
  const formData = new FormData()
  formData.append('lead_id', String(props.leadId))
  formData.append('file', file)

  try {
    await api.post('/lead-files/create', formData, { headers: { 'Content-Type': 'multipart/form-data' } })
    emit('changed')
  } catch (e) {
    message.error(errorMessage(e))
  } finally {
    uploading.value = false
  }
}

async function download(file: LeadFile) {
  try {
    const response = await api.post('/lead-files/download', { id: file.id }, { responseType: 'blob' })
    const url = URL.createObjectURL(response.data as Blob)
    const link = document.createElement('a')
    link.href = url
    link.download = file.original_name
    link.click()
    URL.revokeObjectURL(url)
  } catch (e) {
    message.error(errorMessage(e))
  }
}

function remove(file: LeadFile) {
  dialog.warning({
    title: `Delete ${file.original_name}?`,
    content: 'This can’t be undone.',
    positiveText: 'Delete',
    negativeText: 'Cancel',
    onPositiveClick: async () => {
      try {
        await api.post('/lead-files/delete', { id: file.id })
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
      <input ref="fileInput" type="file" class="hidden-input" @change="onFileChosen" />
      <NButton size="small" :loading="uploading" @click="fileInput?.click()">
        <template #icon><NIcon><CloudUploadOutline /></NIcon></template>
        Upload file
      </NButton>
      <span class="muted small">PDF, Word, Excel, CSV, text, images or zip — up to 10 MB.</span>
    </div>

    <NEmpty v-if="! files.length" description="No files attached yet." />
    <div v-for="file in files" :key="file.id" class="row">
      <div class="body">
        <strong>{{ file.original_name }}</strong>
        <span class="muted small">{{ formatSize(file.size) }} · {{ file.uploader?.name ?? 'Unknown' }} · {{ formatDate(file.created_at) }}</span>
      </div>
      <NButton size="tiny" quaternary @click="download(file)">
        <template #icon><NIcon><DownloadOutline /></NIcon></template>
      </NButton>
      <NButton size="tiny" quaternary type="error" @click="remove(file)">
        <template #icon><NIcon><TrashOutline /></NIcon></template>
      </NButton>
    </div>
  </div>
</template>

<style scoped>
.head {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 10px;
  flex-wrap: wrap;
}

.hidden-input {
  display: none;
}

.row {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 0;
  border-top: 1px solid var(--n-border-color, #eee);
}

.row:first-of-type {
  border-top: none;
}

.body {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.small {
  font-size: 0.85em;
}
</style>
