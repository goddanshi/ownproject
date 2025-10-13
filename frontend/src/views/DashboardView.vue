<template>
  <DashboardLayout>
    <div class="dashboard">
      <h1>📊 Dashboard</h1>

      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon">👥</div>
          <div class="stat-info">
            <h3>Работники</h3>
            <p class="stat-number">24</p>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon">✅</div>
          <div class="stat-info">
            <h3>Задачи</h3>
            <p class="stat-number">156</p>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon">📝</div>
          <div class="stat-info">
            <h3>Заявки</h3>
            <p class="stat-number">42</p>
          </div>
        </div>
      </div>

      <div class="welcome-section">
        <h2>Добро пожаловать, {{ authStore.user?.username }}! 👋</h2>
        <p>Вы успешно авторизованы в системе</p>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup>
import DashboardLayout from '../layouts/DashboardLayout.vue'
import { useAuthStore } from '../stores/auth'

const authStore = useAuthStore()
</script>

<style scoped>
.dashboard h1 {
  color: #333;
  margin-bottom: 2rem;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.stat-card {
  background: white;
  padding: 2rem;
  border-radius: 15px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
  display: flex;
  align-items: center;
  gap: 1.5rem;
  transition: transform 0.3s ease;
}

.stat-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 4px 20px rgba(0,0,0,0.15);
}

.stat-icon {
  font-size: 3rem;
}

.stat-info h3 {
  margin: 0;
  color: #666;
  font-size: 0.9rem;
  font-weight: 500;
}

.stat-number {
  margin: 0.5rem 0 0;
  font-size: 2rem;
  font-weight: bold;
  color: #26c6da;
}

.welcome-section {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 2rem;
  border-radius: 15px;
  text-align: center;
}

.welcome-section h2 {
  margin: 0 0 0.5rem;
}

.welcome-section p {
  margin: 0;
  opacity: 0.9;
}
</style>
