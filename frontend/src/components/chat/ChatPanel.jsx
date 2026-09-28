import { useEffect, useRef, useState } from 'react'
import { useChat } from '../../context/ChatContext'
import ChatMessage from './ChatMessage'

const MAX_LENGTH = 2000

/**
 * Conversation thread, quick replies and composer. Used by the widget and the full support page.
 *
 * @param {{ autoFocus?: boolean }} props
 */
export default function ChatPanel({ autoFocus = false }) {
  const { conversation, status, error, ensureStarted, send, startNew } = useChat()
  const [draft, setDraft] = useState('')
  const threadRef = useRef(null)
  const inputRef = useRef(null)

  const sending = status === 'sending'
  const handedOff = conversation.stage === 'handed_off'
  const lastMessage = conversation.messages.at(-1)
  const quickReplies = !sending && lastMessage?.role === 'assistant' ? lastMessage.quick_replies ?? [] : []

  useEffect(() => {
    ensureStarted()
  }, [ensureStarted])

  useEffect(() => {
    threadRef.current?.scrollTo({ top: threadRef.current.scrollHeight })
  }, [conversation.messages.length, sending])

  useEffect(() => {
    if (autoFocus && status === 'ready') inputRef.current?.focus()
  }, [autoFocus, status])

  const submit = async (text) => {
    if (sending || !text.trim()) return
    setDraft('')
    await send(text)
    inputRef.current?.focus()
  }

  const handleSubmit = (event) => {
    event.preventDefault()
    submit(draft)
  }

  const handleKeyDown = (event) => {
    if (event.key === 'Enter' && !event.shiftKey) {
      event.preventDefault()
      submit(draft)
    }
  }

  return (
    <div className="chat-panel">
      <div ref={threadRef} className="chat-thread" role="log" aria-live="polite" aria-relevant="additions" aria-label="Conversation">
        {status === 'loading' && conversation.messages.length === 0 && (
          <p className="text-body-secondary small">
            <span className="spinner-border spinner-border-sm me-2" aria-hidden="true" />Connecting you to support…
          </p>
        )}
        {conversation.messages.map((message) => <ChatMessage key={message.id} message={message} />)}
        {sending && (
          <div className="d-flex align-items-center gap-2 text-body-secondary small mb-2">
            <span className="spinner-border spinner-border-sm" aria-hidden="true" />
            Support assistant is typing…
          </div>
        )}
      </div>

      {quickReplies.length > 0 && (
        <div className="chat-quick-replies" role="group" aria-label="Suggested replies">
          {quickReplies.map((reply) => (
            <button key={reply} type="button" className="btn btn-outline-primary btn-sm" onClick={() => submit(reply)}>
              {reply}
            </button>
          ))}
        </div>
      )}

      {error && (
        <div className="alert alert-danger py-2 px-3 mx-3 mb-2 small rounded-3" role="alert">
          {error}
          {status === 'error' && <button type="button" className="btn btn-link btn-sm p-0 ms-2 align-baseline" onClick={startNew}>Try again</button>}
        </div>
      )}

      {handedOff ? (
        <div className="chat-composer text-center">
          <p className="small text-body-secondary mb-2">This conversation has been passed to our support team.</p>
          <button type="button" className="btn btn-primary btn-sm" onClick={startNew}>Start a new chat</button>
        </div>
      ) : (
        <form className="chat-composer" onSubmit={handleSubmit}>
          <label htmlFor="chat-input" className="visually-hidden">Type your message</label>
          <div className="chat-input-group">
            <textarea
              ref={inputRef}
              id="chat-input"
              rows={1}
              maxLength={MAX_LENGTH}
              placeholder="Type your message…"
              value={draft}
              onChange={(event) => setDraft(event.target.value)}
              onKeyDown={handleKeyDown}
              disabled={!conversation.id}
            />
            <button type="submit" className="btn btn-primary chat-send" disabled={sending || !draft.trim()} aria-label="Send message">
              <i className="bi bi-send-fill" aria-hidden="true" />
            </button>
          </div>
          <p className="chat-hint mt-2 mb-0 ms-3">Press Enter to send, Shift + Enter for a new line</p>
        </form>
      )}
    </div>
  )
}
