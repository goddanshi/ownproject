<template>
  <div class="modal-overlay" @click.self="$emit('close')">
    <div class="modal">
      <div class="modal-header">
        <h2>{{ team?.name || 'Детали команды' }}</h2>
        <button class="close-btn" @click="$emit('close')">✕</button>
      </div>

      <div v-if="loading" class="modal-body loading">
        <div class="spinner"></div>
        <p>Загрузка...</p>
      </div>

      <div v-else-if="team" class="modal-body">
        <!-- Информация о команде -->
        <div class="section">
          <h3>Информация</h3>
          <div class="info-grid">
            <div class="info-item">
              <label>Название</label>
              <div class="value">{{ team.name }}</div>
            </div>
            <div class="info-item">
              <label>Описание</label>
              <div class="value">{{ team.description || 'Не указано' }}</div>
            </div>
            <div class="info-item">
              <label>Создана</label>
              <div class="value">{{ formatDate(team.created_at) }}</div>
            </div>
          </div>
        </div>

        <!-- Тимлид -->
        <div class="section">
          <h3>Тимлид</h3>
          <div class="member-card teamlead">
            <div class="avatar">
              {{ team.teamlead.username[0].toUpperCase() }}
            </div>
            <div class="member-info">
              <div class="name">{{ team.teamlead.username }}</div>
              <div class="email">{{ team.teamlead.email }}</div>
              <div v-if="team.teamlead.name || team.teamlead.surname" class="full-name">
                {{ team.teamlead.name }} {{ team.teamlead.surname }}
              </div>
            </div>
          </div>
        </div>

        <!-- Участники команды -->
        <div class="section">
          <div class="section-header">
            <h3>Участники ({{ team.members.length }})</h3>
            <button
              v-if="canManageMembers"
              class="btn-add"
              @click="showAddMemberModal = true"
            >
              + Добавить
            </button>
          </div>

          <div v-if="team.members.length > 0" class="members-list">
            <div
              v-for="member in team.members"
              :key="member.id"
              class="member-card"
            >
              <div class="avatar">
                {{ member.username[0].toUpperCase() }}
              </div>
              <div class="member-info">
                <div class="name">{{ member.username }}</div>
                <div class="email">{{ member.email }}</div>
                <div v-if="member.name || member.surname" class="full-name">
                  {{ member.name }} {{ member.surname }}
                </div>
              </div>
              <button
                v-if="canManageMembers"
                class="btn-remove"
                @click="confirmRemoveMember(member)"
                title="Удалить из команды"
              >
                ✕
              </button>
            </div>
          </div>

          <div v-else class="empty-members">
            <p>В команде пока нет участников</p>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn-secondary" @click="$emit('close')">
          Закрыть
        </button>
      </div>

      <!-- Модалка добавления участника -->
      <div v-if="showAddMemberModal" class="modal-overlay" @click.self="showAddMemberModal = false">
        <div class="modal small">
          <div class="modal-header">
            <h2>Добавить участника</h2>
            <button class="close-btn" @click="showAddMemberModal = false">✕</button>
          </div>

          <div class="modal-body">
            <div class="form-group">
              <label>Выберите сотрудника</label>
              <select v-model="selectedEmployeeId">
                <option value="">-- Выберите --</option>
                <option
                  v-for="employee in availableEmployees"
                  :key="employee.id"
                  :value="employee.id"
                >
                  {{ employee.username }} ({{ employee.email }})
                </option>
              </select>
            </div>

            <div v-if="addMemberError" class="error-message">
              {{ addMemberError }}
            </div>
          </div>

          <div class="modal-footer">
            <button class="btn-secondary" @click="showAddMemberModal = false">
              Отмена
            </button>
            <button
              class="btn-primary"
              :disabled="!selectedEmployeeId || addingMember"
              @click="handleAddMember"
            >
              {{ addingMember ? 'Добавление...' : 'Добавить' }}
            </button>
          </div>
        </div>
      </div>

      <!-- Подтверждение удаления участника -->
      <ConfirmModal
        v-if="showRemoveConfirm"
        title="Удалить участника?"
        :message="`Вы уверены, что хотите удалить ${memberToRemove?.username} из команды?`"
        @confirm="handleRemoveMember"
        @cancel="showRemoveConfirm = false"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import teamsApi from '@/services/teams'
import { useAuthStore } from '@/stores/auth'
import ConfirmModal from '../../components/ConfirmModal.vue'

const props = defineProps({
  teamId: {
    type: Number,
    required: true
  }
})

const emit = defineEmits(['close', 'updated'])

const authStore = useAuthStore()

const team = ref(null)
const loading = ref(true)
const showAddMemberModal = ref(false)
const showRemoveConfirm = ref(false)
const memberToRemove = ref(null)
const selectedEmployeeId = ref('')
const allEmployees = ref([])
const addingMember = ref(false)
const addMemberError = ref('')

// Может ли пользователь управлять участниками
const canManageMembers = computed(() => {
  if (!team.value) return false

  // Админ может управлять всеми
  if (authStore.isAdmin) return true

  // Тимлид может управлять только своей командой
  if (authStore.isTeamlead && team.value.teamlead.id === authStore.user?.id) {
    return true
  }

  return false
})

// Доступные сотрудники (которых еще нет в команде)
const availableEmployees = computed(() => {
  if (!team.value) return []

  const memberIds = team.value.members.map(m => m.id)
  return allEmployees.value.filter(e => !memberIds.includes(e.id))
})

// Загрузка команды
const loadTeam = async () => {
  loading.value = true
  try {
    const result = await teamsApi.getTeam(props.teamId)
    if (result.success) {
      team.value = result.team
    }
  } catch (error) {
    console.error('Failed to load team:', error)
  } finally {
    loading.value = false
  }
}

// Загрузка сотрудников
const loadEmployees = async () => {
  try {
    const result = await teamsApi.getEmployees()
    if (result.success) {
      allEmployees.value = result.employees
    }
  } catch (error) {
    console.error('Failed to load employees:', error)
  }
}

// Добавить участника
const handleAddMember = async () => {
  addingMember.value = true
  addMemberError.value = ''

  try {
    const result = await teamsApi.addMember(props.teamId, selectedEmployeeId.value)

    if (result.success) {
      showAddMemberModal.value = false
      selectedEmployeeId.value = ''
      await loadTeam()
      emit('updated')
    } else {
      addMemberError.value = result.message || 'Ошибка добавления участника'
    }
  } catch (error) {
    console.error('Failed to add member:', error)
    addMemberError.value = 'Ошибка подключения к серверу'
  } finally {
    addingMember.value = false
  }
}

// Подтверждение удаления участника
const confirmRemoveMember = (member) => {
  memberToRemove.value = member
  showRemoveConfirm.value = true
}

// Удалить участника
const handleRemoveMember = async () => {
  try {
    const result = await teamsApi.removeMember(props.teamId, memberToRemove.value.id)

    if (result.success) {
      await loadTeam()
      emit('updated')
    }
  } catch (error) {
    console.error('Failed to remove member:', error)
  } finally {
    showRemoveConfirm.value = false
    memberToRemove.value = null
  }
}

// Форматирование даты
const formatDate = (timestamp) => {
  if (!timestamp) return 'Не указано'
  const date = new Date(timestamp * 1000)
  return date.toLocaleDateString('ru-RU', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  })
}

onMounted(async () => {
  await loadTeam()
  if (canManageMembers.value) {
    await loadEmployees()
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
  justify-content: center;
  align-items: center;
  z-index: 1000;
  animation: fadeIn 0.2s ease;
}

.modal {
  background: white;
  border-radius: 12px;
  width: 90%;
  max-width: 700px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  animation: slideUp 0.3s ease;
}

.modal.small {
  max-width: 500px;
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

.section {
  margin-bottom: 2rem;
}

.section:last-child {
  margin-bottom: 0;
}

.section h3 {
  margin: 0 0 1rem 0;
  font-size: 1rem;
  font-weight: 600;
  color: #2d3748;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.section-header h3 {
  margin: 0;
}

.btn-add {
  padding: 0.5rem 1rem;
  background: #2d3748;
  color: white;
  border: none;
  border-radius: 6px;
  font-size: 0.85rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-add:hover {
  background: #1a202c;
}

.info-grid {
  display: grid;
  gap: 1rem;
}

.info-item label {
  display: block;
  font-size: 0.85rem;
  color: #666;
  margin-bottom: 0.25rem;
  font-weight: 500;
}

.info-item .value {
  font-size: 0.95rem;
  color: #1a1a1a;
  padding: 0.75rem 1rem;
  background: #fafafa;
  border-radius: 6px;
  border: 1px solid #e0e0e0;
}

.member-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: #fafafa;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  margin-bottom: 0.75rem;
}

.member-card.teamlead {
  background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
  border: none;
  color: white;
}

.avatar {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  font-weight: 600;
  flex-shrink: 0;
}

.member-card.teamlead .avatar {
  background: rgba(255, 255, 255, 0.2);
}

.member-info {
  flex: 1;
}

.member-info .name {
  font-size: 0.95rem;
  font-weight: 600;
  margin-bottom: 0.25rem;
}

.member-card.teamlead .member-info .name {
  color: white;
}

.member-info .email {
  font-size: 0.85rem;
  color: #666;
}

.member-card.teamlead .member-info .email {
  color: rgba(255, 255, 255, 0.8);
}

.member-info .full-name {
  font-size: 0.8rem;
  color: #999;
  margin-top: 0.25rem;
}

.member-card.teamlead .member-info .full-name {
  color: rgba(255, 255, 255, 0.7);
}

.btn-remove {
  width: 32px;
  height: 32px;
  padding: 0;
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 6px;
  font-size: 1.25rem;
  color: #666;
  cursor: pointer;
  transition: all 0.2s ease;
  flex-shrink: 0;
}

.btn-remove:hover {
  background: #dc2626;
  border-color: #dc2626;
  color: white;
}

.members-list {
  margin-top: 1rem;
}

.empty-members {
  text-align: center;
  padding: 2rem;
  color: #666;
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-size: 0.9rem;
  font-weight: 500;
  color: #333;
}

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

.form-group select:focus {
  outline: none;
  border-color: #2d3748;
  background: white;
  box-shadow: 0 0 0 3px rgba(45, 55, 72, 0.1);
}

.error-message {
  padding: 0.875rem 1rem;
  background: #fef2f2;
  color: #991b1b;
  border: 1px solid #fecaca;
  border-radius: 6px;
  font-size: 0.9rem;
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
}
</style>
