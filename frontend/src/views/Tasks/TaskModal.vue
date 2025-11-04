<template>
  <div class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-content">
      <div class="modal-header">
        <h2>{{ task ? 'Редактировать задачу' : 'Создать задачу' }}</h2>
        <button class="close-btn" @click="$emit('close')">&times;</button>
      </div>

      <form @submit.prevent="handleSubmit">
        <div class="form-group">
          <label>Название задачи *</label>
          <input
            v-model="formData.title"
            type="text"
            required
            placeholder="Введите название задачи"
          />
        </div>

        <div class="form-group">
          <label>Описание</label>
          <textarea
            v-model="formData.description"
            rows="4"
            placeholder="Описание задачи..."
          ></textarea>
        </div>

        <div class="form-group">
          <label>Проект *</label>
          <select v-model="formData.projectId" required>
            <option value="">Выберите проект</option>
            <option
              v-for="project in (projects || projectsList)"
              :key="project.id"
              :value="project.id"
            >
              {{ project.name }}
            </option>
          </select>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Статус</label>
            <select v-model="formData.status">
              <option value="1">К выполнению</option>
              <option value="2">В работе</option>
              <option value="3">На проверке</option>
              <option value="4">Выполнено</option>
            </select>
          </div>

          <div class="form-group">
            <label>Приоритет</label>
            <select v-model="formData.priority">
              <option value="1">Низкий</option>
              <option value="2">Средний</option>
              <option value="3">Высокий</option>
              <option value="4">Срочный</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label>Дедлайн</label>
          <input
            v-model="formData.deadline"
            type="date"
          />
        </div>

        <div class="form-group" v-if="!task && projectParticipants.length > 0">
          <label>Участники</label>
          <div class="participants-list">
            <label
              v-for="participant in projectParticipants"
              :key="participant.id"
              class="participant-checkbox"
            >
              <input
                type="checkbox"
                :value="participant.id"
                v-model="formData.assigneeIds"
              />
              <span>{{ participant.name }} {{ participant.surname }} ({{ participant.username }})</span>
            </label>
          </div>
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
import { ref, onMounted, watch } from 'vue'
import tasksApi from '../../services/tasks'
import projectsApi from '../../services/projects'

const props = defineProps({
  task: Object,
  projects: Array,
  projectId: [Number, String]
})

const emit = defineEmits(['close', 'saved'])

const formData = ref({
  title: '',
  description: '',
  projectId: '',
  status: '1',
  priority: '2',
  deadline: '',
  assigneeIds: []
})

const loading = ref(false)
const error = ref('')
const projectParticipants = ref([])
const projectsList = ref([])

const handleSubmit = async () => {
  try {
    loading.value = true
    error.value = ''

    const deadline = formData.value.deadline
      ? Math.floor(new Date(formData.value.deadline).getTime() / 1000)
      : null

    if (props.task) {
      await tasksApi.updateTask(props.task.id, {
        title: formData.value.title,
        description: formData.value.description,
        status: parseInt(formData.value.status),
        priority: parseInt(formData.value.priority),
        deadline
      })
    } else {
      await tasksApi.createTask({
        title: formData.value.title,
        description: formData.value.description,
        projectId: parseInt(formData.value.projectId),
        status: parseInt(formData.value.status),
        priority: parseInt(formData.value.priority),
        deadline,
        assigneeIds: formData.value.assigneeIds
      })
    }

    emit('saved')
  } catch (err) {
    error.value = err.response?.data?.message || 'Ошибка при сохранении задачи'
  } finally {
    loading.value = false
  }
}

const loadProjectParticipants = async (projectId) => {
  if (!projectId) {
    projectParticipants.value = []
    return
  }

  try {
    const response = await projectsApi.getProject(projectId)
    if (response.success && response.project.participants) {
      projectParticipants.value = response.project.participants
    }
  } catch (err) {
    console.error('Ошибка загрузки участников проекта:', err)
  }
}

watch(() => formData.value.projectId, (newProjectId) => {
  if (newProjectId && !props.task) {
    loadProjectParticipants(newProjectId)
  }
})

onMounted(async () => {
  // Загружаем проекты если они не переданы
  if (!props.projects) {
    try {
      const response = await projectsApi.getProjects()
      if (response.success) {
        projectsList.value = response.projects
      }
    } catch (err) {
      console.error('Ошибка загрузки проектов:', err)
    }
  }

  if (props.task) {
    formData.value = {
      title: props.task.title,
      description: props.task.description || '',
      projectId: props.task.project?.id || props.task.project_id,
      status: String(props.task.status),
      priority: String(props.task.priority),
      deadline: props.task.deadline
        ? new Date(props.task.deadline * 1000).toISOString().split('T')[0]
        : '',
      assigneeIds: []
    }
    loadProjectParticipants(formData.value.projectId)
  } else if (props.projectId) {
    // Если передан projectId, устанавливаем его
    formData.value.projectId = props.projectId
    loadProjectParticipants(props.projectId)
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

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
  color: #333;
}

input[type="text"],
input[type="date"],
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

.participants-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  max-height: 200px;
  overflow-y: auto;
  padding: 0.5rem;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
}

.participant-checkbox {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 6px;
  transition: background 0.2s;
}

.participant-checkbox:hover {
  background: #f9f9f9;
}

.participant-checkbox input[type="checkbox"] {
  width: auto;
  cursor: pointer;
}

.participant-checkbox span {
  font-weight: normal;
}
</style>
