<template>
  <DashboardLayout>
    <div class="profile">
      <div class="profile-header">
        <h1>Профиль пользователя</h1>
        <p class="subtitle">Управление личными данными</p>
      </div>

      <div class="profile-content">
        <div class="profile-card">
          <div class="profile-avatar-section">
            <div class="profile-avatar-large">
              {{ authStore.user?.username?.[0]?.toUpperCase() || 'U' }}
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
                <div class="info-value">{{ authStore.user?.username }}</div>
              </div>

              <div class="info-item">
                <label>Имя</label>
                <div class="info-value">{{ authStore.user?.name }}</div>
              </div>

              <div class="info-item">
                <label>Фамилия</label>
                <div class="info-value">{{ authStore.user?.surname }}</div>
              </div>

              <div class="info-item">
                <label>Email</label>
                <div class="info-value">{{ authStore.user?.email }}</div>
              </div>


              <div class="info-item">
                <label>Дата регистрации</label>
                <div class="info-value">{{ registrationDate }}</div>
              </div>
            </div>

            <div class="profile-actions">
              <button class="btn-secondary" disabled>
                Изменить пароль
              </button>
              <button class="btn-secondary" disabled>
                Редактировать профиль
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
    </div>
  </DashboardLayout>
</template>

<script setup>
import { computed } from 'vue'
import DashboardLayout from '../layouts/DashboardLayout.vue'
import { useAuthStore } from '../stores/auth'

const authStore = useAuthStore()

const registrationDate = computed(() => {
  return new Date().toLocaleDateString('ru-RU', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  })
})
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

.profile-actions {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}

.btn-secondary {
  padding: 0.75rem 1.5rem;
  background: #fafafa;
  border: 1px solid #e0e0e0;
  border-radius: 6px;
  font-size: 0.9rem;
  font-weight: 500;
  cursor: not-allowed;
  color: #999;
  transition: all 0.2s ease;
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
}
</style>
