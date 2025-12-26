<template>
  <DashboardLayout>
    <template #header-left>
      <h1>Аналитика проектов</h1>
    </template>

    <div class="analytics-page">
      <!-- Фильтры -->
      <div class="filters-panel">
        <div class="filter-group">
          <label>Команда:</label>
          <select v-model="selectedTeam" @change="filterByTeam" class="filter-select">
            <option value="">Все команды</option>
            <option v-for="team in teams" :key="team" :value="team">
              {{ team }}
            </option>
          </select>
        </div>

        <div class="filter-group">
          <label>Поиск:</label>
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Название проекта или сайт..."
            @input="filterProjects"
            class="filter-input"
          />
        </div>

        <button @click="loadProjects" class="btn-primary">
          <i class="fas fa-sync-alt"></i>
          Обновить
        </button>
        <button @click="downloadExcel" class="btn-success">
          <i class="fas fa-file-excel"></i>
          Экспорт в Excel
        </button>
      </div>

      <!-- Загрузка -->
      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Загрузка данных...</p>
      </div>

      <!-- Ошибка -->
      <div v-else-if="error" class="error-state">
        <i class="fas fa-exclamation-triangle"></i>
        <p>{{ error }}</p>
      </div>

      <!-- Список проектов -->
      <div v-else-if="filteredProjects.length === 0" class="empty-state">
        <p>Нет проектов для отображения</p>
      </div>

      <div v-else class="projects-grid">
        <div
          v-for="project in filteredProjects"
          :key="project.project_id"
          class="project-card"
          @click="showDetails(project)"
        >
          <div class="project-header">
            <div class="project-info">
              <h3>{{ project.project_name }}</h3>
              <a :href="project.site" target="_blank" class="project-link" @click.stop>
                {{ project.site }}
                <i class="fas fa-external-link-alt"></i>
              </a>
            </div>
            <div class="project-team">
              <span class="team-badge">{{ project.team }}</span>
            </div>
          </div>

          <!-- Диагностика -->
          <div v-if="Object.keys(project.diagnostics || {}).length > 0" class="diagnostics">
            <h4><i class="fas fa-stethoscope"></i> Диагностика</h4>
            <div class="diagnostic-items">
              <div
                v-for="(diagnostic, key) in project.diagnostics"
                :key="key"
                class="diagnostic-item"
                :class="`severity-${diagnostic.severity.toLowerCase()}`"
              >
                <div class="diagnostic-header">
                  <span class="severity-badge">{{ diagnostic.severity }}</span>
                  <span class="state-badge">{{ diagnostic.state }}</span>
                </div>
                <p class="diagnostic-description">{{ diagnostic.description }}</p>
                <small class="diagnostic-date">
                  Обновлено: {{ formatDate(diagnostic.last_state_update) }}
                </small>
              </div>
            </div>
          </div>

          <!-- AI сообщение -->
          <div v-if="project.last_ai_message" class="ai-message">
            <h4><i class="fas fa-robot"></i> AI Анализ</h4>
            <p>{{ project.last_ai_message }}</p>
          </div>

          <!-- Плохие страницы -->
          <div v-if="project.bad_pages" class="bad-pages">
            <h4><i class="fas fa-exclamation-circle"></i> Проблемные страницы</h4>
            <pre>{{ project.bad_pages }}</pre>
          </div>

          <!-- Последние метрики -->
          <div v-if="project.data && project.data.length > 0" class="stats-summary">
            <h4><i class="fas fa-chart-line"></i> Последние показатели ({{ getLastDate(project.data) }})</h4>
            <div class="stats-grid">
              <div class="stat-item">
                <span class="stat-label">Google визиты:</span>
                <span class="stat-value">{{ getLastGoogleVisits(project.data) }}</span>
              </div>
              <div class="stat-item">
                <span class="stat-label">Yandex визиты:</span>
                <span class="stat-value">{{ getLastYandexVisits(project.data) }}</span>
              </div>
              <div class="stat-item">
                <span class="stat-label">Google отказы:</span>
                <span class="stat-value">{{ getLastGoogleBounce(project.data) }}%</span>
              </div>
              <div class="stat-item">
                <span class="stat-label">Yandex отказы:</span>
                <span class="stat-value">{{ getLastYandexBounce(project.data) }}%</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Модальное окно с графиками -->
      <div v-if="showModal" class="modal-overlay" @click="closeModal">
        <div class="modal-content" @click.stop>
          <div class="modal-header">
            <h2>{{ selectedProject.project_name }}</h2>
            <button @click="closeModal" class="btn-close">
              <i class="fas fa-times"></i>
            </button>
          </div>
          <div class="modal-body">
            <div class="project-details">
              <div class="detail-section">
                <h3>Информация о проекте</h3>
                <div class="info-grid">
                  <div class="info-item">
                    <strong>Сайт:</strong>
                    <a :href="selectedProject.site" target="_blank">{{ selectedProject.site }}</a>
                  </div>
                  <div class="info-item">
                    <strong>Команда:</strong>
                    {{ selectedProject.team }}
                  </div>
                  <div class="info-item">
                    <strong>Host ID:</strong>
                    {{ selectedProject.host_id }}
                  </div>
                  <div v-if="selectedProject.webm_get_params" class="info-item">
                    <strong>GET параметры:</strong>
                    {{ selectedProject.webm_get_params }}
                  </div>
                </div>
              </div>

              <!-- График визитов -->
              <div v-if="selectedProject.data && selectedProject.data.length > 0" class="detail-section">
                <h3>График визитов (последние 30 дней)</h3>
                <div class="chart-container">
                  <canvas ref="visitsChart"></canvas>
                </div>
              </div>

              <!-- График отказов -->
              <div v-if="selectedProject.data && selectedProject.data.length > 0" class="detail-section">
                <h3>График показателя отказов (последние 30 дней)</h3>
                <div class="chart-container">
                  <canvas ref="bounceChart"></canvas>
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
import { ref, computed, onMounted, nextTick } from 'vue'
import DashboardLayout from '../layouts/DashboardLayout.vue'
import analyticsApi from '../services/analytics.js'
import Chart from 'chart.js/auto'

const projects = ref([])
const filteredProjects = ref([])
const teams = ref([])
const selectedTeam = ref('')
const searchQuery = ref('')
const loading = ref(false)
const error = ref(null)
const selectedProject = ref(null)
const showModal = ref(false)

let visitsChartInstance = null
let bounceChartInstance = null

const visitsChart = ref(null)
const bounceChart = ref(null)

// Загрузка проектов
const loadProjects = async () => {
  loading.value = true
  error.value = null
  try {
    const response = await analyticsApi.getAllProjects()
    projects.value = response.data
    filteredProjects.value = response.data

    // Извлекаем уникальные команды
    teams.value = [...new Set(response.data.map(p => p.team))].filter(Boolean).sort()
  } catch (err) {
    error.value = 'Не удалось загрузить данные аналитики: ' + (err.message || 'Неизвестная ошибка')
    console.error('Error loading analytics:', err)
  } finally {
    loading.value = false
  }
}

// Фильтрация по команде
const filterByTeam = () => {
  filterProjects()
}

// Фильтрация проектов
const filterProjects = () => {
  let filtered = projects.value

  // Фильтр по команде
  if (selectedTeam.value) {
    filtered = filtered.filter(p => p.team === selectedTeam.value)
  }

  // Фильтр по поисковому запросу
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(p =>
      p.project_name?.toLowerCase().includes(query) ||
      p.site?.toLowerCase().includes(query)
    )
  }

  filteredProjects.value = filtered
}

// Показать детали
const showDetails = async (project) => {
  selectedProject.value = project
  showModal.value = true

  await nextTick()
  renderCharts()
}

// Закрыть модальное окно
const closeModal = () => {
  showModal.value = false
  if (visitsChartInstance) {
    visitsChartInstance.destroy()
    visitsChartInstance = null
  }
  if (bounceChartInstance) {
    bounceChartInstance.destroy()
    bounceChartInstance = null
  }
}

// Рендер графиков
const renderCharts = () => {
  if (!selectedProject.value?.data || selectedProject.value.data.length === 0) return

  const data = selectedProject.value.data.slice(-30) // Последние 30 дней

  // График визитов
  const visitsCtx = visitsChart.value?.getContext('2d')
  if (visitsCtx) {
    if (visitsChartInstance) visitsChartInstance.destroy()

    visitsChartInstance = new Chart(visitsCtx, {
      type: 'line',
      data: {
        labels: data.map(d => d.date),
        datasets: [
          {
            label: 'Google',
            data: data.map(d => d.sources.Google?.visits || 0),
            borderColor: '#4285F4',
            backgroundColor: 'rgba(66, 133, 244, 0.1)',
            tension: 0.4
          },
          {
            label: 'Yandex',
            data: data.map(d => d.sources.Yandex?.visits || 0),
            borderColor: '#FF0000',
            backgroundColor: 'rgba(255, 0, 0, 0.1)',
            tension: 0.4
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
          legend: {
            position: 'top'
          }
        },
        scales: {
          y: {
            beginAtZero: true
          }
        }
      }
    })
  }

  // График отказов
  const bounceCtx = bounceChart.value?.getContext('2d')
  if (bounceCtx) {
    if (bounceChartInstance) bounceChartInstance.destroy()

    bounceChartInstance = new Chart(bounceCtx, {
      type: 'line',
      data: {
        labels: data.map(d => d.date),
        datasets: [
          {
            label: 'Google',
            data: data.map(d => d.sources.Google?.bounce || 0),
            borderColor: '#4285F4',
            backgroundColor: 'rgba(66, 133, 244, 0.1)',
            tension: 0.4
          },
          {
            label: 'Yandex',
            data: data.map(d => d.sources.Yandex?.bounce || 0),
            borderColor: '#FF0000',
            backgroundColor: 'rgba(255, 0, 0, 0.1)',
            tension: 0.4
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
          legend: {
            position: 'top'
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            max: 100
          }
        }
      }
    })
  }
}

// Скачать Excel
const downloadExcel = async () => {
  try {
    const response = await analyticsApi.downloadExcel()
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `analytics_${new Date().toISOString().split('T')[0]}.xlsx`)
    document.body.appendChild(link)
    link.click()
    link.remove()
  } catch (err) {
    alert('Ошибка при скачивании Excel: ' + err.message)
  }
}

// Вспомогательные функции
const formatDate = (dateStr) => {
  return new Date(dateStr).toLocaleDateString('ru-RU', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const getLastDate = (data) => {
  return data[data.length - 1]?.date || '-'
}

const getLastGoogleVisits = (data) => {
  return data[data.length - 1]?.sources?.Google?.visits || 0
}

const getLastYandexVisits = (data) => {
  return data[data.length - 1]?.sources?.Yandex?.visits || 0
}

const getLastGoogleBounce = (data) => {
  const bounce = data[data.length - 1]?.sources?.Google?.bounce
  return bounce ? bounce.toFixed(2) : 0
}

const getLastYandexBounce = (data) => {
  const bounce = data[data.length - 1]?.sources?.Yandex?.bounce
  return bounce ? bounce.toFixed(2) : 0
}

onMounted(() => {
  loadProjects()
})
</script>

<style scoped>
.analytics-page {
  padding: 0;
}

/* Фильтры */
.filters-panel {
  display: flex;
  gap: 16px;
  align-items: flex-end;
  margin-bottom: 24px;
  padding: 20px;
  background: white;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
  flex: 1;
}

.filter-group label {
  font-weight: 600;
  font-size: 14px;
  color: #374151;
}

.filter-select,
.filter-input {
  padding: 10px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
  transition: border-color 0.2s;
}

.filter-select:focus,
.filter-input:focus {
  outline: none;
  border-color: #3b82f6;
}

/* Кнопки */
.btn-primary,
.btn-success {
  padding: 10px 20px;
  border: none;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: all 0.2s;
  white-space: nowrap;
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

/* Состояния */
.loading-state,
.error-state,
.empty-state {
  text-align: center;
  padding: 60px 20px;
  background: white;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.loading-state .spinner {
  border: 3px solid #f3f4f6;
  border-top: 3px solid #3b82f6;
  border-radius: 50%;
  width: 40px;
  height: 40px;
  animation: spin 1s linear infinite;
  margin: 0 auto 16px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.error-state {
  color: #dc2626;
}

.error-state i {
  font-size: 48px;
  margin-bottom: 16px;
}

.empty-state {
  color: #6b7280;
}

/* Сетка проектов */
.projects-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
  gap: 20px;
}

.project-card {
  background: white;
  border-radius: 8px;
  padding: 20px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  cursor: pointer;
  transition: all 0.2s;
  border: 1px solid transparent;
}

.project-card:hover {
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  transform: translateY(-2px);
  border-color: #3b82f6;
}

.project-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 16px;
  padding-bottom: 16px;
  border-bottom: 1px solid #f3f4f6;
}

.project-info h3 {
  margin: 0 0 8px 0;
  color: #111827;
  font-size: 18px;
  font-weight: 600;
}

.project-link {
  color: #3b82f6;
  text-decoration: none;
  font-size: 13px;
  display: flex;
  align-items: center;
  gap: 6px;
}

.project-link:hover {
  text-decoration: underline;
}

.team-badge {
  background: #3b82f6;
  color: white;
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 600;
}

/* Диагностика */
.diagnostics {
  margin: 16px 0;
  padding: 16px;
  background: #f9fafb;
  border-radius: 6px;
}

.diagnostics h4,
.ai-message h4,
.bad-pages h4,
.stats-summary h4 {
  margin: 0 0 12px 0;
  font-size: 14px;
  font-weight: 600;
  color: #374151;
  display: flex;
  align-items: center;
  gap: 8px;
}

.diagnostic-items {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.diagnostic-item {
  padding: 12px;
  border-radius: 6px;
  font-size: 13px;
}

.diagnostic-item.severity-critical {
  background: #fef2f2;
  border-left: 4px solid #dc2626;
}

.diagnostic-item.severity-possible_problem {
  background: #fffbeb;
  border-left: 4px solid #f59e0b;
}

.diagnostic-header {
  display: flex;
  gap: 8px;
  margin-bottom: 8px;
}

.severity-badge,
.state-badge {
  padding: 2px 8px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
}

.severity-badge {
  background: #dc2626;
  color: white;
}

.state-badge {
  background: #6b7280;
  color: white;
}

.diagnostic-description {
  margin: 8px 0;
  color: #374151;
  line-height: 1.5;
}

.diagnostic-date {
  color: #6b7280;
  font-size: 11px;
}

/* AI сообщение и плохие страницы */
.ai-message,
.bad-pages {
  margin: 16px 0;
  padding: 16px;
  background: #eff6ff;
  border-radius: 6px;
  border-left: 4px solid #3b82f6;
}

.ai-message p,
.bad-pages pre {
  margin: 0;
  color: #1e40af;
  font-size: 13px;
  line-height: 1.6;
  white-space: pre-wrap;
}

/* Статистика */
.stats-summary {
  margin-top: 16px;
  padding: 16px;
  background: #f9fafb;
  border-radius: 6px;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
}

.stat-item {
  display: flex;
  justify-content: space-between;
  font-size: 13px;
}

.stat-label {
  color: #6b7280;
}

.stat-value {
  font-weight: 600;
  color: #111827;
}

/* Модальное окно */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
}

.modal-content {
  background: white;
  border-radius: 12px;
  max-width: 1200px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24px 30px;
  border-bottom: 1px solid #e5e7eb;
}

.modal-header h2 {
  margin: 0;
  color: #111827;
  font-size: 24px;
  font-weight: 600;
}

.btn-close {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  color: #6b7280;
  padding: 0;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
  transition: all 0.2s;
}

.btn-close:hover {
  background: #f3f4f6;
  color: #dc2626;
}

.modal-body {
  padding: 30px;
}

.detail-section {
  margin-bottom: 30px;
}

.detail-section:last-child {
  margin-bottom: 0;
}

.detail-section h3 {
  margin: 0 0 16px 0;
  color: #111827;
  font-size: 18px;
  font-weight: 600;
  padding-bottom: 12px;
  border-bottom: 2px solid #3b82f6;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.info-item strong {
  color: #6b7280;
  font-size: 13px;
  font-weight: 500;
}

.info-item a {
  color: #3b82f6;
  text-decoration: none;
}

.info-item a:hover {
  text-decoration: underline;
}

.chart-container {
  position: relative;
  height: 400px;
}

.chart-container canvas {
  max-height: 400px;
}

/* Адаптивность */
@media (max-width: 768px) {
  .projects-grid {
    grid-template-columns: 1fr;
  }

  .filters-panel {
    flex-direction: column;
    align-items: stretch;
  }

  .info-grid {
    grid-template-columns: 1fr;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }
}
</style>
