import { demoScenarios } from '../data/demoScenarios'
import DecisionBadge from './DecisionBadge'

/**
 * Pre-fills the request form with a seeded customer, so every policy rule can be tried in one click.
 *
 * @param {{ onSelect: (scenario: object) => void, disabled: boolean }} props
 */
export default function DemoScenarioList({ onSelect, disabled }) {
  return (
    <section className="card" aria-labelledby="demo-heading">
      <div className="card-header bg-white">
        <h2 id="demo-heading" className="h6 mb-0">Try a demo scenario</h2>
        <p className="small text-body-secondary mb-0">Fills in a seeded customer, order and message.</p>
      </div>
      <div className="list-group list-group-flush">
        {demoScenarios.map((scenario) => (
          <button
            key={scenario.id}
            type="button"
            className="list-group-item list-group-item-action d-flex justify-content-between align-items-center gap-2 py-2"
            onClick={() => onSelect(scenario)}
            disabled={disabled}
          >
            <span>
              <span className="d-block small fw-semibold">{scenario.title}</span>
              <span className="d-block small text-body-secondary font-monospace">{scenario.orderNumber}</span>
            </span>
            <DecisionBadge decision={scenario.expected} label={`Expect ${scenario.expected}`} />
          </button>
        ))}
      </div>
    </section>
  )
}
