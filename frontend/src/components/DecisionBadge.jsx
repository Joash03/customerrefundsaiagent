const VARIANTS = {
  approved: { label: 'Approved', className: 'text-bg-success', icon: 'bi-check-circle-fill' },
  denied: { label: 'Denied', className: 'text-bg-danger', icon: 'bi-x-circle-fill' },
  escalated: { label: 'Escalated', className: 'text-bg-warning', icon: 'bi-exclamation-triangle-fill' },
  awaiting: { label: 'Awaiting review', className: 'text-bg-warning', icon: 'bi-hourglass-split' },
}

/**
 * Decision status with icon and text, so meaning never relies on colour alone.
 *
 * @param {{ decision: 'approved'|'denied'|'escalated'|'awaiting', label?: string }} props
 */
export default function DecisionBadge({ decision, label }) {
  const variant = VARIANTS[decision] ?? VARIANTS.escalated

  return (
    <span className={`badge ${variant.className} d-inline-flex align-items-center gap-1`}>
      <i className={`bi ${variant.icon}`} aria-hidden="true" />
      {label ?? variant.label}
    </span>
  )
}
