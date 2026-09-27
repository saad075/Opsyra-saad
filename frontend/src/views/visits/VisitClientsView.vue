<script setup lang="ts">
import { AddOutline } from '@vicons/ionicons5'
import { NAlert, NButton, NDataTable, NEmpty, NForm, NFormItem, NGrid, NGridItem, NIcon, NInput, NInputNumber, NModal, NSelect, NSpace, NSwitch, NTag, useDialog, useMessage, type DataTableColumns } from 'naive-ui'
import { h, onMounted, reactive, ref, watch } from 'vue'
import { api, errorMessage, fieldErrors } from '@/api/client'
import type { ApiEnvelope, Branch } from '@/api/types'
import type { VisitLocationRow } from '@/api/visitTypes'
import OMap from '@/components/OMap.vue'
import { currentPosition } from '@/lib/attendance'

/**
 * The clients staff visit at home. Kept minimal on purpose: a name, an
 * address and a point on the map — nothing about their care. The point is
 * what check-ins are compared with; place it by clicking the map, typing
 * coordinates, or "use where I am now" when you're at the client's door.
 */
const message = useMessage()
const dialog = useDialog()

const clients = ref<VisitLocationRow[]>([])
const branches = ref<Branch[]>([])
const loading = ref(true)
const error = ref('')
const search = ref('')
const showInactive = ref(false)

async function load() {
  try {
    const { data } = await api.get<ApiEnvelope<VisitLocationRow[]>>('/visits/locations/list', { params: { search: search.value || undefined, include_inactive: showInactive.value ? 1 : 0 } })
    clients.value = data.data
    error.value = ''
  } catch (e) {
    error.value = errorMessage(e)
  } finally {
    loading.value = false
  }
}

const editing = ref<VisitLocationRow | null>(null)
const modal = ref(false)
const saving = ref(false)
const locating = ref(false)
const errors = ref<Record<string, string>>({})
const form = reactive({
  name: '',
  reference: '',
  address: '',
  city: '',
  postcode: '',
  latitude: null as number | null,
  longitude: null as number | null,
  geofence_radius_m: 150,
  access_notes: '',
  branch_id: null as number | null,
  is_active: true,
})

function open(c: VisitLocationRow | null) {
  editing.value = c
  errors.value = {}
  Object.assign(form, {
    name: c?.name ?? '',
    reference: c?.reference ?? '',
    address: c?.address ?? '',
    city: c?.city ?? '',
    postcode: c?.postcode ?? '',
    latitude: c?.latitude ?? null,
    longitude: c?.longitude ?? null,
    geofence_radius_m: c?.geofence_radius_m ?? 150,
    access_notes: c?.access_notes ?? '',
    branch_id: c?.branch_id ?? null,
    is_active: c?.is_active ?? true,
  })
  modal.value = true
}

async function useMyLocation() {
  locating.value = true
  const where = await currentPosition(10000)
  locating.value = false
  if (!where) return message.warning('Your browser didn’t share its location. Click the map instead.')
  form.latitude = Number(where.latitude.toFixed(6))
  form.longitude = Number(where.longitude.toFixed(6))
}

async function save() {
  saving.value = true
  errors.value = {}
  const body = { ...form, reference: form.reference || null, city: form.city || null, postcode: form.postcode || null, access_notes: form.access_notes || null }
  try {
    await api.post(`/visits/locations/${editing.value ? 'update' : 'create'}`, editing.value ? { id: editing.value.id, ...body } : body)
    message.success(editing.value ? 'Saved.' : 'Client added.')
    modal.value = false
    await load()
  } catch (e) {
    errors.value = fieldErrors(e)
    if (!Object.keys(errors.value).length) message.error(errorMessage(e))
  } finally {
    saving.value = false
  }
}

function remove(c: VisitLocationRow) {
  dialog.warning({
    title: `Delete ${c.name}?`,
    content: 'A client with visits can only be switched off — their past visits keep them.',
    positiveText: 'Delete',
    negativeText: 'Cancel',
    onPositiveClick: async () => {
      try {
        await api.post('/visits/locations/delete', { id: c.id })
        message.success('Deleted.')
        await load()
      } catch (e) {
        message.error(errorMessage(e))
      }
    },
  })
}

const columns: DataTableColumns<VisitLocationRow> = [
  {
    title: 'Client',
    key: 'name',
    render: (c) => h('div', [h('strong', { style: c.is_active ? '' : 'opacity:.5' }, c.name + (c.reference ? ` · ${c.reference}` : '')), h('small', [c.address, c.city, c.postcode].filter(Boolean).join(', '))]),
  },
  {
    title: 'Map point',
    key: 'point',
    render: (c) =>
      c.latitude !== null
        ? h('span', `Within ${c.geofence_radius_m} m`)
        : h(NTag, { size: 'small', round: true, type: 'warning' }, { default: () => 'Not set — check-ins can’t be verified' }),
  },
  { title: 'Branch', key: 'branch', render: (c) => c.branch?.name ?? '—' },
  { title: 'Upcoming visits', key: 'upcoming_visits_count', align: 'right' },
  {
    title: '',
    key: 'actions',
    align: 'right',
    render: (c) =>
      h(NSpace, { justify: 'end', size: 4 }, {
        default: () => [
          h(NButton, { size: 'small', onClick: () => open(c) }, { default: () => 'Edit' }),
          h(NButton, { size: 'small', quaternary: true, type: 'error', onClick: () => remove(c) }, { default: () => 'Delete' }),
        ],
      }),
  },
]

let timer: ReturnType<typeof setTimeout> | undefined
watch(search, () => {
  clearTimeout(timer)
  timer = setTimeout(load, 300)
})
watch(showInactive, load)

onMounted(async () => {
  await load()
  branches.value = (await api.get<ApiEnvelope<Branch[]>>('/branches/list').catch(() => ({ data: { data: [] } }))).data.data
})
</script>

<template>
  <div class="page wide">
    <div class="page-header">
      <div>
        <h1>Clients</h1>
        <p class="muted subtitle">The homes your staff visit. The map point is what each check-in is compared with.</p>
      </div>
      <NSpace align="center">
        <NInput v-model:value="search" placeholder="Find a client" clearable style="width: 200px" />
        <NSwitch v-model:value="showInactive"><template #checked>Showing past clients</template><template #unchecked>Current only</template></NSwitch>
        <NButton type="primary" @click="open(null)">
          <template #icon><NIcon><AddOutline /></NIcon></template>
          Add client
        </NButton>
      </NSpace>
    </div>

    <NAlert v-if="error" type="error" :show-icon="false">{{ error }}</NAlert>
    <NDataTable v-else :columns="columns" :data="clients" :loading="loading" :bordered="false" :row-key="(c: VisitLocationRow) => c.id">
      <template #empty><NEmpty description="No clients yet." /></template>
    </NDataTable>

    <NModal v-model:show="modal" preset="card" :title="editing ? `Edit ${editing.name}` : 'Add a client'" style="max-width: 760px">
      <NForm @submit.prevent="save">
        <NGrid :cols="2" :x-gap="12">
          <NGridItem>
            <NFormItem label="Name" :feedback="errors.name" :validation-status="errors.name ? 'error' : undefined"><NInput v-model:value="form.name" placeholder="e.g. Mrs A. Patel" /></NFormItem>
          </NGridItem>
          <NGridItem>
            <NFormItem label="Your reference (optional)"><NInput v-model:value="form.reference" placeholder="e.g. CL-0042" /></NFormItem>
          </NGridItem>
          <NGridItem :span="2">
            <NFormItem label="Address" :feedback="errors.address" :validation-status="errors.address ? 'error' : undefined"><NInput v-model:value="form.address" /></NFormItem>
          </NGridItem>
          <NGridItem><NFormItem label="City"><NInput v-model:value="form.city" /></NFormItem></NGridItem>
          <NGridItem><NFormItem label="Postcode"><NInput v-model:value="form.postcode" /></NFormItem></NGridItem>
        </NGrid>

        <NFormItem label="Map point — click the map on the front door" :feedback="errors.latitude">
          <div class="map-field">
            <OMap :place="form.latitude !== null && form.longitude !== null ? { latitude: form.latitude, longitude: form.longitude } : null" :radius="form.geofence_radius_m" editable :height="240" @pick="(p) => Object.assign(form, p)" />
            <NSpace align="center" class="coords">
              <NInputNumber v-model:value="form.latitude" :precision="6" :show-button="false" placeholder="Latitude" style="width: 130px" />
              <NInputNumber v-model:value="form.longitude" :precision="6" :show-button="false" placeholder="Longitude" style="width: 130px" />
              <NInputNumber v-model:value="form.geofence_radius_m" :min="25" :max="5000" :show-button="false" style="width: 130px"><template #prefix>Within</template><template #suffix>m</template></NInputNumber>
              <NButton size="small" :loading="locating" @click="useMyLocation">Use where I am now</NButton>
            </NSpace>
          </div>
        </NFormItem>

        <NGrid :cols="2" :x-gap="12">
          <NGridItem><NFormItem label="Access notes (optional)"><NInput v-model:value="form.access_notes" placeholder="e.g. Ring twice, side door" /></NFormItem></NGridItem>
          <NGridItem>
            <NFormItem label="Served by (optional)">
              <NSelect v-model:value="form.branch_id" :options="branches.map((b) => ({ label: b.name, value: b.id }))" clearable />
            </NFormItem>
          </NGridItem>
        </NGrid>
        <NFormItem v-if="editing" label="Current client" label-placement="left"><NSwitch v-model:value="form.is_active" /></NFormItem>

        <NSpace justify="end">
          <NButton @click="modal = false">Cancel</NButton>
          <NButton type="primary" attr-type="submit" :loading="saving" :disabled="!form.name.trim() || !form.address.trim()">Save</NButton>
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

.map-field {
  width: 100%;
}

.coords {
  margin-top: 8px;
}

:deep(small) {
  display: block;
  opacity: 0.6;
}
</style>
