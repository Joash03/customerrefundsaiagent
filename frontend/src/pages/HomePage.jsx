import { Link, useOutletContext } from 'react-router-dom'
import ChatMessage from '../components/chat/ChatMessage'
import { useRefundPolicy } from '../hooks/useRefundPolicy'

const STEPS = [
  { title: 'Share your order details', text: 'Your email and order number let us find your order securely.' },
  { title: 'Tell us what went wrong', text: 'Describe the problem in your own words. We may ask a quick follow-up question.' },
  { title: 'Get an answer', text: 'Most requests are decided straight away. Anything unusual goes to our support team.' },
]

const PREVIEW = [
  { id: 1, role: 'customer', content: 'My headphones arrived with a cracked ear cup.' },
  { id: 2, role: 'assistant', content: "Just to confirm: you'd like a refund for the Wireless Headphones because they arrived damaged. Is that right?" },
  { id: 3, role: 'customer', content: 'Yes' },
  { id: 4, role: 'assistant', decision: 'approved', content: 'Your refund has been approved and will reach your original payment method within 5–7 business days.' },
]

export default function HomePage() {
  const { openChat } = useOutletContext()
  const { windowDays, reviewLimit } = useRefundPolicy()

  const faqs = [
    { q: 'How long do I have to request a refund?', a: `You can request a refund within ${windowDays} days of delivery.` },
    { q: 'Can I get a refund on a final sale item?', a: 'Final sale items are not refundable. If a final sale item arrived damaged or incorrect, our support team will review it.' },
    { q: 'Why was my request sent for review?', a: `Refunds above ${reviewLimit}, and requests that need a closer look, are reviewed by a member of our team, usually within 1–2 business days.` },
    { q: 'What do I need before I start?', a: 'The email address you used at checkout and your order number (it looks like ORD-12345), both in your confirmation email.' },
  ]

  return (
    <>
      <section className="hero">
        <div className="container">
          <div className="row g-5 align-items-center">
            <div className="col-lg-6">
              <h1 className="mb-4">Help with your order, in one conversation.</h1>
              <p className="lead mb-4">
                Tell us what went wrong with a delivery. We check your order against our refund policy and give you an answer in minutes.
              </p>
              <div className="d-flex flex-wrap align-items-center gap-3">
                <button type="button" className="btn btn-primary btn-lg" onClick={openChat}>Start a chat</button>
                <Link to="/refunds" className="fw-medium">Read the refund policy</Link>
              </div>
            </div>
            <div className="col-lg-6">
              <figure className="hero-preview mb-0" aria-label="Example support conversation">
                <figcaption className="hero-preview-caption">Example conversation</figcaption>
                {PREVIEW.map((message) => <ChatMessage key={message.id} message={message} />)}
              </figure>
            </div>
          </div>
        </div>
      </section>

      <section className="section" aria-labelledby="steps-heading">
        <div className="container">
          <h2 id="steps-heading" className="h3 mb-5">How refunds work</h2>
          <ol className="row g-5 list-unstyled mb-0">
            {STEPS.map((step, index) => (
              <li className="col-md-4" key={step.title}>
                <span className="step-number" aria-hidden="true">{index + 1}</span>
                <h3 className="h6 mb-2">{step.title}</h3>
                <p className="text-body-secondary mb-0">{step.text}</p>
              </li>
            ))}
          </ol>
        </div>
      </section>

      <section className="section" aria-labelledby="faq-heading">
        <div className="container">
          <div className="row g-5">
            <div className="col-lg-4">
              <h2 id="faq-heading" className="h3 mb-3">Questions</h2>
              <p className="text-body-secondary mb-4">Can&apos;t find your answer? Our assistant is available any time.</p>
              <button type="button" className="btn btn-outline-primary" onClick={openChat}>Ask support</button>
            </div>
            <div className="col-lg-8">
              <div className="faq-list">
                {faqs.map((faq) => (
                  <details className="faq-item" key={faq.q}>
                    <summary>{faq.q}</summary>
                    <p className="mb-0 text-body-secondary">{faq.a}</p>
                  </details>
                ))}
              </div>
            </div>
          </div>
        </div>
      </section>
    </>
  )
}
