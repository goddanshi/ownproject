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
          <div class="project-logo" v-if="project.logo_url">
            <img :src="project.logo_url" :alt="project.name" @error="handleLogoError" />
          </div>
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

        <!-- Кнопка с информацией о команде -->
        <div class="team-info-toggle">
          <button class="toggle-btn" @click="showTeamInfo = !showTeamInfo">
            <span class="toggle-label">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="icon">
                <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
              </svg>
              Команда и статистика
            </span>
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="chevron" :class="{ 'rotated': showTeamInfo }">
              <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
            </svg>
          </button>

          <transition name="expand">
            <div v-if="showTeamInfo" class="team-info-content">
              <div class="info-grid">
                <div class="info-item">
                  <span class="label">Команда:</span>
                  <span class="value">{{ project.team?.name }}</span>
                </div>
                <div class="info-item">
                  <span class="label">Тимлид:</span>
                  <span class="value">{{ project.team?.teamlead?.name }} {{ project.team?.teamlead?.surname }}</span>
                </div>
                <div class="info-item">
                  <span class="label">Всего задач:</span>
                  <span class="value">{{ project.tasks?.length || 0 }}</span>
                </div>
                <div class="info-item">
                  <span class="label">Участников:</span>
                  <span class="value">{{ project.participants?.length || 0 }}</span>
                </div>
              </div>

              <!-- Участники проекта внутри раскрывающейся секции -->
              <div class="participants-section">
                <h4 class="participants-title">Участники проекта</h4>
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
            </div>
          </transition>
        </div>

        <!-- Дополнительная информация о проекте -->
        <div class="project-details-toggle" v-if="project.start_date || project.end_date || project.documents_url">
          <button class="toggle-btn" @click="showProjectDetails = !showProjectDetails">
            <span class="toggle-label">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="icon">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
              </svg>
              Дополнительная информация
            </span>
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="chevron" :class="{ 'rotated': showProjectDetails }">
              <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
            </svg>
          </button>

          <transition name="expand">
            <div v-if="showProjectDetails" class="project-details-content">
              <div class="details-grid">
                <div class="detail-item" v-if="project.start_date">
                  <div class="detail-content">
                    <span class="detail-label">Дата начала</span>
                    <span class="detail-value">{{ formatDate(project.start_date) }}</span>
                  </div>
                </div>
                <div class="detail-item" v-if="project.end_date">
                  <div class="detail-content">
                    <span class="detail-label">Дата завершения</span>
                    <span class="detail-value">{{ formatDate(project.end_date) }}</span>
                  </div>
                </div>
                <div class="detail-item full-width" v-if="project.documents_url">
                  <div class="detail-content">
                    <span class="detail-label">Папка проекта</span>
                    <a :href="project.documents_url" target="_blank" rel="noopener noreferrer" class="detail-link">
                      Открыть папку с документами →
                    </a>
                  </div>
                </div>
              </div>
            </div>
          </transition>
        </div>
      </div>

      <!-- Задачи проекта -->
      <div class="section-card">
        <div class="section-header">
          <h3>Задачи проекта ({{ project.tasks?.length || 0 }})</h3>
          <div class="section-actions">
            <div class="view-toggle">
              <button
                :class="['view-btn', { active: taskViewMode === 'list' }]"
                @click="changeTaskViewMode('list')"
                title="Список"
              >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                </svg>
              </button>
              <button
                :class="['view-btn', { active: taskViewMode === 'table' }]"
                @click="changeTaskViewMode('table')"
                title="Таблица"
              >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3.375 19.5h17.25m-17.25 0a1.125 1.125 0 0 1-1.125-1.125M3.375 19.5h7.5c.621 0 1.125-.504 1.125-1.125m-9.75 0V5.625m0 12.75v-1.5c0-.621.504-1.125 1.125-1.125m18.375 2.625V5.625m0 12.75c0 .621-.504 1.125-1.125 1.125m1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125m0 3.75h-7.5A1.125 1.125 0 0 1 12 18.375m9.75-12.75c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125m19.5 0v1.5c0 .621-.504 1.125-1.125 1.125M2.25 5.625v1.5c0 .621.504 1.125 1.125 1.125m0 0h17.25m-17.25 0h7.5c.621 0 1.125.504 1.125 1.125M3.375 8.25c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125m17.25-3.75h-7.5c-.621 0-1.125.504-1.125 1.125m8.625-1.125c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125m-17.25 0h7.5m-7.5 0c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125M12 10.875v-1.5m0 1.5c0 .621-.504 1.125-1.125 1.125M12 10.875c0 .621.504 1.125 1.125 1.125m-2.25 0c.621 0 1.125.504 1.125 1.125M13.125 12h7.5m-7.5 0c-.621 0-1.125.504-1.125 1.125M20.625 12c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125m-17.25 0h7.5M12 14.625v-1.5m0 1.5c0 .621-.504 1.125-1.125 1.125M12 14.625c0 .621.504 1.125 1.125 1.125m-2.25 0c.621 0 1.125.504 1.125 1.125m0 1.5v-1.5m0 0c0-.621.504-1.125 1.125-1.125m0 0h7.5" />
                </svg>
              </button>
            </div>
            <button class="btn-primary" @click="openCreateTaskModal">
              + Создать задачу
            </button>
          </div>
        </div>

        <!-- Списочный вид задач -->
        <div v-if="project.tasks && project.tasks.length > 0 && taskViewMode === 'list'" class="tasks-list">
          <div
            v-for="task in project.tasks"
            :key="task.id"
            class="task-row"
          >
            <div class="task-main" @click="openTaskDetails(task.id)">
              <div class="task-title-section">
                <h4 class="task-name">{{ task.title }}</h4>
                <span :class="['status-badge', `status-${task.status}`]">
                  {{ getStatusLabel(task.status) }}
                </span>
              </div>

              <div class="task-info-grid">
                <div class="task-info-item">
                  <span class="info-label">Исполнитель:</span>
                  <span class="info-value">
                    <span v-if="task.assignees && task.assignees.length > 0">
                      {{ task.assignees.map(a => `${a.name} ${a.surname}`).join(', ') }}
                    </span>
                    <span v-else class="not-assigned">Не назначен</span>
                  </span>
                </div>

                <div class="task-info-item">
                  <span class="info-label">Проект:</span>
                  <span class="info-value">{{ project.name }}</span>
                </div>

                <div class="task-info-item">
                  <span class="info-label">Даты:</span>
                  <span class="info-value">
                    <span v-if="task.start_date || task.deadline">
                      <span v-if="task.start_date">{{ formatDate(task.start_date) }}</span>
                      <span v-if="task.start_date && task.deadline"> - </span>
                      <span v-if="task.deadline">{{ formatDate(task.deadline) }}</span>
                    </span>
                    <span v-else class="no-dates">Не указаны</span>
                  </span>
                </div>

                <div class="task-info-item">
                  <span class="info-label">Приоритет:</span>
                  <span :class="['priority-badge', `priority-${task.priority}`]">
                    {{ getPriorityLabel(task.priority) }}
                  </span>
                </div>
              </div>
            </div>

            <div class="task-actions">
              <button
                :class="['timer-btn', { 'tracking': isTaskTracking(task.id) }]"
                @click.stop="toggleTimer(task.id)"
                :title="isTaskTracking(task.id) ? 'Остановить таймер' : 'Запустить таймер'"
              >
                <svg v-if="!isTaskTracking(task.id)" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z" />
                </svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25v13.5m-7.5-13.5v13.5" />
                </svg>
              </button>
            </div>
          </div>
        </div>

        <!-- Табличный вид задач -->
        <div v-if="project.tasks && project.tasks.length > 0 && taskViewMode === 'table'" class="tasks-table-wrapper">
          <table class="tasks-table">
            <thead>
              <tr>
                <th class="col-title">Название</th>
                <th class="col-assignee">Исполнитель</th>
                <th class="col-status">Статус</th>
                <th class="col-priority">Приоритет</th>
                <th class="col-dates">Даты</th>
                <th class="col-timer">Таймер</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="task in project.tasks"
                :key="task.id"
                class="task-row-table"
              >
                <td class="col-title" @click="openTaskDetails(task.id)">
                  <div class="task-title-cell">
                    <span class="task-title-text">{{ task.title }}</span>
                  </div>
                </td>
                <td class="col-assignee" @click="openTaskDetails(task.id)">
                  <div class="assignees-cell">
                    <span v-if="task.assignees && task.assignees.length > 0" class="assignees-text">
                      {{ task.assignees.map(a => `${a.name} ${a.surname}`).join(', ') }}
                    </span>
                    <span v-else class="not-assigned-text">Не назначен</span>
                  </div>
                </td>
                <td class="col-status" @click="openTaskDetails(task.id)">
                  <span :class="['status-badge', `status-${task.status}`]">
                    {{ getStatusLabel(task.status) }}
                  </span>
                </td>
                <td class="col-priority" @click="openTaskDetails(task.id)">
                  <span :class="['priority-badge', `priority-${task.priority}`]">
                    {{ getPriorityLabel(task.priority) }}
                  </span>
                </td>
                <td class="col-dates" @click="openTaskDetails(task.id)">
                  <div class="dates-cell">
                    <span v-if="task.start_date" class="date-item start">
                      {{ formatDate(task.start_date) }}
                    </span>
                    <span v-if="task.deadline" class="date-item deadline">
                      {{ formatDate(task.deadline) }}
                    </span>
                    <span v-if="!task.start_date && !task.deadline" class="no-dates-text">
                      —
                    </span>
                  </div>
                </td>
                <td class="col-timer">
                  <button
                    :class="['timer-btn-table', { 'tracking': isTaskTracking(task.id) }]"
                    @click.stop="toggleTimer(task.id)"
                    :title="isTaskTracking(task.id) ? 'Остановить таймер' : 'Запустить таймер'"
                  >
                    <svg v-if="!isTaskTracking(task.id)" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z" />
                    </svg>
                    <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25v13.5m-7.5-13.5v13.5" />
                    </svg>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
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

    <!-- Модалка задачи (редактирование) -->
    <TaskModal
      v-if="showTaskModal"
      :task="selectedTask"
      :projectId="project?.id"
      @close="closeTaskModal"
      @saved="handleTaskSaved"
    />

    <!-- Модалка детализации задачи -->
    <TaskDetailsModal
      v-if="showTaskDetailsModal && selectedTaskId"
      :task-id="selectedTaskId"
      @close="closeTaskDetailsModal"
      @edit="handleTaskEdit"
      @delete="handleTaskDelete"
    />
  </DashboardLayout>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useTaskEvents } from '../composables/useTaskEvents'
import DashboardLayout from '../layouts/DashboardLayout.vue'
import ProjectModal from './Projects/ProjectModal.vue'
import TaskModal from './Tasks/TaskModal.vue'
import TaskDetailsModal from './Tasks/TaskDetailsModal.vue'
import projectsApi from '../services/projects'
import tasksApi from '../services/tasks'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const { onTaskEvent } = useTaskEvents()

const project = ref(null)
const loading = ref(true)
const error = ref('')
const showEditModal = ref(false)
const showTaskModal = ref(false)
const showTaskDetailsModal = ref(false)
const selectedTask = ref(null)
const selectedTaskId = ref(null)
const showTeamInfo = ref(false)
const showProjectDetails = ref(false)
const taskViewMode = ref(localStorage.getItem('project-tasks-view-mode') || 'list')
const trackingTasks = ref(new Set()) // Хранит ID задач с активным отслеживанием

let unsubscribe = null

// Функция изменения режима просмотра задач
const changeTaskViewMode = (mode) => {
  taskViewMode.value = mode
  localStorage.setItem('project-tasks-view-mode', mode)
}

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
      checkActiveTracking()
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

const openTaskDetails = (taskId) => {
  selectedTaskId.value = taskId
  showTaskDetailsModal.value = true
}

const closeTaskDetailsModal = () => {
  showTaskDetailsModal.value = false
  selectedTaskId.value = null
}

const handleTaskEdit = (task) => {
  closeTaskDetailsModal()
  selectedTask.value = task
  showTaskModal.value = true
}

const handleTaskDelete = () => {
  closeTaskDetailsModal()
  loadProject()
}

const isTaskTracking = (taskId) => {
  return trackingTasks.value.has(taskId)
}

const toggleTimer = async (taskId) => {
  if (isTaskTracking(taskId)) {
    await stopTimer(taskId)
  } else {
    await startTimer(taskId)
  }
}

const startTimer = async (taskId) => {
  try {
    await tasksApi.startTracking(taskId)
    trackingTasks.value.add(taskId)
    // Обновляем проект для получения свежих данных
    await loadProject()
  } catch (error) {
    console.error('Ошибка запуска таймера:', error)
  }
}

const stopTimer = async (taskId) => {
  try {
    await tasksApi.stopTracking(taskId)
    trackingTasks.value.delete(taskId)
    // Обновляем проект для получения свежих данных
    await loadProject()
  } catch (error) {
    console.error('Ошибка остановки таймера:', error)
  }
}

const checkActiveTracking = () => {
  if (!project.value?.tasks) return

  trackingTasks.value.clear()

  // Проверяем каждую задачу на активное отслеживание текущего пользователя
  project.value.tasks.forEach(task => {
    if (task.time_trackings) {
      const userTracking = task.time_trackings.find(
        t => t.user_id === authStore.user?.id && !t.ended_at
      )
      if (userTracking) {
        trackingTasks.value.add(task.id)
      }
    }
  })
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

const handleLogoError = (event) => {
  event.target.style.display = 'none'
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

  // Подписываемся на события задач для обновления без перезагрузки
  unsubscribe = onTaskEvent('*', (event) => {
    if (!project.value?.tasks) return

    const { taskId, type, data } = event
    const taskIndex = project.value.tasks.findIndex(t => t.id === taskId)

    if (taskIndex === -1) return

    // Обновляем статус задачи в списке
    if (type === 'status_changed') {
      project.value.tasks[taskIndex].status = data.newStatus
      // Принудительно обновляем реактивность
      project.value = { ...project.value }
    }
  })
})

onUnmounted(() => {
  if (unsubscribe) {
    unsubscribe()
  }
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
  align-items: flex-start;
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.project-logo {
  flex-shrink: 0;
}

.project-logo img {
  width: 64px;
  height: 64px;
  object-fit: cover;
  border-radius: 50%;
  background: #f9fafb;
}

.project-main-info {
  flex: 1;
  min-width: 0;
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

/* Кнопка переключения информации о команде */
.team-info-toggle {
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid #e5e7eb;
}

.toggle-btn {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1.25rem;
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.toggle-btn:hover {
  background: #f3f4f6;
  border-color: #2d3748;
}

.toggle-label {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-weight: 600;
  color: #2d3748;
  font-size: 1rem;
}

.toggle-label .icon {
  width: 24px;
  height: 24px;
  color: #667eea;
}

.chevron {
  width: 20px;
  height: 20px;
  color: #6b7280;
  transition: transform 0.3s ease;
}

.chevron.rotated {
  transform: rotate(180deg);
}

.team-info-content {
  margin-top: 1rem;
  padding: 1.25rem;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.25rem;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.info-item .label {
  font-size: 0.875rem;
  color: #6b7280;
  font-weight: 500;
}

.info-item .value {
  font-size: 1.05rem;
  color: #1a1a1a;
  font-weight: 600;
}

/* Секция участников внутри раскрывающегося блока */
.participants-section {
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid #e5e7eb;
}

.participants-title {
  margin: 0 0 1rem 0;
  font-size: 1rem;
  font-weight: 600;
  color: #2d3748;
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
  background: white;
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

/* Блок с дополнительной информацией проекта */
.project-details-toggle {
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid #e5e7eb;
}

.project-details-content {
  margin-top: 1rem;
  padding: 1.25rem;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
}

.details-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.25rem;
}

.detail-item {
  display: flex;
  flex-direction: column;
}

.detail-item.full-width {
  grid-column: 1 / -1;
}

.detail-content {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.detail-label {
  font-size: 0.875rem;
  color: #6b7280;
  font-weight: 500;
}

.detail-value {
  font-size: 1.05rem;
  color: #1a1a1a;
  font-weight: 600;
}

.detail-link {
  color: #2563eb;
  text-decoration: none;
  font-weight: 600;
  transition: color 0.2s;
  font-size: 1.05rem;
}

.detail-link:hover {
  color: #1d4ed8;
  text-decoration: underline;
}

/* Анимация раскрытия */
.expand-enter-active,
.expand-leave-active {
  transition: all 0.3s ease;
  overflow: hidden;
}

.expand-enter-from,
.expand-leave-to {
  opacity: 0;
  max-height: 0;
}

.expand-enter-to,
.expand-leave-from {
  opacity: 1;
  max-height: 2000px;
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

.view-toggle {
  display: flex;
  gap: 0;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  overflow: hidden;
}

.view-btn {
  padding: 0.6rem;
  background: white;
  border: none;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
}

.view-btn svg {
  width: 18px;
  height: 18px;
  color: #6b7280;
}

.view-btn:first-child {
  border-right: 1px solid #e5e7eb;
}

.view-btn:hover {
  background: #f9fafb;
}

.view-btn.active {
  background: #2d3748;
}

.view-btn.active svg {
  color: white;
}

.tasks-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.task-row {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1.25rem;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  transition: all 0.2s;
  background: white;
}

.task-row:hover {
  border-color: #2d3748;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.task-main {
  flex: 1;
  min-width: 0;
  cursor: pointer;
}

.task-title-section {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1rem;
}

.task-name {
  margin: 0;
  font-size: 1.125rem;
  font-weight: 600;
  color: #1a1a1a;
  flex: 1;
}

.task-info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 0.75rem;
}

.task-info-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.info-label {
  font-size: 0.75rem;
  color: #6b7280;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.025em;
}

.info-value {
  font-size: 0.95rem;
  color: #1a1a1a;
  font-weight: 500;
}

.not-assigned,
.no-dates {
  color: #9ca3af;
  font-style: italic;
  font-weight: 400;
}

.task-actions {
  display: flex;
  gap: 0.5rem;
  flex-shrink: 0;
}

.timer-btn {
  width: 48px;
  height: 48px;
  border-radius: 10px;
  border: 1px solid #e5e7eb;
  background: white;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.timer-btn:hover {
  background: #f0fdf4;
  border-color: #10b981;
}

.timer-btn.tracking {
  background: #fee2e2;
  border-color: #dc2626;
}

.timer-btn.tracking:hover {
  background: #fecaca;
  border-color: #b91c1c;
}

.timer-btn svg {
  width: 24px;
  height: 24px;
  color: #10b981;
}

.timer-btn.tracking svg {
  color: #dc2626;
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

  .participants-grid {
    grid-template-columns: 1fr;
  }

  .task-row {
    flex-direction: column;
    align-items: stretch;
  }

  .task-info-grid {
    grid-template-columns: 1fr;
  }

  .task-actions {
    width: 100%;
    justify-content: flex-end;
  }

  .section-actions {
    flex-direction: column;
    width: 100%;
  }

  .section-actions > * {
    width: 100%;
  }

  .tasks-table-wrapper {
    overflow-x: scroll;
  }

  .tasks-table {
    min-width: 800px;
  }
}

/* Таблица задач в ProjectView */
.tasks-table-wrapper {
  overflow-x: auto;
  border-radius: 10px;
  border: 1px solid #e5e7eb;
}

.tasks-table {
  width: 100%;
  border-collapse: collapse;
  background: white;
}

.tasks-table thead {
  background: #f9fafb;
  border-bottom: 2px solid #e5e7eb;
}

.tasks-table th {
  padding: 1rem;
  text-align: left;
  font-weight: 600;
  font-size: 0.85rem;
  color: #4b5563;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  white-space: nowrap;
}

.tasks-table tbody tr {
  border-bottom: 1px solid #f0f0f0;
  transition: background 0.2s;
}

.tasks-table tbody tr:hover {
  background: #f9fafb;
}

.tasks-table tbody tr:last-child {
  border-bottom: none;
}

.tasks-table td {
  padding: 1rem;
  vertical-align: middle;
  cursor: pointer;
}

.tasks-table .col-title {
  width: 35%;
  min-width: 250px;
}

.tasks-table .col-assignee {
  width: 20%;
  min-width: 150px;
}

.tasks-table .col-status {
  width: 12%;
  min-width: 110px;
}

.tasks-table .col-priority {
  width: 12%;
  min-width: 110px;
}

.tasks-table .col-dates {
  width: 15%;
  min-width: 140px;
}

.tasks-table .col-timer {
  width: 6%;
  min-width: 80px;
  text-align: center;
}

.task-title-cell {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.task-title-text {
  font-weight: 600;
  color: #1a1a1a;
  font-size: 0.95rem;
}

.assignees-cell {
  display: flex;
  align-items: center;
}

.assignees-text {
  font-size: 0.9rem;
  color: #4b5563;
  font-weight: 500;
}

.not-assigned-text {
  font-size: 0.85rem;
  color: #9ca3af;
  font-style: italic;
}

.dates-cell {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.date-item {
  font-size: 0.85rem;
  font-weight: 500;
}

.date-item.start {
  color: #059669;
}

.date-item.deadline {
  color: #dc2626;
}

.no-dates-text {
  color: #9ca3af;
  font-size: 0.85rem;
}

.timer-btn-table {
  width: 40px;
  height: 40px;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
  background: white;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.timer-btn-table:hover {
  background: #f0fdf4;
  border-color: #10b981;
}

.timer-btn-table.tracking {
  background: #fee2e2;
  border-color: #dc2626;
}

.timer-btn-table.tracking:hover {
  background: #fecaca;
  border-color: #b91c1c;
}

.timer-btn-table svg {
  width: 20px;
  height: 20px;
  color: #10b981;
}

.timer-btn-table.tracking svg {
  color: #dc2626;
}
</style>
