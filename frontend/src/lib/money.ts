/**
 * Money as shown to people: the currency's own decimal places (Omani rial
 * has three) and the viewer's number formatting. Matches the API's rounding
 * (Payroll's Money helper).
 */
const THREE_DECIMALS = new Set(['OMR', 'BHD', 'KWD'])

export function formatMoney(amount: number, currency: string): string {
  const digits = THREE_DECIMALS.has(currency.toUpperCase()) ? 3 : 2
  try {
    return new Intl.NumberFormat(undefined, { style: 'currency', currency, minimumFractionDigits: digits, maximumFractionDigits: digits }).format(amount)
  } catch {
    return `${currency} ${amount.toFixed(digits)}`
  }
}
