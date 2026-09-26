<script setup lang="ts">
import { NAlert, NCard, NDataTable, NInput, NRadioButton, NRadioGroup, NTag, useMessage, type DataTableColumns } from 'naive-ui'
import { computed, h, onMounted, ref } from 'vue'
import { api, errorMessage } from '@/api/client'
import type { ApiEnvelope, ApproverRow } from '@/api/types'
import { useAuthStore } from '@/stores/auth'

/**
 * Who approves. Level 1 checks salaries; level 2 gives the final say on
 * hires, leavers and salaries. Given per person on top of their role; a
 * level that comes from a role is shown but changed on the Roles page.
 */
const message = useMessage()
const auth = useAuthStore()

const rows = ref<ApproverRow[]>([])
const loading = ref(true)
const loadError = ref('')
const search = ref('')
const saving = ref<number | null>(null)

async function load() {
  try {
    const { data } = await api.get<ApiEnvelope<ApproverRow[]>>('/approvers/list')
    rows.value = data.data
    loadError.value = ''
  } catch (e) {
    loadError.value = errorMessage(e)
  } finally {
    loading.value = false
  }
}

const shown = computed(() => {
  const q = search.value.trim().toLowerCase()
  const list = q ? rows.value.filter((r) => r.name.toLowerCase().includes(q) || (r.designation ?? '').toLowerCase().includes(q)) : rows.value
  // Approvers first, then everyone else alphabetically.
  return [...list].sort((a, b) => b.level - a.level || a.name.localeCompare(b.name))
})
const counts = computed(() => ({ l1: rows.value.filter((r) => r.level === 1).length, l2: rows.value.filter((r) => r.level === 2).length }))

async function setLevel(row: ApproverRow, level: 0 | 1 | 2) {
  saving.value = row.id
  try {
    const { data } = await api.post<ApiEnvelope<unknown>>('/approvers/set', { user_id: row.id, level })
    message.success(data.message)
    await load()
    if (row.id === auth.me?.user.id) await auth.fetchMe()
  } catch (e) {
    message.error(errorMessage(e))
  } finally {
    saving.value = null
  }
}

const columns: DataTableColumns<ApproverRow> = [
  { title: 'Person', key: 'name', render: (r) => h('div', [h('strong', r.name), h('small', [r.designation, r.roles.join(', ')].filter(Boolean).join(' · '))]) },
  {
    title: 'Approves',
    key: 'level',
    width: 330,
    render: (r) =>
      h(
        NRadioGroup,
        { size: 'small', value: r.level, disabled: saving.value === r.id, 'onUpdate:value': (v: 0 | 1 | 2) => setLevel(r, v) },
        {
          default: () => [
            h(NRadioButton, { value: 0, disabled: r.role_level > 0 }, { default: () => 'No' }),
            h(NRadioButton, { value: 1, disabled: r.role_level > 1 }, { default: () => 'Level 1' }),
            h(NRadioButton, { value: 2 }, { default: () => 'Level 2' }),
          ],
        },
      ),
  },
  { title: '', key: 'via', render: (r) => (r.role_level ? h(NTag, { size: 'small', round: true }, { default: () => `Level ${r.role_level} through their role` }) : null) },
]

onMounted(load)
</script>

<template>
  <div class="page wide">
    <div class="page-header">
      <div>
        <h1>Approvers</h1>
        <p class="muted subtitle">{{ counts.l2 }} at level 2 · {{ counts.l1 }} at level 1</p>
      </div>
      <NInput v-model:value="search" placeholder="Find someone" clearable style="width: 220px" />
    </div>

    <NCard class="explain" size="small">
      <p><strong>Level 1</strong> checks payslips first. <strong>Level 2</strong> gives the final approval for new hires, leavers and pay — at least one person needs it.</p>
      <p class="muted">Nobody can approve their own payslip, or approve the same payslip at both levels.</p>
    </NCard>

    <NAlert v-if="loadError" type="error" :show-icon="false">{{ loadError }}</NAlert>
    <NDataTable v-else :columns="columns" :data="shown" :loading="loading" :bordered="false" :row-key="(r: ApproverRow) => r.id" />
  </div>
</template>

<style scoped>
.wide {
  max-width: 1080px;
}

.subtitle {
  margin: 6px 0 0;
}

.explain {
  margin-bottom: 16px;
}

.explain p {
  margin: 0;
}

.explain p + p {
  margin-top: 4px;
}

:deep(small) {
  display: block;
  opacity: 0.6;
}
</style>
