import { useEffect, useRef } from 'react'
import { Link } from 'react-router-dom'
import ChatPanel from './ChatPanel'

/**
 * Floating support chat. Non-modal: the page stays usable, Escape closes it and focus returns to the launcher.
 *
 * @param {{ open: boolean, onOpenChange: (open: boolean) => void }} props
 */
export default function ChatWidget({ open, onOpenChange }) {
  const launcherRef = useRef(null)
  const wasOpen = useRef(open)

  useEffect(() => {
    if (wasOpen.current && !open) launcherRef.current?.focus()
    wasOpen.current = open
  }, [open])

  useEffect(() => {
    if (!open) return undefined
    const handleKeyDown = (event) => {
      if (event.key === 'Escape') onOpenChange(false)
    }
    document.addEventListener('keydown', handleKeyDown)
    return () => document.removeEventListener('keydown', handleKeyDown)
  }, [open, onOpenChange])

  return (
    <>
      {open && (
        <section id="chat-widget" className="chat-widget" role="dialog" aria-modal="false" aria-labelledby="chat-widget-title">
          <header className="chat-widget-header">
            <div>
              <h2 id="chat-widget-title" className="h6 mb-0">Support chat</h2>
              <span className="small opacity-75">Refunds and order problems</span>
            </div>
            <div className="d-flex gap-1">
              <Link to="/support" className="btn btn-sm btn-link text-white chat-icon-btn" aria-label="Open chat in full page" onClick={() => onOpenChange(false)}>
                <i className="bi bi-arrows-angle-expand" aria-hidden="true" />
              </Link>
              <button type="button" className="btn btn-sm btn-link text-white chat-icon-btn" aria-label="Close chat" onClick={() => onOpenChange(false)}>
                <i className="bi bi-x-lg" aria-hidden="true" />
              </button>
            </div>
          </header>
          <ChatPanel autoFocus />
        </section>
      )}

      <button
        ref={launcherRef}
        type="button"
        className="chat-launcher btn btn-primary"
        aria-expanded={open}
        aria-controls="chat-widget"
        onClick={() => onOpenChange(!open)}
      >
        <i className={`bi ${open ? 'bi-chevron-down' : 'bi-chat-dots-fill'}`} aria-hidden="true" />
        <span className="d-none d-sm-inline ms-2">{open ? 'Close' : 'Chat with us'}</span>
        <span className="visually-hidden d-sm-none">{open ? 'Close support chat' : 'Open support chat'}</span>
      </button>
    </>
  )
}
