<script setup lang="ts">
import { AddOutline } from '@vicons/ionicons5'
import { NAlert, NButton, NCheckbox, NDataTable, NEmpty, NForm, NFormItem, NIcon, NInput, NModal, NSpace, NTag, useDialog, useMessage, type DataTableColumns } from 'naive-ui'
import { computed, h, onMounted, reactive, ref } from 'vue'
import { api, errorMessage, fieldErrors } from '@/api/client'
import type { ApiEnvelope, PermissionInfo, RoleWithPermissions } from '@/api/types'
import { useAuthStore } from '@/stores/auth'

/**
 * Job roles and what each may do. Permissions are shown grouped, in plain
 * words (the catalogue's descriptions), never as their internal names.
 */
const message = useMessage()
const dialog = useDialog()
const auth = useAuthStore()

const roles = ref<RoleWithPermissions[]>([])
const catalogue = ref<PermissionInfo[]>([])
const loading = ref(true)
const loadError = ref('')

async function load() {
  try {
    const [r, p] = await Promise.all([api.get<ApiEnvelope<RoleWithPermissions[]>>('/roles/list'), api.get<ApiEnvelope<PermissionInfo[]>>('/roles/permissions')])
    roles.value = r.data.data
    catalogue.value = p.data.data
    loadError.value = ''
  } catch (e) {
    loadError.value = errorMessage(e)
  } finally {
    loading.value = false
  }
}

const grouped = computed(() => {
  const map = new Map<string, PermissionInfo[]>()
  for (const p of catalogue.value) map.set(p.group, [...(map.get(p.group) ?? []), p])
  return [...map.entries()]
})
const describe = (name: string) => catalogue.value.find((p) => p.name === name)?.description ?? name

const editing = ref<RoleWithPermissions | null>(null)
const modalOpen = ref(false)
const saving = ref(false)
const errors = ref<Record<string, string>>({})
const form = reactive({ name: '', permissions: [] as string[] })

function open(role: RoleWithPermissions | null) {
  editing.value = role
  errors.value = {}
  Object.assign(form, { name: role?.name ?? '', permissions: role?.permissions.map((p) => p.name) ?? [] })
  modalOpen.value = true
}

function togglePermission(name: string, on: boolean) {
  form.permissions = on ? [...form.permissions, name] : form.permissions.filter((p) => p !== name)
}

async function save() {
  saving.value = true
  errors.value = {}
  try {
    if (editing.value) await api.post('/roles/update', { id: editing.value.id, ...form })
    else await api.post('/roles/create', form)
    message.success(editing.value ? 'Role saved.' : 'Role added.')
    modalOpen.value = false
    await load()
    // Your own role may have changed what you can see.
    if (editing.value && auth.me?.roles.includes(editing.value.name)) await auth.fetchMe()
  } catch (e) {
    errors.value = fieldErrors(e)
    if (!Object.keys(errors.value).length) message.error(errorMessage(e))
  } finally {
    saving.value = false
  }
}

function remove(role: RoleWithPermissions) {
  dialog.warning({
    title: `Delete the ${role.name} role?`,
    content: 'This can’t be undone.',
    positiveText: 'Delete',
    negativeText: 'Cancel',
    onPositiveClick: async () => {
      try {
        await api.post('/roles/delete', { id: role.id })
        message.success('Role deleted.')
        await load()
      } catch (e) {
        message.error(errorMessage(e))
      }
    },
  })
}

const columns: DataTableColumns<RoleWithPermissions> = [
  {
    title: 'Role',
    key: 'name',
    render: (r) => h('span', [h('strong', r.name), r.is_locked ? h(NTag, { size: 'small', round: true, class: 'lock' }, { default: () => 'Locked' }) : null]),
  },
  {
    title: 'Can',
    key: 'permissions',
    render: (r) =>
      r.is_locked
        ? h('span', { class: 'can' }, 'Everything, including anything added to Opsyra later. The account owner always has this role.')
        : r.permissions.length
        ? h('span', { class: 'can' }, r.permissions.map((p) => describe(p.name)).join(' · '))
        : h('span', { class: 'muted' }, 'Nothing beyond everyday use'),
  },
  { title: 'Staff', key: 'staff_count', align: 'right', width: 70 },
  {
    title: '',
    key: 'actions',
    align: 'right',
    width: 150,
    render: (r) =>
      r.is_locked
        ? null
        : h(NSpace, { justify: 'end', size: 4 }, {
        default: () => [
          h(NButton, { size: 'small', onClick: () => open(r) }, { default: () => 'Edit' }),
          r.staff_count === 0 ? h(NButton, { size: 'small', quaternary: true, type: 'error', onClick: () => remove(r) }, { default: () => 'Delete' }) : null,
        ],
      }),
  },
]

onMounted(load)
</script>

<template>
  <div class="page wide">
    <div class="page-header">
      <div>
        <h1>Roles</h1>
        <p class="muted subtitle">A person’s role decides what they can do in Opsyra.</p>
      </div>
      <NButton type="primary" @click="open(null)">
        <template #icon><NIcon><AddOutline /></NIcon></template>
        Add role
      </NButton>
    </div>

    <NAlert v-if="loadError" type="error" :show-icon="false">{{ loadError }}</NAlert>
    <NDataTable v-else :columns="columns" :data="roles" :loading="loading" :bordered="false" :row-key="(r: RoleWithPermissions) => r.id">
      <template #empty><NEmpty description="No roles yet." /></template>
    </NDataTable>

    <NModal v-model:show="modalOpen" preset="card" :title="editing ? `Edit ${editing.name}` : 'Add a role'" style="max-width: 560px">
      <NForm @submit.prevent="save">
        <NFormItem label="Name" :feedback="errors.name" :validation-status="errors.name ? 'error' : undefined">
          <NInput v-model:value="form.name" placeholder="e.g. Branch Manager" />
        </NFormItem>
        <p class="muted small">Everyone can see their own tasks, payslips and profile. Tick what else this role may do:</p>
        <div v-for="[group, perms] in grouped" :key="group" class="perm-group">
          <NTag size="small" round>{{ group }}</NTag>
          <NCheckbox v-for="p in perms" :key="p.name" :checked="form.permissions.includes(p.name)" class="perm" @update:checked="(v: boolean) => togglePermission(p.name, v)">
            {{ p.description }}
          </NCheckbox>
        </div>
        <NSpace justify="end" class="actions">
          <NButton @click="modalOpen = false">Cancel</NButton>
          <NButton type="primary" attr-type="submit" :loading="saving" :disabled="!form.name.trim()">Save</NButton>
        </NSpace>
      </NForm>
    </NModal>
  </div>
</template>

<style scoped>
.wide {
  max-width: 1180px;
}

.subtitle {
  margin: 6px 0 0;
}

.small {
  font-size: 0.9rem;
  margin: 0 0 12px;
}

.perm-group {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 6px;
  margin-bottom: 14px;
}

.perm {
  margin-left: 4px;
}

.actions {
  margin-top: 8px;
}

:deep(.can) {
  font-size: 0.9rem;
}

:deep(.lock) {
  margin-left: 8px;
}
</style>
