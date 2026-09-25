const dateTime = new Intl.DateTimeFormat(undefined, { dateStyle: 'medium', timeStyle: 'short' })
const currency = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' })

export const formatDateTime = (iso) => (iso ? dateTime.format(new Date(iso)) : '—')

export const formatMoney = (amount) => (amount == null ? '—' : currency.format(Number(amount)))

export const humanize = (value) => (value ? value.replaceAll('_', ' ').replace(/^\w/, (c) => c.toUpperCase()) : '—')
