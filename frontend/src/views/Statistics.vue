<template>
  <DashboardLayout>
    <template #header-left>
      <h1>Статистика</h1>
    </template>

    <div class="statistics-page">
      <!-- Вкладки -->
      <div class="page-header">
        <div class="tabs-container">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            :class="['tab-button', { active: activeTab === tab.id }]"
            @click="activeTab = tab.id"
          >
            {{ tab.label }}
          </button>
        </div>
      </div>

      <!-- Вкладка: Задачи -->
      <div v-if="activeTab === 'tasks'" class="tab-content">
        <div class="filters-panel">
          <div class="filter-group">
            <label>Период:</label>
            <div class="date-range">
              <input v-model="tasksFilters.date_from" type="date" class="filter-input" />
              <span class="date-separator">—</span>
              <input v-model="tasksFilters.date_to" type="date" class="filter-input" />
            </div>
          </div>
          <div class="filter-group">
            <label>Проект:</label>
            <select v-model="tasksFilters.project_id" class="filter-select">
              <option :value="null">Все проекты</option>
              <option v-for="project in projects" :key="project.id" :value="project.id">
                {{ project.name }}
              </option>
            </select>
          </div>
          <div class="filter-group">
            <label>Сотрудник:</label>
            <select v-model="tasksFilters.user_id" class="filter-select">
              <option :value="null">Все сотрудники</option>
              <option v-for="user in users" :key="user.id" :value="user.id">
                {{ user.name }} {{ user.surname }}
              </option>
            </select>
          </div>
          <button @click="loadTasksStatistics" class="btn-primary">Применить</button>
          <button @click="exportToExcel('tasks')" class="btn-success">Экспорт в Excel</button>
        </div>

        <div v-if="tasksLoading" class="loading-state">
          <div class="spinner"></div>
          <p>Загрузка данных...</p>
        </div>
        <div v-else-if="tasksData.length === 0" class="empty-state">
          <p>Нет данных для отображения</p>
        </div>
        <div v-else class="data-table">
          <table>
            <thead>
              <tr>
                <th>Задача</th>
                <th>Проект</th>
                <th>Статус</th>
                <th>Приоритет</th>
                <th>План (ч)</th>
                <th>Факт (ч)</th>
                <th>Разница (ч)</th>
                <th>%</th>
                <th>Исполнители</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="task in tasksData" :key="task.id">
                <td>{{ task.title }}</td>
                <td>{{ task.project || '—' }}</td>
                <td><span :class="['status-badge', 'status-' + getStatusClass(task.status)]">{{ task.status }}</span></td>
                <td><span :class="['priority-badge', 'priority-' + getPriorityClass(task.priority)]">{{ task.priority }}</span></td>
                <td>{{ formatHours(task.estimated_time) }}</td>
                <td>{{ formatHours(task.total_time) }}</td>
                <td :class="{ 'text-danger': task.time_diff > 0, 'text-success': task.time_diff < 0 }">
                  {{ formatHours(task.time_diff) }}
                </td>
                <td :class="{ 'text-danger': task.time_diff_percent > 0, 'text-success': task.time_diff_percent < 0 }">
                  {{ task.time_diff_percent > 0 ? '+' : '' }}{{ task.time_diff_percent }}%
                </td>
                <td>
                  <div class="assignees-list">
                    <div v-for="assignee in task.assignees" :key="assignee.id" class="assignee-item">
                      {{ assignee.name }} ({{ formatHours(assignee.time) }}ч)
                    </div>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Вкладка: Проекты -->
      <div v-if="activeTab === 'projects'" class="tab-content">
        <div class="filters-panel">
          <div class="filter-group">
            <label>Период:</label>
            <div class="date-range">
              <input v-model="projectsFilters.date_from" type="date" class="filter-input" />
              <span class="date-separator">—</span>
              <input v-model="projectsFilters.date_to" type="date" class="filter-input" />
            </div>
          </div>
          <button @click="loadProjectsStatistics" class="btn-primary">Применить</button>
          <button @click="exportToExcel('projects')" class="btn-success">Экспорт в Excel</button>
        </div>

        <div v-if="projectsLoading" class="loading-state">
          <div class="spinner"></div>
          <p>Загрузка данных...</p>
        </div>
        <div v-else-if="projectsData.length === 0" class="empty-state">
          <p>Нет данных для отображения</p>
        </div>
        <div v-else class="data-table">
          <table>
            <thead>
              <tr>
                <th>Проект</th>
                <th>Всего задач</th>
                <th>Завершено</th>
                <th>% выполнения</th>
                <th>План (ч)</th>
                <th>Факт (ч)</th>
                <th>Разница (ч)</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="project in projectsData" :key="project.id">
                <td><strong>{{ project.name }}</strong></td>
                <td>{{ project.task_count }}</td>
                <td>{{ project.completed_tasks }}</td>
                <td>
                  <div class="progress-badge">
                    {{ project.completion_rate }}%
                  </div>
                </td>
                <td>{{ formatHours(project.estimated_time) }}</td>
                <td>{{ formatHours(project.total_time) }}</td>
                <td :class="{ 'text-danger': project.time_diff > 0, 'text-success': project.time_diff < 0 }">
                  {{ formatHours(project.time_diff) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Вкладка: Сотрудники -->
      <div v-if="activeTab === 'employees'" class="tab-content">
        <div class="filters-panel">
          <div class="filter-group">
            <label>Период:</label>
            <div class="date-range">
              <input v-model="employeesFilters.date_from" type="date" class="filter-input" />
              <span class="date-separator">—</span>
              <input v-model="employeesFilters.date_to" type="date" class="filter-input" />
            </div>
          </div>
          <div class="filter-group">
            <label>Проект:</label>
            <select v-model="employeesFilters.project_id" class="filter-select">
              <option :value="null">Все проекты</option>
              <option v-for="project in projects" :key="project.id" :value="project.id">
                {{ project.name }}
              </option>
            </select>
          </div>
          <div class="filter-group">
            <label>Сотрудник:</label>
            <select v-model="employeesFilters.user_id" class="filter-select">
              <option :value="null">Все сотрудники</option>
              <option v-for="user in users" :key="user.id" :value="user.id">
                {{ user.name }} {{ user.surname }}
              </option>
            </select>
          </div>
          <button @click="loadEmployeesStatistics" class="btn-primary">Применить</button>
          <button @click="exportToExcel('employees')" class="btn-success">Экспорт в Excel</button>
        </div>

        <div v-if="employeesLoading" class="loading-state">
          <div class="spinner"></div>
          <p>Загрузка данных...</p>
        </div>
        <div v-else-if="employeesData.length === 0" class="empty-state">
          <p>Нет данных для отображения</p>
        </div>
        <div v-else class="employees-grid">
          <div v-for="employee in employeesData" :key="employee.id" class="employee-card">
            <div class="employee-header">
              <h3>{{ employee.name }}</h3>
              <div class="total-time-badge">{{ formatHours(employee.total_time) }} ч</div>
            </div>

            <div v-if="employee.projects.length > 0" class="employee-section">
              <h4>По проектам:</h4>
              <div class="items-list">
                <div v-for="project in employee.projects" :key="project.project_id" class="list-item">
                  <span class="item-name">{{ project.project_name }}</span>
                  <span class="item-value">{{ formatHours(project.time) }} ч</span>
                </div>
              </div>
            </div>

            <div v-if="employee.tasks.length > 0" class="employee-section">
              <h4>По задачам:</h4>
              <div class="items-list">
                <div v-for="task in employee.tasks" :key="task.task_id" class="list-item">
                  <div class="task-info">
                    <span class="task-title">{{ task.task_title }}</span>
                    <span class="task-project">({{ task.project_name }})</span>
                  </div>
                  <span class="item-value">{{ formatHours(task.time) }} ч</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Вкладка: Превышения -->
      <div v-if="activeTab === 'overruns'" class="tab-content">
        <div class="filters-panel">
          <div class="filter-group">
            <label>Период:</label>
            <div class="date-range">
              <input v-model="overrunsFilters.date_from" type="date" class="filter-input" />
              <span class="date-separator">—</span>
              <input v-model="overrunsFilters.date_to" type="date" class="filter-input" />
            </div>
          </div>
          <div class="filter-group">
            <label>Проект:</label>
            <select v-model="overrunsFilters.project_id" class="filter-select">
              <option :value="null">Все проекты</option>
              <option v-for="project in projects" :key="project.id" :value="project.id">
                {{ project.name }}
              </option>
            </select>
          </div>
          <div class="filter-group">
            <label>Сотрудник:</label>
            <select v-model="overrunsFilters.user_id" class="filter-select">
              <option :value="null">Все сотрудники</option>
              <option v-for="user in users" :key="user.id" :value="user.id">
                {{ user.name }} {{ user.surname }}
              </option>
            </select>
          </div>
          <button @click="loadOverrunsStatistics" class="btn-primary">Применить</button>
          <button @click="exportToExcel('overruns')" class="btn-success">Экспорт в Excel</button>
        </div>

        <div v-if="overrunsLoading" class="loading-state">
          <div class="spinner"></div>
          <p>Загрузка данных...</p>
        </div>
        <div v-else-if="overrunsData.length === 0" class="empty-state">
          <p>Превышений не найдено</p>
        </div>
        <div v-else class="data-table">
          <table>
            <thead>
              <tr>
                <th>Задача</th>
                <th>Проект</th>
                <th>Статус</th>
                <th>План (ч)</th>
                <th>Факт (ч)</th>
                <th>Превышение (ч)</th>
                <th>% превышения</th>
                <th>Исполнители</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="task in overrunsData" :key="task.id" class="overrun-row">
                <td>{{ task.title }}</td>
                <td>{{ task.project || '—' }}</td>
                <td><span :class="['status-badge', 'status-' + getStatusClass(task.status)]">{{ task.status }}</span></td>
                <td>{{ formatHours(task.estimated_time) }}</td>
                <td>{{ formatHours(task.total_time) }}</td>
                <td class="text-danger"><strong>{{ formatHours(task.overrun) }}</strong></td>
                <td class="text-danger"><strong>+{{ task.overrun_percent }}%</strong></td>
                <td>
                  <div class="assignees-list">
                    <div v-for="assignee in task.assignees" :key="assignee.id" class="assignee-item">
                      {{ assignee.name }} ({{ formatHours(assignee.time) }}ч, {{ assignee.share_percent }}%)
                    </div>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import DashboardLayout from '../layouts/DashboardLayout.vue'
import statisticsApi from '../services/statistics'
import projectsApi from '../services/projects'
import usersApi from '../services/users'

const activeTab = ref('tasks')

const tabs = [
  { id: 'tasks', label: 'Задачи' },
  { id: 'projects', label: 'Проекты' },
  { id: 'employees', label: 'Сотрудники' },
  { id: 'overruns', label: 'Превышения' }
]

// Общие данные
const projects = ref([])
const users = ref([])

// Задачи
const tasksData = ref([])
const tasksLoading = ref(false)
const tasksFilters = ref({
  project_id: null,
  user_id: null,
  date_from: null,
  date_to: null
})

// Проекты
const projectsData = ref([])
const projectsLoading = ref(false)
const projectsFilters = ref({
  date_from: null,
  date_to: null
})

// Сотрудники
const employeesData = ref([])
const employeesLoading = ref(false)
const employeesFilters = ref({
  user_id: null,
  project_id: null,
  date_from: null,
  date_to: null
})

// Превышения
const overrunsData = ref([])
const overrunsLoading = ref(false)
const overrunsFilters = ref({
  project_id: null,
  user_id: null,
  date_from: null,
  date_to: null
})

// Форматирование секунд в часы
const formatHours = (seconds) => {
  if (!seconds) return '0.00'
  return (seconds / 3600).toFixed(2)
}

// Получение класса статуса
const getStatusClass = (statusLabel) => {
  const statusMap = {
    'К выполнению': '1',
    'В работе': '2',
    'На проверке': '3',
    'Выполнено': '4'
  }
  return statusMap[statusLabel] || '1'
}

// Получение класса приоритета
const getPriorityClass = (priorityLabel) => {
  const priorityMap = {
    'Низкий': '1',
    'Средний': '2',
    'Высокий': '3',
    'Срочный': '4'
  }
  return priorityMap[priorityLabel] || '2'
}

// Загрузка списка проектов
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

// Загрузка списка пользователей
const loadUsers = async () => {
  try {
    const response = await usersApi.getProfiles()
    if (response.success) {
      users.value = response.users
    }
  } catch (error) {
    console.error('Ошибка загрузки пользователей:', error)
  }
}

// Загрузка статистики по задачам
const loadTasksStatistics = async () => {
  try {
    tasksLoading.value = true
    const response = await statisticsApi.getTasksStatistics(tasksFilters.value)
    if (response.success) {
      tasksData.value = response.tasks
    }
  } catch (error) {
    console.error('Ошибка загрузки статистики по задачам:', error)
  } finally {
    tasksLoading.value = false
  }
}

// Загрузка статистики по проектам
const loadProjectsStatistics = async () => {
  try {
    projectsLoading.value = true
    const response = await statisticsApi.getProjectsStatistics(projectsFilters.value)
    if (response.success) {
      projectsData.value = response.projects
    }
  } catch (error) {
    console.error('Ошибка загрузки статистики по проектам:', error)
  } finally {
    projectsLoading.value = false
  }
}

// Загрузка статистики по сотрудникам
const loadEmployeesStatistics = async () => {
  try {
    employeesLoading.value = true
    const response = await statisticsApi.getEmployeesStatistics(employeesFilters.value)
    if (response.success) {
      employeesData.value = response.employees
    }
  } catch (error) {
    console.error('Ошибка загрузки статистики по сотрудникам:', error)
  } finally {
    employeesLoading.value = false
  }
}

// Загрузка статистики по превышениям
const loadOverrunsStatistics = async () => {
  try {
    overrunsLoading.value = true
    const response = await statisticsApi.getOverrunsStatistics(overrunsFilters.value)
    if (response.success) {
      overrunsData.value = response.overruns
    }
  } catch (error) {
    console.error('Ошибка загрузки статистики по превышениям:', error)
  } finally {
    overrunsLoading.value = false
  }
}

// Экспорт в Excel
const exportToExcel = (type) => {
  const API_URL = import.meta.env.DEV
    ? 'http://localhost:8001'
    : 'http://185.104.113.132:8001'

  const endpoints = {
    tasks: '/api/statistics/export-tasks',
    projects: '/api/statistics/export-projects',
    employees: '/api/statistics/export-employees',
    overruns: '/api/statistics/export-overruns'
  }

  const endpoint = endpoints[type]
  if (!endpoint) {
    console.error('Неизвестный тип экспорта:', type)
    return
  }

  // Создаем параметры на основе текущих фильтров
  const params = new URLSearchParams()

  if (type === 'tasks') {
    if (tasksFilters.value.project_id) params.append('project_id', tasksFilters.value.project_id)
    if (tasksFilters.value.user_id) params.append('user_id', tasksFilters.value.user_id)
    if (tasksFilters.value.date_from) params.append('date_from', tasksFilters.value.date_from)
    if (tasksFilters.value.date_to) params.append('date_to', tasksFilters.value.date_to)
  } else if (type === 'projects') {
    if (projectsFilters.value.date_from) params.append('date_from', projectsFilters.value.date_from)
    if (projectsFilters.value.date_to) params.append('date_to', projectsFilters.value.date_to)
  } else if (type === 'employees') {
    if (employeesFilters.value.user_id) params.append('user_id', employeesFilters.value.user_id)
    if (employeesFilters.value.project_id) params.append('project_id', employeesFilters.value.project_id)
    if (employeesFilters.value.date_from) params.append('date_from', employeesFilters.value.date_from)
    if (employeesFilters.value.date_to) params.append('date_to', employeesFilters.value.date_to)
  } else if (type === 'overruns') {
    if (overrunsFilters.value.project_id) params.append('project_id', overrunsFilters.value.project_id)
    if (overrunsFilters.value.user_id) params.append('user_id', overrunsFilters.value.user_id)
    if (overrunsFilters.value.date_from) params.append('date_from', overrunsFilters.value.date_from)
    if (overrunsFilters.value.date_to) params.append('date_to', overrunsFilters.value.date_to)
  }

  const queryString = params.toString()
  const url = `${API_URL}${endpoint}${queryString ? '?' + queryString : ''}`

  // Открываем URL для скачивания файла
  window.location.href = url
}

onMounted(async () => {
  await loadProjects()
  await loadUsers()
  await loadTasksStatistics()
})
</script>

<style scoped>
.statistics-page {
  padding: 0;
}

.page-header {
  margin-bottom: 2rem;
}

.tabs-container {
  display: flex;
  gap: 0.5rem;
  border-bottom: 2px solid #e5e7eb;
  padding-bottom: 0;
}

.tab-button {
  padding: 0.75rem 1.5rem;
  background: none;
  border: none;
  border-bottom: 3px solid transparent;
  cursor: pointer;
  font-size: 1rem;
  font-weight: 500;
  color: #6b7280;
  transition: all 0.2s;
  margin-bottom: -2px;
}

.tab-button:hover {
  color: #3b82f6;
}

.tab-button.active {
  color: #3b82f6;
  border-bottom-color: #3b82f6;
}

.tab-content {
  margin-top: 2rem;
}

.filters-panel {
  display: flex;
  gap: 1rem;
  align-items: flex-end;
  margin-bottom: 2rem;
  flex-wrap: wrap;
  padding: 1.5rem;
  background: #f9fafb;
  border-radius: 8px;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.filter-group label {
  font-size: 0.875rem;
  font-weight: 500;
  color: #374151;
}

.filter-input,
.filter-select {
  padding: 0.5rem 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 0.875rem;
  background: white;
}

.date-range {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.date-separator {
  color: #6b7280;
  font-weight: 500;
}

.btn-primary,
.btn-success {
  padding: 0.5rem 1.25rem;
  border: none;
  border-radius: 6px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  font-size: 0.875rem;
}

.btn-primary {
  background: #3b82f6;
  color: white;
}

.btn-primary:hover {
  background: #2563eb;
}

.btn-success {
  background: #10b981;
  color: white;
}

.btn-success:hover {
  background: #059669;
}

.loading-state {
  text-align: center;
  padding: 4rem 2rem;
}

.spinner {
  border: 3px solid #f3f4f6;
  border-top: 3px solid #3b82f6;
  border-radius: 50%;
  width: 40px;
  height: 40px;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.empty-state {
  text-align: center;
  padding: 4rem 2rem;
  color: #9ca3af;
  font-size: 1rem;
}

.data-table {
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.data-table table {
  width: 100%;
  border-collapse: collapse;
}

.data-table th,
.data-table td {
  padding: 1rem;
  text-align: left;
  border-bottom: 1px solid #e5e7eb;
}

.data-table th {
  background: #f9fafb;
  font-weight: 600;
  color: #374151;
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 0.025em;
}

.data-table td {
  color: #1f2937;
  font-size: 0.875rem;
}

.data-table tr:hover {
  background: #f9fafb;
}

.data-table tr:last-child td {
  border-bottom: none;
}

.text-danger {
  color: #ef4444;
  font-weight: 600;
}

.text-success {
  color: #10b981;
  font-weight: 600;
}

.status-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.025em;
}

.status-1 {
  background: #dbeafe;
  color: #1e40af;
}

.status-2 {
  background: #fef3c7;
  color: #92400e;
}

.status-3 {
  background: #fce7f3;
  color: #9f1239;
}

.status-4 {
  background: #d1fae5;
  color: #065f46;
}

.priority-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
}

.priority-1 {
  background: #e5e7eb;
  color: #4b5563;
}

.priority-2 {
  background: #dbeafe;
  color: #1e40af;
}

.priority-3 {
  background: #fef3c7;
  color: #92400e;
}

.priority-4 {
  background: #fee2e2;
  color: #991b1b;
}

.progress-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  background: #dbeafe;
  color: #1e40af;
  border-radius: 6px;
  font-weight: 600;
  font-size: 0.875rem;
}

.assignees-list {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.assignee-item {
  font-size: 0.875rem;
  color: #6b7280;
}

.employees-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
  gap: 1.5rem;
}

.employee-card {
  background: white;
  border-radius: 8px;
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  border: 1px solid #e5e7eb;
}

.employee-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid #e5e7eb;
}

.employee-header h3 {
  margin: 0;
  font-size: 1.25rem;
  color: #1f2937;
}

.total-time-badge {
  padding: 0.5rem 1rem;
  background: #3b82f6;
  color: white;
  border-radius: 6px;
  font-weight: 600;
  font-size: 1rem;
}

.employee-section {
  margin-top: 1.5rem;
}

.employee-section h4 {
  margin: 0 0 0.75rem 0;
  font-size: 0.875rem;
  font-weight: 600;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.025em;
}

.items-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.list-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem;
  background: #f9fafb;
  border-radius: 6px;
  font-size: 0.875rem;
}

.item-name {
  font-weight: 500;
  color: #374151;
}

.item-value {
  color: #3b82f6;
  font-weight: 600;
}

.task-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.task-title {
  font-weight: 500;
  color: #374151;
}

.task-project {
  color: #9ca3af;
  font-size: 0.75rem;
}

.overrun-row {
  background: #fef2f2;
}

.overrun-row:hover {
  background: #fee2e2 !important;
}
</style>
