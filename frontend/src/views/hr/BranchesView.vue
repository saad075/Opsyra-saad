<script setup lang="ts">
import { AddOutline } from '@vicons/ionicons5'
import {
  NAlert,
  NButton,
  NDataTable,
  NEmpty,
  NForm,
  NFormItem,
  NIcon,
  NInput,
  NInputNumber,
  NModal,
  NSelect,
  NSpace,
  NSwitch,
  NTag,
  useDialog,
  useMessage,
  type DataTableColumns,
} from 'naive-ui'
import { h, onMounted, reactive, ref } from 'vue'
import { api, errorMessage, fieldErrors } from '@/api/client'
import { currentPosition } from '@/lib/attendance'
import type { ApiEnvelope, Branch } from '@/api/types'

const message = useMessage()
const dialog = useDialog()

const branches = ref<Branch[]>([])
const loading = ref(true)
const loadError = ref('')
const showInactive = ref(false)

const editing = ref<Branch | null>(null)
const modalOpen = ref(false)
const saving = ref(false)
const errors = ref<Record<string, string>>({})
const form = reactive({
  name: '',
  address: '',
  city: '',
  phone: '',
  timezone: null as string | null,
  status: true,
  latitude: null as number | null,
  longitude: null as number | null,
  geofence_radius_m: 200 as number | null,
})

// Where the branch is, for checking app clock-ins (far away is flagged, never refused).
const locating = ref(false)
async function useMyLocation() {
  locating.value = true
  const where = await currentPosition(10000)
  locating.value = false
  if (!where) return message.warning('Your browser didn’t share its location. Type the coordinates instead.')
  form.latitude = Number(where.latitude.toFixed(6))
  form.longitude = Number(where.longitude.toFixed(6))
  message.success(`Location set (accurate to about ${where.accuracy_m} m).`)
}

const timezones = Intl.supportedValuesOf('timeZone').map((tz) => ({ label: tz.replace(/_/g, ' '), value: tz }))

async function load() {
  loading.value = true
  try {
    const { data } = await api.get<ApiEnvelope<Branch[]>>('/branches/list', { params: { include_inactive: showInactive.value ? 1 : 0 } })
    branches.value = data.data
    loadError.value = ''
  } catch (e) {
    loadError.value = errorMessage(e)
  } finally {
    loading.value = false
  }
}

function open(branch: Branch | null) {
  editing.value = branch
  errors.value = {}
  Object.assign(form, {
    name: branch?.name ?? '',
    address: branch?.address ?? '',
    city: branch?.city ?? '',
    phone: branch?.phone ?? '',
    timezone: branch?.timezone ?? null,
    latitude: branch?.latitude ?? null,
    longitude: branch?.longitude ?? null,
    geofence_radius_m: branch?.geofence_radius_m ?? 200,
    status: branch?.status ?? true,
  })
  modalOpen.value = true
}

async function save() {
  saving.value = true
  errors.value = {}
  try {
    if (editing.value) await api.post('/branches/update', { id: editing.value.id, ...form })
    else await api.post('/branches/create', form)
    message.success(editing.value ? 'Branch updated.' : 'Branch added.')
    modalOpen.value = false
    await load()
  } catch (e) {
    errors.value = fieldErrors(e)
    if (!Object.keys(errors.value).length) message.error(errorMessage(e))
  } finally {
    saving.value = false
  }
}

function remove(branch: Branch) {
  dialog.warning({
    title: `Delete ${branch.name}?`,
    content: 'This can’t be undone. A branch with staff can only be deactivated.',
    positiveText: 'Delete',
    negativeText: 'Cancel',
    onPositiveClick: async () => {
      try {
        await api.post('/branches/delete', { id: branch.id })
        message.success('Branch deleted.')
        await load()
      } catch (e) {
        message.error(errorMessage(e))
      }
    },
  })
}

const columns: DataTableColumns<Branch> = [
  { title: 'Name', key: 'name', render: (b) => h('strong', b.name) },
  { title: 'City', key: 'city', render: (b) => b.city ?? '—' },
  { title: 'Timezone', key: 'timezone', render: (b) => b.timezone?.replace(/_/g, ' ') ?? 'Company default' },
  { title: 'Staff', key: 'staff_count', align: 'right' },
  {
    title: 'Status',
    key: 'status',
    render: (b) => h(NTag, { size: 'small', round: true, type: b.status ? 'success' : 'default' }, { default: () => (b.status ? 'Active' : 'Inactive') }),
  },
  {
    title: '',
    key: 'actions',
    align: 'right',
    render: (b) =>
      h(NSpace, { justify: 'end', size: 4 }, {
        default: () => [
          h(NButton, { size: 'small', onClick: () => open(b) }, { default: () => 'Edit' }),
          b.staff_count === 0 ? h(NButton, { size: 'small', quaternary: true, type: 'error', onClick: () => remove(b) }, { default: () => 'Delete' }) : null,
        ],
      }),
  },
]

onMounted(load)
</script>

<template>
  <div class="page">
    <div class="page-header">
      <div>
        <h1>Branches</h1>
        <p class="muted subtitle">Your offices or sites. Every staff member belongs to at least one.</p>
      </div>
      <NSpace align="center">
        <NSwitch v-model:value="showInactive" @update:value="load">
          <template #checked>Showing inactive</template>
          <template #unchecked>Active only</template>
        </NSwitch>
        <NButton type="primary" @click="open(null)">
          <template #icon><NIcon><AddOutline /></NIcon></template>
          Add branch
        </NButton>
      </NSpace>
    </div>

    <NAlert v-if="loadError" type="error" :show-icon="false">{{ loadError }}</NAlert>

    <NDataTable v-else :columns="columns" :data="branches" :loading="loading" :bordered="false" :row-key="(b: Branch) => b.id">
      <template #empty>
        <NEmpty description="No branches yet. Add your first office or site." />
      </template>
    </NDataTable>

    <NModal v-model:show="modalOpen" preset="card" :title="editing ? `Edit ${editing.name}` : 'Add branch'" style="max-width: 520px" :mask-closable="!saving">
      <NForm @submit.prevent="save">
        <NFormItem label="Name" :feedback="errors.name" :validation-status="errors.name ? 'error' : undefined">
          <NInput v-model:value="form.name" placeholder="e.g. Head office" />
        </NFormItem>
        <NFormItem label="Address"><NInput v-model:value="form.address" /></NFormItem>
        <NFormItem label="City"><NInput v-model:value="form.city" /></NFormItem>
        <NFormItem label="Phone"><NInput v-model:value="form.phone" /></NFormItem>
        <NFormItem label="Timezone" feedback="Leave empty to use the company's timezone.">
          <NSelect v-model:value="form.timezone" :options="timezones" filterable clearable />
        </NFormItem>
        <NFormItem label="Location (for app clock-ins)" feedback="Clock-ins further away than the radius are flagged for HR — never refused. Leave empty to skip the check.">
          <NSpace align="center">
            <NInputNumber v-model:value="form.latitude" :precision="6" :show-button="false" placeholder="Latitude" style="width: 130px" />
            <NInputNumber v-model:value="form.longitude" :precision="6" :show-button="false" placeholder="Longitude" style="width: 130px" />
            <NInputNumber v-model:value="form.geofence_radius_m" :min="25" :max="5000" :show-button="false" style="width: 110px"><template #suffix>m</template></NInputNumber>
            <NButton size="small" :loading="locating" @click="useMyLocation">Use where I am now</NButton>
          </NSpace>
        </NFormItem>
        <NFormItem v-if="editing" label="Active"><NSwitch v-model:value="form.status" /></NFormItem>
        <div class="actions">
          <NButton :disabled="saving" @click="modalOpen = false">Cancel</NButton>
          <NButton type="primary" attr-type="submit" :loading="saving">{{ editing ? 'Save' : 'Add branch' }}</NButton>
        </div>
      </NForm>
    </NModal>
  </div>
</template>

<style scoped>
.subtitle {
  margin: 6px 0 0;
}

.actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
}
</style>
