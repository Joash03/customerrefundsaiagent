import { Link, useOutletContext } from 'react-router-dom'
import { useRefundPolicy } from '../hooks/useRefundPolicy'

const STEPS = [
  { icon: 'bi-person-vcard', title: 'Share your order details', text: 'Your email and order number let us find your order securely.' },
  { icon: 'bi-chat-left-text', title: 'Tell us what went wrong', text: 'Describe the problem in your own words. We may ask a quick follow-up question.' },
  { icon: 'bi-check2-circle', title: 'Get an answer', text: 'Most requests are decided in seconds. Anything unusual goes to our support team.' },
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
        <div className="container py-5">
          <div className="row">
            <div className="col-lg-8">
              <h1 className="display-6 fw-semibold mb-3">How can we help you today?</h1>
              <p className="lead mb-4">Get help with a damaged or wrong item, or request a refund. Our assistant can check your order and give you an answer in minutes.</p>
              <div className="d-flex flex-wrap gap-2">
                <button type="button" className="btn btn-light btn-lg" onClick={openChat}>
                  <i className="bi bi-chat-dots-fill me-2" aria-hidden="true" />Chat with support
                </button>
                <Link to="/refunds" className="btn btn-outline-light btn-lg">Refund policy</Link>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section className="bg-white border-bottom" aria-labelledby="steps-heading">
        <div className="container py-5">
          <h2 id="steps-heading" className="h4 mb-4">How refunds work</h2>
          <ol className="row g-4 list-unstyled mb-0">
            {STEPS.map((step, index) => (
              <li className="col-md-4" key={step.title}>
                <div className="d-flex gap-3">
                  <span className="step-number" aria-hidden="true">{index + 1}</span>
                  <div>
                    <h3 className="h6 mb-1"><i className={`bi ${step.icon} me-2 text-primary`} aria-hidden="true" />{step.title}</h3>
                    <p className="small text-body-secondary mb-0">{step.text}</p>
                  </div>
                </div>
              </li>
            ))}
          </ol>
        </div>
      </section>

      <section className="container py-5" aria-labelledby="faq-heading">
        <div className="row g-4">
          <div className="col-lg-4">
            <h2 id="faq-heading" className="h4">Frequently asked questions</h2>
            <p className="text-body-secondary">Can&apos;t find your answer? Our assistant is available any time.</p>
            <button type="button" className="btn btn-primary" onClick={openChat}>Ask support</button>
          </div>
          <div className="col-lg-8">
            {faqs.map((faq) => (
              <details className="faq-item" key={faq.q}>
                <summary>{faq.q}</summary>
                <p className="mb-0 text-body-secondary">{faq.a}</p>
              </details>
            ))}
          </div>
        </div>
      </section>
    </>
  )
}
