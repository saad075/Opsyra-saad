import type { Me, PayCalculation, Payslip, PayslipStatus } from '@/api/types'
import { formatDate } from '@/lib/staff'

export const PAYSLIP_STATUS: Record<PayslipStatus, { label: string; type: 'default' | 'info' | 'success' | 'warning' }> = {
  draft: { label: 'Draft', type: 'default' },
  l1_approved: { label: 'Level 1 approved', type: 'info' },
  approved: { label: 'Approved', type: 'success' },
  carried_forward: { label: 'Carried forward', type: 'warning' },
}

/** Payroll officers: start runs, regenerate, carry forward, cheques, queries. */
export const PAYROLL_MANAGERS = 'HRMS-SalaryManagement'

/** "Mar 2026" for a whole-month period, otherwise "Mar 2, 2026 – Mar 15, 2026". */
export function periodLabel(p: { period_start: string; period_end: string; frequency?: string }): string {
  const start = new Date(`${p.period_start.slice(0, 10)}T00:00:00`)
  const end = new Date(`${p.period_end.slice(0, 10)}T00:00:00`)
  const wholeMonth = start.getDate() === 1 && end.getMonth() === start.getMonth() && new Date(end.getFullYear(), end.getMonth(), end.getDate() + 1).getDate() === 1
  if (wholeMonth) return start.toLocaleDateString(undefined, { month: 'long', year: 'numeric' })
  return `${formatDate(p.period_start)} – ${formatDate(p.period_end)}`
}

/**
 * Whether this person can approve this payslip now — mirrors the API's
 * rules (PayslipApprovals): level 1 moves a draft on; level 2 approves a
 * draft or a level-1 payslip, but never one they approved at level 1; and
 * nobody approves their own pay.
 */
export function canApprove(slip: Pick<Payslip, 'status' | 'l1_approved_by' | 'user_id'>, me: Pick<Me, 'user' | 'permissions'> | null): boolean {
  if (!me || slip.user_id === me.user.id) return false
  const l1 = me.permissions.includes('HRMS-Approval-L1')
  const l2 = me.permissions.includes('HRMS-Approval-L2')
  if (slip.status === 'draft') return l1 || l2
  if (slip.status === 'l1_approved') return l2 && slip.l1_approved_by !== me.user.id
  return false
}

/** Pay that hasn't gone out yet, so can still be carried forward (mirrors PayslipApprovals::isUnpaid). */
export function isUnpaid(slip: Pick<Payslip, 'status' | 'payment_method' | 'cheque_number'>): boolean {
  return slip.status === 'draft' || slip.status === 'l1_approved' || (slip.status === 'approved' && slip.payment_method === 'cheque' && !slip.cheque_number)
}

export const CALCULATION_LABEL: Record<PayCalculation, string> = {
  fixed: 'A fixed amount',
  percent_of_basic: 'A percentage of basic pay',
  percent_of_gross: 'A percentage of gross pay',
}

export const FREQUENCY_LABEL: Record<'monthly' | 'biweekly', string> = { monthly: 'Monthly', biweekly: 'Every two weeks' }
