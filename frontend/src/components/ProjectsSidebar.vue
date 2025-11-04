<template>
  <div>
    <transition name="slide-sidebar">
      <aside v-if="isOpen" :class="['projects-sidebar', { collapsed: isCollapsed }]" :style="{ left: sidebarLeftPosition }">
        <button class="close-btn" @click="closeSidebar" title="Закрыть">
          ×
        </button>

        <div class="sidebar-header">
          <transition name="fade">
            <span v-if="!isCollapsed" class="header-text">Проекты</span>
            <span v-else class="header-short">П</span>
          </transition>
        </div>

        <!-- Поисковая строка с кнопками создания -->
        <div v-if="!isCollapsed" class="search-container">
          <input
            type="text"
            v-model="searchQuery"
            placeholder="Поиск..."
            class="search-input"
          />
          <button class="add-btn" @click="openCreateFolderModal" title="Создать папку">
            📁+
          </button>
          <button class="add-btn" @click="openCreateProjectModal" title="Создать проект">
            📋+
          </button>
        </div>

        <!-- Список проектов -->
        <div class="projects-list">
          <div v-if="loading" class="loading">
            <div class="spinner-small"></div>
          </div>

          <template v-else>
            <!-- Дерево папок и проектов -->
            <TreeNode
              v-for="item in filteredTree"
              :key="item.type + '-' + item.id"
              :node="item"
              :level="0"
              :collapsed="isCollapsed"
              @toggle="toggleNode"
              @edit-folder="editFolder"
              @delete-folder="deleteFolder"
              @edit-project="editProject"
              @delete-project="deleteProject"
              @add-subfolder="addSubfolder"
              @add-project-to-folder="addProjectToFolder"
            />

            <!-- Проекты без папки (корневые) -->
            <TreeNode
              v-for="project in rootProjects"
              :key="'project-' + project.id"
              :node="project"
              :level="0"
              :collapsed="isCollapsed"
              @edit-project="editProject"
              @delete-project="deleteProject"
            />

            <!-- Пустое состояние -->
            <div v-if="filteredTree.length === 0 && rootProjects.length === 0 && !isCollapsed" class="empty-state">
              <p>{{ searchQuery ? 'Ничего не найдено' : 'Нет папок и проектов' }}</p>
            </div>
          </template>
        </div>
      </aside>
    </transition>

    <!-- Модалка создания/редактирования папки -->
    <FolderModal
      v-if="showFolderModal"
      :folder="editingFolder"
      :parent-id="selectedParentId"
      @close="closeFolderModal"
      @saved="handleFolderSaved"
    />

    <!-- Модалка создания/редактирования проекта -->
    <ProjectModal
      v-if="showProjectModal"
      :project="editingProject"
      :folder-id="selectedFolderId"
      @close="closeProjectModal"
      @saved="handleProjectSaved"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import projectsApi from '../services/projects'
import foldersApi from '../services/folders'
import FolderModal from './FolderModal.vue'
import ProjectModal from './ProjectModal.vue'
import TreeNode from './TreeNode.vue'

const isOpen = ref(localStorage.getItem('projects-sidebar-open') === 'true')
const mainSidebarCollapsed = ref(localStorage.getItem('sidebar-collapsed') === 'true')
const isCollapsed = ref(false)
const tree = ref([])
const projects = ref([])
const loading = ref(true)
const searchQuery = ref('')
const showFolderModal = ref(false)
const showProjectModal = ref(false)
const editingFolder = ref(null)
const editingProject = ref(null)
const selectedParentId = ref(null)
const selectedFolderId = ref(null)
const expandedNodes = ref(new Set())

const sidebarLeftPosition = computed(() => {
  return mainSidebarCollapsed.value ? '80px' : '280px'
})

// Корневые проекты (без папки)
const rootProjects = computed(() => {
  return projects.value.filter(p => !p.folder_id).map(p => ({
    ...p,
    type: 'project'
  }))
})

// Фильтрация дерева по поисковому запросу
const filteredTree = computed(() => {
  if (!searchQuery.value) {
    return tree.value
  }

  const query = searchQuery.value.toLowerCase()
  return filterTreeNodes(tree.value, query)
})

const filterTreeNodes = (nodes, query) => {
  return nodes.reduce((acc, node) => {
    const matchesName = node.name.toLowerCase().includes(query)
    const matchesDesc = node.description && node.description.toLowerCase().includes(query)

    if (node.children && node.children.length > 0) {
      const filteredChildren = filterTreeNodes(node.children, query)
      if (matchesName || matchesDesc || filteredChildren.length > 0) {
        acc.push({
          ...node,
          children: filteredChildren
        })
      }
    } else if (matchesName || matchesDesc) {
      acc.push(node)
    }

    return acc
  }, [])
}

const closeSidebar = () => {
  isOpen.value = false
  localStorage.setItem('projects-sidebar-open', 'false')
}

const openCreateFolderModal = () => {
  editingFolder.value = null
  selectedParentId.value = null
  showFolderModal.value = true
}

const openCreateProjectModal = () => {
  editingProject.value = null
  selectedFolderId.value = null
  showProjectModal.value = true
}

const closeFolderModal = () => {
  showFolderModal.value = false
  editingFolder.value = null
  selectedParentId.value = null
}

const closeProjectModal = () => {
  showProjectModal.value = false
  editingProject.value = null
  selectedFolderId.value = null
}

const handleFolderSaved = () => {
  loadData()
}

const handleProjectSaved = () => {
  loadData()
}

const toggleNode = (nodeId) => {
  if (expandedNodes.value.has(nodeId)) {
    expandedNodes.value.delete(nodeId)
  } else {
    expandedNodes.value.add(nodeId)
  }
}

const editFolder = (folder) => {
  editingFolder.value = folder
  showFolderModal.value = true
}

const deleteFolder = async (folderId) => {
  if (!confirm('Удалить папку? Все вложенные папки и проекты также будут удалены.')) {
    return
  }

  try {
    const response = await foldersApi.deleteFolder(folderId)
    if (response.success) {
      loadData()
    }
  } catch (error) {
    console.error('Ошибка удаления папки:', error)
  }
}

const editProject = (project) => {
  editingProject.value = project
  showProjectModal.value = true
}

const deleteProject = async (projectId) => {
  if (!confirm('Удалить проект? Все задачи также будут удалены.')) {
    return
  }

  try {
    const response = await projectsApi.deleteProject(projectId)
    if (response.success) {
      loadData()
    }
  } catch (error) {
    console.error('Ошибка удаления проекта:', error)
  }
}

const addSubfolder = (parentId) => {
  editingFolder.value = null
  selectedParentId.value = parentId
  showFolderModal.value = true
}

const addProjectToFolder = (folderId) => {
  editingProject.value = null
  selectedFolderId.value = folderId
  showProjectModal.value = true
}

const loadData = async () => {
  try {
    loading.value = true

    // Загружаем дерево папок и проекты параллельно
    const [foldersResponse, projectsResponse] = await Promise.all([
      foldersApi.getFolders(),
      projectsApi.getProjects()
    ])

    if (foldersResponse.success) {
      tree.value = foldersResponse.tree || []
    }

    if (projectsResponse.success) {
      projects.value = projectsResponse.projects || []
    }
  } catch (error) {
    console.error('Ошибка загрузки данных:', error)
  } finally {
    loading.value = false
  }
}

// Слушаем изменения в localStorage
const checkSidebarState = () => {
  const state = localStorage.getItem('projects-sidebar-open')
  isOpen.value = state === 'true'
  const mainSidebarState = localStorage.getItem('sidebar-collapsed')
  mainSidebarCollapsed.value = mainSidebarState === 'true'
}

watch(isOpen, (newValue) => {
  if (newValue) {
    loadData()
  }
})

onMounted(() => {
  checkSidebarState()
  if (isOpen.value) {
    loadData()
  }

  // Слушаем изменения в localStorage
  const interval = setInterval(checkSidebarState, 100)

  return () => clearInterval(interval)
})
</script>

<style scoped>
.projects-sidebar {
  background: white;
  border-right: 1px solid #e0e0e0;
  position: fixed;
  left: 280px;
  top: 0;
  height: 100vh;
  width: 320px;
  padding: 2rem 1rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
  transition: width 0.3s ease, left 0.3s ease;
  z-index: 997;
  overflow-y: auto;
  box-shadow: 2px 0 8px rgba(0, 0, 0, 0.1);
}

.projects-sidebar.collapsed {
  width: 80px;
}

.close-btn {
  position: absolute;
  right: 0.5rem;
  top: 0.5rem;
  width: 32px;
  height: 32px;
  border-radius: 6px;
  background: transparent;
  border: none;
  color: #999;
  font-size: 2rem;
  line-height: 1;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}

.close-btn:hover {
  background: #f5f5f7;
  color: #2d3748;
}

.sidebar-header {
  text-align: center;
  padding: 1rem;
  border-bottom: 1px solid #e0e0e0;
  margin-bottom: 0.5rem;
}

.header-text {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1a1a1a;
}

.header-short {
  font-size: 1rem;
  font-weight: 600;
  color: #1a1a1a;
}

.search-container {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  width: 100%;
}

.search-input {
  flex: 1;
  padding: 0.75rem 1rem;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  font-size: 0.9rem;
  outline: none;
  transition: all 0.2s ease;
  background: #f9fafb;
}

.search-input:focus {
  border-color: #2d3748;
  background: white;
  box-shadow: 0 0 0 3px rgba(45, 55, 72, 0.1);
}

.search-input::placeholder {
  color: #9ca3af;
}

.add-btn {
  width: 40px;
  height: 40px;
  min-width: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #2d3748;
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s ease;
  padding: 0;
  font-size: 1rem;
}

.add-btn:hover {
  background: #1a202c;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(45, 55, 72, 0.3);
}

.projects-list {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  overflow-y: auto;
}

.loading {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 2rem;
}

.spinner-small {
  width: 24px;
  height: 24px;
  border: 3px solid #f3f3f3;
  border-top: 3px solid #2d3748;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.divider {
  border: none;
  border-top: 1px solid #e0e0e0;
  margin: 0.5rem 0;
}

.project-item {
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

.projects-sidebar.collapsed .project-item {
  justify-content: center;
  padding: 1rem 0.5rem;
}

.project-item:hover {
  background: #f5f5f7;
}

.project-item.active,
.project-item.all-projects.active {
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

.icon svg {
  width: 24px;
  height: 24px;
  stroke: currentColor;
}

.project-icon {
  width: 32px;
  height: 32px;
  border-radius: 6px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  font-size: 0.9rem;
  font-weight: 600;
}

.folder-icon {
  font-size: 1.5rem;
}

.folder-desc {
  font-size: 0.7rem;
  color: #999;
  font-weight: 400;
}

.project-item.active .project-icon {
  background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.project-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  flex: 1;
  min-width: 0;
}

.label {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  font-size: 0.9rem;
}

.tasks-count {
  font-size: 0.75rem;
  color: #999;
  font-weight: 400;
}

.project-item.active .tasks-count {
  color: rgba(255, 255, 255, 0.8);
}

.empty-state {
  padding: 2rem 1rem;
  text-align: center;
  color: #999;
  font-size: 0.85rem;
  font-style: italic;
}

.fade-enter-active, .fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from, .fade-leave-to {
  opacity: 0;
}

/* Анимация появления/скрытия сайдбара */
.slide-sidebar-enter-active,
.slide-sidebar-leave-active {
  transition: all 0.3s ease;
}

.slide-sidebar-enter-from {
  transform: translateX(-100%);
  opacity: 0;
}

.slide-sidebar-leave-to {
  transform: translateX(-100%);
  opacity: 0;
}

/* Скрываем скроллбар, но оставляем функциональность */
.projects-sidebar::-webkit-scrollbar {
  width: 6px;
}

.projects-sidebar::-webkit-scrollbar-track {
  background: transparent;
}

.projects-sidebar::-webkit-scrollbar-thumb {
  background: #e0e0e0;
  border-radius: 3px;
}

.projects-sidebar::-webkit-scrollbar-thumb:hover {
  background: #c0c0c0;
}

/* Адаптация под свернутый основной сайдбар */
@media (max-width: 768px) {
  .projects-sidebar {
    left: 0;
    width: 280px;
    z-index: 1000;
  }
}
</style>
