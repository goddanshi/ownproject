<template>
  <DashboardLayout>
    <template #header-left>
      <h1>Задачи</h1>
    </template>

    <div class="tasks-page">
      <!-- Фильтры и кнопка создания -->
      <div class="page-header">
        <div class="header-info">
          <h2>Управление задачами</h2>
          <p class="subtitle">Всего задач: {{ filteredTasks.length }}</p>
        </div>
        <div class="header-actions">
          <select v-model="selectedProject" class="filter-select">
            <option value="">Все проекты</option>
            <option
              v-for="project in projects"
              :key="project.id"
              :value="project.id"
            >
              {{ project.name }}
            </option>
          </select>
          <select v-model="selectedStatus" class="filter-select">
            <option value="">Все статусы</option>
            <option value="1">К выполнению</option>
            <option value="2">В работе</option>
            <option value="3">На проверке</option>
            <option value="4">Выполнено</option>
          </select>
          <button class="btn-primary" @click="openCreateModal">
            + Создать задачу
          </button>
        </div>
      </div>

      <!-- Загрузка -->
      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Загрузка задач...</p>
      </div>

      <!-- Список задач -->
      <div v-else-if="filteredTasks.length > 0" class="tasks-grid">
        <div
          v-for="task in filteredTasks"
          :key="task.id"
          class="task-card"
          @click="openTaskDetails(task.id)"
        >
          <div class="task-header">
            <h3>{{ task.title }}</h3>
            <span :class="['status-badge', `status-${task.status}`]">
              {{ task.status_label }}
            </span>
          </div>

          <p class="task-description">
            {{ task.description || 'Описание отсутствует' }}
          </p>

          <div class="task-meta">
            <span :class="['priority-badge', `priority-${task.priority}`]">
              {{ task.priority_label }}
            </span>
            <span class="project-name">{{ task.project.name }}</span>
            <span v-if="task.deadline" class="deadline">
              ⏰ {{ formatDate(task.deadline) }}
            </span>
          </div>

          <div class="task-footer">
            <div class="assignees">
              <div
                v-for="assignee in task.assignees.slice(0, 3)"
                :key="assignee.id"
                class="avatar-small"
                :title="`${assignee.name} ${assignee.surname}`"
              >
                {{ assignee.username[0].toUpperCase() }}
              </div>
              <span v-if="task.assignees.length > 3" class="more-count">
                +{{ task.assignees.length - 3 }}
              </span>
            </div>
            <div class="time-info" v-if="task.total_time > 0">
              ⏱️ {{ formatDuration(task.total_time) }}
            </div>
          </div>
        </div>
      </div>

      <!-- Пустое состояние -->
      <div v-else class="empty-state">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
        </svg>
        <h3>Задач не найдено</h3>
        <p>Создайте первую задачу для начала работы</p>
        <button class="btn-primary" @click="openCreateModal">
          Создать задачу
        </button>
      </div>

      <!-- Модалка создания/редактирования -->
      <TaskModal
        v-if="showModal"
        :task="selectedTask"
        :projects="projects"
        @close="closeModal"
        @saved="handleTaskSaved"
      />

      <!-- Модалка деталей задачи -->
      <TaskDetailsModal
        v-if="showDetailsModal"
        :taskId="selectedTaskId"
        @close="closeDetailsModal"
        @updated="loadTasks"
        @edit="handleEditTask"
        @delete="handleDeleteTask"
      />
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import DashboardLayout from '../layouts/DashboardLayout.vue'
import TaskModal from './Tasks/TaskModal.vue'
import TaskDetailsModal from './Tasks/TaskDetailsModal.vue'
import tasksApi from '../services/tasks'
import projectsApi from '../services/projects'

const tasks = ref([])
const projects = ref([])
const loading = ref(true)
const showModal = ref(false)
const showDetailsModal = ref(false)
const selectedTask = ref(null)
const selectedTaskId = ref(null)
const selectedProject = ref('')
const selectedStatus = ref('')

// Отфильтрованные задачи
const filteredTasks = computed(() => {
  let filtered = tasks.value

  if (selectedProject.value) {
    filtered = filtered.filter(task => task.project.id === parseInt(selectedProject.value))
  }

  if (selectedStatus.value) {
    filtered = filtered.filter(task => task.status === parseInt(selectedStatus.value))
  }

  return filtered
})

// Загрузка данных
const loadTasks = async () => {
  try {
    loading.value = true
    const response = await tasksApi.getTasks()
    if (response.success) {
      tasks.value = response.tasks
    }
  } catch (error) {
    console.error('Ошибка загрузки задач:', error)
  } finally {
    loading.value = false
  }
}

const loadProjects = async () => {
  try {
    const response = await projectsApi.getProjects()
    if (response.success) {
      projects.value = response.projects
    }
  } catch (error) {
    console.error('Ошибка загрузки проектов:', error)
  }
}

// Модалки
const openCreateModal = () => {
  selectedTask.value = null
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  selectedTask.value = null
}

const openTaskDetails = (taskId) => {
  selectedTaskId.value = taskId
  showDetailsModal.value = true
}

const closeDetailsModal = () => {
  showDetailsModal.value = false
  selectedTaskId.value = null
}

const handleTaskSaved = () => {
  closeModal()
  loadTasks()
}

const handleEditTask = (task) => {
  closeDetailsModal()
  selectedTask.value = task
  showModal.value = true
}

const handleDeleteTask = () => {
  loadTasks()
}

// Форматирование
const formatDate = (timestamp) => {
  const date = new Date(timestamp * 1000)
  return date.toLocaleDateString('ru-RU')
}

const formatDuration = (seconds) => {
  const hours = Math.floor(seconds / 3600)
  const minutes = Math.floor((seconds % 3600) / 60)
  return `${hours}ч ${minutes}м`
}

onMounted(() => {
  loadTasks()
  loadProjects()
})
</script>

<style scoped>
.tasks-page {
  background: white;
  padding: 2rem;
  border-radius: 15px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 2rem;
  gap: 2rem;
  flex-wrap: wrap;
}

.header-info h2 {
  margin: 0 0 0.5rem 0;
  color: #1a1a1a;
}

.subtitle {
  color: #666;
  font-size: 0.9rem;
}

.header-actions {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}

.filter-select {
  padding: 0.75rem 1rem;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  background: white;
  font-size: 0.9rem;
  cursor: pointer;
  transition: border-color 0.2s;
}

.filter-select:hover {
  border-color: #2d3748;
}

.btn-primary {
  padding: 0.75rem 1.5rem;
  background: #2d3748;
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 500;
  transition: background 0.2s;
}

.btn-primary:hover {
  background: #1a202c;
}

.loading-state {
  text-align: center;
  padding: 4rem 2rem;
  color: #666;
}

.spinner {
  width: 48px;
  height: 48px;
  border: 4px solid #f3f3f3;
  border-top: 4px solid #2d3748;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.tasks-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 1.5rem;
}

.task-card {
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 12px;
  padding: 1.5rem;
  cursor: pointer;
  transition: all 0.2s;
}

.task-card:hover {
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  transform: translateY(-2px);
}

.task-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
  margin-bottom: 1rem;
}

.task-header h3 {
  margin: 0;
  font-size: 1.1rem;
  color: #1a1a1a;
  flex: 1;
}

.status-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 500;
  white-space: nowrap;
}

.status-1 { background: #e0e7ff; color: #4338ca; }
.status-2 { background: #fef3c7; color: #92400e; }
.status-3 { background: #dbeafe; color: #1e40af; }
.status-4 { background: #d1fae5; color: #065f46; }

.task-description {
  color: #666;
  font-size: 0.9rem;
  margin-bottom: 1rem;
  line-height: 1.5;
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
  margin-bottom: 1rem;
  flex-wrap: wrap;
}

.priority-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 500;
}

.priority-1 { background: #f3f4f6; color: #6b7280; }
.priority-2 { background: #fef3c7; color: #92400e; }
.priority-3 { background: #fee2e2; color: #991b1b; }
.priority-4 { background: #fecaca; color: #7f1d1d; }

.project-name {
  font-size: 0.85rem;
  color: #666;
}

.deadline {
  font-size: 0.85rem;
  color: #666;
}

.task-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 1rem;
  border-top: 1px solid #f0f0f0;
}

.assignees {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}

.avatar-small {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: #2d3748;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 0.75rem;
}

.more-count {
  font-size: 0.75rem;
  color: #666;
  font-weight: 500;
}

.time-info {
  font-size: 0.85rem;
  color: #666;
}

.empty-state {
  text-align: center;
  padding: 4rem 2rem;
}

.empty-state svg {
  width: 80px;
  height: 80px;
  margin: 0 auto 1.5rem;
  color: #ccc;
}

.empty-state h3 {
  margin: 0 0 0.5rem 0;
  color: #1a1a1a;
}

.empty-state p {
  color: #666;
  margin: 0 0 2rem 0;
}

@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    align-items: stretch;
  }

  .header-actions {
    flex-direction: column;
  }

  .tasks-grid {
    grid-template-columns: 1fr;
  }
}
</style>
