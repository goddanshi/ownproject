<template>
  <div v-if="!authStore.loading">
    <!-- Показываем хедер только для неавторизованных -->
    <header v-if="!authStore.isAuthenticated && showHeader">
      <div class="wrapper">
        <nav>
          <RouterLink to="/login">Login</RouterLink>
          <RouterLink to="/register">Register</RouterLink>
        </nav>
      </div>
    </header>

    <RouterView />
  </div>

  <!-- Лоадер пока проверяется авторизация -->
  <div v-else class="loading-screen">
    <div class="spinner"></div>
    <p>Загрузка...</p>
  </div>
</template>

<script setup>
import { computed, onMounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from './stores/auth'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

// Показывать ли хедер (не показываем на login/register)
const showHeader = computed(() => {
  return !['/login', '/register'].includes(route.path)
})

onMounted(async () => {
  await authStore.checkAuth()

  // После проверки редиректим
  if (authStore.isAuthenticated && ['/login', '/register', '/'].includes(route.path)) {
    router.push('/dashboard')
  } else if (!authStore.isAuthenticated && route.meta.requiresAuth) {
    router.push('/login')
  }
})

// Следим за изменением авторизации
watch(() => authStore.isAuthenticated, (isAuth) => {
  if (isAuth && ['/login', '/register', '/'].includes(route.path)) {
    router.push('/dashboard')
  } else if (!isAuth && route.meta.requiresAuth) {
    router.push('/login')
  }
})
</script>

<style scoped>
header {
  background: white;
  padding: 1rem;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.wrapper {
  max-width: 1200px;
  margin: 0 auto;
}

nav {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
}

nav a {
  padding: 0.5rem 1rem;
  text-decoration: none;
  color: #333;
  border-radius: 5px;
  transition: background 0.3s;
}

nav a:hover {
  background: #f0f0f0;
}

.loading-screen {
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.spinner {
  width: 50px;
  height: 50px;
  border: 5px solid rgba(255,255,255,0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.loading-screen p {
  margin-top: 1rem;
  font-size: 1.2rem;
}
</style>
