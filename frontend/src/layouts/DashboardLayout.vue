<template>
  <div class="dashboard-layout">
    <Sidebar />

    <main :class="['main-content', { collapsed: sidebarCollapsed }]">
      <!-- Хедер внутри лейаута -->
      <div class="dashboard-header">
        <div class="header-left">
          <slot name="header-left">
            <h1>Панель управления</h1>
          </slot>
        </div>

        <div class="header-right">
          <div class="date-info">{{ currentDate }}</div>

          <!-- Кнопка настроек (только для админов) -->
          <div v-if="isAdmin" class="settings-dropdown" ref="settingsRef">
            <button class="settings-button" @click="toggleSettings">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
              </svg>
            </button>

            <transition name="dropdown">
              <div v-if="showSettings" class="settings-menu">
                <button @click="openPermissionsModal" class="menu-item">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                  </svg>
                  <span>Управление правами</span>
                </button>
              </div>
            </transition>
          </div>

          <RouterLink to="/profile" class="user-profile">
            <div class="user-info">
              <span class="user-name">{{ authStore.user?.username }}</span>
              <span class="user-email">{{ authStore.user?.email }}</span>
            </div>
            <div class="user-avatar">
              {{ authStore.user?.username?.[0]?.toUpperCase() || 'U' }}
            </div>
          </RouterLink>
        </div>
      </div>

      <!-- Контент страницы -->
      <div class="content-wrapper">
        <slot />
      </div>
    </main>

    <!-- Модальное окно управления правами -->
    <PermissionsModal v-if="showPermissionsModal" @close="closePermissionsModal" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import Sidebar from '../components/Sidebar.vue'
import PermissionsModal from '../components/PermissionsModal.vue'
import { useAuthStore } from '../stores/auth'

const authStore = useAuthStore()

const sidebarCollapsed = ref(localStorage.getItem('sidebar-collapsed') === 'true')
const showSettings = ref(false)
const showPermissionsModal = ref(false)
const settingsRef = ref(null)

const currentDate = computed(() => {
  const date = new Date()
  return date.toLocaleDateString('ru-RU', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  })
})

const isAdmin = computed(() => {
  return authStore.user?.role === 1
})

const toggleSettings = () => {
  showSettings.value = !showSettings.value
}

const openPermissionsModal = () => {
  showSettings.value = false
  showPermissionsModal.value = true
}

const closePermissionsModal = () => {
  showPermissionsModal.value = false
}

// Закрытие выпадающего меню при клике вне его
const handleClickOutside = (event) => {
  if (settingsRef.value && !settingsRef.value.contains(event.target)) {
    showSettings.value = false
  }
}

// Функция для обновления состояния
const updateSidebarState = () => {
  sidebarCollapsed.value = localStorage.getItem('sidebar-collapsed') === 'true'
}

// Слушаем изменения localStorage
onMounted(() => {
  window.addEventListener('storage', updateSidebarState)
  document.addEventListener('click', handleClickOutside)
  updateSidebarState()

  const interval = setInterval(updateSidebarState, 100)

  onUnmounted(() => {
    window.removeEventListener('storage', updateSidebarState)
    document.removeEventListener('click', handleClickOutside)
    clearInterval(interval)
  })
})
</script>

<style scoped>
.dashboard-layout {
  display: flex;
  min-height: 100vh;
  background: #f5f5f7;
}

.main-content {
  margin-left: 280px;
  flex: 1;
  transition: margin-left 0.3s ease;
  padding: 2rem;
  display: flex;
  flex-direction: column;
}

.main-content.collapsed {
  margin-left: 80px;
}

.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid #e0e0e0;
}

.header-left h1 {
  margin: 0;
  font-size: 1.75rem;
  font-weight: 600;
  color: #1a1a1a;
  letter-spacing: -0.5px;
}

.header-right {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.date-info {
  font-size: 0.9rem;
  color: #666;
  padding: 0.5rem 1rem;
  background: #fafafa;
  border-radius: 6px;
  border: 1px solid #e0e0e0;
}

/* Settings Dropdown */
.settings-dropdown {
  position: relative;
}

.settings-button {
  width: 40px;
  height: 40px;
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

.settings-button:hover {
  background: #2d3748;
  border-color: #2d3748;
}

.settings-button svg {
  width: 20px;
  height: 20px;
  color: #666;
  transition: all 0.2s ease;
}

.settings-button:hover svg {
  color: white;
  transform: rotate(45deg);
}

.settings-menu {
  position: absolute;
  top: calc(100% + 0.5rem);
  right: 0;
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  min-width: 220px;
  z-index: 100;
  overflow: hidden;
}

.menu-item {
  width: 100%;
  padding: 0.875rem 1rem;
  background: none;
  border: none;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  cursor: pointer;
  transition: background 0.2s ease;
  text-align: left;
}

.menu-item:hover {
  background: #f5f5f7;
}

.menu-item svg {
  width: 18px;
  height: 18px;
  color: #666;
  flex-shrink: 0;
}

.menu-item span {
  font-size: 0.9rem;
  color: #333;
  font-weight: 500;
}

.dropdown-enter-active, .dropdown-leave-active {
  transition: all 0.2s ease;
}

.dropdown-enter-from, .dropdown-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

.user-profile {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.5rem 1rem;
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 6px;
  text-decoration: none;
  transition: all 0.2s ease;
}

.user-profile:hover {
  border-color: #2d3748;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.user-info {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.125rem;
}

.user-name {
  font-size: 0.9rem;
  font-weight: 600;
  color: #1a1a1a;
}

.user-email {
  font-size: 0.75rem;
  color: #666;
}

.user-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  font-weight: 600;
  flex-shrink: 0;
}

.content-wrapper {
  flex: 1;
}

@media (max-width: 768px) {
  .main-content {
    margin-left: 0;
  }

  .dashboard-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }

  .header-right {
    width: 100%;
    justify-content: space-between;
  }

  .user-info {
    align-items: flex-start;
  }
}
</style>
