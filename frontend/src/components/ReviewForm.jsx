import { useState } from 'react'
import { apiRequest } from '../api/client'

/**
 * Lets a support agent approve or deny an escalated request.
 *
 * @param {{ requestId: number, onReviewed: (request: object) => void }} props
 */
export default function ReviewForm({ requestId, onReviewed }) {
  const [note, setNote] = useState('')
  const [submitting, setSubmitting] = useState(null)
  const [error, setError] = useState(null)

  const submit = async (decision) => {
    setSubmitting(decision)
    setError(null)

    try {
      const { data } = await apiRequest(`/admin/refund-requests/${requestId}/review`, {
        method: 'POST',
        body: { decision, note: note.trim() || null },
      })
      onReviewed(data)
    } catch (err) {
      setError(err.errors?.decision?.[0] ?? err.message)
    } finally {
      setSubmitting(null)
    }
  }

  return (
    <section className="card border-warning" aria-labelledby="review-heading">
      <div className="card-body">
        <h2 id="review-heading" className="h6">
          <i className="bi bi-person-check me-2" aria-hidden="true" />
          Human review required
        </h2>
        <p className="small text-body-secondary">The policy engine escalated this request. Your decision is final and is recorded in the audit trail.</p>

        {error && <div className="alert alert-danger py-2" role="alert">{error}</div>}

        <div className="mb-3">
          <label htmlFor="review-note" className="form-label small fw-semibold">Review note (optional)</label>
          <textarea id="review-note" className="form-control" rows={3} maxLength={1000} value={note} onChange={(event) => setNote(event.target.value)} />
        </div>
        <div className="d-flex gap-2">
          <button type="button" className="btn btn-success flex-fill" disabled={Boolean(submitting)} onClick={() => submit('approved')}>
            {submitting === 'approved' ? 'Approving…' : 'Approve refund'}
          </button>
          <button type="button" className="btn btn-outline-danger flex-fill" disabled={Boolean(submitting)} onClick={() => submit('denied')}>
            {submitting === 'denied' ? 'Denying…' : 'Deny refund'}
          </button>
        </div>
      </div>
    </section>
  )
}
