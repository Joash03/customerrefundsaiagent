const VARIANTS = {
  approved: { label: 'Approved', icon: 'bi-check-circle-fill' },
  denied: { label: 'Denied', icon: 'bi-x-circle-fill' },
  escalated: { label: 'Escalated', icon: 'bi-exclamation-circle-fill' },
  awaiting: { label: 'Awaiting review', icon: 'bi-hourglass-split' },
}

/**
 * Decision status with icon and text, so meaning never relies on colour alone.
 *
 * @param {{ decision: 'approved'|'denied'|'escalated'|'awaiting', label?: string }} props
 */
export default function DecisionBadge({ decision, label }) {
  const key = VARIANTS[decision] ? decision : 'escalated'
  const variant = VARIANTS[key]

  return (
    <span className={`status-badge status-${key}`}>
      <i className={`bi ${variant.icon}`} aria-hidden="true" />
      {label ?? variant.label}
    </span>
  )
}
