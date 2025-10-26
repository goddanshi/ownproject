<template>
  <div class="modal-overlay" @click.self="$emit('close')">
    <div class="modal">
      <div class="modal-header">
        <h2>{{ isEditMode ? 'Редактировать работника' : 'Добавить работника' }}</h2>
        <button class="close-btn" @click="$emit('close')">✕</button>
      </div>

      <form @submit.prevent="handleSubmit">
        <div class="modal-body">
          <!-- Имя пользователя -->
          <div class="form-group">
            <label for="username">Имя пользователя *</label>
            <input
              id="username"
              v-model="form.username"
              type="text"
              placeholder="Введите имя пользователя"
              required
            />
          </div>

          <!-- Email -->
          <div class="form-group">
            <label for="email">Email *</label>
            <input
              id="email"
              v-model="form.email"
              type="email"
              placeholder="email@example.com"
              required
            />
          </div>

          <!-- Имя -->
          <div class="form-group">
            <label for="name">Имя</label>
            <input
              id="name"
              v-model="form.name"
              type="text"
              placeholder="Введите имя"
            />
          </div>

          <!-- Фамилия -->
          <div class="form-group">
            <label for="surname">Фамилия</label>
            <input
              id="surname"
              v-model="form.surname"
              type="text"
              placeholder="Введите фамилию"
            />
          </div>

          <!-- Роль -->
          <div class="form-group">
            <label for="role">Роль *</label>
            <select
              id="role"
              v-model="form.role"
              required
            >
              <option value="">Выберите роль</option>
              <option value="1">Администратор</option>
              <option value="2">Тимлид</option>
              <option value="3">Сотрудник</option>
            </select>
          </div>

          <!-- Пароль (только при создании) -->
          <div v-if="!isEditMode" class="form-group">
            <label for="password">Пароль *</label>
            <input
              id="password"
              v-model="form.password"
              type="password"
              placeholder="Минимум 6 символов"
              required
              minlength="6"
            />
          </div>

          <!-- Кнопка смены пароля (при редактировании) -->
          <div v-if="isEditMode" class="form-group">
            <button
              type="button"
              class="btn-change-password"
              @click="showPasswordModal = true"
            >
              Изменить пароль
            </button>
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

      <!-- Модалка смены пароля -->
      <div v-if="showPasswordModal" class="modal-overlay" @click.self="showPasswordModal = false">
        <div class="modal small">
          <div class="modal-header">
            <h2>Изменить пароль</h2>
            <button class="close-btn" @click="showPasswordModal = false">✕</button>
          </div>

          <form @submit.prevent="handleChangePassword">
            <div class="modal-body">
              <div class="form-group">
                <label for="new-password">Новый пароль</label>
                <input
                  id="new-password"
                  v-model="newPassword"
                  type="password"
                  placeholder="Минимум 6 символов"
                  required
                  minlength="6"
                />
              </div>

              <div v-if="passwordError" class="error-message">
                {{ passwordError }}
              </div>
            </div>

            <div class="modal-footer">
              <button type="button" class="btn-secondary" @click="showPasswordModal = false">
                Отмена
              </button>
              <button type="submit" class="btn-primary" :disabled="changingPassword">
                {{ changingPassword ? 'Изменение...' : 'Изменить пароль' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import workersApi from '@/services/workers'

const props = defineProps({
  worker: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['close', 'saved'])

const isEditMode = computed(() => !!props.worker)

const form = ref({
  username: props.worker?.username || '',
  email: props.worker?.email || '',
  name: props.worker?.name || '',
  surname: props.worker?.surname || '',
  role: props.worker?.role?.toString() || '',
  password: ''
})

const loading = ref(false)
const errorMessage = ref('')

const showPasswordModal = ref(false)
const newPassword = ref('')
const changingPassword = ref(false)
const passwordError = ref('')

// Отправка формы
const handleSubmit = async () => {
  loading.value = true
  errorMessage.value = ''

  try {
    let result

    if (isEditMode.value) {
      // Обновление работника
      result = await workersApi.updateWorker(
        props.worker.id,
        form.value.username,
        form.value.email,
        parseInt(form.value.role),
        form.value.name,
        form.value.surname
      )
    } else {
      // Создание работника
      result = await workersApi.createWorker(
        form.value.username,
        form.value.email,
        form.value.password,
        parseInt(form.value.role),
        form.value.name,
        form.value.surname
      )
    }

    if (result.success) {
      emit('saved')
    } else {
      errorMessage.value = result.message || 'Ошибка сохранения'
    }
  } catch (error) {
    console.error('Failed to save worker:', error)
    errorMessage.value = 'Ошибка подключения к серверу'
  } finally {
    loading.value = false
  }
}

// Изменить пароль
const handleChangePassword = async () => {
  changingPassword.value = true
  passwordError.value = ''

  try {
    const result = await workersApi.changeWorkerPassword(
      props.worker.id,
      newPassword.value
    )

    if (result.success) {
      showPasswordModal.value = false
      newPassword.value = ''
      errorMessage.value = ''
      // Показываем успешное сообщение в основной модалке
      setTimeout(() => {
        emit('saved')
      }, 500)
    } else {
      passwordError.value = result.message || 'Ошибка изменения пароля'
    }
  } catch (error) {
    console.error('Failed to change password:', error)
    passwordError.value = 'Ошибка подключения к серверу'
  } finally {
    changingPassword.value = false
  }
}
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

.modal.small {
  max-width: 450px;
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
  display: block;
  margin-bottom: 0.5rem;
  font-size: 0.9rem;
  font-weight: 500;
  color: #333;
}

.form-group input,
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
.form-group select:focus {
  outline: none;
  border-color: #2d3748;
  background: white;
  box-shadow: 0 0 0 3px rgba(45, 55, 72, 0.1);
}

.btn-change-password {
  width: 100%;
  padding: 0.75rem 1rem;
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  border-radius: 6px;
  color: #1e40af;
  font-size: 0.9rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-change-password:hover {
  background: #dbeafe;
  border-color: #93c5fd;
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
