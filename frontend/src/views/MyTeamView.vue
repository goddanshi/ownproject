<template>
  <DashboardLayout>
    <template #header-left>
      <h1>Моя команда</h1>
    </template>

    <div class="my-team-page">
      <!-- Загрузка -->
      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Загрузка команды...</p>
      </div>

      <!-- Команда найдена -->
      <div v-else-if="team" class="team-container">
        <!-- Карточка команды -->
        <div class="team-card">
          <div class="team-header">
            <div>
              <h2>{{ team.name }}</h2>
              <p class="description">{{ team.description || 'Описание отсутствует' }}</p>
            </div>
            <div class="team-stats">
              <div class="stat">
                <span class="stat-value">{{ totalMembers }}</span>
                <span class="stat-label">участников</span>
              </div>
            </div>
          </div>

          <!-- Тимлид -->
          <div class="section">
            <h3>
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
              </svg>
              Тимлид
            </h3>
            <div class="member-card teamlead">
              <div class="avatar-large">
                {{ team.teamlead.username[0].toUpperCase() }}
              </div>
              <div class="member-info">
                <div class="name">{{ team.teamlead.username }}</div>
                <div class="email">{{ team.teamlead.email }}</div>
                <div v-if="team.teamlead.name || team.teamlead.surname" class="full-name">
                  {{ team.teamlead.name }} {{ team.teamlead.surname }}
                </div>
              </div>
              <div v-if="isCurrentUserTeamlead" class="badge">
                Вы
              </div>
            </div>
          </div>

          <!-- Участники команды -->
          <div class="section">
            <h3>
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
              </svg>
              Участники команды ({{ team.members.length }})
            </h3>

            <div v-if="team.members.length > 0" class="members-grid">
              <div
                v-for="member in team.members"
                :key="member.id"
                class="member-card"
              >
                <div class="avatar">
                  {{ member.username[0].toUpperCase() }}
                </div>
                <div class="member-info">
                  <div class="name">{{ member.username }}</div>
                  <div class="email">{{ member.email }}</div>
                  <div v-if="member.name || member.surname" class="full-name">
                    {{ member.name }} {{ member.surname }}
                  </div>
                </div>
                <div v-if="member.id === authStore.user?.id" class="badge">
                  Вы
                </div>
              </div>
            </div>

            <div v-else class="empty-state">
              <p>В команде пока нет других участников</p>
            </div>
          </div>
        </div>

        <!-- Боковая панель с информацией -->
        <div class="sidebar-info">
          <div class="info-card">
            <h3>Информация о команде</h3>
            <div class="info-item">
              <span class="label">Дата создания</span>
              <span class="value">{{ formatDate(team.created_at) }}</span>
            </div>
            <div class="info-item">
              <span class="label">Последнее обновление</span>
              <span class="value">{{ formatDate(team.updated_at) }}</span>
            </div>
          </div>

          <div v-if="isCurrentUserTeamlead" class="info-card actions">
            <h3>Действия</h3>
            <button class="action-btn" @click="goToTeams">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
              </svg>
              Управление командой
            </button>
          </div>
        </div>
      </div>

      <!-- Нет команды -->
      <div v-else class="no-team">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
        </svg>
        <h2>Вы не состоите в команде</h2>
        <p>Обратитесь к администратору для добавления в команду</p>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import DashboardLayout from '../layouts/DashboardLayout.vue'
import teamsApi from '../services/teams'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const team = ref(null)
const loading = ref(true)

// Проверка: текущий пользователь - тимлид этой команды
const isCurrentUserTeamlead = computed(() => {
  return team.value?.teamlead.id === authStore.user?.id
})

// Общее количество участников (тимлид + участники)
const totalMembers = computed(() => {
  if (!team.value) return 0
  return 1 + team.value.members.length
})

// Загрузка команды
const loadMyTeam = async () => {
  loading.value = true
  try {
    const result = await teamsApi.getTeams()

    if (result.success && result.teams.length > 0) {
      // Берем первую доступную команду
      const myTeamId = result.teams[0].id

      // Загружаем детали
      const detailsResult = await teamsApi.getTeam(myTeamId)
      if (detailsResult.success) {
        team.value = detailsResult.team
      }
    }
  } catch (error) {
    console.error('Failed to load my team:', error)
  } finally {
    loading.value = false
  }
}

// Переход к управлению командами
const goToTeams = () => {
  router.push('/commands')
}

// Форматирование даты
const formatDate = (timestamp) => {
  if (!timestamp) return 'Не указано'
  const date = new Date(timestamp * 1000)
  return date.toLocaleDateString('ru-RU', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

onMounted(() => {
  loadMyTeam()
})
</script>

<style scoped>
.my-team-page {
  max-width: 1400px;
  margin: 0 auto;
}

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 5rem 2rem;
  gap: 1.5rem;
}

.spinner {
  width: 50px;
  height: 50px;
  border: 4px solid #e0e0e0;
  border-top-color: #2d3748;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.team-container {
  display: grid;
  grid-template-columns: 1fr 350px;
  gap: 2rem;
}

.team-card {
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 12px;
  padding: 2rem;
}

.team-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 2rem;
  padding-bottom: 2rem;
  border-bottom: 1px solid #e0e0e0;
}

.team-header h2 {
  margin: 0 0 0.5rem 0;
  font-size: 1.75rem;
  font-weight: 600;
  color: #1a1a1a;
}

.description {
  margin: 0;
  font-size: 0.95rem;
  color: #666;
  line-height: 1.5;
}

.team-stats {
  display: flex;
  gap: 2rem;
}

.stat {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 1rem 1.5rem;
  background: #fafafa;
  border-radius: 8px;
}

.stat-value {
  font-size: 2rem;
  font-weight: 700;
  color: #2d3748;
  line-height: 1;
}

.stat-label {
  font-size: 0.85rem;
  color: #666;
  margin-top: 0.25rem;
}

.section {
  margin-bottom: 2rem;
}

.section:last-child {
  margin-bottom: 0;
}

.section h3 {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin: 0 0 1.5rem 0;
  font-size: 1.1rem;
  font-weight: 600;
  color: #2d3748;
}

.section h3 svg {
  width: 20px;
  height: 20px;
  color: #2d3748;
}

.member-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1.25rem;
  background: #fafafa;
  border: 1px solid #e0e0e0;
  border-radius: 10px;
  transition: all 0.2s ease;
}

.member-card.teamlead {
  background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
  border: none;
  color: white;
  padding: 1.5rem;
}

.avatar,
.avatar-large {
  border-radius: 50%;
  background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  flex-shrink: 0;
}

.avatar {
  width: 48px;
  height: 48px;
  font-size: 1.1rem;
}

.avatar-large {
  width: 64px;
  height: 64px;
  font-size: 1.5rem;
}

.member-card.teamlead .avatar-large {
  background: rgba(255, 255, 255, 0.2);
}

.member-info {
  flex: 1;
}

.member-info .name {
  font-size: 1rem;
  font-weight: 600;
  margin-bottom: 0.25rem;
  color: #1a1a1a;
}

.member-card.teamlead .member-info .name {
  /* color: white; */
  font-size: 1.1rem;
}

.member-info .email {
  font-size: 0.875rem;
  color: #666;
}

.member-card.teamlead .member-info .email {
  /* color: rgba(255, 255, 255, 0.8); */
}

.member-info .full-name {
  font-size: 0.8rem;
  color: #999;
  margin-top: 0.25rem;
}

.member-card.teamlead .member-info .full-name {
  /* color: rgba(255, 255, 255, 0.7); */
}

.badge {
  padding: 0.375rem 0.875rem;
  background: rgba(45, 55, 72, 0.1);
  color: #2d3748;
  border-radius: 12px;
  font-size: 0.8rem;
  font-weight: 600;
}

.member-card.teamlead .badge {
  background: rgba(255, 255, 255, 0.2);
  color: white;
}

.members-grid {
  display: grid;
  gap: 1rem;
}

.empty-state {
  text-align: center;
  padding: 3rem 2rem;
  color: #666;
  background: #fafafa;
  border-radius: 8px;
  border: 1px dashed #e0e0e0;
}

.sidebar-info {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.info-card {
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 12px;
  padding: 1.5rem;
}

.info-card h3 {
  margin: 0 0 1rem 0;
  font-size: 1rem;
  font-weight: 600;
  color: #2d3748;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  margin-bottom: 1rem;
}

.info-item:last-child {
  margin-bottom: 0;
}

.info-item .label {
  font-size: 0.8rem;
  color: #666;
  font-weight: 500;
}

.info-item .value {
  font-size: 0.9rem;
  color: #1a1a1a;
}

.action-btn {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  padding: 0.875rem 1.25rem;
  background: #2d3748;
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 0.9rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.action-btn:hover {
  background: #1a202c;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(45, 55, 72, 0.3);
}

.action-btn svg {
  width: 18px;
  height: 18px;
}

.no-team {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 5rem 2rem;
  text-align: center;
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 12px;
}

.no-team svg {
  width: 80px;
  height: 80px;
  color: #cbd5e0;
  margin-bottom: 2rem;
}

.no-team h2 {
  margin: 0 0 0.75rem 0;
  font-size: 1.5rem;
  color: #1a1a1a;
}

.no-team p {
  margin: 0;
  font-size: 1rem;
  color: #666;
}

@media (max-width: 1024px) {
  .team-container {
    grid-template-columns: 1fr;
  }

  .sidebar-info {
    order: -1;
  }
}

@media (max-width: 768px) {
  .team-header {
    flex-direction: column;
    gap: 1.5rem;
  }

  .team-stats {
    width: 100%;
    justify-content: space-around;
  }
}
</style>
