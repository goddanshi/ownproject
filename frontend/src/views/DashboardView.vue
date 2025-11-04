<template>
  <DashboardLayout>
    <div class="dashboard">
      <div class="dashboard-header">
        <div>
          <p class="subtitle">Добро пожаловать в систему</p>
        </div>
      </div>

      <div class="dashboard-content">
        <!-- Левая часть: Статистика -->
        <div class="left-section">
          <div class="stats-grid">
            <div class="stat-card">
              <div class="stat-header">
                <span class="stat-label">Сотрудники</span>
                <div class="stat-icon">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                  </svg>
                </div>
              </div>
              <div class="stat-value">{{ profilesData.length || 0 }}</div>
              <div class="stat-footer">
                <span class="stat-change positive">Всего пользователей</span>
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
              <div class="stat-value">{{ tasks.length }}</div>
              <div class="stat-footer">
                <span class="stat-change">{{ activeTasks.length }} активных</span>
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

          <div class="info-card">
            <h3>Быстрый доступ</h3>
            <div class="quick-links">
              <RouterLink to="/workers" class="quick-link">
                <span class="link-icon">👥</span>
                <span>Управление сотрудниками</span>
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
        </div>

        <!-- Правая часть: Список пользователей -->
        <div class="right-section">
          <div class="users-card">
            <div class="users-header">
              <h3>Пользователи системы</h3>
              <span class="users-count">{{ profilesData.length }}</span>
            </div>

            <div v-if="loading" class="users-loading">
              <div class="spinner"></div>
              <p>Загрузка...</p>
            </div>

            <div v-else-if="profilesData.length === 0" class="users-empty">
              <p>Пользователи не найдены</p>
            </div>

            <div v-else class="users-list">
              <div
                v-for="user in profilesData"
                :key="user.id"
                class="user-item"
              >
                <div class="user-avatar-small">
                  {{ user.username?.[0]?.toUpperCase() || 'U' }}
                </div>
                <div class="user-details">
                  <div class="user-name-row">
                    <span class="user-fullname">
                      {{ user.name || user.username }} {{ user.surname || '' }}
                    </span>
                    <span :class="['user-role', getRoleClass(user.role)]">
                      {{ getRoleLabel(user.role) }}
                    </span>
                  </div>
                  <span class="user-email-small">{{ user.email }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import DashboardLayout from '../layouts/DashboardLayout.vue'
import { useAuthStore } from '../stores/auth.js'
import usersApi from '../services/employers.js'
import tasksApi from '../services/tasks'

const authStore = useAuthStore()

const loading = ref(true)
const profilesData = ref([])
const tasks = ref([])
const activeTasks = ref([])

const currentDate = computed(() => {
  const date = new Date()
  return date.toLocaleDateString('ru-RU', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  })
})

const getRoleLabel = (role) => {
  const roles = {
    1: 'Админ',
    2: 'Тимлид',
    3: 'Сотрудник'
  }
  return roles[role] || 'Неизвестно'
}

const getRoleClass = (role) => {
  const classes = {
    1: 'role-admin',
    2: 'role-teamlead',
    3: 'role-employee'
  }
  return classes[role] || 'role-unknown'
}

const loadProfiles = async () => {
  loading.value = true
  try {
    const result = await usersApi.getProfiles()

    if (result.success) {
      // Проверка что users существует и это массив
      profilesData.value = Array.isArray(result.users) ? result.users : []
    } else {
      profilesData.value = []
    }
  } catch (error) {
    console.error('Failed to load profiles:', error)
    profilesData.value = []
  } finally {
    loading.value = false
  }
}

const loadTasks = async () => {
  try {
    const result = await tasksApi.getTasks()
    if (result.success) {
      tasks.value = Array.isArray(result.tasks) ? result.tasks : []
    }
  } catch (error) {
    console.error('Failed to load tasks:', error)
    tasks.value = []
  }
}

const loadActiveTasks = async () => {
  try {
    const result = await tasksApi.getActiveTasks()
    if (result.success) {
      activeTasks.value = Array.isArray(result.tasks) ? result.tasks : []
    }
  } catch (error) {
    console.error('Failed to load active tasks:', error)
    activeTasks.value = []
  }
}

onMounted(() => {
  loadProfiles()
  loadTasks()
  loadActiveTasks()
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
  align-items: center;
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

.header-right {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.date-info {
  font-size: 0.9rem;
  color: #666;
  padding: 0.5rem 1rem;
  background: #fafafa;
  border-radius: 6px;
  border: 1px solid #e0e0e0;
}

.user-profile {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.5rem 1rem;
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 6px;
  text-decoration: none;
  transition: all 0.2s ease;
}

.user-profile:hover {
  border-color: #2d3748;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.user-info {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.125rem;
}

.user-name {
  font-size: 0.9rem;
  font-weight: 600;
  color: #1a1a1a;
}

.user-email {
  font-size: 0.75rem;
  color: #666;
}

.user-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  font-weight: 600;
  flex-shrink: 0;
}

/* Dashboard Content Layout */
.dashboard-content {
  display: grid;
  grid-template-columns: 1fr 400px;
  gap: 1.5rem;
}

.left-section {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.right-section {
  display: flex;
  flex-direction: column;
}

/* Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
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

/* Quick Links */
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

/* Users Card */
.users-card {
  background: white;
  padding: 1.5rem;
  border-radius: 8px;
  border: 1px solid #e0e0e0;
  height: fit-content;
  max-height: calc(100vh - 200px);
  display: flex;
  flex-direction: column;
}

.users-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #e0e0e0;
}

.users-header h3 {
  margin: 0;
  font-size: 1rem;
  font-weight: 600;
  color: #1a1a1a;
}

.users-count {
  background: #2d3748;
  color: white;
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.85rem;
  font-weight: 600;
}

.users-loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 3rem 1rem;
  gap: 1rem;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 3px solid #e0e0e0;
  border-top-color: #2d3748;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.users-loading p {
  margin: 0;
  color: #666;
  font-size: 0.9rem;
}

.users-empty {
  text-align: center;
  padding: 3rem 1rem;
  color: #999;
}

.users-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  overflow-y: auto;
  max-height: calc(100vh - 320px);
}

.user-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: #fafafa;
  border-radius: 6px;
  border: 1px solid #e0e0e0;
  transition: all 0.2s ease;
}

.user-item:hover {
  background: white;
  border-color: #2d3748;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.user-avatar-small {
  width: 45px;
  height: 45px;
  border-radius: 50%;
  background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  font-weight: 600;
  flex-shrink: 0;
}

.user-details {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  min-width: 0;
}

.user-name-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 0.5rem;
}

.user-fullname {
  font-size: 0.9rem;
  font-weight: 600;
  color: #1a1a1a;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.user-role {
  font-size: 0.7rem;
  padding: 0.2rem 0.5rem;
  border-radius: 4px;
  font-weight: 500;
  flex-shrink: 0;
}

.user-role.role-admin {
  background: #fee2e2;
  color: #991b1b;
}

.user-role.role-teamlead {
  background: #dbeafe;
  color: #1e40af;
}

.user-role.role-employee {
  background: #f0fdf4;
  color: #166534;
}

.user-email-small {
  font-size: 0.8rem;
  color: #666;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

@media (max-width: 1024px) {
  .dashboard-content {
    grid-template-columns: 1fr;
  }

  .users-card {
    max-height: 500px;
  }
}

@media (max-width: 768px) {
  .dashboard-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }

  .header-right {
    width: 100%;
    justify-content: space-between;
  }

  .user-info {
    align-items: flex-start;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }
}
</style>
