<template>
  <div class="sidebar-wrapper">
    <!-- Overlay для мобильных -->
    <div
      v-if="isMobileOpen"
      class="overlay"
      @click="closeMobile"
    ></div>

    <!-- Сайдбар -->
    <aside :class="['sidebar', { collapsed: isCollapsed }]">
      <!-- Кнопка сворачивания -->
      <button class="toggle-btn" @click="toggleSidebar">
        {{ isCollapsed ? '>>' : '<<' }}
      </button>

      <!-- Профиль пользователя -->
      <div class="user-section">
        <div class="avatar">
          {{ authStore.user?.username?.[0]?.toUpperCase() || 'U' }}
        </div>
        <transition name="fade">
          <div v-if="!isCollapsed" class="user-info">
            <h3>{{ authStore.user?.username }}</h3>
            <p>{{ authStore.user?.email }}</p>
          </div>
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
          <span class="icon">{{ item.icon }}</span>
          <transition name="fade">
            <span v-if="!isCollapsed" class="label">{{ item.label }}</span>
          </transition>
        </RouterLink>
      </nav>

      <!-- Кнопка выхода -->
      <button class="logout-btn" @click="handleLogout">
        <span class="icon">  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
  </svg></span>
        <transition name="fade">
          <span v-if="!isCollapsed">Выход</span>
        </transition>
      </button>
    </aside>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

import EmploersIcon from "@/components/icons/Emploers.vue"
import DashboardIcon from "@/components/icons/Dashboard.vue";
import TasksIcon from "@/components/icons/Tasks.vue";
import RequestIcon from "@/components/icons/Request.vue";
import LogoutIcon from "@/components/icons/Logout.vue";

const router = useRouter()
const authStore = useAuthStore()

const isCollapsed = ref(false)
const isMobileOpen = ref(false)

const menuItems = [
  { path: '/dashboard', label: 'Дашборд', icon: DashboardIcon },
  { path: '/workers', label: 'Работники', icon: EmploersIcon },
  { path: '/tasks', label: 'Задачи', icon: TasksIcon },
  { path: '/requests', label: 'Заявки', icon: RequestIcon },
]

const toggleSidebar = () => {
  isCollapsed.value = !isCollapsed.value
}

const closeMobile = () => {
  isMobileOpen.value = false
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
  position: fixed;
  left: 0;
  top: 0;
  height: 100vh;
  width: 280px;
  background: linear-gradient(135deg,
  #e0f7fa 0%,
  #b2ebf2 25%,
  #80deea 50%,
  #4dd0e1 75%,
  #26c6da 100%
  );
  background-size: 400% 400%;
  animation: gradient 15s ease infinite;
  padding: 2rem 1rem;
  display: flex;
  flex-direction: column;
  gap: 2rem;
  transition: width 0.3s ease;
  z-index: 999;
  box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1);
}

@keyframes gradient {
  0% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
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
  border: 2px solid #26c6da;
  color: #26c6da;
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
  background: #26c6da;
  color: white;
  transform: scale(1.1);
}

.user-section {
  background: rgba(255, 255, 255, 0.9);
  padding: 1.5rem;
  border-radius: 15px;
  display: flex;
  align-items: center;
  gap: 1rem;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  transition: padding 0.3s ease;
}

.sidebar.collapsed .user-section {
  padding: 1rem;
  justify-content: center;
}

.avatar {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  font-weight: bold;
  flex-shrink: 0;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.user-info {
  overflow: hidden;
}

.user-info h3 {
  margin: 0;
  font-size: 1.1rem;
  color: #333;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.user-info p {
  margin: 0.25rem 0 0;
  font-size: 0.85rem;
  color: #666;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
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
  border-radius: 10px;
  text-decoration: none;
  color: white;
  font-weight: 500;
  transition: all 0.3s ease;
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);
}

.sidebar.collapsed .nav-item {
  justify-content: center;
  padding: 1rem 0.5rem;
}

.nav-item:hover {
  background: rgba(255, 255, 255, 0.25);
  transform: translateX(5px);
}

.nav-item.active {
  background: rgba(255, 255, 255, 0.9);
  color: #26c6da;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.icon {
  font-size: 1.5rem;
  flex-shrink: 0;
}

.label {
  white-space: nowrap;
}

.logout-btn {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  border: none;
  border-radius: 10px;
  background: rgba(244, 67, 54, 0.9);
  color: white;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s ease;
}

.sidebar.collapsed .logout-btn {
  justify-content: center;
  padding: 1rem 0.5rem;
}

.logout-btn:hover {
  background: rgba(244, 67, 54, 1);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(244, 67, 54, 0.3);
}

/* Анимация fade */
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from, .fade-leave-to {
  opacity: 0;
}

/* Адаптив */
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
