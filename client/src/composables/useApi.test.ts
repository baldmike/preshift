/**
 * useApi.test.ts
 *
 * Unit tests for the useApi composable (Axios instance factory).
 *
 * These tests verify:
 *  1. The default export is a configured Axios instance with JSON headers.
 *  2. `useApi()` returns the same singleton instance as the default export.
 *  3. The request interceptor attaches a Bearer token from localStorage.
 *  4. The request interceptor omits Authorization when no token is stored.
 *  5. The response interceptor clears the token and redirects on 401 errors.
 *  6. Non-401 errors are passed through without side effects.
 *
 * We mock `axios`, `localStorage`, and `@/router` to isolate Axios behaviour
 * from the network and Vue Router.
 */

import { describe, it, expect, vi, beforeEach } from 'vitest'

/* Capture the interceptor callbacks registered during module load */
let requestInterceptor: (config: any) => any
let responseSuccessInterceptor: (response: any) => any
let responseErrorInterceptor: (error: any) => any

const mockPush = vi.fn()

/* Mock Vue Router so the 401 interceptor can call router.push */
vi.mock('@/router', () => ({
  default: { push: mockPush },
}))

/* Mock axios.create to return a fake instance that captures interceptors */
vi.mock('axios', () => {
  const instance = {
    interceptors: {
      request: {
        use: vi.fn((fn: any) => {
          requestInterceptor = fn
        }),
      },
      response: {
        use: vi.fn((success: any, error: any) => {
          responseSuccessInterceptor = success
          responseErrorInterceptor = error
        }),
      },
    },
    defaults: { headers: { common: {} } },
    get: vi.fn(),
    post: vi.fn(),
    patch: vi.fn(),
    delete: vi.fn(),
  }
  return {
    default: {
      create: vi.fn(() => instance),
    },
  }
})

describe('useApi composable', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    localStorage.clear()
  })

  /* Verifies that the module-level Axios instance is exported as default
     and that useApi() returns the same object, ensuring a singleton pattern. */
  it('returns the same Axios instance from useApi() and the default export', async () => {
    const mod = await import('@/composables/useApi')
    const api = mod.default
    const composableResult = mod.useApi()
    expect(composableResult).toBe(api)
  })

  /* Verifies the request interceptor reads a stored token from localStorage
     and attaches it as a Bearer Authorization header. */
  it('attaches Bearer token from localStorage on outgoing requests', () => {
    localStorage.setItem('preshift_token', 'test-token-123')
    const config = { headers: {} as Record<string, string> }

    const result = requestInterceptor(config)

    expect(result.headers.Authorization).toBe('Bearer test-token-123')
  })

  /* Verifies that when no token exists in localStorage, the Authorization
     header is simply not set (undefined), allowing unauthenticated requests. */
  it('omits Authorization header when no token is stored', () => {
    const config = { headers: {} as Record<string, string> }

    const result = requestInterceptor(config)

    expect(result.headers.Authorization).toBeUndefined()
  })

  /* Verifies the response success interceptor passes responses through unchanged. */
  it('passes successful responses through unchanged', () => {
    const response = { status: 200, data: { ok: true } }

    const result = responseSuccessInterceptor(response)

    expect(result).toBe(response)
  })

  /* Verifies that a 401 error triggers token removal from localStorage
     and a redirect to the login page via Vue Router. */
  it('clears token and redirects to /login on 401 response', async () => {
    localStorage.setItem('preshift_token', 'stale-token')
    const error = { response: { status: 401 } }

    await expect(responseErrorInterceptor(error)).rejects.toBe(error)

    expect(localStorage.getItem('preshift_token')).toBeNull()
    expect(mockPush).toHaveBeenCalledWith('/login')
  })

  /* Verifies that non-401 errors are rejected without clearing the token
     or triggering a redirect, so calling code can handle them normally. */
  it('rejects non-401 errors without side effects', async () => {
    localStorage.setItem('preshift_token', 'valid-token')
    const error = { response: { status: 500 } }

    await expect(responseErrorInterceptor(error)).rejects.toBe(error)

    expect(localStorage.getItem('preshift_token')).toBe('valid-token')
    expect(mockPush).not.toHaveBeenCalled()
  })
})
