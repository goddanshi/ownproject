<template>
  <div class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-content">
      <div v-if="loading" class="loading">
        <div class="spinner"></div>
        <p>Загрузка...</p>
      </div>

      <div v-else-if="task" class="modal-layout">
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

        <div class="modal-body">
          <div class="task-details">
          <!-- Статус и приоритет -->
          <div class="status-row" style="justify-content: space-between;">
            <div class="status-control">
              <label>Статус:</label>
              <select v-model="selectedStatus" @change="handleStatusChange" class="status-select">
                <option value="1">К выполнению</option>
                <option value="2">В работе</option>
                <option value="3">На проверке</option>
                <option value="4">Выполнено</option>
              </select>
              <span :class="['priority-badge', `priority-${task.priority}`]">
                {{ task.priority_label }}
              </span>
            </div>
            <div class="time-tracking">
              <button style="height: 50px;"
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

          <!-- Описание -->
          <div class="section">
            <h3>Описание</h3>
            <p class="description" v-html="formattedDescription"></p>
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

          <!-- TODO список -->
          <div class="section">
            <div class="section-header">
              <h3>TODO ({{ task.todos?.length || 0 }})</h3>
            </div>
            <div class="todos-list">
              <div
                v-for="todo in task.todos"
                :key="todo.id"
                class="todo-item"
              >
                <input
                  type="checkbox"
                  :checked="todo.is_completed"
                  @change="toggleTodo(todo.id)"
                  class="todo-checkbox"
                />
                <span
                  :class="['todo-title', { 'completed': todo.is_completed }]"
                >
                  {{ todo.title }}
                </span>
                <button
                  @click="deleteTodo(todo.id)"
                  class="btn-delete-todo"
                  title="Удалить"
                >
                  &times;
                </button>
              </div>
              <div class="todo-add">
                <input
                  v-model="newTodoTitle"
                  @keyup.enter="addTodo"
                  type="text"
                  placeholder="Добавить TODO..."
                  class="todo-input"
                />
                <button
                  @click="addTodo"
                  :disabled="!newTodoTitle.trim()"
                  class="btn-add-todo"
                >
                  +
                </button>
              </div>
            </div>
          </div>

          <!-- Отслеживание времени -->
          <!-- <div class="section">
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
          </div> -->

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

        <!-- Правая колонка - чат -->
        <div class="chat-column">
          <TaskChat :task-id="taskId" />
        </div>
      </div>
      </div>
    </div>

    <!-- Модалка выбора проверяющего -->
    <div v-if="showReviewerModal" class="reviewer-modal-overlay" @click.self="cancelReviewerSelection">
      <div class="reviewer-modal">
        <h3>Выберите проверяющего</h3>
        <p class="reviewer-hint">Кто будет проверять эту задачу?</p>
        <div class="reviewers-list">
          <label
            v-for="assignee in task.assignees"
            :key="assignee.id"
            class="reviewer-option"
          >
            <input
              type="radio"
              name="reviewer"
              :value="assignee.id"
              v-model="selectedReviewerId"
            />
            <div class="reviewer-info">
              <div class="avatar-small">{{ assignee.username[0].toUpperCase() }}</div>
              <span>{{ assignee.name }} {{ assignee.surname }}</span>
            </div>
          </label>
        </div>
        <div class="reviewer-actions">
          <button class="btn-secondary" @click="cancelReviewerSelection">Отмена</button>
          <button class="btn-primary" @click="confirmReviewerSelection" :disabled="!selectedReviewerId">
            Отправить на проверку
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, inject, computed } from 'vue'
import tasksApi from '../../services/tasks'
import { useAuthStore } from '../../stores/auth'
import TaskChat from '../../components/TaskChat.vue'
import { useTaskEvents } from '../../composables/useTaskEvents'

const props = defineProps({
  taskId: Number
})

const emit = defineEmits(['close', 'updated', 'edit', 'delete'])

const authStore = useAuthStore()
const task = ref(null)
const loading = ref(true)
const isTracking = ref(false)
const newTodoTitle = ref('')
const $confirm = inject('$confirm')
const selectedStatus = ref('1')
const showReviewerModal = ref(false)
const selectedReviewerId = ref(null)
const pendingStatus = ref(null)
const { emitTaskEvent } = useTaskEvents()

// Форматирование описания с преобразованием ссылок в кликабельные элементы
const formattedDescription = computed(() => {
  if (!task.value?.description) {
    return 'Описание отсутствует'
  }

  const description = task.value.description

  // Регулярное выражение для поиска URL
  const urlRegex = /(https?:\/\/[^\s]+)/g

  // Экранируем HTML, но сохраняем ссылки
  const escapedDescription = description
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;')

  // Заменяем URL на кликабельные ссылки
  const formatted = escapedDescription.replace(urlRegex, (url) => {
    return `<a href="${url}" target="_blank" rel="noopener noreferrer" class="description-link">${url}</a>`
  })

  // Заменяем переводы строк на <br>
  return formatted.replace(/\n/g, '<br>')
})

const loadTask = async () => {
  try {
    loading.value = true
    const response = await tasksApi.getTask(props.taskId)
    if (response.success) {
      task.value = response.task
      selectedStatus.value = String(task.value.status)
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

    // Эмитим событие для обновления чата
    emitTaskEvent(props.taskId, 'tracking_started', {
      userId: authStore.user?.id
    })

    loadTask()
  } catch (error) {
    console.error('Ошибка начала отслеживания:', error)
  }
}

const stopTracking = async () => {
  try {
    const response = await tasksApi.stopTracking(props.taskId)
    isTracking.value = false

    // Эмитим событие для обновления чата
    emitTaskEvent(props.taskId, 'tracking_stopped', {
      userId: authStore.user?.id,
      duration: response.tracking?.duration
    })

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

const handleStatusChange = async () => {
  const newStatus = parseInt(selectedStatus.value)

  // Если статус меняется на "На проверке" (3) и есть участники
  if (newStatus === 3 && task.value.assignees.length > 0) {
    pendingStatus.value = newStatus
    showReviewerModal.value = true
    return
  }

  // Для остальных статусов сразу обновляем
  await updateTaskStatus(newStatus)
}

const updateTaskStatus = async (status, reviewerId = null) => {
  try {
    const oldStatus = task.value.status

    const updateData = {
      title: task.value.title,
      description: task.value.description,
      status: status,
      priority: task.value.priority,
      start_date: task.value.start_date,
      deadline: task.value.deadline
    }

    // Добавляем ID проверяющего если он указан
    if (reviewerId) {
      updateData.reviewer_id = reviewerId
    }

    await tasksApi.updateTask(task.value.id, updateData)

    // Обновляем локальное состояние без полной перезагрузки
    task.value.status = status

    // Эмитим событие для обновления других компонентов
    emitTaskEvent(task.value.id, 'status_changed', {
      oldStatus,
      newStatus: status,
      reviewerId
    })

    emit('updated')
  } catch (error) {
    console.error('Ошибка обновления статуса:', error)
    // Откатываем выбор если ошибка
    selectedStatus.value = String(task.value.status)
  }
}

const cancelReviewerSelection = () => {
  showReviewerModal.value = false
  selectedReviewerId.value = null
  pendingStatus.value = null
  // Откатываем статус
  selectedStatus.value = String(task.value.status)
}

const confirmReviewerSelection = async () => {
  if (!selectedReviewerId.value) return

  // Передаем ID проверяющего в updateTaskStatus
  await updateTaskStatus(pendingStatus.value, selectedReviewerId.value)
  showReviewerModal.value = false
  selectedReviewerId.value = null
  pendingStatus.value = null
}

const editTask = () => {
  emit('edit', task.value)
}

const deleteTask = async () => {
  try {
    await $confirm({
      title: 'Удаление задачи',
      message: 'Вы уверены, что хотите удалить эту задачу? Это действие нельзя отменить.',
      confirmText: 'Удалить',
      cancelText: 'Отмена',
      type: 'danger'
    })

    const response = await tasksApi.deleteTask(task.value.id)
    if (response.success) {
      emit('delete')
      emit('close')
    }
  } catch (rejected) {
    if (rejected === false) {
      // Пользователь отменил
      return
    }
    console.error('Ошибка удаления задачи:', rejected)
  }
}

// TODO методы
const addTodo = async () => {
  if (!newTodoTitle.value.trim()) return

  try {
    const response = await tasksApi.createTodo(props.taskId, newTodoTitle.value.trim())
    if (response.success) {
      newTodoTitle.value = ''
      loadTask()
    }
  } catch (error) {
    console.error('Ошибка добавления TODO:', error)
  }
}

const toggleTodo = async (todoId) => {
  try {
    await tasksApi.toggleTodo(todoId)
    loadTask()
  } catch (error) {
    console.error('Ошибка переключения TODO:', error)
  }
}

const deleteTodo = async (todoId) => {
  try {
    await $confirm({
      title: 'Удаление TODO',
      message: 'Вы уверены, что хотите удалить этот пункт?',
      confirmText: 'Удалить',
      cancelText: 'Отмена',
      type: 'danger'
    })

    const response = await tasksApi.deleteTodo(todoId)
    if (response.success) {
      loadTask()
    }
  } catch (rejected) {
    if (rejected === false) {
      // Пользователь отменил
      return
    }
    console.error('Ошибка удаления TODO:', rejected)
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
  max-width: 1400px;
  max-height: 90vh;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.modal-layout {
  display: flex;
  flex-direction: column;
  height: 100%;
  overflow: hidden;
}

.modal-body {
  display: grid;
  grid-template-columns: 1fr 400px;
  flex: 1;
  overflow: hidden;
  border-top: 1px solid #e0e0e0;
}

.task-details {
  overflow-y: auto;
  height: 100%;
  padding: 1.5rem;
}

.chat-column {
  border-left: 1px solid #e0e0e0;
  background: #f9fafb;
  display: flex;
  flex-direction: column;
  height: 100%;
  overflow: hidden;
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
  background: white;
  flex-shrink: 0;
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

.section .description {
  margin: 0;
  line-height: 1.6;
  /* color: #4b5563; */
  white-space: pre-wrap;
  word-wrap: break-word;
}

.description :deep(a) {
  color: #2563eb !important;
  text-decoration: none;
  font-weight: 500;
  transition: all 0.2s;
}

.description :deep(a):hover {
  color: #1d4ed8 !important;
  background: #eff6ff;
  text-decoration: underline;
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

/* TODO стили */
.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.todos-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.todo-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem;
  background: #f9f9f9;
  border-radius: 8px;
  transition: all 0.2s;
}

.todo-item:hover {
  background: #f3f3f3;
}

.todo-checkbox {
  width: 18px;
  height: 18px;
  cursor: pointer;
  flex-shrink: 0;
}

.todo-title {
  flex: 1;
  font-size: 0.95rem;
  color: #1a1a1a;
  transition: all 0.2s;
}

.todo-title.completed {
  text-decoration: line-through;
  color: #999;
}

.btn-delete-todo {
  background: none;
  border: none;
  color: #dc2626;
  font-size: 1.5rem;
  cursor: pointer;
  padding: 0;
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
  transition: all 0.2s;
  flex-shrink: 0;
}

.btn-delete-todo:hover {
  background: #fee2e2;
}

.todo-add {
  display: flex;
  gap: 0.5rem;
  margin-top: 0.5rem;
}

.todo-input {
  flex: 1;
  padding: 0.75rem;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  font-size: 0.95rem;
  transition: all 0.2s;
}

.todo-input:focus {
  outline: none;
  border-color: #2d3748;
}

.btn-add-todo {
  background: #2d3748;
  color: white;
  border: none;
  border-radius: 8px;
  width: 40px;
  height: 40px;
  cursor: pointer;
  font-size: 1.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
  flex-shrink: 0;
}

.btn-add-todo:hover:not(:disabled) {
  background: #1a202c;
}

.btn-add-todo:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Стили для контроля статуса */
.status-control {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.status-control label {
  font-size: 0.85rem;
  color: #666;
  font-weight: 500;
}

.status-select {
  padding: 0.5rem 1rem;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  font-size: 0.9rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  background: white;
}

.status-select:hover {
  border-color: #2d3748;
}

.status-select:focus {
  outline: none;
  border-color: #2d3748;
  box-shadow: 0 0 0 3px rgba(45, 55, 72, 0.1);
}

/* Модалка выбора проверяющего */
.reviewer-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1100;
}

.reviewer-modal {
  background: white;
  border-radius: 12px;
  padding: 2rem;
  max-width: 500px;
  width: 90%;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
}

.reviewer-modal h3 {
  margin: 0 0 0.5rem 0;
  font-size: 1.25rem;
  color: #1a1a1a;
}

.reviewer-hint {
  margin: 0 0 1.5rem 0;
  color: #666;
  font-size: 0.9rem;
}

.reviewers-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  margin-bottom: 1.5rem;
  max-height: 300px;
  overflow-y: auto;
}

.reviewer-option {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  border: 2px solid #e0e0e0;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
}

.reviewer-option:hover {
  border-color: #2d3748;
  background: #f9fafb;
}

.reviewer-option input[type="radio"] {
  width: 18px;
  height: 18px;
  cursor: pointer;
}

.reviewer-option input[type="radio"]:checked + .reviewer-info {
  font-weight: 600;
}

.reviewer-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex: 1;
}

.avatar-small {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: #2d3748;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 0.9rem;
}

.reviewer-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
}

.btn-secondary {
  padding: 0.75rem 1.5rem;
  background: white;
  color: #666;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.2s;
}

.btn-secondary:hover {
  background: #f9f9f9;
}

/* Адаптивность для планшетов и мобильных */
@media (max-width: 1024px) {
  .modal-content {
    max-width: 95vw;
  }

  .modal-body {
    grid-template-columns: 1fr 350px;
  }
}

@media (max-width: 768px) {
  .modal-body {
    grid-template-columns: 1fr;
  }

  .chat-column {
    border-left: none;
    border-top: 1px solid #e0e0e0;
    max-height: 400px;
  }

  .modal-content {
    max-height: 95vh;
  }

  .reviewer-modal {
    padding: 1.5rem;
    max-width: 95%;
  }
}
</style>
