import { useEffect, useState } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import RefundStatusBadges from '../../components/admin/RefundStatusBadges'
import StatCard from '../../components/admin/StatCard'
import { useApi } from '../../hooks/useApi'
import { formatDateTime, formatMoney, humanize } from '../../utils/format'

const STATS = [
  { key: 'total', label: 'Total requests', icon: 'bi-inbox' },
  { key: 'approved', label: 'Approved', icon: 'bi-check-circle', tone: 'success' },
  { key: 'denied', label: 'Denied', icon: 'bi-x-circle', tone: 'danger' },
  { key: 'awaiting_review', label: 'Awaiting review', icon: 'bi-hourglass-split', tone: 'warning' },
  { key: 'ai_flagged', label: 'AI risk flags', icon: 'bi-shield-exclamation', tone: 'danger' },
]

export default function AdminDashboardPage() {
  const [searchParams, setSearchParams] = useSearchParams()
  const [search, setSearch] = useState(searchParams.get('search') ?? '')

  const stats = useApi('/admin/stats')
  const list = useApi(`/admin/refund-requests?${searchParams.toString()}`)

  const updateParam = (key, value) => {
    setSearchParams((params) => {
      if (value) params.set(key, value)
      else params.delete(key)
      if (key !== 'page') params.delete('page')
      return params
    })
  }

  useEffect(() => {
    const timer = setTimeout(() => {
      if (search !== (searchParams.get('search') ?? '')) updateParam('search', search.trim())
    }, 300)
    return () => clearTimeout(timer)
    // Only the typed value should trigger the debounce.
    // oxlint-disable-next-line react-hooks/exhaustive-deps
  }, [search])

  const refresh = () => {
    stats.reload()
    list.reload()
  }

  const requests = list.data?.data ?? []
  const meta = list.data?.meta

  return (
    <>
      <div className="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
          <h1 className="h3 mb-0">Refund requests</h1>
          <p className="text-body-secondary mb-0">Decisions, AI analysis and escalations awaiting review.</p>
        </div>
        <button type="button" className="btn btn-outline-primary" onClick={refresh} disabled={list.loading}>
          <i className="bi bi-arrow-clockwise me-1" aria-hidden="true" />
          Refresh
        </button>
      </div>

      <div className="row row-cols-2 row-cols-md-3 row-cols-xl-5 g-3 mb-4">
        {STATS.map((stat) => (
          <div className="col" key={stat.key}>
            <StatCard label={stat.label} value={stats.data?.[stat.key]} icon={stat.icon} tone={stat.tone} />
          </div>
        ))}
      </div>

      <div className="card">
        <div className="card-header bg-white">
          <div className="row g-2 align-items-end">
            <div className="col-md-5">
              <label htmlFor="search" className="form-label small fw-semibold">Search</label>
              <input
                id="search"
                type="search"
                className="form-control"
                placeholder="Reference, email or order number"
                value={search}
                onChange={(event) => setSearch(event.target.value)}
              />
            </div>
            <div className="col-sm-6 col-md-3">
              <label htmlFor="decision" className="form-label small fw-semibold">Decision</label>
              <select
                id="decision"
                className="form-select"
                value={searchParams.get('decision') ?? ''}
                onChange={(event) => updateParam('decision', event.target.value)}
              >
                <option value="">All decisions</option>
                <option value="approved">Approved</option>
                <option value="denied">Denied</option>
                <option value="escalated">Escalated</option>
              </select>
            </div>
            <div className="col-sm-6 col-md-4">
              <div className="form-check form-switch mb-2">
                <input
                  id="awaiting"
                  className="form-check-input"
                  type="checkbox"
                  role="switch"
                  checked={searchParams.get('awaiting_review') === '1'}
                  onChange={(event) => updateParam('awaiting_review', event.target.checked ? '1' : '')}
                />
                <label htmlFor="awaiting" className="form-check-label">Awaiting review only</label>
              </div>
            </div>
          </div>
        </div>

        {list.error && <div className="alert alert-danger m-3" role="alert">{list.error.message}</div>}

        <div className="table-responsive">
          <table className="table table-hover table-striped align-middle mb-0" aria-busy={list.loading}>
            <caption className="visually-hidden">Refund requests, newest first</caption>
            <thead className="table-light">
              <tr>
                <th scope="col">Reference</th>
                <th scope="col">Customer</th>
                <th scope="col">Order / item</th>
                <th scope="col">AI reason</th>
                <th scope="col">Status</th>
                <th scope="col" className="text-end">Amount</th>
                <th scope="col">Submitted</th>
              </tr>
            </thead>
            <tbody>
              {list.loading && requests.length === 0 && (
                <tr><td colSpan={7} className="text-center py-5 text-body-secondary">
                  <span className="spinner-border spinner-border-sm me-2" aria-hidden="true" />Loading requests…
                </td></tr>
              )}
              {!list.loading && requests.length === 0 && (
                <tr><td colSpan={7} className="text-center py-5 text-body-secondary">
                  No refund requests match these filters. Submit one from the customer support page.
                </td></tr>
              )}
              {requests.map((request) => (
                <tr key={request.id}>
                  <td><Link to={`/admin/requests/${request.id}`} className="font-monospace fw-semibold">{request.reference}</Link></td>
                  <td>
                    <div>{request.customer?.name ?? <span className="text-body-secondary">Unverified</span>}</div>
                    <div className="small text-body-secondary">{request.submitted_email}</div>
                  </td>
                  <td>
                    <div className="font-monospace small">{request.submitted_order_number}</div>
                    <div className="small text-body-secondary">{request.item?.product_name ?? '—'}</div>
                  </td>
                  <td>
                    <div>{humanize(request.ai.reason)}</div>
                    {request.ai.flags.length > 0 && (
                      <span className="small text-danger">
                        <i className="bi bi-shield-exclamation me-1" aria-hidden="true" />
                        {request.ai.flags.length} risk flag{request.ai.flags.length > 1 ? 's' : ''}
                      </span>
                    )}
                  </td>
                  <td><RefundStatusBadges request={request} /></td>
                  <td className="text-end text-tabular">{formatMoney(request.refund_amount)}</td>
                  <td className="small text-nowrap">{formatDateTime(request.created_at)}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>

        {meta && meta.last_page > 1 && (
          <nav className="card-footer bg-white d-flex justify-content-between align-items-center" aria-label="Pagination">
            <span className="small text-body-secondary">Page {meta.current_page} of {meta.last_page} · {meta.total} requests</span>
            <div className="btn-group">
              <button type="button" className="btn btn-outline-secondary btn-sm" disabled={meta.current_page <= 1} onClick={() => updateParam('page', String(meta.current_page - 1))}>Previous</button>
              <button type="button" className="btn btn-outline-secondary btn-sm" disabled={meta.current_page >= meta.last_page} onClick={() => updateParam('page', String(meta.current_page + 1))}>Next</button>
            </div>
          </nav>
        )}
      </div>
    </>
  )
}
