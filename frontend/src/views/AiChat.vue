<template>
  <DashboardLayout>
     <template #header-left>
      <h1>AI Ассистент SEO</h1>
    </template>
    <div class="ai-chat-page">
      <div class="page-header">
        <p class="subtitle">Профессиональный помощник для создания SEO-текстов и заголовков</p>
      </div>

      <div class="chat-container">
        <!-- История чата -->
        <div class="chat-history" ref="chatHistoryRef">
          <div v-if="chatHistory.length === 0" class="chat-empty">
            <div class="empty-icon">💬</div>
            <h3>Начните диалог с AI</h3>
            <p>Введите ваш запрос ниже, чтобы получить помощь в создании SEO-контента</p>
          </div>

          <div v-else class="messages-list">
            <div 
              v-for="(message, index) in chatHistory" 
              :key="index"
              :class="['message', message.role]"
            >
              <div class="message-avatar">
                <div v-if="message.role === 'user'" class="avatar-user">
                  <img :src="user.avatar" alt="Аватар пользователя" v-if="user.avatar">
                  <div v-else>{{ user?.username?.[0]?.toUpperCase() || 'U' }}</div>
                </div>
                <div v-else class="avatar-ai">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z" />
                  </svg>
                </div>
              </div>
              <div class="message-content">
                <div class="message-header">
                  <span class="message-author">{{ message.role === 'user' ? 'Вы' : 'AI Ассистент' }}</span>
                  <span class="message-time">{{ message.timestamp }}</span>
                </div>
                <div class="message-text">{{ message.content }}</div>
              </div>
            </div>

            <!-- Индикатор печатания -->
            <div v-if="loading" class="message assistant typing-indicator">
              <div class="message-avatar">
                <div class="avatar-ai">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z" />
                  </svg>
                </div>
              </div>
              <div class="message-content">
                <div class="typing-dots">
                  <span></span>
                  <span></span>
                  <span></span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Форма ввода -->
        <div class="chat-input-container">
          <form @submit.prevent="sendPrompt" class="chat-input-form">
            <textarea
              v-model="prompt"
              @keydown.enter.exact.prevent="sendPrompt"
              placeholder="Введите ваш запрос... (Enter для отправки, Shift+Enter для новой строки)"
              class="chat-textarea"
              rows="3"
              :disabled="loading"
            ></textarea>
            <div class="input-actions">
              <button 
                v-if="chatHistory.length > 0"
                type="button" 
                @click="clearChat"
                class="clear-btn"
                :disabled="loading"
              >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                </svg>
                Очистить
              </button>
              <button 
                type="submit" 
                class="send-btn" 
                :disabled="loading || !prompt.trim()"
              >
                <span v-if="!loading">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                  </svg>
                  Отправить
                </span>
                <span v-else>
                  <div class="btn-spinner"></div>
                  Печатает...
                </span>
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Подсказки -->
      <div v-if="chatHistory.length === 0" class="suggestions-container">
        <h3>Примеры запросов:</h3>
        <div class="suggestions-grid">
          <button 
            v-for="(suggestion, index) in suggestions" 
            :key="index"
            @click="useSuggestion(suggestion)"
            class="suggestion-card"
            :disabled="loading"
          >
            <div class="suggestion-icon">{{ suggestion.icon }}</div>
            <div class="suggestion-text">{{ suggestion.text }}</div>
          </button>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref, computed, nextTick, onMounted } from 'vue'
import DashboardLayout from '../layouts/DashboardLayout.vue'
import axios from 'axios'
import { useAuthStore } from '../stores/auth.js'

const authStore = useAuthStore()
const prompt = ref('')
const loading = ref(false)
const chatHistory = ref([])
const chatHistoryRef = ref(null)

// Получаем данные пользователя из authStore
const user = computed(() => authStore.user || {})

const suggestions = [
  { icon: '✍️', text: 'Создать SEO-заголовок для статьи о...' },
  { icon: '📝', text: 'Написать мета-описание для страницы...' },
  { icon: '🔍', text: 'Подобрать ключевые слова для темы...' },
  { icon: '📊', text: 'Создать структуру статьи на тему...' }
]

const formatTime = () => {
  const now = new Date()
  return now.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' })
}

const scrollToBottom = async () => {
  await nextTick()
  if (chatHistoryRef.value) {
    const messagesContainer = chatHistoryRef.value.querySelector('.messages-list')
    if (messagesContainer) {
      messagesContainer.scrollTop = messagesContainer.scrollHeight
    }
  }
}

const sendPrompt = async () => {
  if (!prompt.value.trim() || loading.value) return

  const userMessage = {
    role: 'user',
    content: prompt.value,
    timestamp: formatTime()
  }

  chatHistory.value.push(userMessage)
  const userPrompt = prompt.value
  prompt.value = ''
  loading.value = true

  await scrollToBottom()

  try {
    const res = await axios.post('http://185.213.240.236:3000/api/generate', { 
      prompt: userPrompt 
    })

    const aiMessage = {
      role: 'assistant',
      content: res.data.result,
      timestamp: formatTime()
    }

    chatHistory.value.push(aiMessage)
  } catch (err) {
    console.error('Ошибка при запросе:', err)
    
    const errorMessage = {
      role: 'assistant',
      content: err.response?.data?.result || 'Произошла ошибка при обращении к серверу. Попробуйте еще раз.',
      timestamp: formatTime()
    }

    chatHistory.value.push(errorMessage)
  } finally {
    loading.value = false
    await scrollToBottom()
  }
}

const useSuggestion = (suggestion) => {
  prompt.value = suggestion.text
}

const clearChat = () => {
  if (confirm('Вы уверены, что хотите очистить историю чата?')) {
    chatHistory.value = []
    prompt.value = ''
  }
}

// Загружаем данные пользователя при монтировании компонента (если нужно)
onMounted(async () => {
  if (!authStore.user) {
    await authStore.fetchUser()
  }
})
</script>

<style scoped>
.avatar-user {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 1rem;
  background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
  color: white;
  overflow: hidden; /* Добавьте это */
}

.avatar-user img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.ai-chat-page {
  max-width: 1200px;
  margin: 0 auto;
}

.page-header {
  margin-bottom: 2rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid #e0e0e0;
}

.page-header h1 {
  margin: 0 0 0.5rem 0;
  font-size: 1.75rem;
  font-weight: 600;
  color: #1a1a1a;
}

.subtitle {
  margin: 0;
  font-size: 0.95rem;
  color: #666;
  font-weight: 400;
}

/* Chat Container */
.chat-container {
  background: white;
  border-radius: 12px;
  border: 1px solid #e0e0e0;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
  display: flex;
  flex-direction: column;
  height: calc(100vh - 280px);
  min-height: 500px;
}

/* Chat History */
.chat-history {
  flex: 1;
  overflow-y: auto;
  padding: 1.5rem;
  background: #fafafa;
}

.chat-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100%;
  text-align: center;
  color: #999;
}

.empty-icon {
  font-size: 4rem;
  margin-bottom: 1rem;
  opacity: 0.5;
}

.chat-empty h3 {
  margin: 0 0 0.5rem 0;
  font-size: 1.25rem;
  color: #666;
}

.chat-empty p {
  margin: 0;
  font-size: 0.9rem;
  color: #999;
}

/* Messages */
.messages-list {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.message {
  display: flex;
  gap: 1rem;
  animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.message-avatar {
  flex-shrink: 0;
}

.avatar-user,
.avatar-ai {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 1rem;
}

.avatar-user {
  background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
  color: white;
}

.avatar-ai {
  background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
  color: white;
}

.avatar-ai svg {
  width: 20px;
  height: 20px;
}

.message-content {
  flex: 1;
  min-width: 0;
}

.message-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}

.message-author {
  font-weight: 600;
  font-size: 0.875rem;
  color: #1a1a1a;
}

.message-time {
  font-size: 0.75rem;
  color: #999;
}

.message-text {
  background: white;
  padding: 1rem;
  border-radius: 8px;
  border: 1px solid #e0e0e0;
  color: #333;
  line-height: 1.6;
  white-space: pre-wrap;
  word-wrap: break-word;
}

.message.user .message-text {
  background: #f0f9ff;
  border-color: #bfdbfe;
}

/* Typing Indicator */
.typing-indicator .message-content {
  display: flex;
  align-items: center;
}

.typing-dots {
  background: white;
  padding: 1rem 1.5rem;
  border-radius: 8px;
  border: 1px solid #e0e0e0;
  display: flex;
  gap: 0.5rem;
}

.typing-dots span {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #2563eb;
  animation: typing 1.4s infinite;
}

.typing-dots span:nth-child(2) {
  animation-delay: 0.2s;
}

.typing-dots span:nth-child(3) {
  animation-delay: 0.4s;
}

@keyframes typing {
  0%, 60%, 100% {
    transform: translateY(0);
    opacity: 0.7;
  }
  30% {
    transform: translateY(-10px);
    opacity: 1;
  }
}

/* Chat Input */
.chat-input-container {
  border-top: 1px solid #e0e0e0;
  background: white;
  padding: 1.5rem;
}

.chat-input-form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.chat-textarea {
  width: 100%;
  padding: 1rem;
  border-radius: 8px;
  border: 1px solid #e0e0e0;
  background: #fafafa;
  color: #333;
  font-size: 0.95rem;
  font-family: inherit;
  resize: none;
  transition: all 0.2s ease;
}

.chat-textarea:focus {
  outline: none;
  border-color: #2563eb;
  background: white;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.chat-textarea:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.input-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
}

.clear-btn,
.send-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  border: none;
  font-size: 0.9rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.clear-btn {
  background: #f1f5f9;
  color: #64748b;
}

.clear-btn:hover:not(:disabled) {
  background: #e2e8f0;
  color: #475569;
}

.clear-btn svg {
  width: 18px;
  height: 18px;
}

.send-btn {
  background: #2563eb;
  color: white;
}

.send-btn:hover:not(:disabled) {
  background: #1d4ed8;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
}

.send-btn:disabled {
  background: #cbd5e1;
  cursor: not-allowed;
  transform: none;
}

.send-btn svg {
  width: 18px;
  height: 18px;
}

.btn-spinner {
  width: 14px;
  height: 14px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* Suggestions */
.suggestions-container {
  margin-top: 2rem;
}

.suggestions-container h3 {
  margin: 0 0 1rem 0;
  font-size: 1rem;
  font-weight: 600;
  color: #1a1a1a;
}

.suggestions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1rem;
}

.suggestion-card {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem;
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s ease;
  text-align: left;
}

.suggestion-card:hover:not(:disabled) {
  border-color: #2563eb;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.1);
  transform: translateY(-2px);
}

.suggestion-card:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.suggestion-icon {
  font-size: 1.5rem;
  flex-shrink: 0;
}

.suggestion-text {
  font-size: 0.9rem;
  color: #333;
}

/* Responsive */
@media (max-width: 768px) {
  .chat-container {
    height: calc(100vh - 200px);
  }

  .suggestions-grid {
    grid-template-columns: 1fr;
  }

  .input-actions {
    flex-direction: column;
  }

  .clear-btn,
  .send-btn {
    width: 100%;
    justify-content: center;
  }
}
</style>