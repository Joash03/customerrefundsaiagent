import DecisionBadge from '../DecisionBadge'

const DECISION_HEADINGS = {
  approved: 'Refund approved',
  denied: 'Refund not approved',
  escalated: 'Passed to our support team',
}

/**
 * @param {{ message: { role: 'customer'|'assistant', content: string, decision?: string|null, reference?: string|null } }} props
 */
export default function ChatMessage({ message }) {
  const { role, content, decision, reference } = message

  if (role === 'customer') {
    return (
      <div className="d-flex justify-content-end mb-3">
        <div className="chat-bubble chat-bubble-customer">
          <span className="visually-hidden">You: </span>
          {content}
        </div>
      </div>
    )
  }

  return (
    <div className="d-flex gap-2 mb-3">
      <span className="chat-avatar" aria-hidden="true"><i className="bi bi-headset" /></span>
      <div className={`chat-bubble chat-bubble-assistant${decision ? ` chat-bubble-${decision}` : ''}`}>
        <span className="visually-hidden">Support assistant: </span>
        {decision && (
          <div className="mb-2">
            <DecisionBadge decision={decision} label={DECISION_HEADINGS[decision]} />
          </div>
        )}
        {content}
        {reference && (
          <div className="small text-body-secondary mt-2">
            Reference <span className="fw-semibold">{reference}</span>
          </div>
        )}
      </div>
    </div>
  )
}
