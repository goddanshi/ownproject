<template>
  <DashboardLayout>
    <div class="dashboard">
      <div class="dashboard-header">
        <div>
          <h1>Панель управления</h1>
          <p class="subtitle">Добро пожаловать, {{ authStore.user?.username }}</p>
        </div>
        <div class="date-info">
          {{ currentDate }}
        </div>
      </div>

      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-header">
            <span class="stat-label">Работники</span>
            <div class="stat-icon">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
              </svg>
            </div>
          </div>
          <div class="stat-value">24</div>
          <div class="stat-footer">
            <span class="stat-change positive">+3 за неделю</span>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-header">
            <span class="stat-label">Задачи</span>
            <div class="stat-icon">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
              </svg>
            </div>
          </div>
          <div class="stat-value">156</div>
          <div class="stat-footer">
            <span class="stat-change">42 активных</span>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-header">
            <span class="stat-label">Заявки</span>
            <div class="stat-icon">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
              </svg>
            </div>
          </div>
          <div class="stat-value">42</div>
          <div class="stat-footer">
            <span class="stat-change urgent">8 требуют внимания</span>
          </div>
        </div>
      </div>

      <div class="info-section">
        <div class="info-card">
          <h3>Быстрый доступ</h3>
          <div class="quick-links">
            <RouterLink to="/workers" class="quick-link">
              <span class="link-icon">👥</span>
              <span>Управление работниками</span>
            </RouterLink>
            <RouterLink to="/tasks" class="quick-link">
              <span class="link-icon">✅</span>
              <span>Просмотр задач</span>
            </RouterLink>
            <RouterLink to="/requests" class="quick-link">
              <span class="link-icon">📝</span>
              <span>Обработка заявок</span>
            </RouterLink>
          </div>
        </div>

        <div class="info-card">
          <h3>Последняя активность</h3>
          <div class="activity-list">
            <div class="activity-item">
              <div class="activity-icon">✓</div>
              <div class="activity-content">
                <p class="activity-text">Задача "Подготовка отчета" завершена</p>
                <span class="activity-time">2 часа назад</span>
              </div>
            </div>
            <div class="activity-item">
              <div class="activity-icon">+</div>
              <div class="activity-content">
                <p class="activity-text">Добавлен новый работник</p>
                <span class="activity-time">5 часов назад</span>
              </div>
            </div>
            <div class="activity-item">
              <div class="activity-icon">📄</div>
              <div class="activity-content">
                <p class="activity-text">Создана заявка #1547</p>
                <span class="activity-time">Вчера</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { computed } from 'vue'
import DashboardLayout from '../layouts/DashboardLayout.vue'
import { useAuthStore } from '../stores/auth'

const authStore = useAuthStore()

const currentDate = computed(() => {
  const date = new Date()
  return date.toLocaleDateString('ru-RU', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  })
})
</script>

<style scoped>
.dashboard {
  max-width: 1400px;
  margin: 0 auto;
}

.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 2rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid #e0e0e0;
}

h1 {
  margin: 0 0 0.5rem 0;
  font-size: 1.75rem;
  font-weight: 600;
  color: #1a1a1a;
  letter-spacing: -0.5px;
}

.subtitle {
  margin: 0;
  font-size: 0.95rem;
  color: #666;
  font-weight: 400;
}

.date-info {
  font-size: 0.9rem;
  color: #666;
  padding: 0.5rem 1rem;
  background: #fafafa;
  border-radius: 6px;
  border: 1px solid #e0e0e0;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.stat-card {
  background: white;
  padding: 1.5rem;
  border-radius: 8px;
  border: 1px solid #e0e0e0;
  transition: all 0.2s ease;
}

.stat-card:hover {
  border-color: #d0d0d0;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
  transform: translateY(-2px);
}

.stat-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.stat-label {
  font-size: 0.9rem;
  color: #666;
  font-weight: 500;
}

.stat-icon {
  width: 32px;
  height: 32px;
  color: #999;
}

.stat-icon svg {
  width: 100%;
  height: 100%;
}

.stat-value {
  font-size: 2.5rem;
  font-weight: 600;
  color: #1a1a1a;
  margin-bottom: 0.5rem;
  line-height: 1;
}

.stat-footer {
  font-size: 0.85rem;
}

.stat-change {
  color: #666;
}

.stat-change.positive {
  color: #166534;
}

.stat-change.urgent {
  color: #991b1b;
}

.info-section {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  gap: 1.5rem;
}

.info-card {
  background: white;
  padding: 1.5rem;
  border-radius: 8px;
  border: 1px solid #e0e0e0;
}

.info-card h3 {
  margin: 0 0 1rem 0;
  font-size: 1rem;
  font-weight: 600;
  color: #1a1a1a;
}

.quick-links {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.quick-link {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.875rem 1rem;
  background: #fafafa;
  border-radius: 6px;
  border: 1px solid #e0e0e0;
  color: #333;
  text-decoration: none;
  transition: all 0.2s ease;
  font-size: 0.9rem;
}

.quick-link:hover {
  background: white;
  border-color: #d0d0d0;
  transform: translateX(4px);
}

.link-icon {
  font-size: 1.25rem;
}

.activity-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.activity-item {
  display: flex;
  gap: 1rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #f0f0f0;
}

.activity-item:last-child {
  border-bottom: none;
  padding-bottom: 0;
}

.activity-icon {
  width: 32px;
  height: 32px;
  background: #fafafa;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.9rem;
  flex-shrink: 0;
}

.activity-content {
  flex: 1;
}

.activity-text {
  margin: 0 0 0.25rem 0;
  font-size: 0.9rem;
  color: #333;
}

.activity-time {
  font-size: 0.8rem;
  color: #999;
}

@media (max-width: 768px) {
  .dashboard-header {
    flex-direction: column;
    gap: 1rem;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .info-section {
    grid-template-columns: 1fr;
  }
}
</style>
