import type { VisitRow, VisitStatus } from '@/api/visitTypes'

type TagType = 'default' | 'info' | 'success' | 'warning' | 'error'

export const VISIT_STATUS: Record<VisitStatus, { label: string; type: TagType }> = {
  planned: { label: 'Planned', type: 'default' },
  in_progress: { label: 'In progress', type: 'info' },
  completed: { label: 'Done', type: 'success' },
  missed: { label: 'Missed', type: 'error' },
  cancelled: { label: 'Cancelled', type: 'default' },
}

/** What a coordinator needs to know at a glance about a visit's check-in. */
export function visitFlags(v: VisitRow): string[] {
  const flags: string[] = []
  if (v.review_status === 'needs_review') flags.push('To review')
  if (v.review_status === 'rejected') flags.push('Rejected')
  if (v.check_in_within_range === false) flags.push(v.check_in_distance_m !== null ? `In ${v.check_in_distance_m} m away` : 'In without location')
  if (v.check_out_within_range === false) flags.push(v.check_out_distance_m !== null ? `Out ${v.check_out_distance_m} m away` : 'Out without location')
  if (v.missed_checkout) flags.push('No check-out')
  return flags
}

/** Weekdays (ISO 1–7) as "Mon, Wed, Fri" — or "Every day" / "Weekdays". */
export function weekdaysLabel(days: number[]): string {
  const sorted = [...days].map(Number).sort()
  if (sorted.length === 7) return 'Every day'
  if (sorted.join() === '1,2,3,4,5') return 'Weekdays'
  const names = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']
  return sorted.map((d) => names[d - 1]).join(', ')
}
