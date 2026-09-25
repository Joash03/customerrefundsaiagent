import DecisionBadge from './DecisionBadge'

const DECISION_HEADINGS = {
  approved: 'Refund approved',
  denied: 'Refund not approved',
  escalated: 'Sent to our support team for review',
}

/**
 * @param {{ message: { role: 'customer'|'assistant'|'error', text: string, decision?: string, reference?: string } }} props
 */
export default function ChatMessage({ message }) {
  const { role, text, decision, reference } = message

  if (role === 'customer') {
    return (
      <div className="d-flex justify-content-end mb-3">
        <div className="chat-bubble chat-bubble-customer rounded-3 px-3 py-2">
          <span className="visually-hidden">You said: </span>
          {text}
        </div>
      </div>
    )
  }

  if (role === 'error') {
    return (
      <div className="alert alert-danger d-flex gap-2 mb-3" role="alert">
        <i className="bi bi-exclamation-octagon-fill" aria-hidden="true" />
        <span>{text}</span>
      </div>
    )
  }

  return (
    <div className="d-flex mb-3">
      <div className="chat-bubble bg-white border rounded-3 px-3 py-2">
        <span className="visually-hidden">Support replied: </span>
        {decision && (
          <div className="d-flex flex-wrap align-items-center gap-2 mb-2">
            <DecisionBadge decision={decision} />
            <strong className="small">{DECISION_HEADINGS[decision]}</strong>
          </div>
        )}
        <div>{text}</div>
        {reference && <div className="small text-body-secondary mt-2">Reference: <span className="font-monospace">{reference}</span></div>}
      </div>
    </div>
  )
}
