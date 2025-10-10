<template>
  <div class="dashboard">
    <div class="dashboard-card">
      <h1>Dashboard</h1>

      <div class="user-info">
        <h2>Welcome, {{ authStore.user?.username }}! 🎉</h2>
        <div class="info-grid">
          <div class="info-item">
            <strong>User ID:</strong> {{ authStore.user?.id }}
          </div>
          <div class="info-item">
            <strong>Username:</strong> {{ authStore.user?.username }}
          </div>
          <div class="info-item">
            <strong>Email:</strong> {{ authStore.user?.email }}
          </div>
        </div>
      </div>

      <div class="actions">
        <button @click="handleLogout" class="logout-btn">
          Logout
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const handleLogout = async () => {
  await authStore.logout()
  router.push('/login')
}
</script>

<style scoped>
.dashboard {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.dashboard-card {
  background: white;
  padding: 3rem;
  border-radius: 15px;
  box-shadow: 0 10px 30px rgba(0,0,0,0.3);
  max-width: 600px;
  width: 100%;
}

h1 {
  text-align: center;
  color: #333;
  margin-bottom: 2rem;
}

.user-info {
  background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
  padding: 2rem;
  border-radius: 10px;
  margin-bottom: 2rem;
}

.user-info h2 {
  color: #667eea;
  margin-top: 0;
  margin-bottom: 1.5rem;
}

.info-grid {
  display: grid;
  gap: 1rem;
}

.info-item {
  padding: 0.75rem;
  background: white;
  border-radius: 5px;
  border-left: 4px solid #667eea;
}

.info-item strong {
  color: #555;
  margin-right: 0.5rem;
}

.actions {
  text-align: center;
}

.logout-btn {
  padding: 0.75rem 2rem;
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
  color: white;
  border: none;
  border-radius: 5px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: transform 0.2s;
}

.logout-btn:hover {
  transform: translateY(-2px);
}
</style>
