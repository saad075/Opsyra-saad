import type { ChecklistTask } from '@/api/types'

/** A calendar date's position relative to today, for due-date wording. */
export function daysUntil(due: string | null): number | null {
  if (!due) return null
  const [y, m, d] = due.slice(0, 10).split('-').map(Number)
  const dueDay = Date.UTC(y, m - 1, d)
  const now = new Date()
  const today = Date.UTC(now.getFullYear(), now.getMonth(), now.getDate())
  return Math.round((dueDay - today) / 86_400_000)
}

export function isOpen(task: Pick<ChecklistTask, 'status'>): boolean {
  return task.status === 'todo' || task.status === 'in_progress'
}

export function isOverdue(task: ChecklistTask): boolean {
  const days = daysUntil(task.due_date)
  return isOpen(task) && days !== null && days < 0
}

/** "Overdue by 3 days", "Due today", "Due in 5 days", "No due date". */
export function dueLabel(task: ChecklistTask): string {
  const days = daysUntil(task.due_date)
  if (days === null) return 'No due date'
  if (!isOpen(task)) return ''
  if (days < 0) return `Overdue by ${-days} ${-days === 1 ? 'day' : 'days'}`
  if (days === 0) return 'Due today'
  if (days === 1) return 'Due tomorrow'
  return `Due in ${days} days`
}

export const TASK_STATUS: Record<string, { label: string; type: 'default' | 'info' | 'success' | 'warning' }> = {
  todo: { label: 'To do', type: 'default' },
  in_progress: { label: 'In progress', type: 'info' },
  done: { label: 'Done', type: 'success' },
  skipped: { label: 'Skipped', type: 'warning' },
}

/** Who the task is for: a named person, or everyone with a role. */
export function assigneeLabel(task: ChecklistTask): string {
  if (task.assignee_user) return task.assignee_user.name
  if (task.assignee_role) return `Anyone in ${task.assignee_role.name}`
  return 'Unassigned (HR)'
}

/** A template task's due date relative to the start date (joining) or last day (leaving). */
export function offsetLabel(days: number | null, type: 'onboarding' | 'offboarding'): string {
  const anchor = type === 'onboarding' ? 'start date' : 'last day'
  const n = days ?? 0
  if (n === 0) return `On the ${anchor}`
  const count = `${Math.abs(n)} ${Math.abs(n) === 1 ? 'day' : 'days'}`
  return `${count} ${n < 0 ? 'before' : 'after'} the ${anchor}`
}
