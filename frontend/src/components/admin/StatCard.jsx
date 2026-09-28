/**
 * @param {{ label: string, value: number|undefined, icon: string }} props
 */
export default function StatCard({ label, value, icon }) {
  return (
    <div className="stat-card">
      <div className="d-flex justify-content-between align-items-center small text-body-secondary mb-1">
        {label}
        <i className={`bi ${icon}`} aria-hidden="true" />
      </div>
      <div className="fs-3 fw-semibold text-tabular">{value ?? '—'}</div>
    </div>
  )
}
