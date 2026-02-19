import { createRouter, createWebHistory } from 'vue-router'
import type { RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/views/auth/LoginView.vue'),
  },
  {
    path: '/',
    redirect: '/dashboard',
  },
  {
    path: '/dashboard',
    name: 'Dashboard',
    component: () => import('@/views/staff/DashboardView.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/86',
    name: 'EightySixedBoard',
    component: () => import('@/views/staff/EightySixedBoard.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/specials',
    name: 'Specials',
    component: () => import('@/views/staff/SpecialsView.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/manage',
    name: 'ManageDashboard',
    component: () => import('@/views/admin/ManageDashboard.vue'),
    meta: { requiresAuth: true, roles: ['admin', 'manager'] },
  },
  {
    path: '/manage/86',
    name: 'ManageEightySixed',
    component: () => import('@/views/admin/ManageEightySixed.vue'),
    meta: { requiresAuth: true, roles: ['admin', 'manager'] },
  },
  {
    path: '/manage/specials',
    name: 'ManageSpecials',
    component: () => import('@/views/admin/ManageSpecials.vue'),
    meta: { requiresAuth: true, roles: ['admin', 'manager'] },
  },
  {
    path: '/manage/push-items',
    name: 'ManagePushItems',
    component: () => import('@/views/admin/ManagePushItems.vue'),
    meta: { requiresAuth: true, roles: ['admin', 'manager'] },
  },
  {
    path: '/manage/announcements',
    name: 'ManageAnnouncements',
    component: () => import('@/views/admin/ManageAnnouncements.vue'),
    meta: { requiresAuth: true, roles: ['admin', 'manager'] },
  },
  {
    path: '/manage/menu',
    name: 'ManageMenuItems',
    component: () => import('@/views/admin/ManageMenuItems.vue'),
    meta: { requiresAuth: true, roles: ['admin', 'manager'] },
  },
  {
    path: '/manage/users',
    name: 'ManageUsers',
    component: () => import('@/views/admin/ManageUsers.vue'),
    meta: { requiresAuth: true, roles: ['admin', 'manager'] },
  },
  {
    path: '/manage/acknowledgments',
    name: 'AcknowledgmentTracker',
    component: () => import('@/views/admin/AcknowledgmentTracker.vue'),
    meta: { requiresAuth: true, roles: ['admin', 'manager'] },
  },
  {
    path: '/admin/locations',
    name: 'ManageLocations',
    component: () => import('@/views/admin/ManageLocations.vue'),
    meta: { requiresAuth: true, roles: ['admin'] },
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach(async (to, _from, next) => {
  const token = localStorage.getItem('preshift_token')

  if (to.meta.requiresAuth && !token) {
    return next('/login')
  }

  if (to.meta.roles && token) {
    // Lazy-import auth store to avoid circular dependency
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

    const allowedRoles = to.meta.roles as string[]
    if (authStore.user && !allowedRoles.includes(authStore.user.role)) {
      return next('/dashboard')
    }
  }

  next()
})

export default router
