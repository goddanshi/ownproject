<template>
  <div class="sidebar-wrapper">
    <div v-if="isMobileOpen" class="overlay" @click="closeMobile"></div>

    <aside :class="['sidebar', { collapsed: isCollapsed }]">
      <button class="toggle-btn" @click="toggleSidebar">
        {{ isCollapsed ? '>>' : '<<' }}
      </button>

      <div class="sidebar-logo">
        <transition name="fade">
          <span v-if="!isCollapsed" class="logo-text">Бамбук и Панды</span>
          <span v-else class="logo-short">CRM</span>
        </transition>
      </div>

      <!-- Навигация - отфильтрованная по правам -->
      <nav class="nav-menu">
        <!-- Общие роуты -->
        <div class="menu-section">
          <RouterLink
            v-for="item in visibleGeneralItems"
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
        </div>

        <!-- Разделитель -->
        <hr v-if="visiblePersonalItems.length > 0" class="menu-divider" />

        <!-- Личные роуты -->
        <div v-if="visiblePersonalItems.length > 0" class="menu-section">
          <RouterLink
            v-for="item in visiblePersonalItems"
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
        </div>

        <!-- Разделитель для проектов (не для менеджеров по продажам) -->
        <hr v-if="!authStore.isSalesManager" class="menu-divider" />

        <!-- Проекты - клик открывает боковой сайдбар (не для менеджеров по продажам) -->
        <div v-if="!authStore.isSalesManager" class="menu-section">
          <button
            class="nav-item nav-item-button"
            :class="{ active: projectsSidebarOpen }"
            @click="toggleProjectsSidebar"
          >
            <span class="icon">
              <component :is="projectsMenuItem.icon" />
            </span>
            <transition name="fade">
              <span v-if="!isCollapsed" class="label">{{ projectsMenuItem.label }}</span>
            </transition>
            <transition name="fade">
              <span v-if="!isCollapsed" class="arrow">{{ projectsSidebarOpen ? '▼' : '▶' }}</span>
            </transition>
          </button>
        </div>
      </nav>

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
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth.js'

import EmploersIcon from '@/components/icons/Emploers.vue'
import DashboardIcon from '@/components/icons/Dashboard.vue'
import TasksIcon from '@/components/icons/Tasks.vue'
import ProjectsIcon from '@/components/icons/Projects.vue'
import LogoutIcon from '@/components/icons/Logout.vue'
import ComandsIcon from "@/components/icons/Comands.vue"
import RequestIcon from './icons/Request.vue'
import AiIcon from "@/components/icons/AI.vue"
import LeadsIcon from "@/components/icons/Leads.vue"
import StatisticsIcon from "@/components/icons/Statistics.vue"
import AnalyticsIcon from "@/components/icons/Analytics.vue"

const router = useRouter()
const authStore = useAuthStore()

const isCollapsed = ref(localStorage.getItem('sidebar-collapsed') === 'true')
const isMobileOpen = ref(false)
const projectsSidebarOpen = ref(localStorage.getItem('projects-sidebar-open') === 'true')

// Общие пункты меню
const generalMenuItems = [
  {
    path: '/dashboard',
    label: 'Дашборд',
    icon: DashboardIcon,
    permission: 'view_dashboard'
  },
  {
    path: '/tasks',
    label: 'Задачи',
    icon: TasksIcon,
    permission: 'view_tasks'
  },
  {
    path: '/analytics',
    label: 'Аналитика',
    icon: AnalyticsIcon,
    // permission: 'view_analytics'
  },
  {
    path: '/workers',
    label: 'Сотрудники',
    icon: EmploersIcon,
    permission: 'view_workers'
  },
  {
    path: '/commands',
    label: 'Команды',
    icon: ComandsIcon,
    permission: 'view_teams' // Управление командами - для админов/тимлидов
  },
  {
    path: '/my-team',
    label: 'Моя команда',
    icon: ComandsIcon,
    permission: 'view_my_team'
  },
  {
    path: '/statistics',
    label: 'Статистика',
    icon: StatisticsIcon,
    // permission: 'view_statistics'
  },
  {
    path: '/leads',
    label: 'Лиды',
    icon: RequestIcon
  },
  {
    path: '/aichat',
    label: "AI Ассистент",
    icon: AiIcon,
  }
]

// Личные пункты меню (теперь пустой, все перенесено в общие)
const personalMenuItems = []

// Пункт меню с проектами (отдельная секция)
const projectsMenuItem = {
  path: '/projects',
  label: 'Проекты',
  icon: ProjectsIcon,
  hasSubmenu: true
  //permission: 'view_projects'
}

// Фильтруем общие пункты меню по правам
const visibleGeneralItems = computed(() => {
  return generalMenuItems.filter(item => {
    // Менеджеры по продажам видят только лиды
    if (authStore.isSalesManager) {
      return item.path === '/leads'
    }
    if (!item.permission) return true
    return authStore.can(item.permission)
  })
})

// Фильтруем личные пункты меню по правам
const visiblePersonalItems = computed(() => {
  return personalMenuItems.filter(item => {
    if (!item.permission) return true
    return authStore.can(item.permission)
  })
})

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

const toggleProjectsSidebar = () => {
  projectsSidebarOpen.value = !projectsSidebarOpen.value
  localStorage.setItem('projects-sidebar-open', projectsSidebarOpen.value.toString())
}

watch(isCollapsed, (newValue) => {
  localStorage.setItem('sidebar-collapsed', newValue.toString())
})

onMounted(() => {
  const savedState = localStorage.getItem('sidebar-collapsed')
  if (savedState !== null) {
    isCollapsed.value = savedState === 'true'
  }
  const projectsSidebarState = localStorage.getItem('projects-sidebar-open')
  if (projectsSidebarState !== null) {
    projectsSidebarOpen.value = projectsSidebarState === 'true'
  }
})
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
  background: var(--bg-secondary);
  border-right: 1px solid var(--border-color);
  position: fixed;
  left: 0;
  top: 0;
  height: 100vh;
  width: 280px;
  padding: 2rem 1rem;
  display: flex;
  flex-direction: column;
  gap: 2rem;
  transition: width 0.3s ease, background-color 0.3s ease;
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
  background: var(--bg-secondary);
  border: 2px solid var(--accent-primary);
  color: var(--accent-primary);
  font-weight: bold;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
  box-shadow: 0 2px 8px var(--shadow-sm);
  z-index: 1000;
}

.toggle-btn:hover {
  background: var(--accent-primary);
  color: var(--bg-secondary);
  transform: scale(1.1);
}

.sidebar-logo {
  text-align: center;
  padding: 1rem;
  border-bottom: 1px solid var(--border-color);
  margin-bottom: 1rem;
}

.logo-text {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--text-primary);
}

.logo-short {
  font-size: 1rem;
  font-weight: 600;
  color: var(--text-primary);
}

.nav-menu {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  overflow-y: auto;
}

.menu-section {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.section-title {
  padding: 0.5rem 1rem;
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--text-tertiary);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.menu-divider {
  border: none;
  border-top: 1px solid var(--border-color);
  margin: 0.5rem 0;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  border-radius: 6px;
  text-decoration: none;
  color: var(--text-secondary);
  font-weight: 500;
  transition: all 0.2s ease;
  background: transparent;
  position: relative;
}

.nav-item-button {
  width: 100%;
  border: none;
  cursor: pointer;
  text-align: left;
}

.sidebar.collapsed .nav-item {
  justify-content: center;
  padding: 1rem 0.5rem;
}

.nav-item:hover {
  background: var(--bg-primary);
}

.nav-item.active {
  background: var(--accent-primary);
  color: var(--bg-secondary);
}

.nav-item .arrow {
  margin-left: auto;
  font-size: 0.75rem;
  transition: transform 0.2s;
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
  background: rgba(254, 242, 242, 0.8);
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
  background: rgba(254, 226, 226, 1);
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
