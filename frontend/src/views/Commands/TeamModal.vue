<template>
  <div class="modal-overlay" @click.self="$emit('close')">
    <div class="modal">
      <div class="modal-header">
        <h2>{{ isEditMode ? 'Редактировать команду' : 'Создать команду' }}</h2>
        <button class="close-btn" @click="$emit('close')">✕</button>
      </div>

      <form @submit.prevent="handleSubmit">
        <div class="modal-body">
          <!-- Название команды -->
          <div class="form-group">
            <label for="name">Название команды *</label>
            <input
              id="name"
              v-model="form.name"
              type="text"
              placeholder="Введите название команды"
              required
            />
          </div>

          <!-- Описание -->
          <div class="form-group">
            <label for="description">Описание</label>
            <textarea
              id="description"
              v-model="form.description"
              rows="4"
              placeholder="Описание команды"
            ></textarea>
          </div>

          <!-- Тимлид (только для админа) -->
          <div v-if="authStore.isAdmin" class="form-group">
            <label for="teamlead">Тимлид *</label>
            <select
              id="teamlead"
              v-model="form.teamlead_id"
              required
            >
              <option value="">Выберите тимлида</option>
              <option
                v-for="teamlead in teamleads"
                :key="teamlead.id"
                :value="teamlead.id"
              >
                {{ teamlead.username }} ({{ teamlead.email }})
              </option>
            </select>
          </div>

          <!-- Участники (только при создании) -->
          <div v-if="!isEditMode" class="form-group">
            <label>Участники команды</label>
            <div class="members-list">
              <label
                v-for="employee in employees"
                :key="employee.id"
                class="member-checkbox"
              >
                <input
                  type="checkbox"
                  :value="employee.id"
                  v-model="form.member_ids"
                />
                <span class="username">{{ employee.username }}</span>
                <span class="email">({{ employee.email }})</span>
              </label>
            </div>
          </div>

          <!-- Сообщение об ошибке -->
          <div v-if="errorMessage" class="error-message">
            {{ errorMessage }}
          </div>
        </div>

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
import { ref, onMounted, computed } from 'vue'
import teamsApi from '@/services/teams'
import { useAuthStore } from '@/stores/auth'

const props = defineProps({
  team: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['close', 'saved'])

const authStore = useAuthStore()

const isEditMode = computed(() => !!props.team)

const form = ref({
  name: '',
  description: '',
  teamlead_id: '',
  member_ids: []
})

const teamleads = ref([])
const employees = ref([])
const loading = ref(false)
const errorMessage = ref('')

// Загрузка данных
const loadData = async () => {
  try {
    // Загружаем тимлидов (только для админа)
    if (authStore.isAdmin) {
      const teamleadsResult = await teamsApi.getTeamleads()
      if (teamleadsResult.success) {
        teamleads.value = teamleadsResult.teamleads
      }
    }

    // Загружаем сотрудников (только при создании)
    if (!isEditMode.value) {
      const employeesResult = await teamsApi.getEmployees()
      if (employeesResult.success) {
        employees.value = employeesResult.employees
      }
    }

    // Заполняем форму если редактирование
    if (isEditMode.value && props.team) {
      form.value.name = props.team.name
      form.value.description = props.team.description || ''
      form.value.teamlead_id = props.team.teamlead?.id || ''
    }
  } catch (error) {
    console.error('Failed to load data:', error)
    errorMessage.value = 'Ошибка загрузки данных'
  }
}

// Отправка формы
const handleSubmit = async () => {
  loading.value = true
  errorMessage.value = ''

  try {
    let result

    if (isEditMode.value) {
      // Обновление команды
      result = await teamsApi.updateTeam(
        props.team.id,
        form.value.name,
        form.value.description,
        form.value.teamlead_id || null
      )
    } else {
      // Создание команды
      result = await teamsApi.createTeam(
        form.value.name,
        form.value.description,
        form.value.teamlead_id,
        form.value.member_ids
      )
    }

    if (result.success) {
      emit('saved')
    } else {
      errorMessage.value = result.message || 'Ошибка сохранения'
    }
  } catch (error) {
    console.error('Failed to save team:', error)
    errorMessage.value = 'Ошибка подключения к серверу'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadData()
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
  justify-content: center;
  align-items: center;
  z-index: 1000;
  animation: fadeIn 0.2s ease;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.modal {
  background: white;
  border-radius: 12px;
  width: 90%;
  max-width: 600px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  animation: slideUp 0.3s ease;
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem 2rem;
  border-bottom: 1px solid #e0e0e0;
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
  padding: 0;
  background: none;
  border: none;
  font-size: 1.5rem;
  color: #666;
  cursor: pointer;
  border-radius: 4px;
  transition: all 0.2s ease;
}

.close-btn:hover {
  background: #f5f5f7;
  color: #1a1a1a;
}

.modal-body {
  flex: 1;
  overflow-y: auto;
  padding: 2rem;
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-group label {

  margin-bottom: 0.5rem;
  font-size: 0.9rem;
  font-weight: 500;
  color: #333;
}

.form-group input,
.form-group textarea,
.form-group select {
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

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
  outline: none;
  border-color: #2d3748;
  background: white;
  box-shadow: 0 0 0 3px rgba(45, 55, 72, 0.1);
}

.form-group textarea {
  resize: vertical;
  font-family: inherit;
}

.members-list {
  max-height: 200px;
  overflow-y: auto;
  border: 1px solid #e0e0e0;
  border-radius: 6px;
  padding: 0.5rem;
  background: #fafafa;
}

.member-checkbox {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem;
  border-radius: 4px;
  cursor: pointer;
  transition: background 0.2s ease;
}

.member-checkbox:hover {
  background: white;
}

.member-checkbox input[type="checkbox"] {
  width: 18px !important; /* ← Фиксированная ширина */
  height: 18px;
  min-width: 18px; /* ← Минимальная ширина */
  padding: 0 !important; /* ← Убираем padding */
  margin: 0 !important; /* ← Убираем margin */
  cursor: pointer;
  accent-color: #2d3748;
  flex-shrink: 0; /* ← Не сжимается */
}

.member-checkbox span {
  font-size: 0.9rem;
  color: #1a1a1a;
  white-space: nowrap; /* ← Текст не переносится */
}

.member-checkbox .email {
  margin-left: auto;
  color: #666;
  font-size: 0.85rem;
  white-space: nowrap; /* ← Почта не переносится */
}

.error-message {
  padding: 0.875rem 1rem;
  background: #fef2f2;
  color: #991b1b;
  border: 1px solid #fecaca;
  border-radius: 6px;
  font-size: 0.9rem;
  margin-top: 1rem;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding: 1.5rem 2rem;
  border-top: 1px solid #e0e0e0;
  background: #fafafa;
}

.btn-secondary,
.btn-primary {
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-size: 0.95rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-secondary {
  background: white;
  border: 2px solid #e0e0e0;
  color: #666;
}

.btn-secondary:hover {
  border-color: #2d3748;
  background: #fafafa;
}

.btn-primary {
  background: #2d3748;
  border: 2px solid #2d3748;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: #1a202c;
  border-color: #1a202c;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(45, 55, 72, 0.3);
}

.btn-primary:disabled {
  background: #cbd5e0;
  border-color: #cbd5e0;
  cursor: not-allowed;
}

@media (max-width: 768px) {
  .modal {
    width: 95%;
    max-height: 95vh;
  }

  .modal-header,
  .modal-body,
  .modal-footer {
    padding: 1.25rem;
  }
}
</style>
