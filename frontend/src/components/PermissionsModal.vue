<template>
  <div class="permissions-modal-overlay" @click.self="$emit('close')">
    <div class="permissions-modal">
      <div class="modal-header">
        <h2>Управление правами доступа</h2>
        <button class="close-button" @click="$emit('close')">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <div class="modal-body">
        <!-- Выбор роли -->
        <div class="role-selector">
          <label>Выберите роль:</label>
          <div class="role-tabs">
            <button
              v-for="role in roles"
              :key="role.id"
              :class="['role-tab', { active: selectedRole === role.id }]"
              @click="selectRole(role.id)"
            >
              {{ role.label }}
            </button>
          </div>
        </div>

        <!-- Загрузка -->
        <div v-if="loading" class="loading-state">
          <div class="spinner"></div>
          <p>Загрузка прав...</p>
        </div>

        <!-- Список прав по категориям -->
        <div v-else class="permissions-grid">
          <div
            v-for="(perms, category) in groupedPermissions"
            :key="category"
            class="permission-category"
          >
            <h3>{{ getCategoryLabel(category) }}</h3>
            <div class="permission-list">
              <label
                v-for="perm in perms"
                :key="perm.name"
                class="permission-item"
              >
                <input
                  type="checkbox"
                  :checked="selectedPermissions.includes(perm.name)"
                  @change="togglePermission(perm.name)"
                />
                <div class="permission-info">
                  <span class="permission-label">{{ perm.label }}</span>
                  <span class="permission-description">{{ perm.description }}</span>
                </div>
              </label>
            </div>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <div v-if="message" :class="['message', messageType]">
          {{ message }}
        </div>
        <div class="footer-actions">
          <button class="btn-cancel" @click="$emit('close')">
            Отмена
          </button>
          <button class="btn-save" @click="savePermissions" :disabled="saving">
            {{ saving ? 'Сохранение...' : 'Сохранить изменения' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import settingsApi from '@/services/settings'

const emit = defineEmits(['close'])

const roles = [
  { id: 1, label: 'Администратор' },
  { id: 2, label: 'Тимлид' },
  { id: 3, label: 'Сотрудник' }
]

const categoryLabels = {
  dashboard: 'Панель управления',
  workers: 'Работники',
  tasks: 'Задачи',
  requests: 'Заявки',
  profile: 'Профиль',
  admin: 'Администрирование',
  other: 'Прочее'
}

const selectedRole = ref(1)
const loading = ref(true)
const saving = ref(false)
const allPermissions = ref({})
const selectedPermissions = ref([])
const message = ref('')
const messageType = ref('')

const groupedPermissions = computed(() => allPermissions.value)

const getCategoryLabel = (category) => {
  return categoryLabels[category] || category
}

const selectRole = async (roleId) => {
  selectedRole.value = roleId
  await loadRolePermissions()
}

const togglePermission = (permName) => {
  const index = selectedPermissions.value.indexOf(permName)
  if (index > -1) {
    selectedPermissions.value.splice(index, 1)
  } else {
    selectedPermissions.value.push(permName)
  }
}

const loadPermissions = async () => {
  loading.value = true
  try {
    const result = await settingsApi.getPermissions()
    if (result.success) {
      allPermissions.value = result.permissions
    }
  } catch (error) {
    console.error('Failed to load permissions:', error)
    message.value = 'Ошибка загрузки прав'
    messageType.value = 'error'
  } finally {
    loading.value = false
  }
}

const loadRolePermissions = async () => {
  try {
    const result = await settingsApi.getRolePermissions(selectedRole.value)
    if (result.success) {
      selectedPermissions.value = result.permissions
    }
  } catch (error) {
    console.error('Failed to load role permissions:', error)
  }
}

const savePermissions = async () => {
  saving.value = true
  message.value = ''

  try {
    const result = await settingsApi.updateRolePermissions(
      selectedRole.value,
      selectedPermissions.value
    )

    if (result.success) {
      message.value = 'Права успешно сохранены!'
      messageType.value = 'success'

      setTimeout(() => {
        emit('close')
      }, 1500)
    } else {
      message.value = result.message || 'Ошибка сохранения прав'
      messageType.value = 'error'
    }
  } catch (error) {
    console.error('Failed to save permissions:', error)
    message.value = 'Ошибка подключения к серверу'
    messageType.value = 'error'
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  await loadPermissions()
  await loadRolePermissions()
})
</script>

<style scoped>
.permissions-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.6);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 10000;
  padding: 2rem;
  animation: fadeIn 0.2s ease;
}

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

.permissions-modal {
  background: white;
  border-radius: 12px;
  width: 100%;
  max-width: 1200px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  animation: slideUp 0.3s ease;
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(30px);
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
  padding: 2rem;
  border-bottom: 1px solid #e0e0e0;
}

.modal-header h2 {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 600;
  color: #1a1a1a;
}

.close-button {
  width: 36px;
  height: 36px;
  padding: 0;
  background: none;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  transition: background 0.2s ease;
}

.close-button:hover {
  background: #f5f5f7;
}

.close-button svg {
  width: 20px;
  height: 20px;
  color: #666;
}

.modal-body {
  flex: 1;
  overflow-y: auto;
  padding: 2rem;
}

.role-selector {
  margin-bottom: 2rem;
}

.role-selector label {
  display: block;
  font-size: 0.9rem;
  font-weight: 600;
  color: #333;
  margin-bottom: 1rem;
}

.role-tabs {
  display: flex;
  gap: 1rem;
}

.role-tab {
  padding: 0.75rem 1.5rem;
  background: white;
  border: 2px solid #e0e0e0;
  border-radius: 8px;
  font-size: 0.95rem;
  font-weight: 500;
  color: #666;
  cursor: pointer;
  transition: all 0.2s ease;
}

.role-tab:hover {
  border-color: #2d3748;
  background: #fafafa;
}

.role-tab.active {
  background: #2d3748;
  border-color: #2d3748;
  color: white;
}

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 4rem 2rem;
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
  to {
    transform: rotate(360deg);
  }
}

.loading-state p {
  margin: 0;
  color: #666;
  font-size: 1rem;
}

.permissions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
  gap: 1.5rem;
}

.permission-category {
  background: #fafafa;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  padding: 1.5rem;
}

.permission-category h3 {
  margin: 0 0 1rem 0;
  font-size: 1rem;
  font-weight: 600;
  color: #2d3748;
}

.permission-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.permission-item {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  padding: 0.875rem;
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.permission-item:hover {
  border-color: #2d3748;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.permission-item input[type="checkbox"] {
  margin-top: 0.25rem;
  cursor: pointer;
  width: 18px;
  height: 18px;
  accent-color: #2d3748;
}

.permission-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.permission-label {
  font-size: 0.9rem;
  font-weight: 500;
  color: #1a1a1a;
}

.permission-description {
  font-size: 0.8rem;
  color: #666;
  line-height: 1.4;
}

.modal-footer {
  padding: 1.5rem 2rem;
  border-top: 1px solid #e0e0e0;
  background: #fafafa;
}

.message {
  margin-bottom: 1rem;
  padding: 0.875rem 1rem;
  border-radius: 6px;
  font-size: 0.9rem;
  text-align: center;
}

.message.success {
  background: #f0fdf4;
  color: #166534;
  border: 1px solid #bbf7d0;
}

.message.error {
  background: #fef2f2;
  color: #991b1b;
  border: 1px solid #fecaca;
}

.footer-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
}

.btn-cancel,
.btn-save {
  padding: 0.875rem 1.75rem;
  border-radius: 8px;
  font-size: 0.95rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-cancel {
  background: white;
  border: 2px solid #e0e0e0;
  color: #666;
}

.btn-cancel:hover {
  border-color: #2d3748;
  background: #fafafa;
}

.btn-save {
  background: #2d3748;
  border: 2px solid #2d3748;
  color: white;
}

.btn-save:hover:not(:disabled) {
  background: #1a202c;
  border-color: #1a202c;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(45, 55, 72, 0.3);
}

.btn-save:disabled {
  background: #cbd5e0;
  border-color: #cbd5e0;
  cursor: not-allowed;
}

@media (max-width: 768px) {
  .permissions-modal {
    max-width: 100%;
    max-height: 100vh;
    border-radius: 0;
  }

  .permissions-grid {
    grid-template-columns: 1fr;
  }

  .role-tabs {
    flex-direction: column;
  }

  .footer-actions {
    flex-direction: column;
  }

  .btn-cancel,
  .btn-save {
    width: 100%;
  }
}
</style>
