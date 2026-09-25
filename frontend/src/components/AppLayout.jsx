import { NavLink, Outlet } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'

export default function AppLayout() {
  const { isAuthenticated, logout } = useAuth()

  const navLinkClass = ({ isActive }) => `nav-link${isActive ? ' active fw-semibold' : ''}`

  return (
    <>
      <a href="#main" className="skip-link btn btn-light">Skip to main content</a>
      <nav className="navbar navbar-expand navbar-dark bg-primary" aria-label="Main">
        <div className="container">
          <NavLink to="/" className="navbar-brand fw-semibold">
            <i className="bi bi-arrow-counterclockwise me-2" aria-hidden="true" />
            Refund Desk
          </NavLink>
          <ul className="navbar-nav ms-auto align-items-center gap-1">
            <li className="nav-item">
              <NavLink to="/" end className={navLinkClass}>Customer support</NavLink>
            </li>
            <li className="nav-item">
              <NavLink to="/admin" className={navLinkClass}>Admin dashboard</NavLink>
            </li>
            {isAuthenticated && (
              <li className="nav-item ms-lg-2">
                <button type="button" className="btn btn-outline-light btn-sm" onClick={logout}>
                  Sign out
                </button>
              </li>
            )}
          </ul>
        </div>
      </nav>
      <main id="main" className="container py-4" tabIndex={-1}>
        <Outlet />
      </main>
    </>
  )
}
