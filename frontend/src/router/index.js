import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      redirect: (to) => {
        const authStore = useAuthStore()
        return authStore.isAuthenticated ? '/dashboard' : '/login'
      }
    },
    {
      path: '/login',
      name: 'login',
      component: () => import('../views/LoginView.vue'),
      meta: { guest: true }
    },
    {
      path: '/register',
      name: 'register',
      component: () => import('../views/RegisterView.vue'),
      meta: { guest: true }
    },
    {
      path: '/dashboard',
      name: 'dashboard',
      component: () => import('../views/DashboardView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/profile',
      name: 'profile',
      component: () => import('../views/ProfileView.vue'),
      meta: { requiresAuth: true, permission: 'view_profile' }
    },
    {
      path: '/workers',
      name: 'workers',
      component: () => import('../views/WorkersView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/tasks',
      name: 'tasks',
      component: () => import('../views/TasksView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/requests',
      name: 'requests',
      component: () => import('../views/RequestsView.vue'),
      meta: { requiresAuth: true }
    }
  ]
})

// Navigation guard
// Navigation guard
router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()

  // Ждём проверки авторизации только если ещё не проверяли
  if (!authStore.checked) {
    authStore.loading = true
    await authStore.checkAuth()
    authStore.loading = false
  }

  const isAuthenticated = authStore.isAuthenticated
  const requiresAuth = to.meta.requiresAuth
  const isGuestRoute = to.meta.guest
  const requiredPermission = to.meta.permission

  // Защищённый роут + не авторизован → на логин
  if (requiresAuth && !isAuthenticated) {
    next('/login')
  }
  // Гостевой роут (login/register) + авторизован → на dashboard
  else if (isGuestRoute && isAuthenticated) {
    next('/dashboard')
  }
  // Проверка прав доступа
  else if (requiredPermission && !authStore.can(requiredPermission)) {
    // Нет прав → редирект на dashboard с сообщением (опционально)
    console.warn(`Access denied: missing permission "${requiredPermission}"`)
    next('/dashboard')
  }
  // Всё остальное - пропускаем
  else {
    next()
  }
})

export default router
