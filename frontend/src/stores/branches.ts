import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { api } from '@/api/client'
import type { ApiEnvelope } from '@/api/types'

export interface MyBranch {
  id: number
  name: string
  selected: boolean
}

/**
 * The branch switcher: staff lists show the branches the viewer has
 * selected (SLMS360's rule). `version` bumps after a change so screens
 * showing branch-scoped data know to reload.
 */
export const useBranchesStore = defineStore('branches', () => {
  const mine = ref<MyBranch[]>([])
  const version = ref(0)

  const selected = computed(() => mine.value.filter((b) => b.selected))
  const label = computed(() => {
    if (mine.value.length === 0) return 'No branches'
    if (selected.value.length === mine.value.length) return mine.value.length === 1 ? mine.value[0].name : 'All my branches'
    return selected.value.map((b) => b.name).join(', ')
  })

  async function load(): Promise<void> {
    const { data } = await api.get<ApiEnvelope<MyBranch[]>>('/my-branches')
    mine.value = data.data
  }

  async function select(ids: number[]): Promise<void> {
    const { data } = await api.post<ApiEnvelope<MyBranch[]>>('/my-branches/select', { branch_ids: ids })
    mine.value = data.data
    version.value++
  }

  return { mine, selected, label, version, load, select }
})
