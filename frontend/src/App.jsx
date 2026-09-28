import { lazy, Suspense } from 'react'
import { Link, Route, Routes } from 'react-router-dom'
import AdminLayout from './components/layout/AdminLayout'
import PublicLayout from './components/layout/PublicLayout'
import RequireAuth from './components/RequireAuth'
import HomePage from './pages/HomePage'
import RefundPolicyPage from './pages/RefundPolicyPage'
import SupportPage from './pages/SupportPage'

const AdminLoginPage = lazy(() => import('./pages/admin/AdminLoginPage'))
const AdminDashboardPage = lazy(() => import('./pages/admin/AdminDashboardPage'))
const RefundRequestDetailPage = lazy(() => import('./pages/admin/RefundRequestDetailPage'))

function PageLoader() {
  return (
    <p className="container py-4 text-body-secondary">
      <span className="spinner-border spinner-border-sm me-2" aria-hidden="true" />Loading…
    </p>
  )
}

function NotFound() {
  return (
    <div className="container py-5">
      <h1 className="h3">Page not found</h1>
      <p className="text-body-secondary">The page you are looking for doesn&apos;t exist.</p>
      <Link to="/" className="btn btn-primary">Back to Support Center</Link>
    </div>
  )
}

export default function App() {
  return (
    <Suspense fallback={<PageLoader />}>
      <Routes>
        <Route element={<PublicLayout />}>
          <Route index element={<HomePage />} />
          <Route path="support" element={<SupportPage />} />
          <Route path="refunds" element={<RefundPolicyPage />} />
          <Route path="*" element={<NotFound />} />
        </Route>
        <Route path="admin" element={<AdminLayout />}>
          <Route path="login" element={<AdminLoginPage />} />
          <Route element={<RequireAuth />}>
            <Route index element={<AdminDashboardPage />} />
            <Route path="requests/:id" element={<RefundRequestDetailPage />} />
          </Route>
        </Route>
      </Routes>
    </Suspense>
  )
}
