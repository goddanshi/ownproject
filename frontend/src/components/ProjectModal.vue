<template>
  <div class="modal-overlay" @click.self="$emit('close')">
    <div class="modal">
      <div class="modal-header">
        <h3>{{ project ? 'Редактировать проект' : 'Создать проект' }}</h3>
        <button class="close-btn" @click="$emit('close')">×</button>
      </div>

      <div class="modal-body">
        <div class="form-group">
          <label for="project-name">Название проекта *</label>
          <input
            id="project-name"
            v-model="form.name"
            type="text"
            placeholder="Введите название проекта"
            class="form-input"
            required
          />
        </div>

        <div class="form-group">
          <label for="project-description">Описание</label>
          <textarea
            id="project-description"
            v-model="form.description"
            placeholder="Введите описание проекта"
            class="form-textarea"
            rows="4"
          ></textarea>
        </div>

        <div class="form-group">
          <label for="project-start-date">Дата начала</label>
          <input
            id="project-start-date"
            v-model="form.startDate"
            type="date"
            class="form-input"
          />
        </div>

        <div class="form-group">
          <label for="project-end-date">Дата завершения</label>
          <input
            id="project-end-date"
            v-model="form.endDate"
            type="date"
            class="form-input"
          />
        </div>

        <div class="form-group">
          <label for="project-website">Сайт</label>
          <input
            id="project-website"
            v-model="form.websiteUrl"
            type="url"
            placeholder="https://example.com"
            class="form-input"
          />
        </div>

        <div class="form-group">
          <label for="project-logo">Логотип (ссылка на изображение)</label>
          <input
            id="project-logo"
            v-model="form.logoUrl"
            type="url"
            placeholder="https://example.com/logo.png"
            class="form-input"
          />
          <div v-if="form.logoUrl" class="logo-preview">
            <img :src="form.logoUrl" alt="Превью логотипа" @error="handleImageError" />
          </div>
        </div>

        <div class="form-group">
          <label for="project-documents">Папка с документами (ссылка на Google Drive и т.д.)</label>
          <input
            id="project-documents"
            v-model="form.documentsUrl"
            type="url"
            placeholder="https://drive.google.com/..."
            class="form-input"
          />
        </div>

        <div class="form-group">
          <label for="project-team">Команда *</label>
          <select
            id="project-team"
            v-model="form.team_id"
            class="form-select"
            required
          >
            <option :value="null">Выберите команду</option>
            <option v-for="team in teams" :key="team.id" :value="team.id">
              {{ team.name }}
            </option>
          </select>
        </div>

        <div class="form-group">
          <label for="project-folder">Папка</label>
          <select
            id="project-folder"
            v-model="form.folder_id"
            class="form-select"
          >
            <option :value="null">Без папки (корневой уровень)</option>
            <option v-for="folder in folders" :key="folder.id" :value="folder.id">
              {{ getFolderPath(folder) }}
            </option>
          </select>
        </div>

        <div v-if="error" class="error-message">
          {{ error }}
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary" @click="$emit('close')">
          Отмена
        </button>
        <button class="btn btn-primary" @click="handleSubmit" :disabled="!isValid || loading">
          {{ loading ? 'Сохранение...' : 'Сохранить' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import projectsApi from '../services/projects'
import foldersApi from '../services/folders'
import { useTeamsStore } from '../stores/teams'

const props = defineProps({
  project: {
    type: Object,
    default: null
  },
  folderId: {
    type: Number,
    default: null
  }
})

const emit = defineEmits(['close', 'saved'])

const teamsStore = useTeamsStore()
const teams = computed(() => teamsStore.teams)

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

const form = ref({
  name: props.project?.name || '',
  description: props.project?.description || '',
  startDate: timestampToDateInput(props.project?.start_date),
  endDate: timestampToDateInput(props.project?.end_date),
  websiteUrl: props.project?.website_url || '',
  logoUrl: props.project?.logo_url || '',
  documentsUrl: props.project?.documents_url || '',
  team_id: props.project?.team_id || null,
  folder_id: props.folderId || props.project?.folder_id || null
})

const folders = ref([])
const loading = ref(false)
const error = ref('')

const isValid = computed(() => {
  return form.value.name.trim() !== '' && form.value.team_id !== null
})

const getFolderPath = (folder) => {
  // Можно добавить логику для отображения пути к папке
  // Например: "Папка 1 / Подпапка 1"
  return folder.name
}

const handleSubmit = async () => {
  if (!isValid.value) {
    error.value = 'Заполните все обязательные поля'
    return
  }

  try {
    loading.value = true
    error.value = ''

    const data = {
      name: form.value.name,
      description: form.value.description,
      start_date: dateInputToTimestamp(form.value.startDate),
      end_date: dateInputToTimestamp(form.value.endDate),
      website_url: form.value.websiteUrl || null,
      logo_url: form.value.logoUrl || null,
      documents_url: form.value.documentsUrl || null,
      team_id: form.value.team_id,
      folder_id: form.value.folder_id || null
    }

    let response
    if (props.project) {
      response = await projectsApi.updateProject(props.project.id, data)
    } else {
      response = await projectsApi.createProject(data)
    }

    if (response.success) {
      emit('saved')
      emit('close')
    } else {
      error.value = response.message || 'Ошибка при сохранении проекта'
    }
  } catch (err) {
    console.error('Ошибка сохранения проекта:', err)
    error.value = 'Произошла ошибка при сохранении проекта'
  } finally {
    loading.value = false
  }
}

const handleImageError = (event) => {
  event.target.style.display = 'none'
  error.value = 'Не удалось загрузить изображение. Проверьте URL.'
  setTimeout(() => {
    error.value = ''
  }, 3000)
}

const loadFolders = async () => {
  try {
    const response = await foldersApi.getFolders()
    if (response.success) {
      folders.value = response.folders || []
    }
  } catch (error) {
    console.error('Ошибка загрузки папок:', error)
  }
}

onMounted(async () => {
  await teamsStore.loadTeams()
  await loadFolders()
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

.modal {
  background: white;
  border-radius: 12px;
  width: 100%;
  max-width: 500px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  display: flex;
  flex-direction: column;
  max-height: 90vh;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid #e0e0e0;
}

.modal-header h3 {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 600;
  color: #1a1a1a;
}

.close-btn {
  width: 32px;
  height: 32px;
  border-radius: 6px;
  background: transparent;
  border: none;
  color: #999;
  font-size: 1.5rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}

.close-btn:hover {
  background: #f5f5f7;
  color: #2d3748;
}

.modal-body {
  padding: 1.5rem;
  overflow-y: auto;
  flex: 1;
}

.form-group {
  margin-bottom: 1.25rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
  color: #2d3748;
  font-size: 0.9rem;
}

.form-input,
.form-textarea,
.form-select {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  font-size: 0.9rem;
  outline: none;
  transition: all 0.2s ease;
  background: #f9fafb;
}

.form-input:focus,
.form-textarea:focus,
.form-select:focus {
  border-color: #2d3748;
  background: white;
  box-shadow: 0 0 0 3px rgba(45, 55, 72, 0.1);
}

.form-textarea {
  resize: vertical;
  font-family: inherit;
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

.error-message {
  padding: 0.75rem;
  background: #fee;
  border: 1px solid #fcc;
  border-radius: 6px;
  color: #c33;
  font-size: 0.85rem;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  padding: 1.5rem;
  border-top: 1px solid #e0e0e0;
}

.btn {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 8px;
  font-size: 0.9rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-secondary {
  background: #f5f5f7;
  color: #2d3748;
}

.btn-secondary:hover:not(:disabled) {
  background: #e0e0e0;
}

.btn-primary {
  background: #2d3748;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: #1a202c;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(45, 55, 72, 0.3);
}
</style>
