<template>
  <div class="ai-form">
    <h2 class="title">AI Prompt Tester</h2>

    <form @submit.prevent="sendPrompt" class="prompt-form">
      <textarea
        v-model="prompt"
        placeholder="Введите ваш запрос..."
        class="prompt-input"
        rows="4"
      ></textarea>

      <button type="submit" class="send-btn" :disabled="loading">
        {{ loading ? 'Печатает...' : 'Отправить' }}
      </button>
    </form>

    <div v-if="response" class="response-box">
      <h3>Ответ от ИИ:</h3>
      <p>{{ response }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import axios from 'axios'

const prompt = ref('')
const response = ref('')
const loading = ref(false)

const sendPrompt = async () => {
  if (!prompt.value.trim()) {
    response.value = 'Введите запрос перед отправкой'
    return
  }

  loading.value = true
  response.value = ''

  try {
    const res = await axios.post('http://185.213.240.236:3000/api/generate', { prompt: prompt.value })
    response.value = res.data.result
  } catch (err) {
    console.error('Ошибка при запросе:', err)
    response.value = err.response?.data?.result || 'Ошибка при обращении к серверу'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.ai-form {
  max-width: 600px;
  margin: 40px auto;
  padding: 24px;
  border-radius: 16px;
  background: #0f172a;
  color: #e2e8f0;
  box-shadow: 0 0 12px rgba(0, 0, 0, 0.25);
  font-family: 'Inter', sans-serif;
}
.title {
  text-align: center;
  margin-bottom: 16px;
  font-size: 22px;
}
.prompt-form {
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.prompt-input {
  padding: 10px;
  border-radius: 8px;
  border: 1px solid #334155;
  background: #1e293b;
  color: #f8fafc;
  resize: none;
}
.send-btn {
  background: #2563eb;
  color: white;
  border: none;
  padding: 10px 18px;
  border-radius: 8px;
  cursor: pointer;
  transition: 0.2s;
}
.send-btn:hover {
  background: #1d4ed8;
}
.response-box {
  margin-top: 20px;
  background: #1e293b;
  border-radius: 8px;
  padding: 12px;
  white-space: pre-wrap;
}
</style>
