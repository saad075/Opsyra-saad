import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { api, readToken, writeToken } from '@/api/client'
import type { ApiEnvelope, Me } from '@/api/types'

const CLIENT_CODE_KEY = 'opsyra.clientCode'

/**
 * Who is signed in and what they may do. Permissions come from /auth/me and
 * only decide what the UI *shows* — every action is still authorised by
 * the API.
 */
export const useAuthStore = defineStore('auth', () => {
  const token = ref<string | null>(readToken())
  const me = ref<Me | null>(null)

  const isAuthenticated = computed(() => token.value !== null)
  const mustChangePassword = computed(() => me.value?.must_change_password ?? false)

  function can(permission: string): boolean {
    return me.value?.permissions.includes(permission) ?? false
  }

  function canAny(permissions: string | string[]): boolean {
    return (Array.isArray(permissions) ? permissions : [permissions]).some(can)
  }

  async function login(input: { client_code: string; email: string; password: string }): Promise<void> {
    const { data } = await api.post<ApiEnvelope<{ token: string; must_change_password: boolean }>>('/auth/login', {
      ...input,
      device_name: 'web',
    })
    setToken(data.data.token)
    rememberClientCode(input.client_code)
    await fetchMe()
  }

  async function fetchMe(): Promise<void> {
    const { data } = await api.get<ApiEnvelope<Me>>('/auth/me')
    me.value = data.data
  }

  async function logout(): Promise<void> {
    try {
      if (token.value) await api.post('/auth/logout')
    } finally {
      forget()
    }
  }

  async function changePassword(input: { current_password: string; password: string; password_confirmation: string }): Promise<void> {
    await api.post('/auth/change-password', input)
    await fetchMe()
  }

  /** Local sign-out, e.g. after the API says the token is no longer valid. */
  function forget(): void {
    setToken(null)
    me.value = null
  }

  function setToken(value: string | null): void {
    token.value = value
    writeToken(value)
  }

  return { token, me, isAuthenticated, mustChangePassword, can, canAny, login, fetchMe, logout, changePassword, forget }
})

/** The last client code used on this device, so staff type it once. */
export function rememberedClientCode(): string {
  try {
    return localStorage.getItem(CLIENT_CODE_KEY) ?? ''
  } catch {
    return ''
  }
}

function rememberClientCode(code: string): void {
  try {
    localStorage.setItem(CLIENT_CODE_KEY, code.trim().toLowerCase())
  } catch {
    // Not critical.
  }
}
