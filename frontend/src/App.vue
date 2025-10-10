<template>
  <header>
    <div class="wrapper">
      <nav>
        <RouterLink to="/">Home</RouterLink>
        <RouterLink to="/about">About</RouterLink>

        <template v-if="authStore.isAuthenticated">
          <RouterLink to="/dashboard">Dashboard</RouterLink>
          <a @click="handleLogout" class="logout-link">Logout</a>
        </template>
        <template v-else>
          <RouterLink to="/login">Login</RouterLink>
          <RouterLink to="/register">Register</RouterLink>
        </template>
      </nav>
    </div>
  </header>

  <RouterView />
</template>

<script setup>
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from './stores/auth'

const router = useRouter()
const authStore = useAuthStore()

onMounted(() => {
  authStore.checkAuth()
})

const handleLogout = async () => {
  await authStore.logout()
  router.push('/')
}
</script>

<style scoped>
.logout-link {
  cursor: pointer;
  color: var(--color-text);
  text-decoration: none;
}

.logout-link:hover {
  background-color: var(--color-background-soft);
}
</style>
