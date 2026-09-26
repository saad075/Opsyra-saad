import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import type { ChecklistTask } from '@/api/types'
import { assigneeLabel, daysUntil, dueLabel, isOverdue, offsetLabel } from './tasks'

const task = (over: Partial<ChecklistTask> = {}): ChecklistTask => ({
  id: 1,
  staff_checklist_id: 1,
  title: 'Issue laptop',
  description: null,
  assignee_user_id: null,
  assignee_role_id: null,
  due_date: null,
  is_required: true,
  status: 'todo',
  note: null,
  completed_at: null,
  ...over,
})

describe('task due dates', () => {
  // Late evening local time: a naive UTC comparison would roll to tomorrow.
  beforeEach(() => {
    vi.useFakeTimers()
    vi.setSystemTime(new Date(2026, 8, 25, 23, 30))
  })
  afterEach(() => vi.useRealTimers())

  it('counts calendar days from today, whatever the time of day', () => {
    expect(daysUntil('2026-09-25')).toBe(0)
    expect(daysUntil('2026-09-26T00:00:00.000000Z')).toBe(1)
    expect(daysUntil('2026-09-22')).toBe(-3)
    expect(daysUntil(null)).toBeNull()
  })

  it('words the due date', () => {
    expect(dueLabel(task({ due_date: '2026-09-22' }))).toBe('Overdue by 3 days')
    expect(dueLabel(task({ due_date: '2026-09-24' }))).toBe('Overdue by 1 day')
    expect(dueLabel(task({ due_date: '2026-09-25' }))).toBe('Due today')
    expect(dueLabel(task({ due_date: '2026-09-26' }))).toBe('Due tomorrow')
    expect(dueLabel(task({ due_date: '2026-10-02' }))).toBe('Due in 7 days')
    expect(dueLabel(task())).toBe('No due date')
  })

  it('only open tasks are overdue', () => {
    expect(isOverdue(task({ due_date: '2026-09-20' }))).toBe(true)
    expect(isOverdue(task({ due_date: '2026-09-20', status: 'in_progress' }))).toBe(true)
    expect(isOverdue(task({ due_date: '2026-09-20', status: 'done' }))).toBe(false)
    expect(isOverdue(task({ due_date: '2026-09-20', status: 'skipped' }))).toBe(false)
    expect(isOverdue(task({ due_date: '2026-09-25' }))).toBe(false)
  })
})

describe('assigneeLabel', () => {
  it('names the person, else the role, else HR', () => {
    expect(assigneeLabel(task({ assignee_user: { id: 3, name: 'Sara Khan' } }))).toBe('Sara Khan')
    expect(assigneeLabel(task({ assignee_role: { id: 2, name: 'IT' } }))).toBe('Anyone in IT')
    expect(assigneeLabel(task())).toBe('Unassigned (HR)')
  })
})

describe('offsetLabel', () => {
  it('says when a template task falls due', () => {
    expect(offsetLabel(0, 'onboarding')).toBe('On the start date')
    expect(offsetLabel(-3, 'onboarding')).toBe('3 days before the start date')
    expect(offsetLabel(1, 'offboarding')).toBe('1 day after the last day')
  })
})
