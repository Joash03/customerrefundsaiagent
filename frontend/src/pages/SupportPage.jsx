import { Link } from 'react-router-dom'
import ChatPanel from '../components/chat/ChatPanel'

export default function SupportPage() {
  return (
    <div className="container py-5">
      <div className="row g-5">
        <div className="col-lg-8">
          <h1 className="h2 mb-2">Support chat</h1>
          <p className="text-body-secondary mb-4">Chat with our assistant about a refund or a problem with your order.</p>
          <div className="support-chat-card">
            <ChatPanel autoFocus />
          </div>
        </div>

        <aside className="col-lg-4 pt-lg-5" aria-label="Help with this chat">
          <div className="aside-block">
            <h2 className="h6 mb-2">Before you start</h2>
            <p className="text-body-secondary small mb-0">
              Have the email address you used at checkout and your order number (for example ORD-12345) from your confirmation email.
            </p>
          </div>
          <div className="aside-block">
            <h2 className="h6 mb-2">Your privacy</h2>
            <p className="text-body-secondary small mb-0">
              We only show order details once your email and order number match. Never share card details or passwords in the chat.
            </p>
          </div>
          <div className="aside-block">
            <h2 className="h6 mb-2">Refund rules</h2>
            <p className="text-body-secondary small mb-2">See what can be refunded and how long it takes.</p>
            <Link to="/refunds" className="small fw-medium">Read the refund policy</Link>
          </div>
        </aside>
      </div>
    </div>
  )
}
