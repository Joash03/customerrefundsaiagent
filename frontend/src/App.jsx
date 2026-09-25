import { lazy, Suspense } from 'react'
import { Route, Routes } from 'react-router-dom'
import AppLayout from './components/AppLayout'
import RequireAuth from './components/RequireAuth'
import CustomerSupportPage from './pages/CustomerSupportPage'

const AdminLoginPage = lazy(() => import('./pages/AdminLoginPage'))
const AdminDashboardPage = lazy(() => import('./pages/AdminDashboardPage'))
const RefundRequestDetailPage = lazy(() => import('./pages/RefundRequestDetailPage'))

function PageLoader() {
  return <p className="text-body-secondary"><span className="spinner-border spinner-border-sm me-2" aria-hidden="true" />Loading…</p>
}

function NotFound() {
  return <p>Page not found.</p>
}

export default function App() {
  return (
    <Suspense fallback={<PageLoader />}>
      <Routes>
        <Route element={<AppLayout />}>
          <Route index element={<CustomerSupportPage />} />
          <Route path="admin/login" element={<AdminLoginPage />} />
          <Route path="admin" element={<RequireAuth />}>
            <Route index element={<AdminDashboardPage />} />
            <Route path="requests/:id" element={<RefundRequestDetailPage />} />
          </Route>
          <Route path="*" element={<NotFound />} />
        </Route>
      </Routes>
    </Suspense>
  )
}
