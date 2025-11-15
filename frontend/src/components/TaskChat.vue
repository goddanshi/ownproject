<template>
  <div class="task-chat">
    <div class="chat-header">
      <h3>Обсуждение задачи</h3>
    </div>

    <div class="chat-messages" ref="messagesContainer">
      <div v-if="loading" class="loading">
        <div class="spinner"></div>
        <p>Загрузка сообщений...</p>
      </div>

      <div v-else-if="messages.length === 0" class="no-messages">
        <p>Сообщений пока нет</p>
        <p class="hint">Начните обсуждение задачи</p>
      </div>

      <div v-else class="messages-list">
        <div
          v-for="message in messages"
          :key="message.id"
          :class="['message', { 'own-message': message.user.id === currentUserId }]"
        >
          <div class="message-avatar">
            {{ message.user.username[0].toUpperCase() }}
          </div>
          <div class="message-content">
            <div class="message-header">
              <span class="message-author">{{ message.user.name }} {{ message.user.surname }}</span>
              <span class="message-time">{{ formatTime(message.created_at) }}</span>
            </div>
            <div class="message-text" v-html="formatMessage(message.message)"></div>
          </div>
        </div>
      </div>
    </div>

    <div class="chat-input">
      <textarea
        v-model="newMessage"
        @keydown.enter.exact.prevent="sendMessage"
        @keydown.enter.shift.exact="newMessage += '\n'"
        placeholder="Введите сообщение... (Enter - отправить, Shift+Enter - новая строка)"
        rows="3"
      ></textarea>
      <button
        @click="sendMessage"
        :disabled="!newMessage.trim() || sending"
        class="send-button"
      >
        <svg v-if="!sending" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
        </svg>
        <span v-else>...</span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue'
import tasksApi from '../services/tasks'
import { useAuthStore } from '../stores/auth'

const props = defineProps({
  taskId: {
    type: Number,
    required: true
  }
})

const authStore = useAuthStore()
const currentUserId = authStore.user?.id

const messages = ref([])
const newMessage = ref('')
const loading = ref(false)
const sending = ref(false)
const messagesContainer = ref(null)

let pollingActive = false
let pollingTimeout = null

// Загрузка всех сообщений при открытии
const loadAllMessages = async () => {
  try {
    loading.value = true
    const response = await tasksApi.getAllChatMessages(props.taskId)
    if (response.success) {
      messages.value = response.messages
      await nextTick()
      scrollToBottom()
    }
  } catch (error) {
    console.error('Ошибка загрузки сообщений:', error)
  } finally {
    loading.value = false
  }
}

// Long polling для получения новых сообщений
const pollNewMessages = async () => {
  if (!pollingActive) return

  const lastMessageId = messages.value.length > 0
    ? messages.value[messages.value.length - 1].id
    : null

  try {
    const response = await tasksApi.getChatMessages(props.taskId, lastMessageId)

    if (response.success && response.messages.length > 0) {
      // Проверяем, есть ли уже такие сообщения (чтобы избежать дублирования)
      const existingIds = new Set(messages.value.map(m => m.id))
      const newMessages = response.messages.filter(m => !existingIds.has(m.id))

      if (newMessages.length > 0) {
        messages.value.push(...newMessages)
        await nextTick()
        scrollToBottom()
      }
    }
  } catch (error) {
    console.error('Ошибка polling:', error)
  }

  // Продолжаем polling
  if (pollingActive) {
    pollingTimeout = setTimeout(pollNewMessages, 1000)
  }
}

// Отправка сообщения
const sendMessage = async () => {
  if (!newMessage.value.trim() || sending.value) return

  try {
    sending.value = true
    const response = await tasksApi.sendChatMessage(props.taskId, newMessage.value)

    if (response.success) {
      // Проверяем, не добавлено ли уже это сообщение
      const existingMessage = messages.value.find(m => m.id === response.message.id)
      if (!existingMessage) {
        messages.value.push(response.message)
      }
      newMessage.value = ''
      await nextTick()
      scrollToBottom()
    }
  } catch (error) {
    console.error('Ошибка отправки сообщения:', error)
  } finally {
    sending.value = false
  }
}

// Прокрутка вниз к последнему сообщению
const scrollToBottom = () => {
  if (messagesContainer.value) {
    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
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

// Форматирование сообщения (преобразование ссылок в кликабельные)
const formatMessage = (text) => {
  if (!text) return ''

  // Экранируем HTML
  const escaped = text
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;')

  // Преобразуем URL в ссылки
  const urlPattern = /(https?:\/\/[^\s]+)/g
  return escaped.replace(urlPattern, '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>')
}

// Запуск при монтировании
onMounted(async () => {
  await loadAllMessages()
  pollingActive = true
  pollNewMessages()
})

// Остановка polling при размонтировании
onUnmounted(() => {
  pollingActive = false
  if (pollingTimeout) {
    clearTimeout(pollingTimeout)
  }
})
</script>

<style scoped>
.task-chat {
  display: flex;
  flex-direction: column;
  height: 100%;
  background: #f9fafb;
}

.chat-header {
  padding: 1rem 1.5rem;
  border-bottom: 1px solid #e5e7eb;
  background: white;
}

.chat-header h3 {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 600;
  color: #1f2937;
}

.chat-messages {
  flex: 1;
  overflow-y: auto;
  padding: 1.5rem;
}

.loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem;
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

.no-messages {
  text-align: center;
  padding: 3rem 1rem;
  color: #9ca3af;
}

.no-messages p {
  margin: 0.5rem 0;
}

.hint {
  font-size: 0.875rem;
}

.messages-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.message {
  display: flex;
  gap: 0.75rem;
  animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

.own-message {
  flex-direction: row-reverse;
}

.own-message .message-content {
  align-items: flex-end;
}

.own-message .message-text {
  background: #3b82f6;
  color: white;
}

.message-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: #6b7280;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  flex-shrink: 0;
}

.own-message .message-avatar {
  background: #3b82f6;
}

.message-content {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  max-width: 70%;
  min-width: 0;
  overflow: hidden;
}

.message-header {
  display: flex;
  gap: 0.5rem;
  align-items: center;
  font-size: 0.75rem;
}

.message-author {
  font-weight: 600;
  color: #374151;
}

.message-time {
  color: #9ca3af;
}

.message-text {
  padding: 0.75rem 1rem;
  background: white;
  border-radius: 12px;
  word-wrap: break-word;
  word-break: break-word;
  overflow-wrap: break-word;
  white-space: pre-wrap;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
  max-width: 100%;
  hyphens: auto;
}

.message-text :deep(a) {
  color: #3b82f6;
  text-decoration: underline;
  word-break: break-all;
}

.message-text :deep(a):hover {
  color: #2563eb;
}

.own-message .message-text :deep(a) {
  color: #93c5fd;
}

.own-message .message-text :deep(a):hover {
  color: #bfdbfe;
}

.chat-input {
  display: flex;
  gap: 0.75rem;
  padding: 1rem 1.5rem;
  background: white;
  border-top: 1px solid #e5e7eb;
}

.chat-input textarea {
  flex: 1;
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  resize: none;
  font-family: inherit;
  font-size: 0.875rem;
  transition: border-color 0.2s;
}

.chat-input textarea:focus {
  outline: none;
  border-color: #3b82f6;
}

.send-button {
  padding: 0.75rem 1.25rem;
  background: #3b82f6;
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
}

.send-button:hover:not(:disabled) {
  background: #2563eb;
}

.send-button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.send-button svg {
  width: 20px;
  height: 20px;
}
</style>
