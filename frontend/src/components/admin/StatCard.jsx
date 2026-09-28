/**
 * @param {{ label: string, value: number|undefined, icon: string, tone?: string }} props
 */
export default function StatCard({ label, value, icon, tone = 'primary' }) {
  return (
    <div className="card h-100">
      <div className="card-body d-flex align-items-center gap-3">
        <i className={`bi ${icon} fs-4 text-${tone}`} aria-hidden="true" />
        <div>
          <div className="small text-body-secondary">{label}</div>
          <div className="fs-4 fw-semibold text-tabular">{value ?? '—'}</div>
        </div>
      </div>
    </div>
  )
}
