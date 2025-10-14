<template>
  <div class="login-container">
    <!-- Лоадер поверх формы -->
    <div v-if="loading" class="loader-overlay">
      <div class="loader-spinner"></div>
      <p class="loader-text">Вход в систему...</p>
    </div>

    <div class="login-card" :class="{ 'loading-state': loading }">
      <div class="login-header">
        <h1>Вход в систему</h1>
        <p class="subtitle">Введите свои учетные данные</p>
      </div>

      <form @submit.prevent="handleLogin">
        <div class="form-group">
          <label for="username">Имя пользователя</label>
          <input
            id="username"
            v-model="username"
            type="text"
            placeholder="Введите логин"
            required
            autocomplete="username"
            :disabled="loading"
          />
        </div>

        <div class="form-group">
          <label for="password">Пароль</label>
          <input
            id="password"
            v-model="password"
            type="password"
            placeholder="Введите пароль"
            required
            autocomplete="current-password"
            :disabled="loading"
          />
        </div>

        <button type="submit" :disabled="loading" class="submit-btn">
          <span v-if="!loading">Войти</span>
          <span v-else class="btn-loading">
            <span class="btn-spinner"></span>
            Вход...
          </span>
        </button>
      </form>

      <div v-if="message && !loading" :class="['message', messageType]">
        {{ message }}
      </div>

      <div class="register-link">
        Нет аккаунта?
        <RouterLink to="/register">Зарегистрироваться</RouterLink>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth.js'

const router = useRouter()
const authStore = useAuthStore()

const username = ref('')
const password = ref('')
const loading = ref(false)
const message = ref('')
const messageType = ref('')

const handleLogin = async () => {
  loading.value = true
  message.value = ''

  try {
    const result = await authStore.login(username.value, password.value)

    if (result.success) {
      message.value = 'Вход выполнен успешно'
      messageType.value = 'success'

      // Небольшая задержка для показа успеха
      setTimeout(() => {
        router.push('/dashboard')
      }, 500)
    } else {
      message.value = result.message || 'Неверный логин или пароль'
      messageType.value = 'error'
      loading.value = false
    }
  } catch (error) {
    message.value = 'Ошибка подключения к серверу'
    messageType.value = 'error'
    loading.value = false
  }
}
</script>

<style scoped>
.login-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  background: #f5f5f7;
  padding: 1rem;
  position: relative;
}

/* Overlay лоадер */
.loader-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(245, 245, 247, 0.95);
  backdrop-filter: blur(8px);
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  z-index: 9999;
  animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

.loader-spinner {
  width: 50px;
  height: 50px;
  border: 4px solid #e0e0e0;
  border-top-color: #2d3748;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.loader-text {
  margin-top: 1.5rem;
  font-size: 1rem;
  font-weight: 500;
  color: #2d3748;
  animation: pulse 1.5s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

.login-card {
  background: white;
  padding: 3rem 2.5rem;
  border-radius: 8px;
  border: 1px solid #e0e0e0;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  width: 100%;
  max-width: 420px;
  transition: opacity 0.3s ease;
}

.login-card.loading-state {
  opacity: 0.6;
  pointer-events: none;
}

.login-header {
  text-align: center;
  margin-bottom: 2rem;
}

h1 {
  margin: 0 0 0.5rem 0;
  font-size: 1.75rem;
  font-weight: 600;
  color: #1a1a1a;
  letter-spacing: -0.5px;
}

.subtitle {
  margin: 0;
  font-size: 0.9rem;
  color: #666;
  font-weight: 400;
}

.form-group {
  margin-bottom: 1.25rem;
}

label {
  display: block;
  margin-bottom: 0.5rem;
  color: #333;
  font-size: 0.9rem;
  font-weight: 500;
}

input {
  width: 100%;
  padding: 0.75rem 1rem;
  border: 1px solid #d1d1d6;
  border-radius: 6px;
  font-size: 0.95rem;
  color: #1a1a1a;
  background: #fafafa;
  transition: all 0.2s ease;
  box-sizing: border-box;
}

input::placeholder {
  color: #999;
}

input:hover:not(:disabled) {
  border-color: #b0b0b0;
  background: white;
}

input:focus {
  outline: none;
  border-color: #4a5568;
  background: white;
  box-shadow: 0 0 0 3px rgba(74, 85, 104, 0.1);
}

input:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.submit-btn {
  width: 100%;
  padding: 0.875rem;
  background: #2d3748;
  color: white;
  border: none;
  border-radius: 6px;
  font-size: 0.95rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  margin-top: 0.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.submit-btn:hover:not(:disabled) {
  background: #1a202c;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(45, 55, 72, 0.2);
}

.submit-btn:active:not(:disabled) {
  transform: translateY(0);
}

.submit-btn:disabled {
  background: #cbd5e0;
  cursor: not-allowed;
  transform: none;
}

.btn-loading {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-spinner {
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
}

.message {
  margin-top: 1rem;
  padding: 0.75rem 1rem;
  border-radius: 6px;
  font-size: 0.9rem;
  text-align: center;
  animation: slideDown 0.3s ease;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.message.success {
  background: #f0fdf4;
  color: #166534;
  border: 1px solid #bbf7d0;
}

.message.error {
  background: #fef2f2;
  color: #991b1b;
  border: 1px solid #fecaca;
}

.register-link {
  text-align: center;
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid #e0e0e0;
  font-size: 0.9rem;
  color: #666;
}

.register-link a {
  color: #2d3748;
  text-decoration: none;
  font-weight: 600;
  transition: color 0.2s ease;
}

.register-link a:hover {
  color: #1a202c;
  text-decoration: underline;
}

@media (max-width: 480px) {
  .login-card {
    padding: 2rem 1.5rem;
  }

  h1 {
    font-size: 1.5rem;
  }
}
</style>
