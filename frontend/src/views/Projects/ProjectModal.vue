<template>
  <div class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-content">
      <div class="modal-header">
        <h2>{{ project ? 'Редактировать проект' : 'Создать проект' }}</h2>
        <button class="close-btn" @click="$emit('close')">&times;</button>
      </div>

      <form @submit.prevent="handleSubmit">
        <div class="form-group">
          <label>Название проекта *</label>
          <input
            v-model="formData.name"
            type="text"
            required
            placeholder="Введите название проекта"
          />
        </div>

        <div class="form-group">
          <label>Описание</label>
          <textarea
            v-model="formData.description"
            rows="4"
            placeholder="Описание проекта..."
          ></textarea>
        </div>

        <div class="form-group">
          <label>Дата начала</label>
          <input
            v-model="formData.startDate"
            type="date"
          />
        </div>

        <div class="form-group">
          <label>Дата завершения</label>
          <input
            v-model="formData.endDate"
            type="date"
          />
        </div>

        <div class="form-group">
          <label>Сайт</label>
          <input
            v-model="formData.websiteUrl"
            type="url"
            placeholder="https://example.com"
          />
        </div>

        <div class="form-group">
          <label>Логотип (ссылка на изображение)</label>
          <input
            v-model="formData.logoUrl"
            type="url"
            placeholder="https://example.com/logo.png"
          />
          <div v-if="formData.logoUrl" class="logo-preview">
            <img :src="formData.logoUrl" alt="Превью логотипа" @error="handleImageError" />
          </div>
        </div>

        <div class="form-group">
          <label>Папка с документами (ссылка на Google Drive и т.д.)</label>
          <input
            v-model="formData.documentsUrl"
            type="url"
            placeholder="https://drive.google.com/..."
          />
        </div>

        <div class="form-group" v-if="!project">
          <label>Команда *</label>
          <select v-model="formData.teamId" required>
            <option value="">Выберите команду</option>
            <option
              v-for="team in teams"
              :key="team.id"
              :value="team.id"
            >
              {{ team.name }}
            </option>
          </select>
          <p class="help-text">
            Все участники выбранной команды автоматически станут участниками проекта
          </p>
        </div>

        <div v-if="error" class="error-message">{{ error }}</div>

        <div class="modal-footer">
          <button type="button" class="btn-secondary" @click="$emit('close')">
            Отмена
          </button>
          <button type="submit" class="btn-primary" :disabled="loading">
            {{ loading ? 'Сохранение...' : 'Сохранить' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import projectsApi from '../../services/projects'
import teamsApi from '../../services/teams'

const props = defineProps({
  project: Object
})

const emit = defineEmits(['close', 'saved'])

const formData = ref({
  name: '',
  description: '',
  teamId: '',
  startDate: '',
  endDate: '',
  websiteUrl: '',
  logoUrl: '',
  documentsUrl: ''
})

const teams = ref([])
const loading = ref(false)
const error = ref('')

// Функция конвертации timestamp в формат yyyy-MM-dd для input[type="date"]
const timestampToDateInput = (timestamp) => {
  if (!timestamp) return ''
  const date = new Date(timestamp * 1000)
  return date.toISOString().split('T')[0]
}

// Функция конвертации формата yyyy-MM-dd в timestamp
const dateInputToTimestamp = (dateStr) => {
  if (!dateStr) return null
  const date = new Date(dateStr)
  return Math.floor(date.getTime() / 1000)
}

const handleImageError = () => {
  error.value = 'Не удалось загрузить изображение. Проверьте URL.'
  setTimeout(() => {
    error.value = ''
  }, 3000)
}

const loadTeams = async () => {
  try {
    const response = await teamsApi.getTeams()
    if (response.success) {
      teams.value = response.teams
    }
  } catch (err) {
    console.error('Ошибка загрузки команд:', err)
  }
}

const handleSubmit = async () => {
  try {
    loading.value = true
    error.value = ''

    const data = {
      name: formData.value.name,
      description: formData.value.description,
      start_date: dateInputToTimestamp(formData.value.startDate),
      end_date: dateInputToTimestamp(formData.value.endDate),
      website_url: formData.value.websiteUrl || null,
      logo_url: formData.value.logoUrl || null,
      documents_url: formData.value.documentsUrl || null,
      team_id: parseInt(formData.value.teamId)
    }

    if (props.project) {
      await projectsApi.updateProject(props.project.id, data)
    } else {
      await projectsApi.createProject(data)
    }

    emit('saved')
  } catch (err) {
    error.value = err.response?.data?.message || 'Ошибка при сохранении проекта'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadTeams()

  if (props.project) {
    formData.value = {
      name: props.project.name,
      description: props.project.description || '',
      teamId: props.project.team.id,
      startDate: timestampToDateInput(props.project.start_date),
      endDate: timestampToDateInput(props.project.end_date),
      websiteUrl: props.project.website_url || '',
      logoUrl: props.project.logo_url || '',
      documentsUrl: props.project.documents_url || ''
    }
  }
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
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
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
}

.close-btn {
  background: none;
  border: none;
  font-size: 2rem;
  cursor: pointer;
  color: #666;
  line-height: 1;
  padding: 0;
  width: 32px;
  height: 32px;
}

form {
  padding: 1.5rem;
}

.form-group {
  margin-bottom: 1.5rem;
}

label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
  color: #333;
}

input[type="text"],
input[type="date"],
input[type="url"],
textarea,
select {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  font-size: 1rem;
  transition: border-color 0.2s;
}

input:focus,
textarea:focus,
select:focus {
  outline: none;
  border-color: #2d3748;
}

textarea {
  resize: vertical;
  font-family: inherit;
}

.help-text {
  margin-top: 0.5rem;
  font-size: 0.85rem;
  color: #666;
  font-style: italic;
}

.error-message {
  background: #fee2e2;
  color: #991b1b;
  padding: 0.75rem;
  border-radius: 8px;
  margin-bottom: 1rem;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding: 1.5rem;
  border-top: 1px solid #e0e0e0;
}

.btn-primary,
.btn-secondary {
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.2s;
}

.btn-primary {
  background: #2d3748;
  color: white;
  border: none;
}

.btn-primary:hover:not(:disabled) {
  background: #1a202c;
}

.btn-primary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-secondary {
  background: white;
  color: #666;
  border: 1px solid #e0e0e0;
}

.btn-secondary:hover {
  background: #f9f9f9;
}

.logo-preview {
  margin-top: 0.75rem;
  padding: 0.75rem;
  background: #f9fafb;
  border-radius: 8px;
  display: flex;
  justify-content: center;
  align-items: center;
}

.logo-preview img {
  width: 64px;
  height: 64px;
  object-fit: cover;
  border-radius: 50%;
}
</style>
