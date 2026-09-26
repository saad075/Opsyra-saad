import { describe, expect, it } from 'vitest'
import { formatDate, initials, statusOf, toDateInput } from '@/lib/staff'

describe('formatDate', () => {
  it('never shifts a calendar date into the viewer’s time zone', () => {
    // Midnight UTC is what the API sends for a date; west of UTC a naive
    // Date() would show the previous day.
    const fromApi = formatDate('2022-09-01T00:00:00.000000Z')
    const plain = formatDate('2022-09-01')

    expect(fromApi).toBe(plain)
    expect(plain).toContain('2022')
    expect(plain).toMatch(/\b1\b/)
  })

  it('is empty for missing dates', () => {
    expect(formatDate(null)).toBe('')
  })
})

describe('staff helpers', () => {
  it('makes initials from up to two names', () => {
    expect(initials('Ada Lovelace King')).toBe('AL')
    expect(initials(null)).toBe('?')
  })

  it('labels statuses in plain words', () => {
    expect(statusOf('pending_approval').label).toBe('Awaiting approval')
    expect(statusOf('offboarded').label).toBe('Left')
  })

  it('turns an API date into a form date', () => {
    expect(toDateInput('2026-10-03T00:00:00.000000Z')).toBe('2026-10-03')
  })
})
