import { describe, expect, it } from 'vitest'
import { canApprove, periodLabel } from './payroll'

const me = (permissions: string[], id = 5) => ({ user: { id } as never, permissions })

describe('canApprove', () => {
  it('lets level 1 move a draft on, and nothing further', () => {
    expect(canApprove({ status: 'draft', l1_approved_by: null }, me(['HRMS-Approval-L1']))).toBe(true)
    expect(canApprove({ status: 'l1_approved', l1_approved_by: 9 }, me(['HRMS-Approval-L1']))).toBe(false)
  })

  it('lets level 2 approve a draft or a level-1 payslip, but not their own level-1 approval', () => {
    expect(canApprove({ status: 'draft', l1_approved_by: null }, me(['HRMS-Approval-L2']))).toBe(true)
    expect(canApprove({ status: 'l1_approved', l1_approved_by: 9 }, me(['HRMS-Approval-L2']))).toBe(true)
    expect(canApprove({ status: 'l1_approved', l1_approved_by: 5 }, me(['HRMS-Approval-L2']))).toBe(false)
  })

  it('never re-approves a finished payslip, and payroll officers alone cannot approve', () => {
    expect(canApprove({ status: 'approved', l1_approved_by: null }, me(['HRMS-Approval-L2']))).toBe(false)
    expect(canApprove({ status: 'carried_forward', l1_approved_by: null }, me(['HRMS-Approval-L2']))).toBe(false)
    expect(canApprove({ status: 'draft', l1_approved_by: null }, me(['HRMS-SalaryManagement']))).toBe(false)
    expect(canApprove({ status: 'draft', l1_approved_by: null }, null)).toBe(false)
  })
})

describe('periodLabel', () => {
  it('names a whole month, and spells out a part-month period', () => {
    expect(periodLabel({ period_start: '2026-02-01T00:00:00.000000Z', period_end: '2026-02-28T00:00:00.000000Z' })).toMatch(/February 2026/)
    expect(periodLabel({ period_start: '2026-03-02', period_end: '2026-03-15' })).toMatch(/2026 – .*2026/)
  })
})
