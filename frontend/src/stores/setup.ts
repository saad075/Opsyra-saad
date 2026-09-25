import { defineStore } from 'pinia'
import { ref } from 'vue'
import { api } from '@/api/client'
import type { ApiEnvelope, SetupStatus } from '@/api/types'

/** The Setup wizard's progress, shared by the wizard page and the home banner. */
export const useSetupStore = defineStore('setup', () => {
  const status = ref<SetupStatus | null>(null)

  async function load(): Promise<void> {
    const { data } = await api.get<ApiEnvelope<SetupStatus>>('/setup-wizard/status')
    status.value = data.data
  }

  async function act(action: 'skip' | 'mark-reviewed' | 'reset', key: string): Promise<void> {
    const { data } = await api.post<ApiEnvelope<SetupStatus>>(`/setup-wizard/${action}`, { key })
    status.value = data.data
  }

  return { status, load, act }
})
