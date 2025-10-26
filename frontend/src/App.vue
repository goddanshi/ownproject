<template>
  <!-- Глобальный лоадер -->
  <GlobalLoader :show="authStore.loading" text="Проверка авторизации..." />

  <!-- Основной контент -->
  <div v-if="!authStore.loading">
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
</template>

<script setup>
import { computed, onMounted, onUnmounted } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from './stores/auth'
import GlobalLoader from './components/GlobalLoader.vue'

const route = useRoute()
const authStore = useAuthStore()

const showHeader = computed(() => {
  return !['/login', '/register'].includes(route.path)
})

let permissionCheckInterval = null

onMounted(() => {
  // Первая проверка прав сразу после монтирования
  if (authStore.isAuthenticated && authStore.user) {
    authStore.refreshPermissions()
  }

  // Запускаем периодическую проверку прав каждые 30 секунд
  permissionCheckInterval = setInterval(() => {
    authStore.refreshPermissions()
  }, 30000) // 30 секунд
})

onUnmounted(() => {
  // Очищаем интервал при размонтировании компонента
  if (permissionCheckInterval) {
    clearInterval(permissionCheckInterval)
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
</style>
