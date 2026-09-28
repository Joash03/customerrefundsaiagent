import { Link, useParams } from 'react-router-dom'
import AuditTimeline from '../../components/admin/AuditTimeline'
import RefundStatusBadges from '../../components/admin/RefundStatusBadges'
import ReviewForm from '../../components/admin/ReviewForm'
import { useApi } from '../../hooks/useApi'
import { formatDateTime, formatMoney, humanize } from '../../utils/format'

/**
 * @param {{ label: string, children: import('react').ReactNode }} props
 */
function Field({ label, children }) {
  return (
    <>
      <dt className="col-5 small text-body-secondary fw-normal">{label}</dt>
      <dd className="col-7 small mb-2">{children}</dd>
    </>
  )
}

export default function RefundRequestDetailPage() {
  const { id } = useParams()
  const { data, loading, error, setData } = useApi(`/admin/refund-requests/${id}`)
  const request = data?.data

  if (loading && !request) {
    return <p className="text-body-secondary"><span className="spinner-border spinner-border-sm me-2" aria-hidden="true" />Loading request…</p>
  }

  if (error) {
    return (
      <div className="alert alert-danger" role="alert">
        {error.status === 404 ? 'This refund request does not exist.' : error.message} <Link to="/admin">Back to dashboard</Link>
      </div>
    )
  }

  const { ai } = request

  return (
    <>
      <Link to="/admin" className="small d-inline-block mb-2">
        <i className="bi bi-arrow-left me-1" aria-hidden="true" />All requests
      </Link>
      <div className="d-flex flex-wrap align-items-center gap-3 mb-4">
        <h1 className="h3 mb-0 font-monospace">{request.reference}</h1>
        <RefundStatusBadges request={request} />
        <span className="small text-body-secondary">{formatDateTime(request.created_at)}</span>
      </div>

      <div className="row g-4">
        <div className="col-lg-7 d-flex flex-column gap-4">
          <section className="card" aria-labelledby="message-heading">
            <div className="card-body">
              <h2 id="message-heading" className="h6">Customer message</h2>
              <blockquote className="mb-0 border-start border-3 ps-3 text-break text-prewrap">{request.message}</blockquote>
            </div>
          </section>

          <section className="card" aria-labelledby="ai-heading">
            <div className="card-body">
              <h2 id="ai-heading" className="h6">
                <i className="bi bi-cpu me-2" aria-hidden="true" />AI analysis
              </h2>
              {ai.provider ? (
                <>
                  <dl className="row mb-2">
                    <Field label="Provider">{ai.provider}</Field>
                    <Field label="Reason">{humanize(ai.reason)}</Field>
                    <Field label="Confidence">{ai.confidence != null ? `${Math.round(ai.confidence * 100)}%` : '—'}</Field>
                    <Field label="Summary">{ai.summary ?? '—'}</Field>
                  </dl>
                  <div className="d-flex flex-wrap gap-1" aria-label="Risk flags">
                    {ai.flags.length === 0 && <span className="small text-body-secondary">No risk flags raised.</span>}
                    {ai.flags.map((flag) => (
                      <span key={flag} className="badge text-bg-danger">
                        <i className="bi bi-shield-exclamation me-1" aria-hidden="true" />{humanize(flag)}
                      </span>
                    ))}
                  </div>
                </>
              ) : (
                <p className="small text-body-secondary mb-0">Not run: the order could not be verified, so no customer data was sent to the AI.</p>
              )}
            </div>
          </section>

          <section className="card" aria-labelledby="policy-heading">
            <div className="card-body">
              <h2 id="policy-heading" className="h6">
                <i className="bi bi-journal-check me-2" aria-hidden="true" />Policy decision
              </h2>
              <ul className="list-unstyled mb-3">
                {request.rules.map((rule) => (
                  <li key={rule.id} className="d-flex gap-2 mb-1">
                    <span className="badge text-bg-secondary font-monospace align-self-start">{rule.id}</span>
                    <span className="small">{rule.description}</span>
                  </li>
                ))}
              </ul>
              <div className="small">Refund amount: <strong className="text-tabular">{formatMoney(request.refund_amount)}</strong></div>
            </div>
          </section>

          {request.transcript && (
            <section className="card" aria-labelledby="transcript-heading">
              <div className="card-body">
                <h2 id="transcript-heading" className="h6">
                  <i className="bi bi-chat-left-text me-2" aria-hidden="true" />Chat transcript
                </h2>
                <ol className="transcript list-unstyled mb-0" tabIndex={0} aria-label="Chat transcript, scrollable">
                  {request.transcript.map((message, index) => (
                    <li key={`${message.created_at}-${index}`} className="mb-2">
                      <span className={`small fw-semibold ${message.role === 'customer' ? 'text-primary' : 'text-body-secondary'}`}>
                        {message.role === 'customer' ? 'Customer' : 'Assistant'}
                      </span>
                      <span className="small text-body-secondary ms-2">{formatDateTime(message.created_at)}</span>
                      <p className="small mb-0 text-prewrap">{message.content}</p>
                    </li>
                  ))}
                </ol>
              </div>
            </section>
          )}

          <section className="card" aria-labelledby="reply-heading">
            <div className="card-body">
              <h2 id="reply-heading" className="h6">Reply sent to customer</h2>
              <p className="small mb-0">{request.customer_reply}</p>
            </div>
          </section>
        </div>

        <div className="col-lg-5 d-flex flex-column gap-4">
          {request.awaiting_review && <ReviewForm requestId={request.id} onReviewed={(updated) => setData({ data: updated })} />}

          {request.review && (
            <section className="card" aria-labelledby="review-result-heading">
              <div className="card-body">
                <h2 id="review-result-heading" className="h6">Human review</h2>
                <dl className="row mb-0">
                  <Field label="Outcome">{humanize(request.final_decision)}</Field>
                  <Field label="Reviewer">{request.review.reviewer ?? '—'}</Field>
                  <Field label="Reviewed">{formatDateTime(request.review.reviewed_at)}</Field>
                  <Field label="Note">{request.review.note ?? '—'}</Field>
                </dl>
              </div>
            </section>
          )}

          <section className="card" aria-labelledby="customer-heading">
            <div className="card-body">
              <h2 id="customer-heading" className="h6">Customer &amp; order</h2>
              <dl className="row mb-0">
                <Field label="Customer">{request.customer?.name ?? 'Not verified'}</Field>
                <Field label="Submitted email">{request.submitted_email}</Field>
                <Field label="Order">{request.order?.order_number ?? request.submitted_order_number}</Field>
                <Field label="Order status">{humanize(request.order?.status)}</Field>
                <Field label="Delivered">{formatDateTime(request.order?.delivered_at)}</Field>
                <Field label="Item">{request.item?.product_name ?? 'Not identified'}</Field>
                <Field label="Final sale">{request.item ? (request.item.is_final_sale ? 'Yes' : 'No') : '—'}</Field>
              </dl>
            </div>
          </section>

          <section className="card" aria-labelledby="audit-heading">
            <div className="card-body">
              <h2 id="audit-heading" className="h6">
                <i className="bi bi-list-check me-2" aria-hidden="true" />Audit trail
              </h2>
              <AuditTimeline logs={request.audit_logs ?? []} />
            </div>
          </section>
        </div>
      </div>
    </>
  )
}
