import { useEffect, useRef, useState } from 'react'
import { ApiError, apiRequest } from '../api/client'
import ChatMessage from '../components/ChatMessage'
import DemoScenarioList from '../components/DemoScenarioList'

const MAX_MESSAGE_LENGTH = 2000

const WELCOME = {
  id: 'welcome',
  role: 'assistant',
  text: 'Hi! Enter the email and order number you used at checkout, then tell us what went wrong with your order.',
}

export default function CustomerSupportPage() {
  const [form, setForm] = useState({ email: '', orderNumber: '', message: '' })
  const [fieldErrors, setFieldErrors] = useState({})
  const [messages, setMessages] = useState([WELCOME])
  const [submitting, setSubmitting] = useState(false)
  const threadRef = useRef(null)
  const emailRef = useRef(null)
  const orderNumberRef = useRef(null)
  const messageRef = useRef(null)

  useEffect(() => {
    threadRef.current?.scrollTo({ top: threadRef.current.scrollHeight })
  }, [messages, submitting])

  const addMessage = (message) => setMessages((current) => [...current, { id: crypto.randomUUID(), ...message }])

  const handleChange = (event) => {
    const { name, value } = event.target
    setForm((current) => ({ ...current, [name]: value }))
  }

  const handleScenario = (scenario) => {
    setForm({ email: scenario.email, orderNumber: scenario.orderNumber, message: scenario.message })
    setFieldErrors({})
    messageRef.current?.focus()
  }

  const handleSubmit = async (event) => {
    event.preventDefault()
    const message = form.message.trim()
    if (!message || submitting) return

    setSubmitting(true)
    setFieldErrors({})
    addMessage({ role: 'customer', text: message })

    try {
      const { data } = await apiRequest('/refund-requests', {
        method: 'POST',
        body: { email: form.email.trim(), order_number: form.orderNumber.trim(), message },
      })
      addMessage({ role: 'assistant', text: data.reply, decision: data.decision, reference: data.reference })
      setForm((current) => ({ ...current, message: '' }))
    } catch (error) {
      const errors = error instanceof ApiError ? error.errors : {}
      setFieldErrors(errors)
      addMessage({ role: 'error', text: Object.values(errors)[0]?.[0] ?? error.message })
      const fieldRefs = { email: emailRef, order_number: orderNumberRef, message: messageRef }
      const firstInvalid = Object.keys(fieldRefs).find((key) => errors[key])
      if (firstInvalid) fieldRefs[firstInvalid].current?.focus()
    } finally {
      setSubmitting(false)
    }
  }

  const fieldClass = (name) => `form-control${fieldErrors[name] ? ' is-invalid' : ''}`

  return (
    <div className="row g-4">
      <div className="col-lg-8">
        <h1 className="h3 mb-1">Request a refund</h1>
        <p className="text-body-secondary">Describe the problem in your own words. You&apos;ll get a decision in a few seconds.</p>

        <form className="card" onSubmit={handleSubmit} noValidate>
          <div className="card-header bg-white">
            <div className="row g-3">
              <div className="col-sm-7">
                <label htmlFor="email" className="form-label small fw-semibold">Email address</label>
                <input
                  ref={emailRef}
                  id="email"
                  name="email"
                  type="email"
                  autoComplete="email"
                  required
                  className={fieldClass('email')}
                  value={form.email}
                  onChange={handleChange}
                  aria-describedby={fieldErrors.email ? 'email-error' : undefined}
                />
                {fieldErrors.email && <div id="email-error" className="invalid-feedback">{fieldErrors.email[0]}</div>}
              </div>
              <div className="col-sm-5">
                <label htmlFor="orderNumber" className="form-label small fw-semibold">Order number</label>
                <input
                  ref={orderNumberRef}
                  id="orderNumber"
                  name="orderNumber"
                  type="text"
                  placeholder="ORD-10001"
                  required
                  className={`${fieldClass('order_number')} font-monospace`}
                  value={form.orderNumber}
                  onChange={handleChange}
                  aria-describedby={fieldErrors.order_number ? 'order-error' : undefined}
                />
                {fieldErrors.order_number && <div id="order-error" className="invalid-feedback">{fieldErrors.order_number[0]}</div>}
              </div>
            </div>
          </div>

          <div ref={threadRef} className="card-body chat-thread" role="log" aria-live="polite" aria-label="Conversation">
            {messages.map((message) => <ChatMessage key={message.id} message={message} />)}
            {submitting && (
              <div className="d-flex align-items-center gap-2 text-body-secondary small">
                <span className="spinner-border spinner-border-sm" aria-hidden="true" />
                Reviewing your request against our refund policy…
              </div>
            )}
          </div>

          <div className="card-footer bg-white">
            <label htmlFor="message" className="form-label small fw-semibold">What went wrong?</label>
            <textarea
              ref={messageRef}
              id="message"
              name="message"
              rows={3}
              required
              maxLength={MAX_MESSAGE_LENGTH}
              className={fieldClass('message')}
              value={form.message}
              onChange={handleChange}
              aria-describedby="message-help"
            />
            <div className="d-flex justify-content-between align-items-center mt-2 gap-2">
              <span id="message-help" className="small text-body-secondary">
                {fieldErrors.message?.[0] ?? `${form.message.length}/${MAX_MESSAGE_LENGTH} characters`}
              </span>
              <button type="submit" className="btn btn-primary" disabled={submitting || !form.message.trim()}>
                {submitting ? 'Sending…' : 'Send request'}
              </button>
            </div>
          </div>
        </form>
      </div>

      <div className="col-lg-4">
        <DemoScenarioList onSelect={handleScenario} disabled={submitting} />
      </div>
    </div>
  )
}
