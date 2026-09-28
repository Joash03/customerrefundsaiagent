import { Link, NavLink, Outlet } from 'react-router-dom'
import { useAuth } from '../../context/AuthContext'

export default function AdminLayout() {
  const { isAuthenticated, logout } = useAuth()

  return (
    <div className="d-flex flex-column min-vh-100">
      <a href="#main" className="skip-link btn btn-light">Skip to main content</a>
      <nav className="navbar navbar-expand navbar-dark bg-dark" aria-label="Support console">
        <div className="container">
          <NavLink to="/admin" end className="navbar-brand fw-semibold">
            <i className="bi bi-shield-check me-2" aria-hidden="true" />Support Console
          </NavLink>
          <ul className="navbar-nav ms-auto align-items-center gap-2">
            <li className="nav-item"><Link to="/" className="nav-link">View site</Link></li>
            {isAuthenticated && (
              <li className="nav-item">
                <button type="button" className="btn btn-outline-light btn-sm" onClick={logout}>Sign out</button>
              </li>
            )}
          </ul>
        </div>
      </nav>
      <main id="main" className="container py-4 flex-grow-1" tabIndex={-1}>
        <Outlet />
      </main>
    </div>
  )
}
