import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import HomeView from '../views/HomeView.vue'
import LoginView from '../views/LoginView.vue'
import RegisterView from '../views/RegisterView.vue'
import DashboardView from '../views/DashboardView.vue'
import WorkersView from '../views/WorkersView.vue'
import TasksView from '../views/TasksView.vue'
import RequestsView from '../views/RequestsView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'home',
      component: HomeView
    },
    {
      path: '/login',
      name: 'login',
      component: LoginView,
      meta: { guest: true }
    },
    {
      path: '/register',
      name: 'register',
      component: RegisterView,
      meta: { guest: true }
    },
    {
      path: '/dashboard',
      name: 'dashboard',
      component: DashboardView,
      meta: { requiresAuth: true }
    },
    {
      path: '/workers',
      name: 'workers',
      component: WorkersView,
      meta: { requiresAuth: true }
    },
    {
      path: '/tasks',
      name: 'tasks',
      component: TasksView,
      meta: { requiresAuth: true }
    },
    {
      path: '/requests',
      name: 'requests',
      component: RequestsView,
      meta: { requiresAuth: true }
    },
    {
      path: '/about',
      name: 'about',
      component: () => import('../views/AboutView.vue')
    }
  ]
})

router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()

  if (authStore.user === null && !authStore.loading) {
    await authStore.checkAuth()
  }

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next('/login')
  }
  else if (to.meta.guest && authStore.isAuthenticated) {
    next('/dashboard')
  }
  else {
    next()
  }
})

export default router
