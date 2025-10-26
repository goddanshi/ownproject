<template>
  <DashboardLayout>
    <template #header-left>
      <h1>Команды</h1>
    </template>

    <div class="teams-page">
      <!-- Хедер с кнопкой создания -->
      <div class="page-header">
        <div class="header-info">
          <h2>Управление командами</h2>
          <p class="subtitle">Всего команд: {{ teams.length }}</p>
        </div>
        <button
          v-if="authStore.can('manage_teams')"
          class="btn-primary"
          @click="openCreateModal"
        >
          + Создать команду
        </button>
      </div>

      <!-- Загрузка -->
      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Загрузка команд...</p>
      </div>

      <!-- Список команд -->
      <div v-else-if="teams.length > 0" class="teams-grid">
        <div
          v-for="team in teams"
          :key="team.id"
          class="team-card"
          @click="openTeamDetails(team.id)"
        >
          <div class="team-header">
            <h3>{{ team.name }}</h3>
            <span class="members-badge">{{1 + team.members_count }} участников</span>
          </div>

          <p class="team-description">
            {{ team.description || 'Описание отсутствует' }}
          </p>

          <div class="team-footer">
            <div class="teamlead-info">
              <div class="avatar-small">
                {{ team.teamlead.username[0].toUpperCase() }}
              </div>
              <div class="teamlead-details">
                <span class="label">Тимлид:</span>
                <span class="name">{{ team.teamlead.username }}</span>
              </div>
            </div>

            <div class="team-actions">
              <button
                v-if="canManageTeam(team)"
                class="btn-icon"
                @click.stop="openEditModal(team)"
                title="Редактировать"
              >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                </svg>
              </button>
              <button
                v-if="authStore.can('delete_teams')"
                class="btn-icon danger"
                @click.stop="confirmDelete(team)"
                title="Удалить"
              >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Пустое состояние -->
      <div v-else class="empty-state">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
        </svg>
        <h3>У вас пока нет команд</h3>
        <p>Создайте первую команду для начала работы</p>
        <button
          v-if="authStore.can('manage_teams')"
          class="btn-primary"
          @click="openCreateModal"
        >
          Создать команду
        </button>
      </div>

      <!-- Модалка создания/редактирования -->
      <TeamModal
        v-if="showModal"
        :team="selectedTeam"
        @close="closeModal"
        @saved="handleTeamSaved"
      />

      <!-- Модалка деталей команды -->
      <TeamDetailsModal
        v-if="showDetailsModal"
        :teamId="selectedTeamId"
        @close="closeDetailsModal"
        @updated="loadTeams"
      />

      <!-- Модалка подтверждения удаления -->
      <ConfirmModal
        v-if="showDeleteModal"
        title="Удалить команду?"
        :message="deleteMessage"
        @confirm="handleDelete"
        @cancel="closeDeleteModal"
      />
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import DashboardLayout from '../layouts/DashboardLayout.vue'
import TeamModal from '@/views/Commands/TeamModal.vue'
import TeamDetailsModal from '@/views/Commands/TeamDetailsModal.vue'
import ConfirmModal from '../components/ConfirmModal.vue'
import teamsApi from '../services/teams'
import { useAuthStore } from '../stores/auth'

const authStore = useAuthStore()

const teams = ref([])
const loading = ref(true)
const showModal = ref(false)
const showDetailsModal = ref(false)
const showDeleteModal = ref(false)
const selectedTeam = ref(null)
const selectedTeamId = ref(null)
const teamToDelete = ref(null)

// Computed для сообщения об удалении
const deleteMessage = computed(() =>
  `Вы уверены, что хотите удалить команду "${teamToDelete.value?.name}"? Это действие нельзя отменить.`
)

// Проверка: может ли пользователь управлять командой
const canManageTeam = (team) => {
  // Админ может управлять всеми
  if (authStore.isAdmin) {
    return true
  }
  // Тимлид может управлять только своей командой
  if (authStore.isTeamlead && team.teamlead.id === authStore.user?.id) {
    return true
  }
  return false
}

// Загрузка команд
const loadTeams = async () => {
  loading.value = true
  try {
    const result = await teamsApi.getTeams()
    if (result.success) {
      teams.value = result.teams
    }
  } catch (error) {
    console.error('Failed to load teams:', error)
  } finally {
    loading.value = false
  }
}

// Открыть модалку создания
const openCreateModal = () => {
  selectedTeam.value = null
  showModal.value = true
}

// Открыть модалку редактирования
const openEditModal = (team) => {
  selectedTeam.value = team
  showModal.value = true
}

// Закрыть модалку
const closeModal = () => {
  showModal.value = false
  selectedTeam.value = null
}

// Открыть детали команды
const openTeamDetails = (teamId) => {
  selectedTeamId.value = teamId
  showDetailsModal.value = true
}

// Закрыть детали
const closeDetailsModal = () => {
  showDetailsModal.value = false
  selectedTeamId.value = null
}

// После сохранения команды
const handleTeamSaved = () => {
  closeModal()
  loadTeams()
}

// Подтверждение удаления
const confirmDelete = (team) => {
  teamToDelete.value = team
  showDeleteModal.value = true
}

// Закрыть модалку удаления
const closeDeleteModal = () => {
  showDeleteModal.value = false
  teamToDelete.value = null
}

// Удалить команду
const handleDelete = async () => {
  try {
    const result = await teamsApi.deleteTeam(teamToDelete.value.id)
    if (result.success) {
      await loadTeams()
    }
  } catch (error) {
    console.error('Failed to delete team:', error)
  } finally {
    closeDeleteModal()
  }
}

onMounted(() => {
  loadTeams()
})
</script>

<style scoped>
.teams-page {
  max-width: 1400px;
  margin: 0 auto;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid #e0e0e0;
}

.header-info h2 {
  margin: 0 0 0.5rem 0;
  font-size: 1.5rem;
  font-weight: 600;
  color: #1a1a1a;
}

.subtitle {
  margin: 0;
  font-size: 0.9rem;
  color: #666;
}

.btn-primary {
  padding: 0.75rem 1.5rem;
  background: #2d3748;
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 0.95rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-primary:hover {
  background: #1a202c;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(45, 55, 72, 0.3);
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

.teams-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 1.5rem;
}

.team-card {
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 12px;
  padding: 1.5rem;
  cursor: pointer;
  transition: all 0.2s ease;
}

.team-card:hover {
  border-color: #2d3748;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  transform: translateY(-2px);
}

.team-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.team-header h3 {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 600;
  color: #1a1a1a;
}

.members-badge {
  padding: 0.25rem 0.75rem;
  background: #f0f0f0;
  border-radius: 12px;
  font-size: 0.8rem;
  color: #666;
  font-weight: 500;
}

.team-description {
  margin: 0 0 1.5rem 0;
  font-size: 0.9rem;
  color: #666;
  line-height: 1.5;
  min-height: 3rem;
}

.team-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 1rem;
  border-top: 1px solid #f0f0f0;
}

.teamlead-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.avatar-small {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.9rem;
  font-weight: 600;
}

.teamlead-details {
  display: flex;
  flex-direction: column;
  gap: 0.125rem;
}

.teamlead-details .label {
  font-size: 0.75rem;
  color: #999;
}

.teamlead-details .name {
  font-size: 0.9rem;
  color: #1a1a1a;
  font-weight: 500;
}

.team-actions {
  display: flex;
  gap: 0.5rem;
}

.btn-icon {
  width: 36px;
  height: 36px;
  padding: 0;
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-icon:hover {
  background: #2d3748;
  border-color: #2d3748;
}

.btn-icon:hover svg {
  color: white;
}

.btn-icon.danger:hover {
  background: #dc2626;
  border-color: #dc2626;
}

.btn-icon svg {
  width: 18px;
  height: 18px;
  color: #666;
  transition: color 0.2s ease;
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 5rem 2rem;
  text-align: center;
}

.empty-state svg {
  width: 80px;
  height: 80px;
  color: #cbd5e0;
  margin-bottom: 1.5rem;
}

.empty-state h3 {
  margin: 0 0 0.5rem 0;
  font-size: 1.5rem;
  color: #1a1a1a;
}

.empty-state p {
  margin: 0 0 2rem 0;
  color: #666;
  font-size: 1rem;
}

@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }

  .teams-grid {
    grid-template-columns: 1fr;
  }
}
</style>
