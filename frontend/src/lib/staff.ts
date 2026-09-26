/** How staff statuses read and look everywhere in the app. */
export const STATUS: Record<string, { label: string; type: 'success' | 'info' | 'warning' | 'default' | 'error' }> = {
  active: { label: 'Active', type: 'success' },
  pending: { label: 'Starting soon', type: 'info' },
  pending_approval: { label: 'Awaiting approval', type: 'warning' },
  offboarded: { label: 'Left', type: 'default' },
}

export function statusOf(status: string) {
  return STATUS[status] ?? { label: status, type: 'default' as const }
}

export function initials(name: string | null | undefined): string {
  return (name ?? '?')
    .split(/\s+/)
    .filter(Boolean)
    .map((part) => part[0])
    .slice(0, 2)
    .join('')
    .toUpperCase()
}

/**
 * "3 Oct 2026" from an ISO date or datetime; empty for null.
 *
 * Calendar dates (joining day, birthday, last working day) arrive as
 * "2026-10-03" or as midnight UTC ("2026-10-03T00:00:00.000000Z"). They are
 * dates, not moments: shown as-is, never shifted into the viewer's time
 * zone — otherwise anyone west of UTC sees the day before.
 */
export function formatDate(value: string | null | undefined): string {
  if (!value) return ''
  const calendarDate = /^\d{4}-\d{2}-\d{2}(T00:00:00(\.0+)?Z?)?$/.test(value)
  const date = calendarDate ? new Date(`${value.slice(0, 10)}T00:00:00`) : new Date(value)
  return date.toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' })
}

/** yyyy-MM-dd for the API, from an ISO datetime the API sent. */
export function toDateInput(value: string | null | undefined): string | null {
  return value ? value.slice(0, 10) : null
}
