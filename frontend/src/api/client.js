const TOKEN_KEY = 'refund-desk-token'

export class ApiError extends Error {
  constructor(message, status, errors = {}) {
    super(message)
    this.status = status
    this.errors = errors
  }
}

export const tokenStore = {
  get: () => sessionStorage.getItem(TOKEN_KEY),
  set: (token) => sessionStorage.setItem(TOKEN_KEY, token),
  clear: () => sessionStorage.removeItem(TOKEN_KEY),
}

let onUnauthorized = () => {}

export function setUnauthorizedHandler(handler) {
  onUnauthorized = handler
}

export async function apiRequest(path, { method = 'GET', body, signal } = {}) {
  const headers = { Accept: 'application/json' }
  const token = tokenStore.get()

  if (body !== undefined) headers['Content-Type'] = 'application/json'
  if (token) headers.Authorization = `Bearer ${token}`

  let response
  try {
    response = await fetch(`/api${path}`, {
      method,
      headers,
      signal,
      body: body === undefined ? undefined : JSON.stringify(body),
    })
  } catch (error) {
    if (error.name === 'AbortError') throw error
    throw new ApiError('Could not reach the server. Check your connection and try again.', 0)
  }

  if (response.status === 204) return null

  const data = await response.json().catch(() => ({}))

  if (!response.ok) {
    if (response.status === 401 && token) onUnauthorized()

    const message = response.status === 429
      ? 'Too many requests. Please wait a minute and try again.'
      : data.message ?? 'Something went wrong. Please try again.'
    throw new ApiError(message, response.status, data.errors ?? {})
  }

  return data
}
