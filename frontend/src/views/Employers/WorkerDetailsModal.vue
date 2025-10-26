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
            {{ worker.username[0].toUpperCase() }}
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
            <div class="info-item">
              <span class="label">Дата регистрации</span>
              <span class="value">{{ formatDate(worker.created_at) }}</span>
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
import { ref, onMounted } from 'vue'
import workersApi from '@/services/workers'

const props = defineProps({
  workerId: {
    type: Number,
    required: true
  }
})

const emit = defineEmits(['close', 'updated'])

const worker = ref(null)
const loading = ref(true)

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
