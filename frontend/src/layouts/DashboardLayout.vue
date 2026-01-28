<template>
  <div class="dashboard-layout">
    <Sidebar />

    <main :class="['main-content', { collapsed: sidebarCollapsed, 'projects-open': projectsSidebarOpen }]">
      <div class="dashboard-header">
        <div class="header-left">
          <slot name="header-left">
            <h1>Панель управления</h1>
          </slot>
        </div>

        <div class="header-right">
          <div class="date-info">{{ currentDate }}</div>

          <!-- Кнопка переключения темы -->
          <button class="theme-toggle" @click="toggleTheme" :title="isDark ? 'Светлая тема' : 'Темная тема'">
            <svg v-if="isDark" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
            </svg>
            <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
            </svg>
          </button>

          <!-- Кнопка настроек (проверка по праву manage_permissions) -->
          <div v-if="authStore.can('manage_permissions')" class="settings-dropdown" ref="settingsRef">
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

          <RouterLink
            v-if="authStore.can('view_profile')"
            to="/profile"
            class="user-profile"
          >
            <div class="user-info">
              <span class="user-name">{{ authStore.user?.username }}</span>
              <span class="user-email">{{ authStore.user?.email }}</span>
            </div>
            <div class="user-avatar">
              <img :src="authStore.user?.avatar" alt="Аватар" v-if="authStore.user?.avatar">
              <div v-else>{{ authStore.user?.username?.[0]?.toUpperCase() || 'U' }}</div>
            </div>
          </RouterLink>
        </div>
      </div>

      <div class="content-wrapper">
        <slot />
      </div>
    </main>

    <ProjectsSidebar v-if="!authStore.isSalesManager" />

    <PermissionsModal v-if="showPermissionsModal" @close="closePermissionsModal" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import Sidebar from '../components/Sidebar.vue'
import ProjectsSidebar from '../components/ProjectsSidebar.vue'
import PermissionsModal from '../components/PermissionsModal.vue'
import { useAuthStore } from '../stores/auth'

const authStore = useAuthStore()

const sidebarCollapsed = ref(localStorage.getItem('sidebar-collapsed') === 'true')
const projectsSidebarOpen = ref(localStorage.getItem('projects-sidebar-open') === 'true')
const showSettings = ref(false)
const showPermissionsModal = ref(false)
const settingsRef = ref(null)
const isDark = ref(localStorage.getItem('theme') === 'dark')

const currentDate = computed(() => {
  const date = new Date()
  return date.toLocaleDateString('ru-RU', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  })
})

// Удалили isAdmin computed, теперь используем authStore.can('manage_permissions')

const toggleTheme = () => {
  isDark.value = !isDark.value
  const theme = isDark.value ? 'dark' : 'light'
  localStorage.setItem('theme', theme)
  document.documentElement.setAttribute('data-theme', theme)
  console.log('Theme changed to:', theme, 'Attribute set:', document.documentElement.getAttribute('data-theme'))
}

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

const handleClickOutside = (event) => {
  if (settingsRef.value && !settingsRef.value.contains(event.target)) {
    showSettings.value = false
  }
}

const updateSidebarState = () => {
  sidebarCollapsed.value = localStorage.getItem('sidebar-collapsed') === 'true'
  projectsSidebarOpen.value = localStorage.getItem('projects-sidebar-open') === 'true'
}

onMounted(() => {
  // Применяем сохраненную тему при загрузке
  const savedTheme = localStorage.getItem('theme') || 'light'
  document.documentElement.setAttribute('data-theme', savedTheme)
  isDark.value = savedTheme === 'dark'

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
  background: var(--bg-primary);
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

.main-content.projects-open {
  margin-left: 600px;
}

.main-content.collapsed.projects-open {
  margin-left: 400px;
}

.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid var(--border-color);
}

.header-left h1 {
  margin: 0;
  font-size: 1.75rem;
  font-weight: 600;
  color: var(--text-primary);
  letter-spacing: -0.5px;
}

.header-right {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.date-info {
  font-size: 0.9rem;
  color: var(--text-secondary);
  padding: 0.5rem 1rem;
  background: var(--bg-tertiary);
  border-radius: 6px;
  border: 1px solid var(--border-color);
}

/* Theme Toggle Button */
.theme-toggle {
  width: 40px;
  height: 40px;
  padding: 0;
  background: var(--bg-secondary);
  border: 1px solid var(--border-color);
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
}

.theme-toggle:hover {
  background: var(--accent-primary);
  border-color: var(--accent-primary);
}

.theme-toggle svg {
  width: 20px;
  height: 20px;
  color: var(--text-secondary);
  transition: all 0.2s ease;
}

.theme-toggle:hover svg {
  color: var(--bg-secondary);
}

/* Settings Dropdown */
.settings-dropdown {
  position: relative;
}

.settings-button {
  width: 40px;
  height: 40px;
  padding: 0;
  background: var(--bg-secondary);
  border: 1px solid var(--border-color);
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
}

.settings-button:hover {
  background: var(--accent-primary);
  border-color: var(--accent-primary);
}

.settings-button svg {
  width: 20px;
  height: 20px;
  color: var(--text-secondary);
  transition: all 0.2s ease;
}

.settings-button:hover svg {
  color: var(--bg-secondary);
  transform: rotate(45deg);
}

.settings-menu {
  position: absolute;
  top: calc(100% + 0.5rem);
  right: 0;
  background: var(--bg-secondary);
  border: 1px solid var(--border-color);
  border-radius: 8px;
  box-shadow: 0 4px 12px var(--shadow-md);
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
  background: var(--bg-primary);
}

.menu-item svg {
  width: 18px;
  height: 18px;
  color: var(--text-secondary);
  flex-shrink: 0;
}

.menu-item span {
  font-size: 0.9rem;
  color: var(--text-primary);
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
  background: var(--bg-secondary);
  border: 1px solid var(--border-color);
  border-radius: 6px;
  text-decoration: none;
  transition: all 0.2s ease;
}

.user-profile:hover {
  border-color: var(--accent-primary);
  box-shadow: 0 2px 8px var(--shadow-sm);
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
  color: var(--text-primary);
}

.user-email {
  font-size: 0.75rem;
  color: var(--text-secondary);
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
  overflow: hidden;
}

.user-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.content-wrapper {
  flex: 1;
}

@media (max-width: 768px) {
  .main-content {
    margin-left: 0;
  }

  .main-content.projects-open {
    margin-left: 0;
  }

  .main-content.collapsed.projects-open {
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
