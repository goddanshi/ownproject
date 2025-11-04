<template>
  <DashboardLayout>
    <template #header-left>
      <div class="breadcrumb">
        <RouterLink to="/projects" class="breadcrumb-link">Проекты</RouterLink>
        <span class="separator">/</span>
        <h1>{{ project?.name || 'Загрузка...' }}</h1>
      </div>
    </template>

    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Загрузка проекта...</p>
    </div>

    <div v-else-if="error" class="error-state">
      <h2>Ошибка загрузки</h2>
      <p>{{ error }}</p>
      <RouterLink to="/projects" class="btn-primary">
        Вернуться к проектам
      </RouterLink>
    </div>

    <div v-else-if="project" class="project-page">
      <!-- Карточка проекта -->
      <div class="project-card">
        <div class="project-header">
          <div class="project-main-info">
            <h2>{{ project.name }}</h2>
            <p class="description">{{ project.description || 'Описание отсутствует' }}</p>
          </div>
          <div class="project-actions" v-if="canManageProject">
            <button class="btn-icon" @click="openEditModal" title="Редактировать">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
              </svg>
            </button>
          </div>
        </div>

        <div class="project-meta">
          <div class="meta-item">
            <span class="label">Команда:</span>
            <span class="value">{{ project.team?.name }}</span>
          </div>
          <div class="meta-item">
            <span class="label">Тимлид:</span>
            <span class="value">{{ project.team?.teamlead?.name }} {{ project.team?.teamlead?.surname }}</span>
          </div>
          <div class="meta-item">
            <span class="label">Всего задач:</span>
            <span class="value">{{ project.tasks?.length || 0 }}</span>
          </div>
          <div class="meta-item">
            <span class="label">Участников:</span>
            <span class="value">{{ project.participants?.length || 0 }}</span>
          </div>
        </div>
      </div>

      <!-- Участники проекта -->
      <div class="section-card">
        <h3>Участники проекта ({{ project.participants?.length || 0 }})</h3>
        <div class="participants-grid">
          <div
            v-for="participant in project.participants"
            :key="participant.id"
            class="participant-card"
          >
            <div class="avatar">
              {{ participant.username?.[0]?.toUpperCase() || 'U' }}
            </div>
            <div class="participant-info">
              <div class="name">{{ participant.name }} {{ participant.surname }}</div>
              <div class="email">{{ participant.email }}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Задачи проекта -->
      <div class="section-card">
        <div class="section-header">
          <h3>Задачи проекта ({{ project.tasks?.length || 0 }})</h3>
          <div class="section-actions">
            <button class="btn-primary" @click="openCreateTaskModal">
              + Создать задачу
            </button>
            <RouterLink :to="`/tasks?project=${project.id}`" class="btn-secondary">
              Посмотреть все задачи →
            </RouterLink>
          </div>
        </div>

        <div v-if="project.tasks && project.tasks.length > 0" class="tasks-grid">
          <div
            v-for="task in project.tasks.slice(0, 6)"
            :key="task.id"
            class="task-card"
            @click="openTaskModal(task)"
          >
            <div class="task-header">
              <h4>{{ task.title }}</h4>
              <span :class="['status-badge', `status-${task.status}`]">
                {{ getStatusLabel(task.status) }}
              </span>
            </div>
            <p class="task-description">{{ task.description || 'Нет описания' }}</p>
            <div class="task-meta">
              <span :class="['priority-badge', `priority-${task.priority}`]">
                {{ getPriorityLabel(task.priority) }}
              </span>
              <span v-if="task.deadline" class="deadline">
                ⏰ {{ formatDate(task.deadline) }}
              </span>
            </div>
          </div>
        </div>
        <div v-else class="empty-state">
          <p>В этом проекте пока нет задач</p>
          <button class="btn-primary" @click="openCreateTaskModal">
            Создать первую задачу
          </button>
        </div>
      </div>
    </div>

    <!-- Модалка редактирования проекта -->
    <ProjectModal
      v-if="showEditModal"
      :project="project"
      @close="closeEditModal"
      @saved="handleProjectUpdated"
    />

    <!-- Модалка задачи -->
    <TaskModal
      v-if="showTaskModal"
      :task="selectedTask"
      :projectId="project?.id"
      @close="closeTaskModal"
      @saved="handleTaskSaved"
    />
  </DashboardLayout>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import DashboardLayout from '../layouts/DashboardLayout.vue'
import ProjectModal from './Projects/ProjectModal.vue'
import TaskModal from './Tasks/TaskModal.vue'
import projectsApi from '../services/projects'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const project = ref(null)
const loading = ref(true)
const error = ref('')
const showEditModal = ref(false)
const showTaskModal = ref(false)
const selectedTask = ref(null)

const canManageProject = computed(() => {
  if (!project.value) return false

  // Админ может управлять всеми проектами
  if (authStore.user?.role === 1) return true

  // Тимлид может управлять проектами своей команды
  if (authStore.user?.role === 2 && project.value.team?.teamlead?.id === authStore.user?.id) {
    return true
  }

  return false
})

const loadProject = async () => {
  try {
    loading.value = true
    error.value = ''

    const response = await projectsApi.getProject(route.params.id)

    if (response.success) {
      project.value = response.project
    } else {
      error.value = response.message || 'Не удалось загрузить проект'
    }
  } catch (err) {
    console.error('Ошибка загрузки проекта:', err)
    error.value = 'Произошла ошибка при загрузке проекта'
  } finally {
    loading.value = false
  }
}

const openEditModal = () => {
  showEditModal.value = true
}

const closeEditModal = () => {
  showEditModal.value = false
}

const handleProjectUpdated = () => {
  closeEditModal()
  loadProject()
}

const openCreateTaskModal = () => {
  selectedTask.value = null
  showTaskModal.value = true
}

const openTaskModal = (task) => {
  selectedTask.value = task
  showTaskModal.value = true
}

const closeTaskModal = () => {
  showTaskModal.value = false
  selectedTask.value = null
}

const handleTaskSaved = () => {
  closeTaskModal()
  loadProject()
}

const formatDate = (timestamp) => {
  if (!timestamp) return ''
  const date = new Date(timestamp * 1000)
  return date.toLocaleDateString('ru-RU')
}

const getStatusLabel = (status) => {
  const labels = {
    1: 'Новая',
    2: 'В работе',
    3: 'На проверке',
    4: 'Завершена',
    5: 'Отменена'
  }
  return labels[status] || 'Неизвестно'
}

const getPriorityLabel = (priority) => {
  const labels = {
    1: 'Низкий',
    2: 'Средний',
    3: 'Высокий',
    4: 'Критический'
  }
  return labels[priority] || 'Неизвестно'
}

// Следим за изменением ID проекта в URL
watch(() => route.params.id, (newId, oldId) => {
  if (newId && newId !== oldId) {
    console.log('ID проекта изменился:', oldId, '->', newId)
    loadProject()
  }
})

onMounted(() => {
  loadProject()
})
</script>

<style scoped>
.breadcrumb {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.breadcrumb h1 {
  margin: 0;
  font-size: 1.75rem;
}

.breadcrumb-link {
  color: #6b7280;
  text-decoration: none;
  font-size: 1rem;
  transition: color 0.2s;
}

.breadcrumb-link:hover {
  color: #2d3748;
}

.separator {
  color: #9ca3af;
}

.loading-state,
.error-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 2rem;
  text-align: center;
}

.spinner {
  width: 48px;
  height: 48px;
  border: 4px solid #f3f3f3;
  border-top: 4px solid #2d3748;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.error-state h2 {
  color: #dc2626;
  margin-bottom: 0.5rem;
}

.project-page {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.project-card,
.section-card {
  background: white;
  border-radius: 12px;
  padding: 2rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.project-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1.5rem;
}

.project-main-info h2 {
  margin: 0 0 0.5rem 0;
  font-size: 1.5rem;
  color: #1a1a1a;
}

.description {
  color: #6b7280;
  margin: 0;
  line-height: 1.6;
}

.project-actions {
  display: flex;
  gap: 0.5rem;
}

.btn-icon {
  width: 40px;
  height: 40px;
  border-radius: 8px;
  border: 1px solid #e0e0e0;
  background: white;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.btn-icon:hover {
  background: #f5f5f7;
  border-color: #2d3748;
}

.btn-icon svg {
  width: 20px;
  height: 20px;
  color: #666;
}

.project-meta {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  padding-top: 1.5rem;
  border-top: 1px solid #e5e7eb;
}

.meta-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.meta-item .label {
  font-size: 0.875rem;
  color: #6b7280;
  font-weight: 500;
}

.meta-item .value {
  font-size: 1rem;
  color: #1a1a1a;
  font-weight: 600;
}

.section-card h3 {
  margin: 0 0 1.5rem 0;
  font-size: 1.25rem;
  color: #1a1a1a;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.section-header h3 {
  margin: 0;
}

.section-actions {
  display: flex;
  gap: 0.75rem;
  align-items: center;
}

.participants-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 1rem;
}

.participant-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  transition: all 0.2s;
}

.participant-card:hover {
  border-color: #2d3748;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.avatar {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  font-weight: 600;
  flex-shrink: 0;
}

.participant-info {
  flex: 1;
  min-width: 0;
}

.participant-info .name {
  font-weight: 600;
  color: #1a1a1a;
  margin-bottom: 0.25rem;
}

.participant-info .email {
  font-size: 0.875rem;
  color: #6b7280;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.tasks-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1rem;
}

.task-card {
  padding: 1.25rem;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  transition: all 0.2s;
  cursor: pointer;
}

.task-card:hover {
  border-color: #2d3748;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  transform: translateY(-2px);
}

.task-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 0.75rem;
  margin-bottom: 0.75rem;
}

.task-header h4 {
  margin: 0;
  font-size: 1rem;
  color: #1a1a1a;
  flex: 1;
}

.task-description {
  font-size: 0.875rem;
  color: #6b7280;
  margin: 0 0 1rem 0;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}

.task-meta {
  display: flex;
  gap: 0.75rem;
  align-items: center;
  flex-wrap: wrap;
}

.status-badge,
.priority-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 500;
  white-space: nowrap;
}

.status-1 { background: #dbeafe; color: #1e40af; }
.status-2 { background: #fef3c7; color: #92400e; }
.status-3 { background: #e0e7ff; color: #4338ca; }
.status-4 { background: #d1fae5; color: #065f46; }
.status-5 { background: #fee2e2; color: #991b1b; }

.priority-1 { background: #f3f4f6; color: #6b7280; }
.priority-2 { background: #dbeafe; color: #1e40af; }
.priority-3 { background: #fef3c7; color: #92400e; }
.priority-4 { background: #fee2e2; color: #991b1b; }

.deadline {
  font-size: 0.75rem;
  color: #6b7280;
}

.empty-state {
  padding: 3rem;
  text-align: center;
  color: #9ca3af;
  font-style: italic;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
}

.empty-state p {
  margin: 0;
}

.btn-primary,
.btn-secondary {
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 500;
  text-decoration: none;
  display: inline-block;
  transition: all 0.2s;
  border: none;
  cursor: pointer;
}

.btn-primary {
  background: #2d3748;
  color: white;
}

.btn-primary:hover {
  background: #1a202c;
}

.btn-secondary {
  background: #f3f4f6;
  color: #374151;
}

.btn-secondary:hover {
  background: #e5e7eb;
}

@media (max-width: 768px) {
  .project-header {
    flex-direction: column;
    gap: 1rem;
  }

  .project-meta {
    grid-template-columns: 1fr;
  }

  .participants-grid,
  .tasks-grid {
    grid-template-columns: 1fr;
  }
}
</style>
