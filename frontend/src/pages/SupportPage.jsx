import { Link } from 'react-router-dom'
import ChatPanel from '../components/chat/ChatPanel'

export default function SupportPage() {
  return (
    <div className="container py-4 py-lg-5">
      <div className="row g-4">
        <div className="col-lg-8">
          <h1 className="h3 mb-1">Support chat</h1>
          <p className="text-body-secondary mb-3">Chat with our assistant about a refund or a problem with your order.</p>
          <div className="card support-chat-card">
            <ChatPanel autoFocus />
          </div>
        </div>

        <aside className="col-lg-4" aria-labelledby="before-heading">
          <div className="card mb-3">
            <div className="card-body">
              <h2 id="before-heading" className="h6">Before you start</h2>
              <ul className="small ps-3 mb-0">
                <li className="mb-2">The <strong>email address</strong> you used at checkout</li>
                <li className="mb-2">Your <strong>order number</strong>, e.g. ORD-12345, from your confirmation email</li>
                <li>A short description of what went wrong</li>
              </ul>
            </div>
          </div>
          <div className="card mb-3">
            <div className="card-body">
              <h2 className="h6"><i className="bi bi-shield-lock me-2 text-primary" aria-hidden="true" />Your privacy</h2>
              <p className="small text-body-secondary mb-0">We only show order details after your email and order number match. Please don&apos;t share card details or passwords in the chat.</p>
            </div>
          </div>
          <div className="card">
            <div className="card-body">
              <h2 className="h6">Refund rules</h2>
              <p className="small text-body-secondary">Check what can be refunded and how long it takes.</p>
              <Link to="/refunds" className="btn btn-outline-primary btn-sm">Read the refund policy</Link>
            </div>
          </div>
        </aside>
      </div>
    </div>
  )
}
