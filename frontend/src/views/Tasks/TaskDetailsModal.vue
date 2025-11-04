<template>
  <div class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-content">
      <div v-if="loading" class="loading">
        <div class="spinner"></div>
        <p>Загрузка...</p>
      </div>

      <div v-else-if="task">
        <div class="modal-header">
          <h2>{{ task.title }}</h2>
          <div class="header-actions">
            <button class="btn-icon" @click="editTask" title="Редактировать">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
              </svg>
            </button>
            <button class="btn-icon danger" @click="deleteTask" title="Удалить">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
              </svg>
            </button>
            <button class="close-btn" @click="$emit('close')">&times;</button>
          </div>
        </div>

        <div class="task-details">
          <!-- Статус и приоритет -->
          <div class="status-row">
            <span :class="['status-badge', `status-${task.status}`]">
              {{ task.status_label }}
            </span>
            <span :class="['priority-badge', `priority-${task.priority}`]">
              {{ task.priority_label }}
            </span>
          </div>

          <!-- Описание -->
          <div class="section">
            <h3>Описание</h3>
            <p>{{ task.description || 'Описание отсутствует' }}</p>
          </div>

          <!-- Проект и дедлайн -->
          <div class="info-grid">
            <div class="info-item">
              <span class="label">Проект:</span>
              <span>{{ task.project.name }}</span>
            </div>
            <div class="info-item" v-if="task.deadline">
              <span class="label">Дедлайн:</span>
              <span>{{ formatDate(task.deadline) }}</span>
            </div>
            <div class="info-item">
              <span class="label">Создал:</span>
              <span>{{ task.creator.name }} {{ task.creator.surname }}</span>
            </div>
            <div class="info-item">
              <span class="label">Общее время:</span>
              <span>{{ formatDuration(task.total_time) }}</span>
            </div>
          </div>

          <!-- Участники -->
          <div class="section">
            <h3>Участники ({{ task.assignees.length }})</h3>
            <div class="assignees-list">
              <div
                v-for="assignee in task.assignees"
                :key="assignee.id"
                class="assignee-card"
              >
                <div class="avatar">
                  {{ assignee.username[0].toUpperCase() }}
                </div>
                <div class="assignee-info">
                  <div class="name">{{ assignee.name }} {{ assignee.surname }}</div>
                  <div class="time">⏱️ {{ formatDuration(assignee.time_spent) }}</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Отслеживание времени -->
          <div class="section">
            <h3>Отслеживание времени</h3>
            <div class="time-tracking">
              <button
                v-if="!isTracking"
                @click="startTracking"
                class="btn-primary"
              >
                ▶ Начать отслеживание
              </button>
              <button
                v-else
                @click="stopTracking"
                class="btn-danger"
              >
                ⏸ Остановить отслеживание
              </button>
            </div>
          </div>

          <!-- История отслеживания -->
          <div class="section" v-if="task.time_trackings.length > 0">
            <h3>История ({{ task.time_trackings.length }})</h3>
            <div class="tracking-list">
              <div
                v-for="tracking in task.time_trackings.slice(0, 5)"
                :key="tracking.id"
                class="tracking-item"
              >
                <div class="tracking-user">
                  {{ tracking.user.name }} {{ tracking.user.surname }}
                </div>
                <div class="tracking-time">
                  {{ formatDuration(tracking.duration) }}
                </div>
                <div class="tracking-date">
                  {{ formatDate(tracking.started_at) }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import tasksApi from '../../services/tasks'
import { useAuthStore } from '../../stores/auth'

const props = defineProps({
  taskId: Number
})

const emit = defineEmits(['close', 'updated', 'edit', 'delete'])

const authStore = useAuthStore()
const task = ref(null)
const loading = ref(true)
const isTracking = ref(false)

const loadTask = async () => {
  try {
    loading.value = true
    const response = await tasksApi.getTask(props.taskId)
    if (response.success) {
      task.value = response.task
      // Проверка активного отслеживания
      checkActiveTracking()
    }
  } catch (error) {
    console.error('Ошибка загрузки задачи:', error)
  } finally {
    loading.value = false
  }
}

const checkActiveTracking = () => {
  if (!task.value) return
  const userTracking = task.value.time_trackings.find(
    t => t.user.id === authStore.user?.id && !t.ended_at
  )
  isTracking.value = !!userTracking
}

const startTracking = async () => {
  try {
    await tasksApi.startTracking(props.taskId)
    isTracking.value = true
    loadTask()
  } catch (error) {
    console.error('Ошибка начала отслеживания:', error)
  }
}

const stopTracking = async () => {
  try {
    await tasksApi.stopTracking(props.taskId)
    isTracking.value = false
    loadTask()
    emit('updated')
  } catch (error) {
    console.error('Ошибка остановки отслеживания:', error)
  }
}

const formatDate = (timestamp) => {
  const date = new Date(timestamp * 1000)
  return date.toLocaleDateString('ru-RU')
}

const formatDuration = (seconds) => {
  if (!seconds) return '0ч 0м'
  const hours = Math.floor(seconds / 3600)
  const minutes = Math.floor((seconds % 3600) / 60)
  return `${hours}ч ${minutes}м`
}

const editTask = () => {
  emit('edit', task.value)
}

const deleteTask = async () => {
  if (!confirm('Вы уверены, что хотите удалить эту задачу?')) return

  try {
    const response = await tasksApi.deleteTask(task.value.id)
    if (response.success) {
      emit('delete')
      emit('close')
    }
  } catch (error) {
    console.error('Ошибка удаления задачи:', error)
    alert('Ошибка удаления задачи')
  }
}

onMounted(() => {
  loadTask()
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
  max-width: 800px;
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
  font-size: 1.5rem;
  flex: 1;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-icon {
  background: none;
  border: none;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 6px;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
}

.btn-icon svg {
  width: 20px;
  height: 20px;
  stroke: currentColor;
}

.btn-icon:hover {
  background: #f3f4f6;
}

.btn-icon.danger {
  color: #dc2626;
}

.btn-icon.danger:hover {
  background: #fee2e2;
}

.close-btn {
  background: none;
  border: none;
  font-size: 2rem;
  cursor: pointer;
  color: #666;
  padding: 0;
}

.task-details {
  padding: 1.5rem;
}

.status-row {
  display: flex;
  gap: 0.75rem;
  margin-bottom: 1.5rem;
}

.status-badge,
.priority-badge {
  padding: 0.5rem 1rem;
  border-radius: 12px;
  font-size: 0.85rem;
  font-weight: 500;
}

.status-1 { background: #e0e7ff; color: #4338ca; }
.status-2 { background: #fef3c7; color: #92400e; }
.status-3 { background: #dbeafe; color: #1e40af; }
.status-4 { background: #d1fae5; color: #065f46; }

.priority-1 { background: #f3f4f6; color: #6b7280; }
.priority-2 { background: #fef3c7; color: #92400e; }
.priority-3 { background: #fee2e2; color: #991b1b; }
.priority-4 { background: #fecaca; color: #7f1d1d; }

.section {
  margin-bottom: 1.5rem;
}

.section h3 {
  margin: 0 0 1rem 0;
  font-size: 1.1rem;
  color: #1a1a1a;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.label {
  font-size: 0.85rem;
  color: #666;
}

.assignees-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.assignee-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.75rem;
  background: #f9f9f9;
  border-radius: 8px;
}

.avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: #2d3748;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
}

.assignee-info {
  flex: 1;
}

.name {
  font-weight: 500;
  margin-bottom: 0.25rem;
}

.time {
  font-size: 0.85rem;
  color: #666;
}

.time-tracking {
  display: flex;
  gap: 1rem;
}

.btn-primary,
.btn-danger {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.2s;
}

.btn-primary {
  background: #2d3748;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: #1a202c;
}

.btn-primary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-danger {
  background: #dc2626;
  color: white;
}

.btn-danger:hover {
  background: #b91c1c;
}

.tracking-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.tracking-item {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr;
  gap: 1rem;
  padding: 0.75rem;
  background: #f9f9f9;
  border-radius: 8px;
  font-size: 0.9rem;
}

.tracking-user {
  font-weight: 500;
}

.tracking-time {
  color: #666;
}

.tracking-date {
  color: #999;
  text-align: right;
}
</style>
