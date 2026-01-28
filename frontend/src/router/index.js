import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      redirect: (to) => {
        const authStore = useAuthStore()
        if (!authStore.isAuthenticated) return '/login'
        // Менеджеры по продажам всегда на страницу лидов
        if (authStore.isSalesManager) return '/leads'
        return '/dashboard'
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
      path: '/projects',
      name: 'projects',
      component: () => import('../views/ProjectsView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/projects/:id',
      name: 'project',
      component: () => import('../views/ProjectView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/leads',
      name: 'leads',
      component: () => import('../views/LeadsView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/commands',
      name: 'commands',
      component: () => import('../views/CommandsView.vue'),
      meta: { requiresAuth: true}
    },
    {
      path: '/aichat',
      name: 'aichat',
      component: () => import('../views/AiChat.vue'),
      meta: { requiresAuth: true}
    },
    {
      path: '/my-team',
      name: 'my-team',
      component: () => import('../views/MyTeamView.vue'),
      meta: { requiresAuth: true, permission: 'view_my_team' } // ← Изменили на view_my_team
    },
    {
      path: '/statistics',
      name: 'statistics',
      component: () => import('../views/Statistics.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/analytics',
      name: 'analytics',
      component: () => import('../views/Analytics.vue'),
      meta: { requiresAuth: true }
    }
  ]
})


// Navigation guard
router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()

  // ВСЕГДА проверяем авторизацию и загружаем свежие права
  authStore.loading = true
  await authStore.checkAuth()
  authStore.loading = false

  const isAuthenticated = authStore.isAuthenticated
  const requiresAuth = to.meta.requiresAuth
  const isGuestRoute = to.meta.guest
  const requiredPermission = to.meta.permission

  // Защищённый роут + не авторизован → на логин
  if (requiresAuth && !isAuthenticated) {
    next('/login')
  }
  // Гостевой роут + авторизован → на dashboard (или leads для менеджеров)
  else if (isGuestRoute && isAuthenticated) {
    next(authStore.isSalesManager ? '/leads' : '/dashboard')
  }
  // Менеджер по продажам может заходить ТОЛЬКО на /leads и /profile
  else if (authStore.isSalesManager && to.path !== '/leads' && to.path !== '/profile') {
    next('/leads')
  }
  // Проверка прав доступа
  else if (requiredPermission && !authStore.can(requiredPermission)) {
    console.warn(`Access denied: missing permission "${requiredPermission}"`)
    next('/dashboard')
  }
  else {
    next()
  }
})

export default router
