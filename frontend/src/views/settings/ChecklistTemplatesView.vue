<script setup lang="ts">
import { AddOutline, ArrowDownOutline, ArrowUpOutline, TrashOutline } from '@vicons/ionicons5'
import {
  NAlert,
  NButton,
  NCard,
  NCheckbox,
  NDrawer,
  NDrawerContent,
  NEmpty,
  NForm,
  NFormItem,
  NIcon,
  NInput,
  NInputNumber,
  NRadioButton,
  NRadioGroup,
  NSelect,
  NSkeleton,
  NSpace,
  NTag,
  useDialog,
  useMessage,
} from 'naive-ui'
import { computed, onMounted, reactive, ref } from 'vue'
import { api, errorMessage } from '@/api/client'
import type { ApiEnvelope, ChecklistTemplate, ChecklistTemplateTask, Named, PersonRef, TemplateAssignee } from '@/api/types'
import { offsetLabel } from '@/lib/tasks'

/**
 * The task lists copied onto a person's checklist when they join or leave.
 * One template per type is the default; HR can pick another per person.
 */
const message = useMessage()
const dialog = useDialog()

const templates = ref<ChecklistTemplate[] | null>(null)
const loadError = ref('')
const roles = ref<Named[]>([])
const people = ref<PersonRef[]>([])

async function load() {
  try {
    const { data } = await api.get<ApiEnvelope<ChecklistTemplate[]>>('/checklist-templates/list', { params: { include_inactive: 1 } })
    templates.value = data.data
  } catch (e) {
    loadError.value = errorMessage(e)
  }
}

const groups = computed(() => [
  { type: 'onboarding' as const, title: 'Joining', list: (templates.value ?? []).filter((t) => t.type === 'onboarding') },
  { type: 'offboarding' as const, title: 'Leaving', list: (templates.value ?? []).filter((t) => t.type === 'offboarding') },
])

// Editor.
const open = ref(false)
const saving = ref(false)
const editingId = ref<number | null>(null)
const form = reactive({
  type: 'onboarding' as 'onboarding' | 'offboarding',
  name: '',
  description: '',
  is_default: false,
  is_active: true,
  tasks: [] as ChecklistTemplateTask[],
})

const blankTask = (): ChecklistTemplateTask => ({ title: '', description: null, assignee_type: 'manager', assignee_role_id: null, assignee_user_id: null, due_offset_days: 0, is_required: true })

async function edit(template: ChecklistTemplate | null, type: 'onboarding' | 'offboarding' = 'onboarding') {
  editingId.value = template?.id ?? null
  Object.assign(form, {
    type: template?.type ?? type,
    name: template?.name ?? '',
    description: template?.description ?? '',
    is_default: template?.is_default ?? false,
    is_active: template?.is_active ?? true,
    tasks: template ? template.tasks.map((t) => ({ ...t })) : [blankTask()],
  })
  open.value = true
  // Pickers for "a role" / "a person". Roles need role-management access; without it the option is just empty.
  if (!people.value.length) people.value = (await api.get<ApiEnvelope<PersonRef[]>>('/staff/eligible-managers').catch(() => ({ data: { data: [] } }))).data.data
  if (!roles.value.length) roles.value = (await api.get<ApiEnvelope<Named[]>>('/roles/list').catch(() => ({ data: { data: [] } }))).data.data
}

const assigneeOptions = computed(() => [
  { label: 'Their manager', value: 'manager' },
  { label: form.type === 'onboarding' ? 'The new joiner' : 'The person leaving', value: 'employee' },
  { label: 'Anyone with a role', value: 'role' },
  { label: 'A specific person', value: 'user' },
])

function move(i: number, by: -1 | 1) {
  const [task] = form.tasks.splice(i, 1)
  form.tasks.splice(i + by, 0, task)
}

const invalid = computed(
  () =>
    !form.name.trim() ||
    !form.tasks.length ||
    form.tasks.some((t) => !t.title.trim() || (t.assignee_type === 'role' && !t.assignee_role_id) || (t.assignee_type === 'user' && !t.assignee_user_id)),
)

async function save() {
  saving.value = true
  try {
    const body = { ...form, description: form.description || null }
    await api.post(`/checklist-templates/${editingId.value ? 'update' : 'create'}`, editingId.value ? { id: editingId.value, ...body } : body)
    message.success('Template saved. New checklists will use it; ones already running keep their tasks.')
    open.value = false
    await load()
  } catch (e) {
    message.error(errorMessage(e))
  } finally {
    saving.value = false
  }
}

function remove(template: ChecklistTemplate) {
  dialog.warning({
    title: `Delete “${template.name}”?`,
    content: 'Checklists already started from it keep their tasks.',
    positiveText: 'Delete',
    negativeText: 'Cancel',
    onPositiveClick: async () => {
      try {
        await api.post('/checklist-templates/delete', { id: template.id })
        message.success('Deleted.')
        await load()
      } catch (e) {
        message.error(errorMessage(e))
      }
    },
  })
}

onMounted(load)
</script>

<template>
  <div class="page">
    <div class="page-header">
      <div>
        <h1>Checklist templates</h1>
        <p class="muted subtitle">The tasks created when someone joins or leaves — who does what, and when.</p>
      </div>
    </div>

    <NAlert v-if="loadError" type="error" :show-icon="false">{{ loadError }}</NAlert>
    <NSkeleton v-else-if="!templates" height="240px" :sharp="false" />

    <template v-else>
      <section v-for="g in groups" :key="g.type" class="group">
        <div class="group-head">
          <h2>{{ g.title }}</h2>
          <NButton size="small" @click="edit(null, g.type)">
            <template #icon><NIcon><AddOutline /></NIcon></template>
            New template
          </NButton>
        </div>
        <NCard v-if="!g.list.length"><NEmpty description="No templates yet." /></NCard>
        <NCard v-for="t in g.list" :key="t.id" class="template" hoverable @click="edit(t)">
          <div class="template-head">
            <strong :class="{ off: !t.is_active }">{{ t.name }}</strong>
            <NTag v-if="t.is_default" size="small" round type="primary">Default</NTag>
            <NTag v-if="!t.is_active" size="small" round>Not in use</NTag>
            <span class="muted">{{ t.tasks.length }} tasks</span>
            <NButton v-if="!t.is_default" size="tiny" quaternary type="error" class="del" @click.stop="remove(t)">Delete</NButton>
          </div>
          <p v-if="t.description" class="muted desc">{{ t.description }}</p>
          <p class="muted preview">{{ t.tasks.slice(0, 4).map((x) => x.title).join(' · ') }}<template v-if="t.tasks.length > 4"> · …</template></p>
        </NCard>
      </section>
    </template>

    <NDrawer v-model:show="open" :width="720" placement="right" :trap-focus="false">
      <NDrawerContent :title="editingId ? 'Edit template' : 'New template'" closable>
        <NForm @submit.prevent="save">
          <NFormItem label="For">
            <NRadioGroup v-model:value="form.type" :disabled="!!editingId">
              <NRadioButton value="onboarding">Someone joining</NRadioButton>
              <NRadioButton value="offboarding">Someone leaving</NRadioButton>
            </NRadioGroup>
          </NFormItem>
          <NFormItem label="Name"><NInput v-model:value="form.name" placeholder="e.g. Sales onboarding" /></NFormItem>
          <NFormItem label="Description (optional)"><NInput v-model:value="form.description" /></NFormItem>
          <NSpace class="flags">
            <NCheckbox v-model:checked="form.is_default">Use by default</NCheckbox>
            <NCheckbox v-model:checked="form.is_active">In use</NCheckbox>
          </NSpace>

          <h3>Tasks</h3>
          <div v-for="(task, i) in form.tasks" :key="i" class="task">
            <div class="task-main">
              <NInput v-model:value="task.title" placeholder="What needs doing" />
              <div class="task-row">
                <NSelect v-model:value="task.assignee_type" :options="assigneeOptions" size="small" style="width: 190px" @update:value="(v: TemplateAssignee) => v !== 'role' && v !== 'user' && ((task.assignee_role_id = null), (task.assignee_user_id = null))" />
                <NSelect v-if="task.assignee_type === 'role'" v-model:value="task.assignee_role_id" :options="roles.map((r) => ({ label: r.name, value: r.id }))" size="small" placeholder="Which role" style="width: 170px" />
                <NSelect v-if="task.assignee_type === 'user'" v-model:value="task.assignee_user_id" :options="people.map((p) => ({ label: p.name, value: p.id }))" filterable size="small" placeholder="Who" style="width: 170px" />
                <NInputNumber v-model:value="task.due_offset_days" size="small" :min="-365" :max="365" style="width: 90px" />
                <span class="muted small">{{ offsetLabel(task.due_offset_days, form.type) }}</span>
                <NCheckbox v-model:checked="task.is_required" size="small">Required</NCheckbox>
              </div>
            </div>
            <div class="task-tools">
              <NButton size="tiny" quaternary circle :disabled="i === 0" aria-label="Move up" @click="move(i, -1)"><template #icon><NIcon><ArrowUpOutline /></NIcon></template></NButton>
              <NButton size="tiny" quaternary circle :disabled="i === form.tasks.length - 1" aria-label="Move down" @click="move(i, 1)"><template #icon><NIcon><ArrowDownOutline /></NIcon></template></NButton>
              <NButton size="tiny" quaternary circle type="error" :disabled="form.tasks.length === 1" aria-label="Remove task" @click="form.tasks.splice(i, 1)"><template #icon><NIcon><TrashOutline /></NIcon></template></NButton>
            </div>
          </div>
          <NButton dashed block class="add-task" @click="form.tasks.push(blankTask())">
            <template #icon><NIcon><AddOutline /></NIcon></template>
            Add a task
          </NButton>
        </NForm>
        <template #footer>
          <NSpace>
            <NButton @click="open = false">Cancel</NButton>
            <NButton type="primary" :loading="saving" :disabled="invalid" @click="save">Save template</NButton>
          </NSpace>
        </template>
      </NDrawerContent>
    </NDrawer>
  </div>
</template>

<style scoped>
.subtitle {
  margin: 6px 0 0;
}

.group + .group {
  margin-top: 28px;
}

.group-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
}

.group-head h2 {
  margin: 0;
  font-size: 1.1rem;
}

.template {
  cursor: pointer;
}

.template + .template {
  margin-top: 10px;
}

.template-head {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.off {
  opacity: 0.5;
}

.del {
  margin-left: auto;
}

.desc,
.preview {
  margin: 6px 0 0;
  font-size: 0.9rem;
}

.flags {
  margin-bottom: 18px;
}

h3 {
  margin: 0 0 8px;
  font-size: 1rem;
}

.task {
  display: flex;
  gap: 8px;
  padding: 10px 0;
  border-top: 1px solid rgba(127, 127, 127, 0.18);
}

.task-main {
  flex: 1;
  min-width: 0;
}

.task-row {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
  margin-top: 8px;
}

.task-tools {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.small {
  font-size: 0.85rem;
}

.add-task {
  margin-top: 12px;
}
</style>
