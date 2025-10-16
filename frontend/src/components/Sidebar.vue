<template>
  <div class="sidebar-wrapper">
    <div v-if="isMobileOpen" class="overlay" @click="closeMobile"></div>

    <aside :class="['sidebar', { collapsed: isCollapsed }]">
      <button class="toggle-btn" @click="toggleSidebar">
        {{ isCollapsed ? '>>' : '<<' }}
      </button>

      <!-- Логотип или название системы -->
      <div class="sidebar-logo">
        <transition name="fade">
          <span v-if="!isCollapsed" class="logo-text">CRM System</span>
          <span v-else class="logo-short">CRM</span>
        </transition>
      </div>

      <!-- Навигация -->
      <nav class="nav-menu">
        <RouterLink
          v-for="item in menuItems"
          :key="item.path"
          :to="item.path"
          class="nav-item"
          active-class="active"
        >
          <span class="icon">
            <component :is="item.icon" />
          </span>
          <transition name="fade">
            <span v-if="!isCollapsed" class="label">{{ item.label }}</span>
          </transition>
        </RouterLink>
      </nav>

      <!-- Кнопка выхода -->
      <button class="logout-btn" @click="handleLogout">
        <span class="icon">
          <LogoutIcon />
        </span>
        <transition name="fade">
          <span v-if="!isCollapsed">Выход</span>
        </transition>
      </button>
    </aside>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { usePermissions } from '../composables/usePermissions'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const { can } = usePermissions()

const collapsed = ref(localStorage.getItem('sidebar-collapsed') === 'true')

const toggleSidebar = () => {
  collapsed.value = !collapsed.value
  localStorage.setItem('sidebar-collapsed', collapsed.value)
}

const menuItems = computed(() => [
  {
    name: 'dashboard',
    path: '/dashboard',
    label: 'Дашборд',
    icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
    permission: 'view_dashboard'
  },
  {
    name: 'workers',
    path: '/workers',
    label: 'Работники',
    icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
    permission: 'view_workers'
  },
  {
    name: 'tasks',
    path: '/tasks',
    label: 'Задачи',
    icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
    permission: 'view_tasks'
  },
  {
    name: 'requests',
    path: '/requests',
    label: 'Заявки',
    icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    permission: 'view_requests'
  }
].filter(item => can(item.permission)))

const isActive = (path) => {
  return route.path === path
}

const handleLogout = async () => {
  await authStore.logout()
  router.push('/login')
}
</script>

<style scoped>
.sidebar-wrapper {
  position: relative;
}

.overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 998;
}

.sidebar {
  background: white;
  border-right: 1px solid #e0e0e0;
  position: fixed;
  left: 0;
  top: 0;
  height: 100vh;
  width: 280px;
  padding: 2rem 1rem;
  display: flex;
  flex-direction: column;
  gap: 2rem;
  transition: width 0.3s ease;
  z-index: 999;
}

.sidebar.collapsed {
  width: 80px;
}

.toggle-btn {
  position: absolute;
  right: -15px;
  top: 20px;
  width: 30px;
  height: 30px;
  border-radius: 50%;
  background: white;
  border: 2px solid #2d3748;
  color: #2d3748;
  font-weight: bold;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
  z-index: 1000;
}

.toggle-btn:hover {
  background: #2d3748;
  color: white;
  transform: scale(1.1);
}

.sidebar-logo {
  text-align: center;
  padding: 1rem;
  border-bottom: 1px solid #e0e0e0;
  margin-bottom: 1rem;
}

.logo-text {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1a1a1a;
}

.logo-short {
  font-size: 1rem;
  font-weight: 600;
  color: #1a1a1a;
}

.nav-menu {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  border-radius: 6px;
  text-decoration: none;
  color: #555;
  font-weight: 500;
  transition: all 0.2s ease;
  background: transparent;
  position: relative;
}

.sidebar.collapsed .nav-item {
  justify-content: center;
  padding: 1rem 0.5rem;
}

.nav-item:hover {
  background: #f5f5f7;
}

.nav-item.active {
  background: #2d3748;
  color: white;
}

.icon {
  width: 24px;
  height: 24px;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
}

.icon :deep(svg) {
  width: 24px;
  height: 24px;
  stroke: currentColor;
}

.label {
  white-space: nowrap;
  font-size: 0.9rem;
}

.logout-btn {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  border: none;
  border-radius: 6px;
  background: #fef2f2;
  color: #991b1b;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 0.9rem;
}

.sidebar.collapsed .logout-btn {
  justify-content: center;
  padding: 1rem 0.5rem;
}

.logout-btn:hover {
  background: #fee2e2;
}

.fade-enter-active, .fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from, .fade-leave-to {
  opacity: 0;
}

@media (max-width: 768px) {
  .sidebar {
    width: 280px;
    transform: translateX(-100%);
  }

  .sidebar.mobile-open {
    transform: translateX(0);
  }
}
</style>
