<template>
  <div class="modal-overlay" @click.self="$emit('close')">
    <div class="modal">
      <div class="modal-header">
        <h2>{{ worker?.username || 'Детали работника' }}</h2>
        <button class="close-btn" @click="$emit('close')">✕</button>
      </div>

      <div v-if="loading" class="modal-body loading">
        <div class="spinner"></div>
        <p>Загрузка...</p>
      </div>

      <div v-else-if="worker" class="modal-body">
        <!-- Аватар и основная инфо -->
        <div class="worker-header">
          <div class="avatar-large">
              <img :src="worker.avatar" alt="Аватар" v-if="worker.avatar">
                  <div v-else>{{ worker.username[0].toUpperCase() }}</div>
          </div>
          <div class="worker-main-info">
            <h3>{{ worker.username }}</h3>
            <p class="email">{{ worker.email }}</p>
            <span :class="['role-badge', getRoleClass(worker.role)]">
              {{ getRoleLabel(worker.role) }}
            </span>
          </div>
        </div>

        <!-- Детальная информация -->
        <div class="info-section">
          <h4>Личные данные</h4>
          <div class="info-grid">
            <div class="info-item">
              <span class="label">Имя</span>
              <span class="value">{{ worker.name || '—' }}</span>
            </div>
            <div class="info-item">
              <span class="label">Фамилия</span>
              <span class="value">{{ worker.surname || '—' }}</span>
            </div>
            <div class="info-item date-edit-item">
              <span class="label">Дата регистрации</span>
              <div class="date-edit-container">
                <input
                  v-if="isEditingDate"
                  type="datetime-local"
                  v-model="editedDate"
                  class="date-input"
                  @keyup.enter="saveDate"
                  @keyup.escape="cancelDateEdit"
                />
                <span v-else class="value">{{ formatDate(worker.created_at) }}</span>
                <div class="date-actions" v-if="canEdit">
                  <button
                    v-if="!isEditingDate"
                    class="btn-icon-small"
                    @click="startDateEdit"
                    title="Редактировать дату"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                    </svg>
                  </button>
                  <template v-else>
                    <button
                      class="btn-icon-small success"
                      @click="saveDate"
                      title="Сохранить"
                      :disabled="saving"
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                      </svg>
                    </button>
                    <button
                      class="btn-icon-small danger"
                      @click="cancelDateEdit"
                      title="Отмена"
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                      </svg>
                    </button>
                  </template>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn-secondary" @click="$emit('close')">
          Закрыть
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, inject } from 'vue'
import workersApi from '@/services/workers'
import { useAuthStore } from '@/stores/auth'

const props = defineProps({
  workerId: {
    type: Number,
    required: true
  }
})

const emit = defineEmits(['close', 'updated'])

const authStore = useAuthStore()
const $confirm = inject('$confirm')

const worker = ref(null)
const loading = ref(true)
const isEditingDate = ref(false)
const editedDate = ref('')
const saving = ref(false)

// Проверка прав на редактирование (только админ)
const canEdit = computed(() => {
  return authStore.user?.role === 1
})

// Получить метку роли
const getRoleLabel = (role) => {
  const roles = {
    1: 'Администратор',
    2: 'Тимлид',
    3: 'Сотрудник'
  }
  return roles[role] || 'Неизвестно'
}

// Получить класс для роли
const getRoleClass = (role) => {
  const classes = {
    1: 'admin',
    2: 'teamlead',
    3: 'employee'
  }
  return classes[role] || ''
}

// Форматирование даты
const formatDate = (timestamp) => {
  if (!timestamp) return 'Не указано'
  const date = new Date(timestamp * 1000)
  return date.toLocaleDateString('ru-RU', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Преобразование timestamp в формат datetime-local
const timestampToDatetimeLocal = (timestamp) => {
  const date = new Date(timestamp * 1000)
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  const hours = String(date.getHours()).padStart(2, '0')
  const minutes = String(date.getMinutes()).padStart(2, '0')
  return `${year}-${month}-${day}T${hours}:${minutes}`
}

// Загрузка работника
const loadWorker = async () => {
  loading.value = true
  try {
    const result = await workersApi.getWorker(props.workerId)
    if (result.success) {
      worker.value = result.worker
    }
  } catch (error) {
    console.error('Failed to load worker:', error)
  } finally {
    loading.value = false
  }
}

// Начать редактирование даты
const startDateEdit = () => {
  editedDate.value = timestampToDatetimeLocal(worker.value.created_at)
  isEditingDate.value = true
}

// Отменить редактирование даты
const cancelDateEdit = () => {
  isEditingDate.value = false
  editedDate.value = ''
}

// Сохранить дату
const saveDate = async () => {
  if (!editedDate.value) {
    return
  }

  try {
    const confirmed = await $confirm({
      title: 'Изменить дату регистрации?',
      message: 'Вы уверены, что хотите изменить дату регистрации этого пользователя?',
      type: 'warning',
      confirmText: 'Изменить',
      cancelText: 'Отмена'
    })

    if (!confirmed) {
      return
    }

    saving.value = true
    const result = await workersApi.updateCreatedAt(props.workerId, editedDate.value)

    if (result.success) {
      worker.value.created_at = result.created_at
      isEditingDate.value = false
      editedDate.value = ''
      emit('updated')
    } else {
      alert(result.message || 'Ошибка при обновлении даты регистрации')
    }
  } catch (error) {
    if (error !== false) {
      console.error('Failed to update created_at:', error)
      alert('Произошла ошибка при обновлении даты регистрации')
    }
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  loadWorker()
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

.modal-body.loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 1rem;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #e0e0e0;
  border-top-color: #2d3748;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.worker-header {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  padding-bottom: 2rem;
  border-bottom: 1px solid #e0e0e0;
  margin-bottom: 2rem;
}

.avatar-large {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
  font-weight: 600;
  flex-shrink: 0;
  overflow: hidden;
}

.avatar-large img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.worker-main-info {
  flex: 1;
}

.worker-main-info h3 {
  margin: 0 0 0.5rem 0;
  font-size: 1.5rem;
  font-weight: 600;
  color: #1a1a1a;
}

.worker-main-info .email {
  display: block;
  margin: 0 0 0.75rem 0;
  font-size: 0.95rem;
  color: #666;
}

.role-badge {
  display: inline-block;
  padding: 0.375rem 0.875rem;
  border-radius: 12px;
  font-size: 0.8rem;
  font-weight: 600;
}

.role-badge.admin {
  background: #fef2f2;
  color: #991b1b;
}

.role-badge.teamlead {
  background: #eff6ff;
  color: #1e40af;
}

.role-badge.employee {
  background: #f0fdf4;
  color: #166534;
}

.info-section {
  margin-bottom: 2rem;
}

.info-section:last-child {
  margin-bottom: 0;
}

.info-section h4 {
  margin: 0 0 1rem 0;
  font-size: 1rem;
  font-weight: 600;
  color: #2d3748;
}

.info-grid {
  display: grid;
  gap: 1rem;
}

.info-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.875rem 1rem;
  background: #fafafa;
  border-radius: 6px;
  border: 1px solid #e0e0e0;
}

.info-item .label {
  font-size: 0.85rem;
  color: #666;
  font-weight: 500;
}

.info-item .value {
  font-size: 0.95rem;
  color: #1a1a1a;
  font-weight: 500;
}

.date-edit-item {
  flex-direction: column;
  align-items: flex-start;
  gap: 0.5rem;
}

.date-edit-container {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  width: 100%;
}

.date-input {
  flex: 1;
  padding: 0.5rem;
  border: 1px solid #e0e0e0;
  border-radius: 6px;
  font-size: 0.9rem;
  color: #1a1a1a;
  background: white;
  transition: border-color 0.2s;
}

.date-input:focus {
  outline: none;
  border-color: #2d3748;
  box-shadow: 0 0 0 3px rgba(45, 55, 72, 0.1);
}

.date-actions {
  display: flex;
  gap: 0.5rem;
}

.btn-icon-small {
  width: 28px;
  height: 28px;
  padding: 0;
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-icon-small:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-icon-small:not(:disabled):hover {
  background: #2d3748;
  border-color: #2d3748;
}

.btn-icon-small:not(:disabled):hover svg {
  color: white;
}

.btn-icon-small.success:not(:disabled):hover {
  background: #10b981;
  border-color: #10b981;
}

.btn-icon-small.danger:not(:disabled):hover {
  background: #dc2626;
  border-color: #dc2626;
}

.btn-icon-small svg {
  width: 14px;
  height: 14px;
  color: #666;
  transition: color 0.2s ease;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding: 1.5rem 2rem;
  border-top: 1px solid #e0e0e0;
  background: #fafafa;
}

.btn-secondary {
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-size: 0.95rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  background: white;
  border: 2px solid #e0e0e0;
  color: #666;
}

.btn-secondary:hover {
  border-color: #2d3748;
  background: #fafafa;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
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

  .worker-header {
    flex-direction: column;
    text-align: center;
  }

  .info-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }
}
</style>
