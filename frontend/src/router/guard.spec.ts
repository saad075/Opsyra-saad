import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createMemoryHistory, createRouter, type RouteLocationNormalized } from 'vue-router'
import type { Me } from '@/api/types'
import { guard, routes } from '@/router'
import { useAuthStore } from '@/stores/auth'

function to(name: string, meta: Record<string, unknown> = {}, fullPath = `/${name}`): RouteLocationNormalized {
  return { name, meta, fullPath, path: fullPath, query: {}, params: {}, hash: '', matched: [], redirectedFrom: undefined } as unknown as RouteLocationNormalized
}

function signedIn(overrides: Partial<Me> = {}) {
  const auth = useAuthStore()
  auth.token = 'token'
  auth.me = {
    user: { id: 1, name: 'Ada Admin', email: 'ada@acme.test', designation: null, avatar: null, status: 'active' },
    must_change_password: false,
    company: { name: 'Acme', client_code: 'acme', country: 'gb' },
    roles: [],
    permissions: [],
    ...overrides,
  }
  return auth
}

describe('route table', () => {
  it('opens Home at "/" and every screen by its URL (not an empty layout)', () => {
    const r = createRouter({ history: createMemoryHistory(), routes })

    expect(r.resolve('/').name).toBe('dashboard')
    expect(r.resolve('/login').name).toBe('login')
    expect(r.resolve('/reset-password').name).toBe('reset-password')
    expect(r.resolve('/setup').name).toBe('setup')
    expect(r.resolve('/hr/branches').name).toBe('branches')
  })
})

describe('router guard', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    localStorage.clear()
  })

  it('sends signed-out visitors to login, remembering where they were going', async () => {
    expect(await guard(to('setup'))).toEqual({ name: 'login', query: { redirect: '/setup' } })
  })

  it('lets signed-out visitors open login but sends signed-in users home', async () => {
    expect(await guard(to('login', { guest: true }))).toBe(true)
    signedIn()
    expect(await guard(to('login', { guest: true }))).toEqual({ name: 'dashboard' })
  })

  it('always opens the reset-password link, signed in or not', async () => {
    expect(await guard(to('reset-password', { public: true }))).toBe(true)
  })

  it('holds a temporary-password user at the change-password screen', async () => {
    signedIn({ must_change_password: true })
    expect(await guard(to('dashboard'))).toEqual({ name: 'change-password' })
    expect(await guard(to('change-password'))).toBe(true)
  })

  it('hides permission-gated screens from users without the permission', async () => {
    signedIn({ permissions: [] })
    expect(await guard(to('setup', { permission: 'HRMS-CompanySettings' }))).toEqual({ name: 'dashboard' })

    signedIn({ permissions: ['HRMS-CompanySettings'] })
    expect(await guard(to('setup', { permission: 'HRMS-CompanySettings' }))).toBe(true)
  })

  it('signs out when the saved token no longer works', async () => {
    const auth = useAuthStore()
    auth.token = 'stale'
    auth.fetchMe = vi.fn().mockRejectedValue(new Error('401'))

    expect(await guard(to('dashboard'))).toEqual({ name: 'login' })
    expect(auth.token).toBeNull()
  })
})
