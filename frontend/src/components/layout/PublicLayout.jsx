import { useCallback, useState } from 'react'
import { Link, NavLink, Outlet, useLocation } from 'react-router-dom'
import { SITE_NAME } from '../../config/site'
import ChatWidget from '../chat/ChatWidget'

const navLinkClass = ({ isActive }) => `nav-link px-3${isActive ? ' active' : ''}`

export default function PublicLayout() {
  const { pathname } = useLocation()
  const [chatOpen, setChatOpen] = useState(false)
  const openChat = useCallback(() => setChatOpen(true), [])

  // The support page is the chat itself, so the floating widget would duplicate it.
  const showWidget = pathname !== '/support'

  return (
    <div className="d-flex flex-column min-vh-100">
      <a href="#main" className="skip-link btn btn-light">Skip to main content</a>

      <header className="site-header">
        <nav className="navbar navbar-expand container" aria-label="Main">
          <Link to="/" className="navbar-brand d-flex align-items-center gap-2">
            <span className="brand-mark" aria-hidden="true"><i className="bi bi-bag-check-fill" /></span>
            <span className="fw-semibold">{SITE_NAME}</span>
          </Link>
          <ul className="navbar-nav ms-auto">
            <li className="nav-item"><NavLink to="/support" className={navLinkClass}>Support</NavLink></li>
            <li className="nav-item"><NavLink to="/refunds" className={navLinkClass}>Refunds</NavLink></li>
          </ul>
        </nav>
      </header>

      <main id="main" className="flex-grow-1" tabIndex={-1}>
        <Outlet context={{ openChat }} />
      </main>

      <footer className="site-footer">
        <div className="container d-flex flex-column flex-sm-row justify-content-between gap-2 small">
          <span>© {new Date().getFullYear()} {SITE_NAME}. Help with orders, returns and refunds.</span>
          <nav aria-label="Footer" className="d-flex gap-3">
            <Link to="/refunds">Refund policy</Link>
            <Link to="/admin">Staff login</Link>
          </nav>
        </div>
      </footer>

      {showWidget && <ChatWidget open={chatOpen} onOpenChange={setChatOpen} />}
    </div>
  )
}
