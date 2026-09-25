import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react'
import { apiRequest, setUnauthorizedHandler, tokenStore } from '../api/client'

const AuthContext = createContext(null)

/**
 * Holds the admin session. The token lives in sessionStorage so it is
 * cleared when the tab closes, and an expired token signs the admin out.
 *
 * @param {{ children: import('react').ReactNode }} props
 */
export function AuthProvider({ children }) {
  const [token, setToken] = useState(tokenStore.get)

  const clearSession = useCallback(() => {
    tokenStore.clear()
    setToken(null)
  }, [])

  useEffect(() => {
    setUnauthorizedHandler(clearSession)
  }, [clearSession])

  const login = useCallback(async (email, password) => {
    const data = await apiRequest('/admin/login', { method: 'POST', body: { email, password } })
    tokenStore.set(data.token)
    setToken(data.token)
  }, [])

  const logout = useCallback(async () => {
    try {
      await apiRequest('/admin/logout', { method: 'POST' })
    } finally {
      clearSession()
    }
  }, [clearSession])

  const value = useMemo(
    () => ({ isAuthenticated: Boolean(token), login, logout }),
    [token, login, logout],
  )

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}

export function useAuth() {
  return useContext(AuthContext)
}
