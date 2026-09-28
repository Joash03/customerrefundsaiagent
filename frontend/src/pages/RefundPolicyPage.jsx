import { useOutletContext } from 'react-router-dom'
import { useRefundPolicy } from '../hooks/useRefundPolicy'

export default function RefundPolicyPage() {
  const { openChat } = useOutletContext()
  const { windowDays, reviewLimit, error } = useRefundPolicy()

  const sections = [
    {
      id: 'window',
      title: 'Refund window',
      body: `You can request a refund within ${windowDays} days of your order being delivered. Requests made after this period cannot be refunded. Orders that have not been delivered yet can't be refunded until they arrive.`,
    },
    {
      id: 'eligible',
      title: 'What qualifies for a refund',
      list: [
        'Items that arrived damaged, defective or faulty.',
        'Incorrect items, such as the wrong product, size or colour.',
        `Items you have changed your mind about, requested within ${windowDays} days of delivery.`,
      ],
    },
    {
      id: 'final-sale',
      title: 'Final sale items',
      body: 'Items marked as final sale are not eligible for refunds. If a final sale item arrives damaged or incorrect, tell us and our support team will review it.',
    },
    {
      id: 'review',
      title: 'Requests reviewed by our team',
      body: 'Some requests are reviewed by a member of our support team before a decision is made. We aim to reply within 1–2 business days. This includes:',
      list: [
        `Refunds above ${reviewLimit}.`,
        'Parcels reported as not received when our courier has marked them as delivered.',
        'Requests with conflicting or unusual details, or a high number of recent refunds.',
        'Requests about an item that was previously reviewed and declined.',
      ],
    },
    {
      id: 'once',
      title: 'One refund per item',
      body: 'Each item can only be refunded once. If a request for an item is already being reviewed, please wait for that decision.',
    },
    {
      id: 'how',
      title: 'How refunds are paid',
      body: 'Approved refunds are returned to your original payment method within 5–7 business days.',
    },
  ]

  return (
    <div className="container py-5">
      <div className="row g-4">
        <nav className="col-lg-3 d-none d-lg-block pt-2" aria-label="On this page">
          <div className="policy-toc">
            <ul className="list-unstyled small">
              {sections.map((section) => <li key={section.id} className="mb-2"><a href={`#${section.id}`}>{section.title}</a></li>)}
            </ul>
          </div>
        </nav>

        <article className="col-lg-9 policy-article">
          <h1 className="display-6 fw-semibold mb-3">Refund policy</h1>
          <p className="lead text-body-secondary">We want you to be happy with your order. Here is how refunds work.</p>
          {error && <div className="alert alert-warning" role="alert">We couldn&apos;t load the latest policy values. Please refresh the page.</div>}

          {sections.map((section) => (
            <section key={section.id} id={section.id} className="policy-section" aria-labelledby={`${section.id}-heading`}>
              <h2 id={`${section.id}-heading`} className="h5">{section.title}</h2>
              {section.body && <p>{section.body}</p>}
              {section.list && <ul>{section.list.map((item) => <li key={item}>{item}</li>)}</ul>}
            </section>
          ))}

          <div className="policy-section">
            <div className="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
              <div>
                <h2 className="h5 mb-1">Ready to request a refund?</h2>
                <p className="text-body-secondary mb-0">Have your email address and order number ready.</p>
              </div>
              <div className="d-flex gap-2">
                <button type="button" className="btn btn-primary" onClick={openChat}>Start a chat</button>
              </div>
            </div>
          </div>
        </article>
      </div>
    </div>
  )
}
