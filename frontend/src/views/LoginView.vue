<template>
  <div class="login-container">
    <div class="login-card">
      <h1>Login</h1>

      <form @submit.prevent="handleLogin">
        <div class="form-group">
          <label>Username</label>
          <input
            v-model="username"
            type="text"
            placeholder="testuser"
            required
          />
        </div>

        <div class="form-group">
          <label>Password</label>
          <input
            v-model="password"
            type="password"
            placeholder="testpass"
            required
          />
        </div>

        <button type="submit" :disabled="loading">
          {{ loading ? 'Loading...' : 'Login' }}
        </button>
      </form>

      <div v-if="message" :class="['message', messageType]">
        {{ message }}
      </div>

      <div v-if="user" class="user-info">
        <h3>Logged in as:</h3>
        <p>ID: {{ user.id }}</p>
        <p>Username: {{ user.username }}</p>
        <button @click="handleLogout">Logout</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import api from '../services/api'

const username = ref('testuser')
const password = ref('testpass')
const loading = ref(false)
const message = ref('')
const messageType = ref('')
const user = ref(null)

const handleLogin = async () => {
  loading.value = true
  message.value = ''

  try {
    const result = await api.login(username.value, password.value)

    if (result.success) {
      message.value = result.message
      messageType.value = 'success'
      user.value = result.user
    } else {
      message.value = result.message
      messageType.value = 'error'
    }
  } catch (error) {
    message.value = 'Connection error: ' + error.message
    messageType.value = 'error'
  } finally {
    loading.value = false
  }
}

const handleLogout = async () => {
  try {
    await api.logout()
    user.value = null
    message.value = 'Logged out successfully'
    messageType.value = 'success'
  } catch (error) {
    message.value = 'Logout error: ' + error.message
    messageType.value = 'error'
  }
}
</script>

<style scoped>
.login-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.login-card {
  background: white;
  padding: 2rem;
  border-radius: 10px;
  box-shadow: 0 10px 25px rgba(0,0,0,0.2);
  width: 100%;
  max-width: 400px;
}

h1 {
  text-align: center;
  margin-bottom: 2rem;
  color: #333;
}

.form-group {
  margin-bottom: 1.5rem;
}

label {
  display: block;
  margin-bottom: 0.5rem;
  color: #555;
  font-weight: 500;
}

input {
  width: 100%;
  padding: 0.75rem;
  border: 2px solid #e0e0e0;
  border-radius: 5px;
  font-size: 1rem;
  transition: border-color 0.3s;
}

input:focus {
  outline: none;
  border-color: #667eea;
}

button {
  width: 100%;
  padding: 0.75rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 5px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: transform 0.2s;
}

button:hover:not(:disabled) {
  transform: translateY(-2px);
}

button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.message {
  margin-top: 1rem;
  padding: 0.75rem;
  border-radius: 5px;
  text-align: center;
}

.message.success {
  background: #d4edda;
  color: #155724;
}

.message.error {
  background: #f8d7da;
  color: #721c24;
}

.user-info {
  margin-top: 2rem;
  padding: 1rem;
  background: #f8f9fa;
  border-radius: 5px;
}

.user-info h3 {
  margin-top: 0;
  color: #333;
}

.user-info p {
  margin: 0.5rem 0;
  color: #666;
}
</style>
