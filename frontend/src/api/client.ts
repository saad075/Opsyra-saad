import axios, { AxiosError } from 'axios'

/**
 * One axios instance for the whole app. The bearer token is attached here,
 * and two API-wide answers are handled once rather than in every screen:
 *
 * - 401: the token is gone (logged out elsewhere, password reset) → forget
 *   it and go to login.
 * - 403 with password_change_required: the account holds a temporary
 *   password → the change-password screen (see EnsureAccountIsUsable).
 *
 * Handlers are registered by main.ts so this file doesn't import the
 * router or stores (avoids circular imports).
 */
export const TOKEN_KEY = 'opsyra.token'

export const api = axios.create({
  baseURL: '/api',
  headers: { Accept: 'application/json' },
})

api.interceptors.request.use((config) => {
  const token = readToken()
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

type Handlers = { onUnauthenticated: () => void; onPasswordChangeRequired: () => void }

export function installAuthHandlers(handlers: Handlers): void {
  api.interceptors.response.use(undefined, (error: AxiosError<{ password_change_required?: boolean }>) => {
    const status = error.response?.status
    if (status === 401) handlers.onUnauthenticated()
    if (status === 403 && error.response?.data?.password_change_required) handlers.onPasswordChangeRequired()
    return Promise.reject(error)
  })
}

// localStorage can be unavailable (private mode, blocked storage).
export function readToken(): string | null {
  try {
    return localStorage.getItem(TOKEN_KEY)
  } catch {
    return null
  }
}

export function writeToken(token: string | null): void {
  try {
    if (token) localStorage.setItem(TOKEN_KEY, token)
    else localStorage.removeItem(TOKEN_KEY)
  } catch {
    // Session still works for this tab via the in-memory store.
  }
}

/**
 * The message to show for a failed request: the first validation error,
 * else the API's message, else a generic fallback.
 */
export function errorMessage(error: unknown, fallback = 'Something went wrong. Please try again.'): string {
  if (axios.isAxiosError(error)) {
    const data = error.response?.data as { message?: string; errors?: Record<string, string[]> } | undefined
    const firstError = data?.errors ? Object.values(data.errors)[0]?.[0] : undefined
    if (firstError) return firstError
    if (data?.message) return data.message
    if (!error.response) return 'Cannot reach the server. Check your connection.'
  }
  return fallback
}

/** Field-level validation errors from a 422, keyed by field name. */
export function fieldErrors(error: unknown): Record<string, string> {
  if (!axios.isAxiosError(error) || error.response?.status !== 422) return {}
  const errors = (error.response.data as { errors?: Record<string, string[]> }).errors ?? {}
  return Object.fromEntries(Object.entries(errors).map(([field, messages]) => [field, messages[0] ?? '']))
}
