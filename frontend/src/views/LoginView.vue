<template>
  <div class="login-container">
    <div class="login-card">
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
          />
        </div>

        <button type="submit" :disabled="loading" class="submit-btn">
          {{ loading ? 'Вход...' : 'Войти' }}
        </button>
      </form>

      <div v-if="message" :class="['message', messageType]">
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
import { useAuthStore } from '../stores/auth'

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

      setTimeout(() => {
        router.push('/dashboard')
      }, 300)
    } else {
      message.value = result.message || 'Неверный логин или пароль'
      messageType.value = 'error'
    }
  } catch (error) {
    message.value = 'Ошибка подключения к серверу'
    messageType.value = 'error'
  } finally {
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
}

.login-card {
  background: white;
  padding: 3rem 2.5rem;
  border-radius: 8px;
  border: 1px solid #e0e0e0;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  width: 100%;
  max-width: 420px;
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

input:hover {
  border-color: #b0b0b0;
  background: white;
}

input:focus {
  outline: none;
  border-color: #4a5568;
  background: white;
  box-shadow: 0 0 0 3px rgba(74, 85, 104, 0.1);
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

.message {
  margin-top: 1rem;
  padding: 0.75rem 1rem;
  border-radius: 6px;
  font-size: 0.9rem;
  text-align: center;
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

/* Адаптив */
@media (max-width: 480px) {
  .login-card {
    padding: 2rem 1.5rem;
  }

  h1 {
    font-size: 1.5rem;
  }
}
</style>
