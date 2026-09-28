import { Link, NavLink, Outlet } from 'react-router-dom'
import { useAuth } from '../../context/AuthContext'

export default function AdminLayout() {
  const { isAuthenticated, logout } = useAuth()

  return (
    <div className="d-flex flex-column min-vh-100">
      <a href="#main" className="skip-link btn btn-light">Skip to main content</a>
      <header className="admin-header">
      <nav className="navbar navbar-expand container" aria-label="Support console">
          <NavLink to="/admin" end className="navbar-brand fw-semibold d-flex align-items-center">
            <span className="brand-mark me-2" aria-hidden="true"><i className="bi bi-shield-check" /></span>Support Console
          </NavLink>
          <ul className="navbar-nav ms-auto align-items-center gap-2">
            <li className="nav-item"><Link to="/" className="nav-link">View site</Link></li>
            {isAuthenticated && (
              <li className="nav-item">
                <button type="button" className="btn btn-outline-primary btn-sm" onClick={logout}>Sign out</button>
              </li>
            )}
          </ul>
      </nav>
      </header>
      <main id="main" className="container py-4 flex-grow-1" tabIndex={-1}>
        <Outlet />
      </main>
    </div>
  )
}
