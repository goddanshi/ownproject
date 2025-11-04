<template>
  <div class="modal-overlay" @click.self="closeModal">
    <div class="modal-container">
      <div class="modal-header">
        <h2>{{ folder ? 'Редактировать папку' : 'Создать папку' }}</h2>
        <button class="close-btn" @click="closeModal">×</button>
      </div>

      <div class="modal-body">
        <form @submit.prevent="handleSubmit">
          <div class="form-group">
            <label for="name">Название папки *</label>
            <input
              id="name"
              v-model="formData.name"
              type="text"
              placeholder="Введите название папки"
              required
              class="form-input"
            />
          </div>

          <div class="form-group">
            <label for="description">Описание</label>
            <textarea
              id="description"
              v-model="formData.description"
              placeholder="Введите описание папки"
              rows="4"
              class="form-textarea"
            ></textarea>
          </div>

          <div class="form-group">
            <label for="parent">Родительская папка</label>
            <select
              id="parent"
              v-model="formData.parent_id"
              class="form-select"
            >
              <option :value="null">Корневая папка</option>
              <option
                v-for="availableFolder in availableFolders"
                :key="availableFolder.id"
                :value="availableFolder.id"
              >
                {{ availableFolder.name }}
              </option>
            </select>
          </div>

          <div v-if="error" class="error-message">
            {{ error }}
          </div>

          <div class="modal-actions">
            <button type="button" class="btn-secondary" @click="closeModal">
              Отмена
            </button>
            <button type="submit" class="btn-primary" :disabled="loading">
              {{ loading ? 'Сохранение...' : 'Сохранить' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import foldersApi from '../services/folders'

const props = defineProps({
  folder: {
    type: Object,
    default: null
  },
  parentId: {
    type: Number,
    default: null
  }
})

const emit = defineEmits(['close', 'saved'])

const formData = ref({
  name: '',
  description: '',
  parent_id: props.parentId || null
})

const availableFolders = ref([])
const loading = ref(false)
const error = ref('')

const loadFolders = async () => {
  try {
    const response = await foldersApi.getFolders()
    if (response.success) {
      // Исключаем текущую папку из списка доступных родительских папок
      availableFolders.value = response.folders.filter(f =>
        props.folder ? f.id !== props.folder.id : true
      )
    }
  } catch (err) {
    console.error('Ошибка загрузки папок:', err)
  }
}

const handleSubmit = async () => {
  loading.value = true
  error.value = ''

  try {
    // Валидация на фронте
    if (!formData.value.name || !formData.value.name.trim()) {
      error.value = 'Название папки обязательно'
      loading.value = false
      return
    }

    // Подготовка данных: убедимся что ID числа, а не строки
    const submitData = {
      name: formData.value.name.trim(),
      description: formData.value.description?.trim() || '',
      parent_id: formData.value.parent_id ? parseInt(formData.value.parent_id) : null
    }

    console.log('Отправка данных:', submitData)

    let response

    if (props.folder) {
      // Редактирование
      response = await foldersApi.updateFolder(props.folder.id, submitData)
    } else {
      // Создание
      response = await foldersApi.createFolder(submitData)
    }

    console.log('Ответ от сервера:', response)

    if (response.success) {
      emit('saved', response.folder)
      closeModal()
    } else {
      // Показываем ошибки валидации если есть
      if (response.errors) {
        const errorMessages = Object.entries(response.errors)
          .map(([field, messages]) => `${field}: ${messages.join(', ')}`)
          .join('; ')
        error.value = errorMessages
      } else {
        error.value = response.message || 'Ошибка сохранения папки'
      }
    }
  } catch (err) {
    console.error('Ошибка сохранения папки:', err)
    console.error('Ошибка ответа:', err.response?.data)

    // Обработка ошибок от бэкенда
    if (err.response?.data?.errors) {
      const errorMessages = Object.entries(err.response.data.errors)
        .map(([field, messages]) => `${field}: ${Array.isArray(messages) ? messages.join(', ') : messages}`)
        .join('; ')
      error.value = errorMessages
    } else if (err.response?.data?.message) {
      error.value = err.response.data.message
    } else if (err.message) {
      error.value = `Ошибка: ${err.message}`
    } else {
      error.value = 'Произошла ошибка при сохранении папки'
    }
  } finally {
    loading.value = false
  }
}

const closeModal = () => {
  emit('close')
}

onMounted(() => {
  loadFolders()

  if (props.folder) {
    formData.value = {
      name: props.folder.name || '',
      description: props.folder.description || '',
      parent_id: props.folder.parent_id || null
    }
  } else if (props.parentId) {
    formData.value.parent_id = props.parentId
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
  z-index: 1001;
  padding: 1rem;
}

.modal-container {
  background: white;
  border-radius: 12px;
  width: 100%;
  max-width: 500px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid #e5e7eb;
}

.modal-header h2 {
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
  color: #6b7280;
  font-size: 1.5rem;
  line-height: 1;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.close-btn:hover {
  background: #f3f4f6;
  color: #1a1a1a;
}

.modal-body {
  padding: 1.5rem;
}

.form-group {
  margin-bottom: 1.25rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
  color: #374151;
  font-size: 0.875rem;
}

.form-input,
.form-textarea,
.form-select {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 0.875rem;
  transition: all 0.2s;
  background: white;
}

.form-input:focus,
.form-textarea:focus,
.form-select:focus {
  outline: none;
  border-color: #2d3748;
  box-shadow: 0 0 0 3px rgba(45, 55, 72, 0.1);
}

.form-textarea {
  resize: vertical;
  font-family: inherit;
}

.error-message {
  padding: 0.75rem;
  background: #fee2e2;
  border: 1px solid #fecaca;
  border-radius: 8px;
  color: #991b1b;
  font-size: 0.875rem;
  margin-bottom: 1rem;
}

.modal-actions {
  display: flex;
  gap: 0.75rem;
  justify-content: flex-end;
  margin-top: 1.5rem;
}

.btn-secondary,
.btn-primary {
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 500;
  font-size: 0.875rem;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
}

.btn-secondary {
  background: #f3f4f6;
  color: #374151;
}

.btn-secondary:hover {
  background: #e5e7eb;
}

.btn-primary {
  background: #2d3748;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: #1a202c;
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>
