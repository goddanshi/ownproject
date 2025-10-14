<template>
  <DashboardLayout>
    <div class="profile">
      <div class="profile-header">
        <h1>Профиль пользователя</h1>
        <p class="subtitle">Управление личными данными</p>
      </div>

      <div v-if="loading" class="loading">
        <div class="spinner"></div>
        <p>Загрузка профиля...</p>
      </div>

      <div v-else class="profile-content">
        <div class="profile-card">
          <div class="profile-avatar-section">
            <div class="profile-avatar-large">
              {{ profileData?.username?.[0]?.toUpperCase() || 'U' }}
            </div>
            <button class="change-avatar-btn" disabled>
              Изменить фото
            </button>
          </div>

          <div class="profile-info-section">
            <h2>Личная информация</h2>

            <div class="info-grid">
              <div class="info-item">
                <label>Логин</label>
                <div class="info-value">{{ profileData?.username || '—' }}</div>
              </div>

              <div class="info-item">
                <label>Имя</label>
                <div class="info-value-with-edit">
                  <span class="info-value">{{ profileData?.name || '—' }}</span>
                  <button class="edit-icon-btn" @click="openEditModal" title="Редактировать">
                    <component :is="EditIcon" />
                  </button>
                </div>
              </div>

              <div class="info-item">
                <label>Фамилия</label>
                <div class="info-value-with-edit">
                  <span class="info-value">{{ profileData?.surname || '—' }}</span>
                  <button class="edit-icon-btn" @click="openEditModal" title="Редактировать">
                    <component :is="EditIcon" />
                  </button>
                </div>
              </div>

              <div class="info-item">
                <label>Email</label>
                <div class="info-value">{{ profileData?.email || '—' }}</div>
              </div>

              <div class="info-item">
                <label>Дата регистрации</label>
                <div class="info-value">{{ registrationDate }}</div>
              </div>
            </div>

            <div class="profile-actions">
              <button class="btn-primary" @click="openEditModal">
                Редактировать профиль
              </button>
              <button class="btn-secondary" @click="openPasswordModal">
                Изменить пароль
              </button>
            </div>
          </div>
        </div>

        <div class="settings-card">
          <h2>Настройки</h2>

          <div class="setting-item">
            <div class="setting-info">
              <h3>Уведомления</h3>
              <p>Получать уведомления о новых задачах</p>
            </div>
            <label class="toggle">
              <input type="checkbox" disabled>
              <span class="toggle-slider"></span>
            </label>
          </div>

          <div class="setting-item">
            <div class="setting-info">
              <h3>Email рассылка</h3>
              <p>Получать еженедельные отчеты на почту</p>
            </div>
            <label class="toggle">
              <input type="checkbox" disabled>
              <span class="toggle-slider"></span>
            </label>
          </div>

          <div class="danger-zone">
            <h3>Опасная зона</h3>
            <button class="btn-danger" disabled>
              Удалить аккаунт
            </button>
          </div>
        </div>
      </div>

      <!-- Модалка редактирования профиля -->
      <div v-if="showEditModal" class="modal-overlay" @click.self="closeEditModal">
        <div class="modal">
          <div class="modal-header">
            <h2>Редактировать профиль</h2>
            <button class="modal-close" @click="closeEditModal">✕</button>
          </div>

          <form @submit.prevent="handleUpdateProfile">
            <div class="form-group">
              <label for="edit-name">Имя</label>
              <input
                id="edit-name"
                v-model="editForm.name"
                type="text"
                placeholder="Введите имя"
              />
            </div>

            <div class="form-group">
              <label for="edit-surname">Фамилия</label>
              <input
                id="edit-surname"
                v-model="editForm.surname"
                type="text"
                placeholder="Введите фамилию"
              />
            </div>

            <div class="form-group">
              <label for="edit-email">Email</label>
              <input
                id="edit-email"
                v-model="editForm.email"
                type="email"
                placeholder="Введите email"
                required
              />
            </div>

            <div v-if="editMessage" :class="['message', editMessageType]">
              {{ editMessage }}
            </div>

            <div class="modal-actions">
              <button type="button" class="btn-secondary" @click="closeEditModal">
                Отмена
              </button>
              <button type="submit" class="btn-primary" :disabled="editLoading">
                {{ editLoading ? 'Сохранение...' : 'Сохранить' }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Модалка смены пароля -->
      <div v-if="showPasswordModal" class="modal-overlay" @click.self="closePasswordModal">
        <div class="modal">
          <div class="modal-header">
            <h2>Изменить пароль</h2>
            <button class="modal-close" @click="closePasswordModal">✕</button>
          </div>

          <form @submit.prevent="handleChangePassword">
            <div class="form-group">
              <label for="old-password">Текущий пароль</label>
              <input
                id="old-password"
                v-model="passwordForm.oldPassword"
                type="password"
                placeholder="Введите текущий пароль"
                required
              />
            </div>

            <div class="form-group">
              <label for="new-password">Новый пароль</label>
              <input
                id="new-password"
                v-model="passwordForm.newPassword"
                type="password"
                placeholder="Минимум 6 символов"
                required
                minlength="6"
              />
            </div>

            <div class="form-group">
              <label for="confirm-password">Подтвердите пароль</label>
              <input
                id="confirm-password"
                v-model="passwordForm.confirmPassword"
                type="password"
                placeholder="Повторите новый пароль"
                required
              />
            </div>

            <div v-if="passwordMessage" :class="['message', passwordMessageType]">
              {{ passwordMessage }}
            </div>

            <div class="modal-actions">
              <button type="button" class="btn-secondary" @click="closePasswordModal">
                Отмена
              </button>
              <button type="submit" class="btn-primary" :disabled="passwordLoading">
                {{ passwordLoading ? 'Изменение...' : 'Изменить пароль' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import DashboardLayout from '../layouts/DashboardLayout.vue'
import userApi from '../services/me.js'
import EditIcon from '@/components/icons/EditPencil.vue'

// Локальное состояние профиля
const profileData = ref(null)
const loading = ref(true)

// Модалка редактирования
const showEditModal = ref(false)
const editForm = ref({
  name: '',
  surname: '',
  email: ''
})
const editLoading = ref(false)
const editMessage = ref('')
const editMessageType = ref('')

// Модалка смены пароля
const showPasswordModal = ref(false)
const passwordForm = ref({
  oldPassword: '',
  newPassword: '',
  confirmPassword: ''
})
const passwordLoading = ref(false)
const passwordMessage = ref('')
const passwordMessageType = ref('')

// Дата регистрации
const registrationDate = computed(() => {
  if (!profileData.value?.created_at) {
    return '—'
  }
  const date = new Date(profileData.value.created_at * 1000)
  return date.toLocaleDateString('ru-RU', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  })
})

// Загрузка профиля
const loadProfile = async () => {
  loading.value = true
  try {
    const result = await userApi.getProfile()
    console.log('Profile loaded:', result)

    if (result.success) {
      profileData.value = result.user
    }
  } catch (error) {
    console.error('Failed to load profile:', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadProfile()
})

// Открыть модалку редактирования
const openEditModal = () => {
  editForm.value = {
    name: profileData.value?.name || '',
    surname: profileData.value?.surname || '',
    email: profileData.value?.email || ''
  }
  editMessage.value = ''
  showEditModal.value = true
}

// Закрыть модалку редактирования
const closeEditModal = () => {
  showEditModal.value = false
  editMessage.value = ''
}

// Обновить профиль
const handleUpdateProfile = async () => {
  editLoading.value = true
  editMessage.value = ''

  try {
    const result = await userApi.updateProfile(
      editForm.value.name,
      editForm.value.surname,
      editForm.value.email
    )

    console.log('Update result:', result)

    if (result.success) {
      profileData.value = result.user
      editMessage.value = 'Профиль успешно обновлён'
      editMessageType.value = 'success'

      setTimeout(() => {
        closeEditModal()
      }, 1500)
    } else {
      editMessage.value = result.message || 'Ошибка обновления профиля'
      editMessageType.value = 'error'
    }
  } catch (error) {
    console.error('Update error:', error)
    editMessage.value = 'Ошибка подключения к серверу'
    editMessageType.value = 'error'
  } finally {
    editLoading.value = false
  }
}

// Открыть модалку смены пароля
const openPasswordModal = () => {
  passwordForm.value = {
    oldPassword: '',
    newPassword: '',
    confirmPassword: ''
  }
  passwordMessage.value = ''
  showPasswordModal.value = true
}

// Закрыть модалку смены пароля
const closePasswordModal = () => {
  showPasswordModal.value = false
  passwordMessage.value = ''
}

// Изменить пароль
const handleChangePassword = async () => {
  if (passwordForm.value.newPassword !== passwordForm.value.confirmPassword) {
    passwordMessage.value = 'Пароли не совпадают'
    passwordMessageType.value = 'error'
    return
  }

  passwordLoading.value = true
  passwordMessage.value = ''

  try {
    const result = await userApi.changePassword(
      passwordForm.value.oldPassword,
      passwordForm.value.newPassword
    )

    console.log('Password change result:', result)

    if (result.success) {
      passwordMessage.value = 'Пароль успешно изменён'
      passwordMessageType.value = 'success'

      setTimeout(() => {
        closePasswordModal()
      }, 1500)
    } else {
      passwordMessage.value = result.message || 'Ошибка смены пароля'
      passwordMessageType.value = 'error'
    }
  } catch (error) {
    console.error('Password change error:', error)
    passwordMessage.value = 'Ошибка подключения к серверу'
    passwordMessageType.value = 'error'
  } finally {
    passwordLoading.value = false
  }
}
</script>

<style scoped>
.profile {
  max-width: 1200px;
  margin: 0 auto;
}

.profile-header {
  margin-bottom: 2rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid #e0e0e0;
}

h1 {
  margin: 0 0 0.5rem 0;
  font-size: 1.75rem;
  font-weight: 600;
  color: #1a1a1a;
}

.subtitle {
  margin: 0;
  font-size: 0.95rem;
  color: #666;
}

.loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
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
  to {
    transform: rotate(360deg);
  }
}

.loading p {
  margin: 0;
  font-size: 1rem;
  color: #666;
}

.profile-content {
  display: grid;
  gap: 1.5rem;
}

.profile-card,
.settings-card {
  background: white;
  padding: 2rem;
  border-radius: 8px;
  border: 1px solid #e0e0e0;
}

.profile-card {
  display: grid;
  grid-template-columns: auto 1fr;
  gap: 2rem;
}

.profile-avatar-section {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
}

.profile-avatar-large {
  width: 120px;
  height: 120px;
  border-radius: 50%;
  background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 3rem;
  font-weight: 600;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.change-avatar-btn {
  padding: 0.5rem 1rem;
  background: #fafafa;
  border: 1px solid #e0e0e0;
  border-radius: 6px;
  font-size: 0.85rem;
  cursor: not-allowed;
  color: #999;
}

.profile-info-section h2 {
  margin: 0 0 1.5rem 0;
  font-size: 1.25rem;
  font-weight: 600;
  color: #1a1a1a;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.info-item label {
  display: block;
  font-size: 0.85rem;
  color: #666;
  margin-bottom: 0.5rem;
  font-weight: 500;
}

.info-value {
  font-size: 1rem;
  color: #1a1a1a;
  padding: 0.75rem 1rem;
  background: #fafafa;
  border-radius: 6px;
  border: 1px solid #e0e0e0;
}

.info-value-with-edit {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.info-value-with-edit .info-value {
  flex: 1;
}

.edit-icon-btn {
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
  flex-shrink: 0;
}

.edit-icon-btn:hover {
  background: #2d3748;
  border-color: #2d3748;
  color: white;
}

.edit-icon-btn svg {
  width: 18px;
  height: 18px;
  stroke: currentColor;
}

.profile-actions {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}

.btn-primary {
  padding: 0.75rem 1.5rem;
  background: #2d3748;
  color: white;
  border: none;
  border-radius: 6px;
  font-size: 0.9rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-primary:hover:not(:disabled) {
  background: #1a202c;
  transform: translateY(-1px);
}

.btn-primary:disabled {
  background: #cbd5e0;
  cursor: not-allowed;
}

.btn-secondary {
  padding: 0.75rem 1.5rem;
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 6px;
  font-size: 0.9rem;
  font-weight: 500;
  cursor: pointer;
  color: #333;
  transition: all 0.2s ease;
}

.btn-secondary:hover {
  border-color: #2d3748;
  background: #f5f5f7;
}

.settings-card h2 {
  margin: 0 0 1.5rem 0;
  font-size: 1.25rem;
  font-weight: 600;
  color: #1a1a1a;
}

.setting-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.25rem 0;
  border-bottom: 1px solid #f0f0f0;
}

.setting-item:last-of-type {
  border-bottom: none;
}

.setting-info h3 {
  margin: 0 0 0.25rem 0;
  font-size: 1rem;
  font-weight: 500;
  color: #1a1a1a;
}

.setting-info p {
  margin: 0;
  font-size: 0.85rem;
  color: #666;
}

.toggle {
  position: relative;
  display: inline-block;
  width: 50px;
  height: 28px;
}

.toggle input {
  opacity: 0;
  width: 0;
  height: 0;
}

.toggle-slider {
  position: absolute;
  cursor: not-allowed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: #e0e0e0;
  transition: 0.3s;
  border-radius: 28px;
}

.toggle-slider:before {
  position: absolute;
  content: "";
  height: 20px;
  width: 20px;
  left: 4px;
  bottom: 4px;
  background-color: white;
  transition: 0.3s;
  border-radius: 50%;
}

.toggle input:checked + .toggle-slider {
  background-color: #2d3748;
}

.toggle input:checked + .toggle-slider:before {
  transform: translateX(22px);
}

.danger-zone {
  margin-top: 2rem;
  padding-top: 2rem;
  border-top: 1px solid #f0f0f0;
}

.danger-zone h3 {
  margin: 0 0 1rem 0;
  font-size: 1rem;
  font-weight: 600;
  color: #991b1b;
}

.btn-danger {
  padding: 0.75rem 1.5rem;
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 6px;
  color: #991b1b;
  font-size: 0.9rem;
  font-weight: 500;
  cursor: not-allowed;
}

/* Модалка */
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
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

.modal {
  background: white;
  padding: 2rem;
  border-radius: 8px;
  width: 90%;
  max-width: 500px;
  max-height: 90vh;
  overflow-y: auto;
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
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #e0e0e0;
}

.modal-header h2 {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 600;
  color: #1a1a1a;
}

.modal-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  color: #666;
  cursor: pointer;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
  transition: all 0.2s ease;
}

.modal-close:hover {
  background: #f5f5f7;
  color: #1a1a1a;
}

.form-group {
  margin-bottom: 1.25rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  color: #333;
  font-size: 0.9rem;
  font-weight: 500;
}

.form-group input {
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

.form-group input:focus {
  outline: none;
  border-color: #2d3748;
  background: white;
  box-shadow: 0 0 0 3px rgba(45, 55, 72, 0.1);
}

.message {
  margin: 1rem 0;
  padding: 0.75rem 1rem;
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

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid #e0e0e0;
}

@media (max-width: 768px) {
  .profile-card {
    grid-template-columns: 1fr;
  }

  .profile-avatar-section {
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #e0e0e0;
  }

  .info-grid {
    grid-template-columns: 1fr;
  }

  .modal {
    width: 95%;
    padding: 1.5rem;
  }
}
</style>
