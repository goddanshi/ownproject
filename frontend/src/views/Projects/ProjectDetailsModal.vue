<template>
  <div class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-content">
      <div v-if="loading" class="loading">
        <div class="spinner"></div>
        <p>Загрузка...</p>
      </div>

      <div v-else-if="project">
        <div class="modal-header">
          <h2>{{ project.name }}</h2>
          <button class="close-btn" @click="$emit('close')">&times;</button>
        </div>

        <div class="project-details">
          <!-- Описание -->
          <div class="section">
            <h3>Описание</h3>
            <p>{{ project.description || 'Описание отсутствует' }}</p>
          </div>

          <!-- Команда и статистика -->
          <div class="info-grid">
            <div class="info-item">
              <span class="label">Команда:</span>
              <div class="team-detail">
                <span class="team-name">{{ project.team.name }}</span>
                <span class="teamlead">Тимлид: {{ project.team.teamlead.name }} {{ project.team.teamlead.surname }}</span>
              </div>
            </div>
            <div class="info-item">
              <span class="label">Создан:</span>
              <span>{{ formatDate(project.created_at) }}</span>
            </div>
            <div class="info-item">
              <span class="label">Задач:</span>
              <span>{{ project.tasks.length }}</span>
            </div>
          </div>

          <!-- Участники -->
          <div class="section">
            <h3>Участники ({{ project.participants.length }})</h3>
            <div class="participants-grid">
              <div
                v-for="participant in project.participants"
                :key="participant.id"
                class="participant-card"
              >
                <div class="avatar">
                  {{ participant.username[0].toUpperCase() }}
                </div>
                <div class="participant-info">
                  <div class="name">{{ participant.name }} {{ participant.surname }}</div>
                  <div class="email">{{ participant.email }}</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Задачи проекта -->
          <div class="section">
            <div class="section-header">
              <h3>Задачи ({{ project.tasks.length }})</h3>
              <button class="btn-sm" @click="goToTasks">
                Посмотреть все →
              </button>
            </div>

            <div v-if="project.tasks.length > 0" class="tasks-list">
              <div
                v-for="task in project.tasks.slice(0, 5)"
                :key="task.id"
                class="task-item"
                @click="openTask(task.id)"
              >
                <div class="task-main">
                  <span class="task-title">{{ task.title }}</span>
                  <span :class="['status-badge', `status-${task.status}`]">
                    {{ task.status_label }}
                  </span>
                </div>
                <div class="task-meta">
                  <span :class="['priority-badge', `priority-${task.priority}`]">
                    {{ task.priority_label }}
                  </span>
                  <span v-if="task.deadline" class="deadline">
                    ⏰ {{ formatDate(task.deadline) }}
                  </span>
                </div>
              </div>
            </div>
            <div v-else class="no-tasks">
              Задач пока нет. Создайте первую задачу для этого проекта.
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Модальное окно детализации задачи -->
    <TaskDetailsModal
      v-if="showTaskDetails && selectedTaskId"
      :task-id="selectedTaskId"
      @close="closeTaskDetails"
      @updated="closeTaskDetails"
      @delete="closeTaskDetails"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import projectsApi from '../../services/projects'
import TaskDetailsModal from '../Tasks/TaskDetailsModal.vue'

const props = defineProps({
  projectId: Number
})

const emit = defineEmits(['close', 'updated'])

const router = useRouter()
const project = ref(null)
const loading = ref(true)
const selectedTaskId = ref(null)
const showTaskDetails = ref(false)

const loadProject = async () => {
  try {
    loading.value = true
    const response = await projectsApi.getProject(props.projectId)
    if (response.success) {
      project.value = response.project
    }
  } catch (error) {
    console.error('Ошибка загрузки проекта:', error)
  } finally {
    loading.value = false
  }
}

const formatDate = (timestamp) => {
  const date = new Date(timestamp * 1000)
  return date.toLocaleDateString('ru-RU')
}

const goToTasks = () => {
  emit('close')
  router.push(`/tasks?project=${props.projectId}`)
}

const openTask = (taskId) => {
  selectedTaskId.value = taskId
  showTaskDetails.value = true
}

const closeTaskDetails = () => {
  showTaskDetails.value = false
  selectedTaskId.value = null
  loadProject() // Перезагружаем проект для обновления данных задач
}

onMounted(() => {
  loadProject()
})
</script>

<style scoped>
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
  padding: 1rem;
}

.modal-content {
  background: white;
  border-radius: 12px;
  width: 100%;
  max-width: 900px;
  max-height: 90vh;
  overflow-y: auto;
}

.loading {
  text-align: center;
  padding: 4rem 2rem;
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

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid #e0e0e0;
}

.modal-header h2 {
  margin: 0;
  font-size: 1.75rem;
  flex: 1;
}

.close-btn {
  background: none;
  border: none;
  font-size: 2rem;
  cursor: pointer;
  color: #666;
}

.project-details {
  padding: 1.5rem;
}

.section {
  margin-bottom: 2rem;
}

.section h3 {
  margin: 0 0 1rem 0;
  font-size: 1.1rem;
  color: #1a1a1a;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.btn-sm {
  padding: 0.5rem 1rem;
  background: #f5f5f7;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 0.85rem;
  font-weight: 500;
  color: #2d3748;
  transition: background 0.2s;
}

.btn-sm:hover {
  background: #e5e5e7;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.label {
  font-size: 0.85rem;
  color: #666;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.team-detail {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.team-name {
  font-weight: 600;
  color: #2d3748;
}

.teamlead {
  font-size: 0.85rem;
  color: #666;
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
  padding: 0.75rem;
  background: #f9f9f9;
  border-radius: 8px;
}

.avatar {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: #2d3748;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 1.1rem;
  flex-shrink: 0;
}

.participant-info {
  flex: 1;
  min-width: 0;
}

.name {
  font-weight: 500;
  margin-bottom: 0.25rem;
  color: #1a1a1a;
}

.email {
  font-size: 0.85rem;
  color: #666;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.tasks-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.task-item {
  padding: 1rem;
  background: #f9f9f9;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
}

.task-item:hover {
  background: #f0f0f0;
  transform: translateX(4px);
}

.task-main {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  margin-bottom: 0.5rem;
}

.task-title {
  font-weight: 500;
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

.task-meta {
  display: flex;
  gap: 0.75rem;
  align-items: center;
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

.deadline {
  font-size: 0.85rem;
  color: #666;
}

.no-tasks {
  padding: 2rem;
  text-align: center;
  color: #999;
  font-style: italic;
  background: #f9f9f9;
  border-radius: 8px;
}
</style>
