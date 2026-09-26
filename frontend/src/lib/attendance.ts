import type { AttendanceDayRecord, DayStatus } from '@/api/attendanceTypes'

type TagType = 'default' | 'info' | 'success' | 'warning' | 'error'

export const DAY_STATUS: Record<DayStatus, { label: string; type: TagType }> = {
  present: { label: 'Present', type: 'success' },
  open: { label: 'In now', type: 'info' },
  half_day: { label: 'Half day', type: 'warning' },
  absent: { label: 'Absent', type: 'error' },
  on_leave: { label: 'On leave', type: 'info' },
  remote: { label: 'Remote', type: 'success' },
  day_off: { label: 'Day off', type: 'default' },
  holiday: { label: 'Holiday', type: 'default' },
}

export const WEEKDAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']

export const MODE_LABEL: Record<'schedule' | 'visits' | 'not_tracked', string> = {
  schedule: 'Working hours',
  visits: 'Client visits',
  not_tracked: 'Not tracked',
}

/** 450 → "7h 30m"; 0 → "—". */
export function formatMinutes(minutes: number | null | undefined): string {
  if (!minutes) return '—'
  const h = Math.floor(minutes / 60)
  const m = minutes % 60
  return h ? (m ? `${h}h ${m}m` : `${h}h`) : `${m}m`
}

/**
 * "09:05" from an ISO time the API sent in the person's own timezone — the
 * time on their clock, not the viewer's, so a New York HR manager sees a
 * Chicago clock-in as 9:05, not 10:05.
 */
export function localTime(iso: string | null | undefined): string {
  if (!iso) return ''
  const match = /T(\d{2}):(\d{2})/.exec(iso)
  return match ? `${match[1]}:${match[2]}` : ''
}

/** The day's status label, with paid/unpaid for leave. */
export function statusLabel(day: Pick<AttendanceDayRecord, 'status' | 'leave_paid'>): string {
  if (day.status === 'on_leave' && day.leave_paid !== null) return day.leave_paid ? 'Paid leave' : 'Unpaid leave'
  return DAY_STATUS[day.status]?.label ?? day.status
}

/** Short notes for a day: "Late 25m", "Left 40m early", "Missed check-out". */
export function dayFlags(day: AttendanceDayRecord): string[] {
  return [
    day.is_late ? `Late ${formatMinutes(day.late_minutes)}` : null,
    day.left_early ? `Left ${formatMinutes(day.early_minutes)} early` : null,
    day.missed_checkout ? 'Missed check-out' : null,
    day.needs_review ? 'Away from branch' : null,
  ].filter((f): f is string => !!f)
}

/**
 * The browser's position, or null (refused, unavailable, too slow) —
 * clocking never depends on it. Our own timer as well as the browser's:
 * the browser's timeout doesn't start while its "share your location?"
 * prompt is still open, so without this a clock-in could wait forever.
 */
export function currentPosition(timeoutMs = 8000): Promise<{ latitude: number; longitude: number; accuracy_m: number } | null> {
  if (typeof navigator === 'undefined' || !navigator.geolocation) return Promise.resolve(null)
  return new Promise((resolve) => {
    const giveUp = setTimeout(() => resolve(null), timeoutMs + 2000)
    navigator.geolocation.getCurrentPosition(
      (p) => {
        clearTimeout(giveUp)
        resolve({ latitude: p.coords.latitude, longitude: p.coords.longitude, accuracy_m: Math.round(p.coords.accuracy) })
      },
      () => {
        clearTimeout(giveUp)
        resolve(null)
      },
      { enableHighAccuracy: true, timeout: timeoutMs, maximumAge: 60_000 },
    )
  })
}
