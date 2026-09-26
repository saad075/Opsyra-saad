import { describe, expect, it, vi } from 'vitest'
import type { AttendanceDayRecord } from '@/api/attendanceTypes'
import { currentPosition, dayFlags, formatMinutes, localTime, statusLabel } from './attendance'

const day = (over: Partial<AttendanceDayRecord> = {}): AttendanceDayRecord => ({
  id: 1,
  user_id: 1,
  date: '2026-03-02',
  status: 'present',
  leave_paid: null,
  expected: 'work',
  scheduled_minutes: 420,
  worked_minutes: 450,
  is_late: false,
  late_minutes: 0,
  left_early: false,
  early_minutes: 0,
  missed_checkout: false,
  first_in_at: null,
  last_out_at: null,
  is_manual: false,
  source: 'app',
  note: null,
  needs_review: false,
  ...over,
})

describe('attendance helpers', () => {
  it('writes minutes as hours and minutes', () => {
    expect(formatMinutes(450)).toBe('7h 30m')
    expect(formatMinutes(480)).toBe('8h')
    expect(formatMinutes(25)).toBe('25m')
    expect(formatMinutes(0)).toBe('—')
  })

  it('shows the time on the person’s own clock, whatever the viewer’s timezone', () => {
    expect(localTime('2026-03-02T09:05:00-06:00')).toBe('09:05')
    expect(localTime(null)).toBe('')
  })

  it('says whether leave was paid', () => {
    expect(statusLabel(day({ status: 'on_leave', leave_paid: false }))).toBe('Unpaid leave')
    expect(statusLabel(day({ status: 'on_leave', leave_paid: true }))).toBe('Paid leave')
    expect(statusLabel(day({ status: 'open' }))).toBe('In now')
  })

  it('lists what stood out about a day', () => {
    expect(dayFlags(day({ is_late: true, late_minutes: 25, missed_checkout: true }))).toEqual(['Late 25m', 'Missed check-out'])
    expect(dayFlags(day())).toEqual([])
  })
})

describe('currentPosition', () => {
  it('gives up when the browser never answers (its permission prompt left open)', async () => {
    vi.useFakeTimers()
    vi.stubGlobal('navigator', { geolocation: { getCurrentPosition: () => {} } })

    const result = currentPosition(1000)
    vi.advanceTimersByTime(3100)

    expect(await result).toBeNull()
    vi.unstubAllGlobals()
    vi.useRealTimers()
  })
})
