<template>
  <div class="activity-log">
    <div class="activity-header">
      <h3>История активности ({{ activities.length }})</h3>
    </div>

    <div class="activity-list-scrollable">
      <div v-if="loading" class="loading">
        <div class="spinner"></div>
        <p>Загрузка...</p>
      </div>

      <div v-else-if="activities.length === 0" class="no-activities">
        <p>Активности пока нет</p>
      </div>

      <div v-else>
        <div
          v-for="activity in activities"
          :key="activity.id"
          class="activity-item"
        >
          <div class="activity-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
            </svg>
          </div>
          <div class="activity-content">
            <div class="activity-text">{{ activity.message }}</div>
            <div class="activity-time">{{ formatTime(activity.created_at) }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue'
import tasksApi from '../services/tasks'
import { useTaskEvents } from '../composables/useTaskEvents'

const props = defineProps({
  taskId: {
    type: Number,
    required: true
  }
})

const { onTaskEvent } = useTaskEvents()

const activities = ref([])
const loading = ref(false)

let pollingActive = false
let pollingTimeout = null
let unsubscribe = null

// Загрузка системных сообщений (активностей)
const loadActivities = async () => {
  try {
    loading.value = true
    const response = await tasksApi.getAllChatMessages(props.taskId)
    if (response.success) {
      // Фильтруем только системные сообщения
      activities.value = response.messages.filter(msg => msg.is_system)
    }
  } catch (error) {
    console.error('Ошибка загрузки активностей:', error)
  } finally {
    loading.value = false
  }
}

// Polling для новых активностей
const pollNewActivities = async () => {
  if (!pollingActive) return

  const lastMessageId = activities.value.length > 0
    ? activities.value[activities.value.length - 1].id
    : null

  try {
    const response = await tasksApi.getChatMessages(props.taskId, lastMessageId)

    if (response.success && response.messages.length > 0) {
      const existingIds = new Set(activities.value.map(m => m.id))
      const newSystemMessages = response.messages
        .filter(m => m.is_system && !existingIds.has(m.id))

      if (newSystemMessages.length > 0) {
        activities.value.push(...newSystemMessages)
      }
    }
  } catch (error) {
    console.error('Ошибка polling активностей:', error)
  }

  if (pollingActive) {
    pollingTimeout = setTimeout(pollNewActivities, 1000)
  }
}

// Форматирование времени
const formatTime = (timestamp) => {
  const date = new Date(timestamp * 1000)
  const now = new Date()
  const isToday = date.toDateString() === now.toDateString()

  if (isToday) {
    return date.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' })
  }

  return date.toLocaleString('ru-RU', {
    day: '2-digit',
    month: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  })
}

onMounted(async () => {
  await loadActivities()
  pollingActive = true
  pollNewActivities()

  // Подписываемся на события задачи
  unsubscribe = onTaskEvent(props.taskId, async (event) => {
    const lastMessageId = activities.value.length > 0
      ? activities.value[activities.value.length - 1].id
      : null

    try {
      const response = await tasksApi.getChatMessages(props.taskId, lastMessageId, 1)
      if (response.success && response.messages.length > 0) {
        const existingIds = new Set(activities.value.map(m => m.id))
        const newSystemMessages = response.messages
          .filter(m => m.is_system && !existingIds.has(m.id))

        if (newSystemMessages.length > 0) {
          activities.value.push(...newSystemMessages)
        }
      }
    } catch (error) {
      console.error('Ошибка получения новых активностей:', error)
    }
  })
})

onUnmounted(() => {
  pollingActive = false
  if (pollingTimeout) {
    clearTimeout(pollingTimeout)
  }
  if (unsubscribe) {
    unsubscribe()
  }
})
</script>

<style scoped>
.activity-log {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.activity-header h3 {
  margin: 0;
  font-size: 1.1rem;
  color: #1a1a1a;
}

.activity-list-scrollable {
  max-height: 300px;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  padding: 1rem;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background: #fafafa;
}

.activity-list-scrollable::-webkit-scrollbar {
  width: 8px;
}

.activity-list-scrollable::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 4px;
}

.activity-list-scrollable::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 4px;
}

.activity-list-scrollable::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}

.loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  color: #6b7280;
}

.spinner {
  width: 32px;
  height: 32px;
  border: 3px solid #e5e7eb;
  border-top: 3px solid #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 0.5rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.no-activities {
  text-align: center;
  padding: 2rem 1rem;
  color: #9ca3af;
  font-style: italic;
}

.no-activities p {
  margin: 0;
}

.activity-item {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  padding: 0.875rem 1rem;
  background: white;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
  border-left: 3px solid #3b82f6;
  transition: all 0.2s;
}

.activity-item:hover {
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
  border-left-color: #2563eb;
}

.activity-icon {
  flex-shrink: 0;
  width: 24px;
  height: 24px;
  color: #3b82f6;
}

.activity-icon svg {
  width: 100%;
  height: 100%;
}

.activity-content {
  flex: 1;
  min-width: 0;
}

.activity-text {
  font-size: 0.9rem;
  color: #374151;
  line-height: 1.4;
  margin-bottom: 0.25rem;
}

.activity-time {
  font-size: 0.75rem;
  color: #9ca3af;
}
</style>
