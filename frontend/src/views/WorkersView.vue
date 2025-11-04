<template>
  <DashboardLayout>
    <template #header-left>
      <h1>Сотрудники</h1>
    </template>

    <div class="workers-page">
      <!-- Хедер с фильтрами и кнопкой создания -->
      <div class="page-header">
        <div class="header-info">
          <h2>Управление сотрудниками</h2>
          <p class="subtitle">Всего сотрудников: {{ workers.length }}</p>
        </div>

        <div class="header-actions">
          <!-- Фильтр по роли -->
          <select v-model="filterRole" class="filter-select">
            <option value="">Все роли</option>
            <option value="1">Администраторы</option>
            <option value="2">Тимлиды</option>
            <option value="3">Сотрудники</option>
          </select>

          <button
            v-if="authStore.can('manage_workers')"
            class="btn-primary"
            @click="openCreateModal"
          >
            + Добавить работника
          </button>
        </div>
      </div>

      <!-- Загрузка -->
      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Загрузка сотрудников...</p>
      </div>

      <!-- Таблица работников -->
      <div v-else-if="filteredWorkers.length > 0" class="workers-table-container">
        <table class="workers-table">
          <thead>
          <tr>
            <th>Имя пользователя</th>
            <th>ФИО</th>
            <th>Email</th>
            <th>Роль</th>
            <th>Дата регистрации</th>
            <th v-if="authStore.can('manage_workers')" class="actions-column">Действия</th>
          </tr>
          </thead>
          <tbody>
          <tr
            v-for="worker in filteredWorkers"
            :key="worker.id"
            @click="openWorkerDetails(worker.id)"
            class="worker-row"
          >
            <td>
              <div class="user-cell">
                <div class="avatar">
                  <img :src="worker.avatar" alt="Аватар" v-if="worker.avatar">
                  <div v-else>{{ worker.username[0].toUpperCase() }}</div>
                </div>
                <span class="username">{{ worker.username }}</span>
              </div>
            </td>
            <td>
                <span class="full-name">
                  {{ getFullName(worker) }}
                </span>
            </td>
            <td>
              <span class="email">{{ worker.email }}</span>
            </td>
            <td>
                <span :class="['role-badge', getRoleClass(worker.role)]">
                  {{ getRoleLabel(worker.role) }}
                </span>
            </td>
            <td>
              <span class="date">{{ formatDate(worker.created_at) }}</span>
            </td>
            <td v-if="authStore.can('manage_workers')" class="actions-cell" @click.stop>
              <button
                class="btn-icon"
                @click="openEditModal(worker)"
                title="Редактировать"
              >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                </svg>
              </button>
              <button
                v-if="authStore.can('delete_workers') && worker.id !== authStore.user?.id"
                class="btn-icon danger"
                @click="confirmDelete(worker)"
                title="Удалить"
              >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                </svg>
              </button>
            </td>
          </tr>
          </tbody>
        </table>
      </div>

      <!-- Пустое состояние -->
      <div v-else class="empty-state">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
        </svg>
        <h3>{{ filterRole ? 'Сотрудники не найдены' : 'Сотрудников пока нет' }}</h3>
        <p>{{ filterRole ? 'Попробуйте изменить фильтр' : 'Добавьте первого сотрудника для начала работы' }}</p>
        <button
          v-if="authStore.can('manage_workers')"
          class="btn-primary"
          @click="openCreateModal"
        >
          Добавить работника
        </button>
      </div>

      <!-- Модалка создания/редактирования -->
      <WorkerModal
        v-if="showModal"
        :worker="selectedWorker"
        @close="closeModal"
        @saved="handleWorkerSaved"
      />

      <!-- Модалка деталей работника -->
      <WorkerDetailsModal
        v-if="showDetailsModal"
        :workerId="selectedWorkerId"
        @close="closeDetailsModal"
        @updated="loadWorkers"
      />

      <!-- Модалка подтверждения удаления -->
      <ConfirmModal
        v-if="showDeleteModal"
        title="Удалить работника?"
        :message="deleteMessage"
        @confirm="handleDelete"
        @cancel="closeDeleteModal"
      />
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import DashboardLayout from '../layouts/DashboardLayout.vue'
import WorkerModal from '@/views/Employers/WorkerModal.vue'
import WorkerDetailsModal from '@/views/Employers/WorkerDetailsModal.vue'
import ConfirmModal from '../components/ConfirmModal.vue'
import workersApi from '../services/workers'
import { useAuthStore } from '../stores/auth'

const authStore = useAuthStore()

const workers = ref([])
const loading = ref(true)
const showModal = ref(false)
const showDetailsModal = ref(false)
const showDeleteModal = ref(false)
const selectedWorker = ref(null)
const selectedWorkerId = ref(null)
const workerToDelete = ref(null)
const filterRole = ref('')

// Фильтрация работников по роли
const filteredWorkers = computed(() => {
  if (!filterRole.value) return workers.value
  return workers.value.filter(w => w.role == filterRole.value)
})

// Сообщение об удалении
const deleteMessage = computed(() =>
  `Вы уверены, что хотите удалить работника "${workerToDelete.value?.username}"? Это действие нельзя отменить.`
)

// Получить полное имя
const getFullName = (worker) => {
  const parts = []
  if (worker.name) parts.push(worker.name)
  if (worker.surname) parts.push(worker.surname)
  return parts.length > 0 ? parts.join(' ') : '—'
}

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
  if (!timestamp) return '—'
  const date = new Date(timestamp * 1000)
  return date.toLocaleDateString('ru-RU', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  })
}

// Загрузка работников
const loadWorkers = async () => {
  loading.value = true
  try {
    const result = await workersApi.getWorkers()
    if (result.success) {
      workers.value = result.workers
    }
  } catch (error) {
    console.error('Failed to load workers:', error)
  } finally {
    loading.value = false
  }
}

// Открыть модалку создания
const openCreateModal = () => {
  selectedWorker.value = null
  showModal.value = true
}

// Открыть модалку редактирования
const openEditModal = (worker) => {
  selectedWorker.value = worker
  showModal.value = true
}

// Закрыть модалку
const closeModal = () => {
  showModal.value = false
  selectedWorker.value = null
}

// Открыть детали работника
const openWorkerDetails = (workerId) => {
  selectedWorkerId.value = workerId
  showDetailsModal.value = true
}

// Закрыть детали
const closeDetailsModal = () => {
  showDetailsModal.value = false
  selectedWorkerId.value = null
}

// После сохранения работника
const handleWorkerSaved = () => {
  closeModal()
  loadWorkers()
}

// Подтверждение удаления
const confirmDelete = (worker) => {
  workerToDelete.value = worker
  showDeleteModal.value = true
}

// Закрыть модалку удаления
const closeDeleteModal = () => {
  showDeleteModal.value = false
  workerToDelete.value = null
}

// Удалить работника
const handleDelete = async () => {
  try {
    const result = await workersApi.deleteWorker(workerToDelete.value.id)
    if (result.success) {
      await loadWorkers()
    }
  } catch (error) {
    console.error('Failed to delete worker:', error)
  } finally {
    closeDeleteModal()
  }
}

onMounted(() => {
  loadWorkers()
})
</script>

<style scoped>
.workers-page {
  max-width: 1400px;
  margin: 0 auto;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid #e0e0e0;
}

.header-info h2 {
  margin: 0 0 0.5rem 0;
  font-size: 1.5rem;
  font-weight: 600;
  color: #1a1a1a;
}

.subtitle {
  margin: 0;
  font-size: 0.9rem;
  color: #666;
}

.header-actions {
  display: flex;
  gap: 1rem;
  align-items: center;
}

.filter-select {
  padding: 0.75rem 1rem;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  font-size: 0.9rem;
  color: #333;
  background: white;
  cursor: pointer;
  transition: all 0.2s ease;
}

.filter-select:focus {
  outline: none;
  border-color: #2d3748;
  box-shadow: 0 0 0 3px rgba(45, 55, 72, 0.1);
}

.btn-primary {
  padding: 0.75rem 1.5rem;
  background: #2d3748;
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 0.95rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  white-space: nowrap;
}

.btn-primary:hover {
  background: #1a202c;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(45, 55, 72, 0.3);
}

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 5rem 2rem;
  gap: 1.5rem;
}

.spinner {
  width: 50px;
  height: 50px;
  border: 4px solid #e0e0e0;
  border-top-color: #2d3748;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.workers-table-container {
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 12px;
  overflow: hidden;
}

.workers-table {
  width: 100%;
  border-collapse: collapse;
}

.workers-table thead {
  background: #fafafa;
  border-bottom: 2px solid #e0e0e0;
}

.workers-table th {
  padding: 1rem 1.5rem;
  text-align: left;
  font-size: 0.85rem;
  font-weight: 600;
  color: #666;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.workers-table th.actions-column {
  text-align: center;
}

.worker-row {
  border-bottom: 1px solid #f0f0f0;
  cursor: pointer;
  transition: background 0.2s ease;
}

.worker-row:hover {
  background: #fafafa;
}

.worker-row:last-child {
  border-bottom: none;
}

.workers-table td {
  padding: 1.25rem 1.5rem;
  font-size: 0.9rem;
  color: #333;
}

.user-cell {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
  font-weight: 600;
  flex-shrink: 0;
  overflow: hidden;
}

.avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.username {
  font-weight: 600;
  color: #1a1a1a;
}

.full-name {
  color: #666;
}

.email {
  color: #666;
}

.date {
  color: #999;
  font-size: 0.85rem;
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

.actions-cell {
  text-align: center;
}

.actions-cell {
  display: flex;
  gap: 0.5rem;
  justify-content: center;
}

.btn-icon {
  width: 36px;
  height: 36px;
  padding: 0;
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-icon:hover {
  background: #2d3748;
  border-color: #2d3748;
}

.btn-icon:hover svg {
  color: white;
}

.btn-icon.danger:hover {
  background: #dc2626;
  border-color: #dc2626;
}

.btn-icon svg {
  width: 18px;
  height: 18px;
  color: #666;
  transition: color 0.2s ease;
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 5rem 2rem;
  text-align: center;
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 12px;
}

.empty-state svg {
  width: 80px;
  height: 80px;
  color: #cbd5e0;
  margin-bottom: 1.5rem;
}

.empty-state h3 {
  margin: 0 0 0.5rem 0;
  font-size: 1.5rem;
  color: #1a1a1a;
}

.empty-state p {
  margin: 0 0 2rem 0;
  color: #666;
  font-size: 1rem;
}

@media (max-width: 1024px) {
  .page-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }

  .header-actions {
    width: 100%;
  }

  .filter-select {
    flex: 1;
  }
}

@media (max-width: 768px) {
  .workers-table-container {
    overflow-x: auto;
  }

  .workers-table {
    min-width: 800px;
  }
}
</style>
