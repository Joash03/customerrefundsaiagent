import { useCallback, useEffect, useState } from 'react'
import { apiRequest } from '../api/client'

/**
 * GET a resource with loading/error state; aborts in-flight requests when the path changes or on unmount.
 */
export function useApi(path) {
  const [state, setState] = useState({ data: null, loading: true, error: null })
  const [reloadKey, setReloadKey] = useState(0)

  useEffect(() => {
    const controller = new AbortController()
    setState((current) => ({ ...current, loading: true, error: null }))

    apiRequest(path, { signal: controller.signal })
      .then((data) => setState({ data, loading: false, error: null }))
      .catch((error) => {
        if (error.name !== 'AbortError') setState((current) => ({ ...current, loading: false, error }))
      })

    return () => controller.abort()
  }, [path, reloadKey])

  const reload = useCallback(() => setReloadKey((key) => key + 1), [])

  return { ...state, reload, setData: (data) => setState((current) => ({ ...current, data })) }
}
