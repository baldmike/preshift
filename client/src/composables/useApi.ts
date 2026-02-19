/**
 * composables/useApi.ts
 *
 * Creates and exports a pre-configured Axios instance used for all HTTP
 * communication with the Laravel backend API.
 *
 * Key responsibilities:
 *  1. Sets default JSON headers so every request is sent/received as JSON.
 *  2. Attaches the Bearer token from localStorage on every outgoing request
 *     via a request interceptor.
 *  3. Handles 401 (Unauthorized) responses globally via a response
 *     interceptor -- if the API says the token is invalid or expired,
 *     the token is cleared and the user is redirected to `/login`.
 *
 * Usage:
 *   - Import the default export (`api`) directly for use outside of Vue
 *     components (e.g. inside Pinia stores).
 *   - Call `useApi()` inside `<script setup>` to get the same singleton
 *     instance in a composable-friendly way.
 */

import axios from 'axios'
import router from '@/router'

// Create a shared Axios instance with sensible defaults.
// `baseURL` is left empty so requests use the current origin (the Vite dev
// server proxies `/api/*` to Laravel during development).
const api = axios.create({
  baseURL: '',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
})

/**
 * REQUEST INTERCEPTOR -- runs before every outgoing request.
 * Reads the persisted auth token from localStorage and attaches it as a
 * Bearer token in the Authorization header.  If no token exists the header
 * is simply omitted, which will cause protected endpoints to return 401.
 */
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('preshift_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

/**
 * RESPONSE INTERCEPTOR -- runs after every response (including errors).
 * On success: passes the response through unchanged.
 * On 401 error: the token is stale or invalid, so we remove it from
 * localStorage and redirect the user to the login page.  The original
 * error is still rejected so calling code can handle it if needed.
 */
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      // Token is no longer valid -- clear it and bounce to login
      localStorage.removeItem('preshift_token')
      router.push('/login')
    }
    return Promise.reject(error)
  }
)

/**
 * Vue composable wrapper that returns the shared Axios instance.
 * Useful inside `<script setup>` blocks for consistency with other composables.
 *
 * @returns The pre-configured Axios instance
 */
export function useApi() {
  return api
}

/** Default export for non-composable usage (Pinia stores, utility functions). */
export default api
