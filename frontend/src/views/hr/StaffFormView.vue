<script setup lang="ts">
import { NAlert, NButton, NCard, NDatePicker, NForm, NFormItem, NGrid, NGridItem, NInput, NSelect, NSkeleton, NSwitch, useMessage } from 'naive-ui'
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api, errorMessage, fieldErrors } from '@/api/client'
import type { ApiEnvelope, Branch, Named, PersonRef, StaffProfile } from '@/api/types'
import { toDateInput } from '@/lib/staff'
import { useAuthStore } from '@/stores/auth'

/** Add a new staff member, or edit an existing one (/hr/staff/:id/edit). */
const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const message = useMessage()

const editingId = computed(() => (route.params.id ? Number(route.params.id) : null))
const isEdit = computed(() => editingId.value !== null)
const canSetRole = computed(() => auth.can('HRMS-StaffPermissionUpdate'))
const willAutoApprove = computed(() => auth.can('HRMS-Approval-L2'))

const loaded = ref(false)
const loadError = ref('')
const saving = ref(false)
const errors = ref<Record<string, string>>({})

const designations = ref<{ label: string; value: string }[]>([])
const branches = ref<Branch[]>([])
const managers = ref<PersonRef[]>([])
const roles = ref<Named[]>([])
const templates = ref<Named[]>([])
const currentRoleId = ref<number | null>(null)

const form = reactive({
  name: '',
  email: '',
  phone: '',
  designation: null as string | null,
  joined_at: null as string | null,
  dob: null as string | null,
  is_remote: false,
  branches: [] as number[],
  hierarchy_parent_id: null as number | null,
  role_id: null as number | null,
  checklist_template_id: null as number | null,
  national_id: '',
  address: '',
  city: '',
  zip_code: '',
})

onMounted(async () => {
  try {
    const [d, b, m] = await Promise.all([
      api.get<ApiEnvelope<{ name: string; department?: { name: string } | null }[]>>('/designations-management/list'),
      api.get<ApiEnvelope<Branch[]>>('/branches/list'),
      api.get<ApiEnvelope<PersonRef[]>>('/staff/eligible-managers', { params: editingId.value ? { exclude_id: editingId.value } : {} }),
    ])
    designations.value = d.data.data.map((x) => ({ label: x.department ? `${x.name} (${x.department.name})` : x.name, value: x.name }))
    branches.value = b.data.data
    managers.value = m.data.data

    // Optional lists — hidden if the user can't read them.
    if (canSetRole.value) roles.value = (await api.get<ApiEnvelope<Named[]>>('/roles/list').catch(() => ({ data: { data: [] } }))).data.data
    if (!isEdit.value) {
      templates.value = (
        await api.get<ApiEnvelope<Named[]>>('/checklist-templates/list', { params: { type: 'onboarding' } }).catch(() => ({ data: { data: [] } }))
      ).data.data
    }

    if (isEdit.value) {
      const { data } = await api.post<ApiEnvelope<StaffProfile>>('/staff/show', { id: editingId.value })
      const s = data.data.staff
      Object.assign(form, {
        name: s.name,
        email: s.email,
        phone: s.phone ?? '',
        designation: s.designation,
        joined_at: toDateInput(s.joined_at),
        dob: toDateInput(s.dob),
        is_remote: s.is_remote,
        branches: s.branches.map((x) => x.branch_id),
        hierarchy_parent_id: s.hierarchy_parent_id,
        national_id: s.national_id ?? '',
        address: s.address ?? '',
        city: s.city ?? '',
        zip_code: s.zip_code ?? '',
      })
      currentRoleId.value = s.roles[0]?.id ?? null
      form.role_id = currentRoleId.value
    } else if (branches.value.length === 1) {
      form.branches = [branches.value[0].id]
    }

    loaded.value = true
  } catch (e) {
    loadError.value = errorMessage(e)
  }
})

async function save() {
  saving.value = true
  errors.value = {}

  const payload: Record<string, unknown> = {
    ...form,
    national_id: form.national_id || null,
    address: form.address || null,
    city: form.city || null,
    zip_code: form.zip_code || null,
  }
  // Only send a role if the user may set one and actually changed it.
  if (!canSetRole.value || form.role_id === currentRoleId.value) delete payload.role_id
  if (isEdit.value) delete payload.checklist_template_id

  try {
    if (isEdit.value) {
      await api.post('/staff/update', { user_id: editingId.value, ...payload })
      message.success('Saved.')
      await router.push({ name: 'staff-profile', params: { id: editingId.value! } })
    } else {
      const { data } = await api.post<ApiEnvelope<{ id: number; requires_approval: boolean }> & { message: string }>('/staff/create', payload)
      message.success(data.message, { duration: 6000 })
      await router.push({ name: 'staff-profile', params: { id: data.data.id } })
    }
  } catch (e) {
    errors.value = fieldErrors(e)
    if (!Object.keys(errors.value).length) message.error(errorMessage(e))
  } finally {
    saving.value = false
  }
}

const err = (field: string) => ({ feedback: errors.value[field] ?? errors.value[`${field}.0`], validationStatus: errors.value[field] || errors.value[`${field}.0`] ? ('error' as const) : undefined })
</script>

<template>
  <div class="page narrow">
    <div class="page-header">
      <h1>{{ isEdit ? `Edit ${form.name || 'staff member'}` : 'Add staff' }}</h1>
    </div>

    <NAlert v-if="loadError" type="error" :show-icon="false">{{ loadError }}</NAlert>
    <NSkeleton v-else-if="!loaded" height="520px" :sharp="false" />

    <NCard v-else>
      <NAlert v-if="!isEdit" :type="willAutoApprove ? 'info' : 'warning'" :show-icon="false" class="gap">
        {{ willAutoApprove ? 'You can approve hires, so they’ll be added straight away.' : 'New staff wait for a level-2 approver before they become active.' }}
      </NAlert>

      <NForm @submit.prevent="save">
        <NGrid :cols="2" :x-gap="16" item-responsive responsive="screen">
          <NGridItem span="2 m:1">
            <NFormItem label="Full name" v-bind="err('name')"><NInput v-model:value="form.name" /></NFormItem>
          </NGridItem>
          <NGridItem span="2 m:1">
            <NFormItem label="Job title" v-bind="err('designation')">
              <NSelect v-model:value="form.designation" :options="designations" filterable tag placeholder="Pick or type a job title" />
            </NFormItem>
          </NGridItem>
          <NGridItem span="2 m:1">
            <NFormItem label="Work email" v-bind="err('email')"><NInput v-model:value="form.email" :input-props="{ type: 'email' }" /></NFormItem>
          </NGridItem>
          <NGridItem span="2 m:1">
            <NFormItem label="Phone" v-bind="err('phone')"><NInput v-model:value="form.phone" /></NFormItem>
          </NGridItem>
          <NGridItem span="2 m:1">
            <NFormItem label="Joining date" v-bind="err('joined_at')">
              <NDatePicker v-model:formatted-value="form.joined_at" value-format="yyyy-MM-dd" type="date" style="width: 100%" />
            </NFormItem>
          </NGridItem>
          <NGridItem span="2 m:1">
            <NFormItem label="Date of birth" v-bind="err('dob')">
              <NDatePicker v-model:formatted-value="form.dob" value-format="yyyy-MM-dd" type="date" style="width: 100%" />
            </NFormItem>
          </NGridItem>
          <NGridItem span="2 m:1">
            <NFormItem label="Branches" v-bind="err('branches')">
              <NSelect v-model:value="form.branches" multiple :options="branches.map((b) => ({ label: b.name, value: b.id }))" />
            </NFormItem>
          </NGridItem>
          <NGridItem span="2 m:1">
            <NFormItem label="Reports to" v-bind="err('hierarchy_parent_id')">
              <NSelect
                v-model:value="form.hierarchy_parent_id"
                clearable
                filterable
                :options="managers.map((m) => ({ label: m.designation ? `${m.name} — ${m.designation}` : m.name, value: m.id }))"
              />
            </NFormItem>
          </NGridItem>
          <NGridItem v-if="canSetRole && roles.length" span="2 m:1">
            <NFormItem label="Role" v-bind="err('role_id')">
              <NSelect v-model:value="form.role_id" clearable :options="roles.map((r) => ({ label: r.name, value: r.id }))" />
            </NFormItem>
          </NGridItem>
          <NGridItem v-if="!isEdit && templates.length" span="2 m:1">
            <NFormItem label="Onboarding checklist" feedback="Leave empty to use your default.">
              <NSelect v-model:value="form.checklist_template_id" clearable :options="templates.map((t) => ({ label: t.name, value: t.id }))" />
            </NFormItem>
          </NGridItem>
          <NGridItem span="2 m:1">
            <NFormItem label="Works remotely"><NSwitch v-model:value="form.is_remote" /></NFormItem>
          </NGridItem>
          <NGridItem span="2 m:1">
            <NFormItem label="National ID (optional)"><NInput v-model:value="form.national_id" /></NFormItem>
          </NGridItem>
          <NGridItem span="2">
            <NFormItem label="Address (optional)"><NInput v-model:value="form.address" /></NFormItem>
          </NGridItem>
          <NGridItem span="2 m:1">
            <NFormItem label="City (optional)"><NInput v-model:value="form.city" /></NFormItem>
          </NGridItem>
          <NGridItem span="2 m:1">
            <NFormItem label="Postcode (optional)"><NInput v-model:value="form.zip_code" /></NFormItem>
          </NGridItem>
        </NGrid>

        <div class="actions">
          <NButton :disabled="saving" @click="router.back()">Cancel</NButton>
          <NButton type="primary" attr-type="submit" :loading="saving">{{ isEdit ? 'Save' : 'Add staff member' }}</NButton>
        </div>
      </NForm>
    </NCard>
  </div>
</template>

<style scoped>
.narrow {
  max-width: 880px;
}

.gap {
  margin-bottom: 16px;
}

.actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
}
</style>
