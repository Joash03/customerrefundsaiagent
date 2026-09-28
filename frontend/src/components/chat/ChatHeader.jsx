import { useChat } from '../../context/ChatContext'

/**
 * Title bar shared by the widget and the support page, with the "New chat" action.
 *
 * @param {{ titleId: string, children?: import('react').ReactNode }} props  children: extra actions (e.g. expand/close).
 */
export default function ChatHeader({ titleId, children }) {
  const { status, startNew } = useChat()
  const busy = status === 'loading' || status === 'sending'

  return (
    <header className="chat-header">
      <div className="d-flex align-items-center gap-2 min-w-0">
        <span className="chat-avatar" aria-hidden="true"><i className="bi bi-headset" /></span>
        <div className="min-w-0">
          <h2 id={titleId} className="h6 mb-0 text-truncate">Support</h2>
          <span className="d-block small text-body-secondary text-truncate">Replies in seconds</span>
        </div>
      </div>
      <div className="d-flex align-items-center gap-1">
        <button type="button" className="btn btn-outline-primary btn-sm chat-new-btn" onClick={startNew} disabled={busy}>
          <i className="bi bi-plus-lg" aria-hidden="true" />
          New chat
        </button>
        {children}
      </div>
    </header>
  )
}
