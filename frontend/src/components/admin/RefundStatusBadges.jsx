import DecisionBadge from '../DecisionBadge'

/**
 * Shows the system decision and, for escalations, the review outcome.
 *
 * @param {{ request: { decision: string, final_decision: string|null, awaiting_review: boolean } }} props
 */
export default function RefundStatusBadges({ request }) {
  const { decision, final_decision: finalDecision, awaiting_review: awaitingReview } = request

  return (
    <span className="d-inline-flex flex-wrap gap-1">
      <DecisionBadge decision={decision} />
      {awaitingReview && <DecisionBadge decision="awaiting" />}
      {decision === 'escalated' && finalDecision && (
        <DecisionBadge decision={finalDecision} label={`Reviewed: ${finalDecision}`} />
      )}
    </span>
  )
}
