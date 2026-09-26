import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createMemoryHistory, createRouter, type RouteLocationNormalized } from 'vue-router'
import type { Me } from '@/api/types'
import { setupStepRoutes } from '@/lib/setupSteps'
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
    company: { name: 'Acme', client_code: 'acme', country: 'gb', is_demo: false },
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
    expect(r.resolve('/hr/staff').name).toBe('staff')
    expect(r.resolve('/hr/staff/new').name).toBe('staff-new')
    expect(r.resolve('/hr/staff/42').name).toBe('staff-profile')
    expect(r.resolve('/hr/staff/42/edit').name).toBe('staff-edit')
    expect(r.resolve('/hr/approvals').name).toBe('approvals')
    expect(r.resolve('/tasks').name).toBe('tasks')
    expect(r.resolve('/settings/departments').name).toBe('departments')
    expect(r.resolve('/settings/roles').name).toBe('roles')
    expect(r.resolve('/settings/approvers').name).toBe('approvers')
    expect(r.resolve('/settings/leaving-reasons').name).toBe('leaving-reasons')
    expect(r.resolve('/settings/checklist-templates').name).toBe('checklist-templates')
    expect(r.resolve('/payroll/runs').name).toBe('payroll-runs')
    expect(r.resolve('/payroll/runs/3').name).toBe('payroll-run')
    expect(r.resolve('/payroll/payslips/12').name).toBe('payslip')
    expect(r.resolve('/payroll/cheques').name).toBe('payroll-cheques')
    expect(r.resolve('/payroll/queries').name).toBe('payroll-queries')
    expect(r.resolve('/my/payslips').name).toBe('my-payslips')
    expect(r.resolve('/payroll/salaries').name).toBe('payroll-salaries')
    expect(r.resolve('/payroll/components').name).toBe('payroll-components')
    expect(r.resolve('/hr/checklists').name).toBe('checklists')
    expect(r.resolve('/hr/checklists/7').name).toBe('checklist')
  })
})

describe('setup wizard links', () => {
  it('sends every setup step to a screen that exists', () => {
    const r = createRouter({ history: createMemoryHistory(), routes })
    for (const [step, name] of Object.entries(setupStepRoutes)) {
      expect(name, `${step} has no screen`).not.toBeNull()
      expect(r.hasRoute(name!), `${step} links to missing route ${name}`).toBe(true)
    }
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

  it('opens the demo sign-in even for someone already signed in elsewhere', async () => {
    signedIn()
    const demo = { ...to('login', { guest: true }), query: { demo: '1' } } as RouteLocationNormalized
    expect(await guard(demo)).toBe(true)
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
