/**
 * router/index.ts
 *
 * Vue Router configuration for the Pre-Shift Meeting SPA.
 *
 * Route structure overview:
 *  - `/login`                     : Public login page (no auth required)
 *  - `/`                          : Redirects to `/dashboard`
 *  - `/dashboard`, `/86`, `/specials` : Staff-facing views (any authenticated user)
 *  - `/manage/*`                  : Admin/manager CRUD views (role-restricted)
 *  - `/admin/locations`           : Admin-only location management
 *
 * Meta fields used on route records:
 *  - `requiresAuth` (boolean) : If true, the navigation guard will redirect
 *    unauthenticated users (no token in localStorage) to `/login`.
 *  - `roles` (string[])       : If present, only users whose `role` is in
 *    this array may access the route.  Others are redirected to `/dashboard`.
 *
 * All view components are lazy-loaded via dynamic `import()` so that Vite
 * code-splits them into separate chunks, reducing the initial bundle size.
 *
 * Navigation guard logic (beforeEach):
 *  1. If the target route requires auth and there is no token, redirect to `/login`.
 *  2. If the target route specifies allowed roles AND a token exists:
 *     a. Lazy-import the auth store to avoid circular dependencies with
 *        the Axios interceptor (which also imports the router).
 *     b. If the user object is not yet loaded (e.g. page reload), call
 *        `fetchUser()` to rehydrate it from the API.
 *     c. If fetchUser fails (token expired), clear the token and redirect
 *        to `/login`.
 *     d. If the user's role is not in the allowed list, redirect to
 *        `/dashboard` (a safe fallback).
 *  3. Otherwise, allow navigation to proceed.
 */

import { createRouter, createWebHistory } from 'vue-router'
import type { RouteRecordRaw } from 'vue-router'

/**
 * Application route definitions.
 * Each route uses lazy-loaded components via `() => import(...)` for
 * automatic code-splitting by Vite/Rollup.
 */
const routes: RouteRecordRaw[] = [
  // -----------------------------------------------------------------------
  // Public routes
  // -----------------------------------------------------------------------
  {
    path: '/login',
    name: 'Login',
    // Lazy-loaded login page -- only downloaded when the user navigates here
    component: () => import('@/views/auth/LoginView.vue'),
  },

  // -----------------------------------------------------------------------
  // Root redirect -- sends "/" straight to the staff dashboard
  // -----------------------------------------------------------------------
  {
    path: '/',
    redirect: '/dashboard',
  },

  // -----------------------------------------------------------------------
  // Staff-facing routes (any authenticated user)
  // meta.requiresAuth = true ensures the nav guard checks for a token
  // -----------------------------------------------------------------------
  {
    path: '/dashboard',
    name: 'Dashboard',
    component: () => import('@/views/staff/DashboardView.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/86',
    name: 'EightySixedBoard',
    // Full-page view of all currently 86'd items
    component: () => import('@/views/staff/EightySixedBoard.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/specials',
    name: 'Specials',
    // Full-page view of active specials
    component: () => import('@/views/staff/SpecialsView.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/my-schedule',
    name: 'MySchedule',
    // Staff view of their upcoming shifts
    component: () => import('@/views/staff/MyScheduleView.vue'),
    meta: { requiresAuth: true },
  },
  // Staff shift-drop and time-off routes removed for now

  // -----------------------------------------------------------------------
  // Management routes (admin + manager roles only)
  // meta.roles restricts access; the nav guard checks the user's role
  // -----------------------------------------------------------------------
  {
    path: '/manage',
    redirect: '/manage/daily',
  },
  {
    path: '/manage/daily',
    name: 'DailyManage',
    // Unified daily management panel for all four content areas
    component: () => import('@/views/admin/DailyManageView.vue'),
    meta: { requiresAuth: true, roles: ['admin', 'manager'] },
  },
  {
    path: '/manage/86',
    name: 'ManageEightySixed',
    // CRUD for 86'd items -- managers can 86 or restore items here
    component: () => import('@/views/admin/ManageEightySixed.vue'),
    meta: { requiresAuth: true, roles: ['admin', 'manager'] },
  },
  {
    path: '/manage/specials',
    name: 'ManageSpecials',
    // CRUD for specials
    component: () => import('@/views/admin/ManageSpecials.vue'),
    meta: { requiresAuth: true, roles: ['admin', 'manager'] },
  },
  {
    path: '/manage/push-items',
    name: 'ManagePushItems',
    // CRUD for push items (upsell targets)
    component: () => import('@/views/admin/ManagePushItems.vue'),
    meta: { requiresAuth: true, roles: ['admin', 'manager'] },
  },
  {
    path: '/manage/announcements',
    name: 'ManageAnnouncements',
    // CRUD for staff announcements
    component: () => import('@/views/admin/ManageAnnouncements.vue'),
    meta: { requiresAuth: true, roles: ['admin', 'manager'] },
  },
  {
    path: '/manage/menu',
    name: 'ManageMenuItems',
    // CRUD for the restaurant menu (categories and items)
    component: () => import('@/views/admin/ManageMenuItems.vue'),
    meta: { requiresAuth: true, roles: ['admin', 'manager'] },
  },
  {
    path: '/manage/users',
    name: 'ManageUsers',
    // User management -- add/edit/remove staff accounts
    component: () => import('@/views/admin/ManageUsers.vue'),
    meta: { requiresAuth: true, roles: ['admin', 'manager'] },
  },
  {
    path: '/manage/acknowledgments',
    name: 'AcknowledgmentTracker',
    // Read-only view showing which staff have acknowledged key items
    component: () => import('@/views/admin/AcknowledgmentTracker.vue'),
    meta: { requiresAuth: true, roles: ['admin', 'manager'] },
  },
  {
    path: '/manage/schedule',
    name: 'ScheduleBuilder',
    // Weekly schedule builder -- assign staff to shifts and publish
    component: () => import('@/views/admin/ScheduleBuilderView.vue'),
    meta: { requiresAuth: true, roles: ['admin', 'manager'] },
  },
  {
    path: '/manage/shift-drops',
    name: 'ManageShiftDrops',
    component: () => import('@/views/admin/ManageShiftDrops.vue'),
    meta: { requiresAuth: true, roles: ['admin', 'manager'] },
  },
  {
    path: '/manage/time-off',
    name: 'ManageTimeOff',
    // Manager view to approve/deny time-off requests
    component: () => import('@/views/admin/ManageTimeOff.vue'),
    meta: { requiresAuth: true, roles: ['admin', 'manager'] },
  },

  // -----------------------------------------------------------------------
  // SuperAdmin config page
  // -----------------------------------------------------------------------
  {
    path: '/config',
    name: 'Config',
    component: () => import('@/views/admin/ConfigView.vue'),
    meta: { requiresAuth: true, requiresSuperAdmin: true },
  },

  // -----------------------------------------------------------------------
  // Admin-only routes (admin role exclusively)
  // -----------------------------------------------------------------------
  {
    path: '/admin/locations',
    name: 'ManageLocations',
    // CRUD for locations -- only super-admins can manage multi-location setup
    component: () => import('@/views/admin/ManageLocations.vue'),
    meta: { requiresAuth: true, roles: ['admin'] },
  },
]

/**
 * Create the Vue Router instance with HTML5 history mode.
 * `createWebHistory()` uses the browser's History API (no hash fragments),
 * which requires the server to be configured to serve `index.html` for all
 * routes (handled by Vite dev server and Laravel's catch-all in production).
 */
const router = createRouter({
  history: createWebHistory(),
  routes,
})

/**
 * Global navigation guard -- runs before every route change.
 *
 * This is the central gatekeeper for authentication and role-based access
 * control (RBAC) in the application.
 *
 * @param to    - The target route being navigated to
 * @param _from - The route being navigated away from (unused)
 * @param next  - Callback to resolve the navigation (call with no args to
 *                proceed, or with a path string to redirect)
 */
router.beforeEach(async (to, _from, next) => {
  // Read the persisted auth token from localStorage
  const token = localStorage.getItem('preshift_token')

  // GUARD 1: If the route requires authentication and there is no token,
  // redirect to the login page immediately.
  if (to.meta.requiresAuth && !token) {
    return next('/login')
  }

  // GUARD 2: If the route requires auth and we have a token, ensure the
  // user object is loaded (it may be null after a page refresh).
  if (to.meta.requiresAuth && token) {
    const { useAuthStore } = await import('@/stores/auth')
    const authStore = useAuthStore()

    if (!authStore.user) {
      try {
        await authStore.fetchUser()
      } catch {
        localStorage.removeItem('preshift_token')
        return next('/login')
      }
    }

    // GUARD 3: SuperAdmin access control
    if (to.meta.requiresSuperAdmin) {
      if (!authStore.user?.is_superadmin) {
        return next('/dashboard')
      }
    }

    // GUARD 4: Role-based access control
    if (to.meta.roles) {
      const allowedRoles = to.meta.roles as string[]
      if (authStore.user && !allowedRoles.includes(authStore.user.role)) {
        return next('/dashboard')
      }
    }
  }

  // All guards passed -- allow the navigation to proceed
  next()
})

export default router
