<script setup lang="ts">
import { AddOutline, ArrowBackOutline } from '@vicons/ionicons5'
import { NAlert, NButton, NCard, NDatePicker, NForm, NFormItem, NIcon, NInput, NModal, NProgress, NSelect, NSkeleton, NSpace, NSwitch, NTag, useDialog, useMessage } from 'naive-ui'
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api, errorMessage } from '@/api/client'
import type { ApiEnvelope, Checklist, ChecklistTask, PersonRef } from '@/api/types'
import TaskRow from '@/components/TaskRow.vue'
import { formatDate } from '@/lib/staff'
import { useAuthStore } from '@/stores/auth'

/** One person's onboarding or offboarding checklist. */
const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const message = useMessage()
const dialog = useDialog()

const checklist = ref<Checklist | null>(null)
const error = ref('')
const isHr = computed(() => auth.can('HRMS-StaffManagement'))

const tasks = computed(() => checklist.value?.tasks ?? [])
const closed = computed(() => tasks.value.filter((t) => t.status === 'done' || t.status === 'skipped').length)
const percent = computed(() => (tasks.value.length ? Math.round((closed.value / tasks.value.length) * 100) : 0))
const active = computed(() => checklist.value?.status !== 'cancelled')

/** HR may change any task; others only theirs (by name or role). The API has the final say. */
function canChange(task: ChecklistTask): boolean {
  if (!active.value) return false
  if (isHr.value) return true
  if (task.assignee_user_id === auth.me?.user.id) return true
  return !!task.assignee_role && (auth.me?.roles ?? []).includes(task.assignee_role.name)
}

async function load() {
  try {
    const { data } = await api.post<ApiEnvelope<Checklist>>('/checklists/show', { id: Number(route.params.id) })
    checklist.value = data.data
  } catch (e) {
    error.value = errorMessage(e)
  }
}

function cancelChecklist() {
  dialog.warning({
    title: 'Cancel this checklist?',
    content: 'Open tasks will no longer appear on anyone’s list.',
    positiveText: 'Cancel checklist',
    negativeText: 'Keep it',
    onPositiveClick: async () => {
      try {
        await api.post('/checklists/cancel', { id: checklist.value!.id })
        message.success('Checklist cancelled.')
        await load()
      } catch (e) {
        message.error(errorMessage(e))
      }
    },
  })
}

// Add a one-off task (HR).
const adding = ref(false)
const people = ref<PersonRef[]>([])
const newTask = reactive({ title: '', assignee_user_id: null as number | null, due_date: null as string | null, is_required: true })

async function openAdd() {
  adding.value = true
  if (!people.value.length) people.value = (await api.get<ApiEnvelope<PersonRef[]>>('/staff/eligible-managers')).data.data
}

async function addTask() {
  try {
    await api.post('/checklists/add-task', { staff_checklist_id: checklist.value!.id, ...newTask })
    message.success('Task added.')
    adding.value = false
    Object.assign(newTask, { title: '', assignee_user_id: null, due_date: null, is_required: true })
    await load()
  } catch (e) {
    message.error(errorMessage(e))
  }
}

watch(() => route.params.id, load)
onMounted(load)
</script>

<template>
  <div class="page">
    <NButton text class="back" @click="router.back()">
      <template #icon><NIcon><ArrowBackOutline /></NIcon></template>
      Back
    </NButton>

    <NAlert v-if="error" type="error" :show-icon="false">{{ error }}</NAlert>
    <NSkeleton v-else-if="!checklist" height="320px" :sharp="false" />

    <template v-else>
      <div class="page-header">
        <div>
          <h1>{{ checklist.type === 'onboarding' ? 'Onboarding' : 'Offboarding' }}: {{ checklist.staff.name }}</h1>
          <p class="muted subtitle">
            {{ checklist.staff.designation }} ·
            {{ checklist.type === 'onboarding' ? 'starts' : 'last day' }} {{ formatDate(checklist.anchor_date) }}
            <template v-if="checklist.template"> · {{ checklist.template.name }}</template>
          </p>
        </div>
        <NSpace v-if="isHr && active">
          <NButton @click="openAdd">
            <template #icon><NIcon><AddOutline /></NIcon></template>
            Add task
          </NButton>
          <NButton v-if="checklist.status === 'in_progress'" quaternary type="error" @click="cancelChecklist">Cancel checklist</NButton>
        </NSpace>
      </div>

      <NCard>
        <div class="progress-head">
          <strong>{{ closed }} of {{ tasks.length }} tasks done</strong>
          <NTag round :type="checklist.status === 'completed' ? 'success' : checklist.status === 'cancelled' ? 'default' : 'info'">
            {{ checklist.status === 'in_progress' ? 'In progress' : checklist.status === 'completed' ? 'Completed' : 'Cancelled' }}
          </NTag>
        </div>
        <NProgress type="line" :percentage="percent" :show-indicator="false" :height="10" class="bar" />
        <TaskRow v-for="t in tasks" :key="t.id" :task="t" :can-change="canChange(t)" show-assignee @changed="load" />
      </NCard>

      <NModal v-model:show="adding" preset="card" title="Add a task" style="max-width: 480px">
        <NForm @submit.prevent="addTask">
          <NFormItem label="Task"><NInput v-model:value="newTask.title" placeholder="e.g. Order business cards" /></NFormItem>
          <NFormItem label="Who does it">
            <NSelect v-model:value="newTask.assignee_user_id" filterable clearable :options="people.map((p) => ({ label: p.name, value: p.id }))" />
          </NFormItem>
          <NFormItem label="Due">
            <NDatePicker v-model:formatted-value="newTask.due_date" value-format="yyyy-MM-dd" type="date" clearable style="width: 100%" />
          </NFormItem>
          <NFormItem label="Required"><NSwitch v-model:value="newTask.is_required" /></NFormItem>
          <NSpace justify="end">
            <NButton @click="adding = false">Cancel</NButton>
            <NButton type="primary" attr-type="submit" :disabled="!newTask.title.trim()">Add task</NButton>
          </NSpace>
        </NForm>
      </NModal>
    </template>
  </div>
</template>

<style scoped>
.back {
  margin-bottom: 14px;
}

.subtitle {
  margin: 6px 0 0;
}

.progress-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.bar {
  margin: 10px 0 8px;
}
</style>
