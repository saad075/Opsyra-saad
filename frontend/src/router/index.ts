import { createRouter, createWebHistory, type RouteLocationNormalized, type RouteRecordRaw } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

declare module 'vue-router' {
  interface RouteMeta {
    /** Only for signed-out visitors (login, forgot password). */
    guest?: boolean
    /** Open to anyone, signed in or not (reset link from an email). */
    public?: boolean
    /** Hide the screen unless the user holds this permission (or any of these). The API still checks. */
    permission?: string | string[]
    title?: string
  }
}

/** Payroll staff, or anyone who approves pay (mirrors the API's view-payroll gate). */
export const PAYROLL_VIEWERS = ['HRMS-SalaryManagement', 'HRMS-Approval-L1', 'HRMS-Approval-L2']

export const routes: RouteRecordRaw[] = [
  // Order matters: both layouts sit at "/", and on a tie Vue Router picks
  // the first. The app layout must come first so "/" opens Home — with the
  // auth layout first, "/" matched it with no child and showed an empty
  // sign-in card. Covered by guard.spec.ts.
  {
    path: '/',
    component: () => import('@/layouts/AppLayout.vue'),
    children: [
      { path: '', name: 'dashboard', component: () => import('@/views/DashboardView.vue'), meta: { title: 'Home' } },
      { path: 'setup', name: 'setup', component: () => import('@/views/setup/SetupWizardView.vue'), meta: { title: 'Setup', permission: 'HRMS-CompanySettings' } },
      { path: 'settings/company', name: 'company-settings', component: () => import('@/views/settings/CompanySettingsView.vue'), meta: { title: 'Company details', permission: 'HRMS-CompanySettings' } },
      { path: 'hr/dashboard', name: 'hr-dashboard', component: () => import('@/views/hr/HrDashboardView.vue'), meta: { title: 'HR dashboard', permission: 'HRMS-StaffManagement' } },
      { path: 'payroll/dashboard', name: 'payroll-dashboard', component: () => import('@/views/payroll/PayrollDashboardView.vue'), meta: { title: 'Payroll dashboard', permission: PAYROLL_VIEWERS } },
      { path: 'hr/branches', name: 'branches', component: () => import('@/views/hr/BranchesView.vue'), meta: { title: 'Branches', permission: 'HRMS-StaffManagement' } },
    ],
  },
  {
    path: '/',
    component: () => import('@/layouts/AuthLayout.vue'),
    children: [
      { path: 'login', name: 'login', component: () => import('@/views/auth/LoginView.vue'), meta: { guest: true, title: 'Sign in' } },
      { path: 'forgot-password', name: 'forgot-password', component: () => import('@/views/auth/ForgotPasswordView.vue'), meta: { guest: true, title: 'Forgot password' } },
      { path: 'reset-password', name: 'reset-password', component: () => import('@/views/auth/ResetPasswordView.vue'), meta: { public: true, title: 'Reset password' } },
      { path: 'change-password', name: 'change-password', component: () => import('@/views/auth/ChangePasswordView.vue'), meta: { title: 'Change password' } },
    ],
  },
  { path: '/:pathMatch(.*)*', redirect: '/' },
]

export const router = createRouter({ history: createWebHistory(), routes })

/**
 * Decides where a navigation may go. Exported separately so it can be unit
 * tested without a browser.
 */
export async function guard(to: RouteLocationNormalized) {
  const auth = useAuthStore()

  if (to.meta.public) return true

  // "Enter the demo" from the website always reaches the demo sign-in, even
  // for someone already signed in to their own company on this device.
  if (to.meta.guest && to.query.demo === '1') return true

  if (to.meta.guest) return auth.isAuthenticated ? { name: 'dashboard' } : true

  if (!auth.isAuthenticated) return { name: 'login', query: to.fullPath !== '/' ? { redirect: to.fullPath } : {} }

  if (!auth.me) {
    try {
      await auth.fetchMe()
    } catch {
      auth.forget()
      return { name: 'login' }
    }
  }

  if (auth.mustChangePassword && to.name !== 'change-password') return { name: 'change-password' }

  if (to.meta.permission && !auth.canAny(to.meta.permission)) return { name: 'dashboard' }

  return true
}

router.beforeEach(guard)

router.afterEach((to) => {
  document.title = to.meta.title ? `${to.meta.title} · Opsyra` : 'Opsyra'
})
