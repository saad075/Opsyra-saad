import { describe, expect, it } from 'vitest'
import type { VisitRow } from '@/api/visitTypes'
import { visitFlags, weekdaysLabel } from './visits'

const visit = (over: Partial<VisitRow> = {}) =>
  ({ status: 'completed', check_in_within_range: true, check_out_within_range: true, check_in_distance_m: 20, check_out_distance_m: 20, missed_checkout: false, review_status: null, ...over }) as VisitRow

describe('visit helpers', () => {
  it('names the plan days plainly', () => {
    expect(weekdaysLabel([1, 3, 5])).toBe('Mon, Wed, Fri')
    expect(weekdaysLabel([5, 4, 3, 2, 1])).toBe('Weekdays')
    expect(weekdaysLabel([1, 2, 3, 4, 5, 6, 7])).toBe('Every day')
  })

  it('flags what a coordinator should look at', () => {
    expect(visitFlags(visit())).toEqual([])
    expect(visitFlags(visit({ review_status: 'needs_review', check_in_within_range: false, check_in_distance_m: 1112 }))).toEqual(['To review', 'In 1112 m away'])
    expect(visitFlags(visit({ check_out_within_range: false, check_out_distance_m: null, missed_checkout: true }))).toEqual(['Out without location', 'No check-out'])
  })
})
