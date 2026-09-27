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

/** Who uses the Approvals screen: level-2 approvers, and HR (rejected hires to notify). */
export const HR_APPROVERS = ['HRMS-Approval-L2', 'HRMS-StaffManagement']

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
      { path: 'settings/departments', name: 'departments', component: () => import('@/views/settings/DepartmentsView.vue'), meta: { title: 'Departments', permission: 'HRMS-StaffManagement' } },
      { path: 'settings/leaving-reasons', name: 'leaving-reasons', component: () => import('@/views/settings/LeavingReasonsView.vue'), meta: { title: 'Leaving reasons', permission: 'HRMS-StaffManagement' } },
      { path: 'settings/checklist-templates', name: 'checklist-templates', component: () => import('@/views/settings/ChecklistTemplatesView.vue'), meta: { title: 'Checklist templates', permission: 'HRMS-StaffManagement' } },
      { path: 'settings/roles', name: 'roles', component: () => import('@/views/settings/RolesView.vue'), meta: { title: 'Roles', permission: 'HRMS-RoleManagement' } },
      { path: 'settings/approvers', name: 'approvers', component: () => import('@/views/settings/ApproversView.vue'), meta: { title: 'Approvers', permission: 'HRMS-StaffPermissionUpdate' } },
      { path: 'attendance/dashboard', name: 'attendance-dashboard', component: () => import('@/views/attendance/AttendanceDashboardView.vue'), meta: { title: 'Attendance dashboard', permission: 'HRMS-AttendanceManagement' } },
      { path: 'attendance/today', name: 'attendance-today', component: () => import('@/views/attendance/DailySheetView.vue'), meta: { title: 'Attendance', permission: 'HRMS-AttendanceManagement' } },
      { path: 'attendance/staff/:id(\\d+)', name: 'attendance-person', component: () => import('@/views/attendance/PersonMonthView.vue'), meta: { title: 'Attendance', permission: 'HRMS-AttendanceManagement' } },
      // Managers decide their own team's requests too; the API shows each person only what they may decide.
      { path: 'attendance/requests', name: 'attendance-requests', component: () => import('@/views/attendance/CorrectionsView.vue'), meta: { title: 'Attendance requests' } },
      { path: 'my/attendance', name: 'my-attendance', component: () => import('@/views/attendance/MyAttendanceView.vue'), meta: { title: 'My attendance' } },
      { path: 'settings/working-hours', name: 'working-hours', component: () => import('@/views/settings/WorkingHoursView.vue'), meta: { title: 'Working hours', permission: 'HRMS-AttendanceManagement' } },
      { path: 'settings/holidays', name: 'holidays', component: () => import('@/views/settings/HolidaysView.vue'), meta: { title: 'Public holidays', permission: 'HRMS-AttendanceManagement' } },
      { path: 'settings/attendance-rules', name: 'attendance-rules', component: () => import('@/views/settings/AttendanceRulesView.vue'), meta: { title: 'Attendance rules', permission: 'HRMS-AttendanceManagement' } },
      { path: 'visits', name: 'visits-board', component: () => import('@/views/visits/VisitBoardView.vue'), meta: { title: 'Visits', permission: 'HRMS-VisitPlanning' } },
      { path: 'visits/review', name: 'visits-review', component: () => import('@/views/visits/VisitReviewView.vue'), meta: { title: 'Visits to review', permission: 'HRMS-VisitPlanning' } },
      { path: 'visits/clients', name: 'visits-clients', component: () => import('@/views/visits/VisitClientsView.vue'), meta: { title: 'Clients', permission: 'HRMS-VisitPlanning' } },
      { path: 'visits/plans', name: 'visits-plans', component: () => import('@/views/visits/VisitPlansView.vue'), meta: { title: 'Visit plans', permission: 'HRMS-VisitPlanning' } },
      { path: 'hr/dashboard', name: 'hr-dashboard', component: () => import('@/views/hr/HrDashboardView.vue'), meta: { title: 'HR dashboard', permission: 'HRMS-StaffManagement' } },
      { path: 'payroll/dashboard', name: 'payroll-dashboard', component: () => import('@/views/payroll/PayrollDashboardView.vue'), meta: { title: 'Payroll dashboard', permission: PAYROLL_VIEWERS } },
      { path: 'payroll/runs', name: 'payroll-runs', component: () => import('@/views/payroll/PayRunsView.vue'), meta: { title: 'Pay runs', permission: PAYROLL_VIEWERS } },
      { path: 'payroll/runs/:id(\\d+)', name: 'payroll-run', component: () => import('@/views/payroll/PayRunView.vue'), meta: { title: 'Pay run', permission: PAYROLL_VIEWERS } },
      { path: 'payroll/salaries', name: 'payroll-salaries', component: () => import('@/views/payroll/SalariesView.vue'), meta: { title: 'Salaries', permission: 'HRMS-SalaryManagement' } },
      { path: 'payroll/components', name: 'payroll-components', component: () => import('@/views/payroll/PayComponentsView.vue'), meta: { title: 'Pay components', permission: 'HRMS-SalaryManagement' } },
      { path: 'payroll/cheques', name: 'payroll-cheques', component: () => import('@/views/payroll/ChequesView.vue'), meta: { title: 'Cheques', permission: PAYROLL_VIEWERS } },
      { path: 'payroll/queries', name: 'payroll-queries', component: () => import('@/views/payroll/SalaryQueriesView.vue'), meta: { title: 'Salary queries', permission: 'HRMS-SalaryManagement' } },
      // No route permission: staff open their own approved payslips; the API decides.
      { path: 'payroll/payslips/:id(\\d+)', name: 'payslip', component: () => import('@/views/payroll/PayslipView.vue'), meta: { title: 'Payslip' } },
      { path: 'my/payslips', name: 'my-payslips', component: () => import('@/views/payroll/MyPayslipsView.vue'), meta: { title: 'My payslips' } },
      { path: 'hr/staff', name: 'staff', component: () => import('@/views/hr/StaffListView.vue'), meta: { title: 'Staff', permission: 'HRMS-StaffManagement' } },
      { path: 'hr/staff/new', name: 'staff-new', component: () => import('@/views/hr/StaffFormView.vue'), meta: { title: 'Add staff', permission: 'HRMS-StaffManagement' } },
      // No route permission: people may open their own profile; the API decides who sees whom.
      { path: 'hr/staff/:id(\\d+)', name: 'staff-profile', component: () => import('@/views/hr/StaffProfileView.vue'), meta: { title: 'Staff profile' } },
      { path: 'hr/staff/:id(\\d+)/edit', name: 'staff-edit', component: () => import('@/views/hr/StaffFormView.vue'), meta: { title: 'Edit staff', permission: 'HRMS-StaffManagement' } },
      { path: 'hr/approvals', name: 'approvals', component: () => import('@/views/hr/ApprovalsView.vue'), meta: { title: 'Approvals', permission: HR_APPROVERS } },
      // My tasks and a single checklist are for everyone with a task; the API filters.
      { path: 'tasks', name: 'tasks', component: () => import('@/views/tasks/MyTasksView.vue'), meta: { title: 'My tasks' } },
      { path: 'hr/checklists', name: 'checklists', component: () => import('@/views/tasks/ChecklistsView.vue'), meta: { title: 'Checklists', permission: 'HRMS-StaffManagement' } },
      { path: 'hr/checklists/:id(\\d+)', name: 'checklist', component: () => import('@/views/tasks/ChecklistView.vue'), meta: { title: 'Checklist' } },
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
