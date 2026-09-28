import { formatDateTime, humanize } from '../../utils/format'

const STEP_LABELS = {
  input_screened: 'Input screened for prompt injection',
  order_verified: 'Order ownership checked',
  ai_classified: 'AI classification',
  policy_evaluated: 'Refund policy evaluated',
  reply_generated: 'Customer reply generated',
  item_refunded: 'Item marked as refunded',
  admin_reviewed: 'Reviewed by support team',
}

/**
 * @param {{ logs: Array<{ step: string, payload: object|null, created_at: string }> }} props
 */
export default function AuditTimeline({ logs }) {
  return (
    <ol className="audit-timeline list-unstyled mb-0">
      {logs.map((log, index) => (
        <li key={`${log.step}-${log.created_at}-${index}`} className="mb-3">
          <div className="fw-semibold small">{STEP_LABELS[log.step] ?? humanize(log.step)}</div>
          <div className="small text-body-secondary">{formatDateTime(log.created_at)}</div>
          {log.payload && (
            <details className="mt-1">
              <summary className="small text-primary">View details</summary>
              <pre className="payload bg-light border rounded p-2 mt-1 mb-0">{JSON.stringify(log.payload, null, 2)}</pre>
            </details>
          )}
        </li>
      ))}
    </ol>
  )
}
