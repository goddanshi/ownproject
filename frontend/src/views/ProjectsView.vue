<template>
  <DashboardLayout>
    <template #header-left>
      <h1>Проекты</h1>
    </template>

    <div class="projects-page">
      <!-- Хедер с кнопкой создания -->
      <div class="page-header">
        <div class="header-info">
          <h2>Управление проектами</h2>
          <p class="subtitle">Всего проектов: {{ projects.length }}</p>
        </div>
        <button
          class="btn-primary"
          @click="openCreateModal"
        >
          + Создать проект
        </button>
      </div>

      <!-- Загрузка -->
      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Загрузка проектов...</p>
      </div>

      <!-- Список проектов -->
      <div v-else-if="projects.length > 0" class="projects-grid">
        <div
          v-for="project in projects"
          :key="project.id"
          class="project-card"
          @click="openProjectDetails(project.id)"
        >
          <div class="project-header">
            <h3>{{ project.name }}</h3>
            <span class="badge">{{ project.tasks_count }} задач</span>
          </div>

          <p class="project-description">
            {{ project.description || 'Описание отсутствует' }}
          </p>

          <div class="project-footer">
            <div class="team-info">
              <span class="label">Команда:</span>
              <span class="team-name">{{ project.team.name }}</span>
            </div>
            <div class="participants-info">
              <span class="icon">👥</span>
              <span>{{ project.participants_count }} участников</span>
            </div>
          </div>

          <div class="project-actions">
            <button
              v-if="canManageProject(project)"
              class="btn-icon"
              @click.stop="openEditModal(project)"
              title="Редактировать"
            >
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
              </svg>
            </button>
            <button
              v-if="canDeleteProject(project)"
              class="btn-icon danger"
              @click.stop="confirmDelete(project)"
              title="Удалить"
            >
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Пустое состояние -->
      <div v-else class="empty-state">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" />
        </svg>
        <h3>У вас пока нет проектов</h3>
        <p>Создайте первый проект для начала работы</p>
        <button class="btn-primary" @click="openCreateModal">
          Создать проект
        </button>
      </div>

      <!-- Модалка создания/редактирования -->
      <ProjectModal
        v-if="showModal"
        :project="selectedProject"
        @close="closeModal"
        @saved="handleProjectSaved"
      />

      <!-- Модалка деталей проекта -->
      <ProjectDetailsModal
        v-if="showDetailsModal"
        :projectId="selectedProjectId"
        @close="closeDetailsModal"
        @updated="loadProjects"
      />

      <!-- Модалка подтверждения удаления -->
      <ConfirmModal
        v-if="showDeleteModal"
        title="Удалить проект?"
        :message="deleteMessage"
        @confirm="handleDelete"
        @cancel="closeDeleteModal"
      />
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import DashboardLayout from '../layouts/DashboardLayout.vue'
import ProjectModal from './Projects/ProjectModal.vue'
import ProjectDetailsModal from './Projects/ProjectDetailsModal.vue'
import ConfirmModal from '../components/ConfirmModal.vue'
import projectsApi from '../services/projects'
import { useAuthStore } from '../stores/auth'

const authStore = useAuthStore()

const projects = ref([])
const loading = ref(true)
const showModal = ref(false)
const showDetailsModal = ref(false)
const showDeleteModal = ref(false)
const selectedProject = ref(null)
const selectedProjectId = ref(null)
const projectToDelete = ref(null)

// Computed для сообщения об удалении
const deleteMessage = computed(() =>
  `Вы уверены, что хотите удалить проект "${projectToDelete.value?.name}"? Все задачи проекта также будут удалены. Это действие нельзя отменить.`
)

// Загрузка проектов
const loadProjects = async () => {
  try {
    loading.value = true
    const response = await projectsApi.getProjects()
    if (response.success) {
      projects.value = response.projects
    }
  } catch (error) {
    console.error('Ошибка загрузки проектов:', error)
  } finally {
    loading.value = false
  }
}

// Модалки
const openCreateModal = () => {
  selectedProject.value = null
  showModal.value = true
}

const openEditModal = (project) => {
  selectedProject.value = project
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  selectedProject.value = null
}

const openProjectDetails = (projectId) => {
  selectedProjectId.value = projectId
  showDetailsModal.value = true
}

const closeDetailsModal = () => {
  showDetailsModal.value = false
  selectedProjectId.value = null
}

const handleProjectSaved = () => {
  closeModal()
  loadProjects()
}

// Удаление
const confirmDelete = (project) => {
  projectToDelete.value = project
  showDeleteModal.value = true
}

const closeDeleteModal = () => {
  showDeleteModal.value = false
  projectToDelete.value = null
}

const handleDelete = async () => {
  try {
    const response = await projectsApi.deleteProject(projectToDelete.value.id)
    if (response.success) {
      await loadProjects()
      closeDeleteModal()
    }
  } catch (error) {
    console.error('Ошибка удаления проекта:', error)
  }
}

// Проверка прав
const canManageProject = (project) => {
  // Админ может управлять всеми
  if (authStore.user?.role === 1) return true

  // Тимлид может управлять проектами своих команд
  // (проверка через team будет в деталях проекта)
  return authStore.user?.role === 2
}

const canDeleteProject = (project) => {
  // Только админ может удалять проекты
  return authStore.user?.role === 1
}

onMounted(() => {
  loadProjects()
})
</script>

<style scoped>
.projects-page {
  background: white;
  padding: 2rem;
  border-radius: 15px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 2rem;
  gap: 2rem;
  flex-wrap: wrap;
}

.header-info h2 {
  margin: 0 0 0.5rem 0;
  color: #1a1a1a;
}

.subtitle {
  color: #666;
  font-size: 0.9rem;
}

.btn-primary {
  padding: 0.75rem 1.5rem;
  background: #2d3748;
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 500;
  transition: background 0.2s;
}

.btn-primary:hover {
  background: #1a202c;
}

.loading-state {
  text-align: center;
  padding: 4rem 2rem;
  color: #666;
}

.spinner {
  width: 48px;
  height: 48px;
  border: 4px solid #f3f3f3;
  border-top: 4px solid #2d3748;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.projects-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 1.5rem;
}

.project-card {
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 12px;
  padding: 1.5rem;
  cursor: pointer;
  transition: all 0.2s;
  position: relative;
}

.project-card:hover {
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  transform: translateY(-2px);
}

.project-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
  margin-bottom: 1rem;
}

.project-header h3 {
  margin: 0;
  font-size: 1.25rem;
  color: #1a1a1a;
  flex: 1;
}

.badge {
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 500;
  background: #e0e7ff;
  color: #4338ca;
  white-space: nowrap;
}

.project-description {
  color: #666;
  font-size: 0.9rem;
  margin-bottom: 1.5rem;
  line-height: 1.5;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}

.project-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 1rem;
  border-top: 1px solid #f0f0f0;
  margin-bottom: 1rem;
}

.team-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.label {
  font-size: 0.75rem;
  color: #999;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.team-name {
  font-weight: 500;
  color: #2d3748;
}

.participants-info {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.85rem;
  color: #666;
}

.icon {
  font-size: 1.2rem;
}

.project-actions {
  display: flex;
  gap: 0.5rem;
  justify-content: flex-end;
}

.btn-icon {
  width: 36px;
  height: 36px;
  border-radius: 6px;
  border: 1px solid #e0e0e0;
  background: white;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.btn-icon:hover {
  background: #f5f5f7;
  border-color: #2d3748;
}

.btn-icon.danger:hover {
  background: #fee2e2;
  border-color: #dc2626;
}

.btn-icon svg {
  width: 20px;
  height: 20px;
  color: #666;
}

.btn-icon.danger svg {
  color: #dc2626;
}

.empty-state {
  text-align: center;
  padding: 4rem 2rem;
}

.empty-state svg {
  width: 80px;
  height: 80px;
  margin: 0 auto 1.5rem;
  color: #ccc;
}

.empty-state h3 {
  margin: 0 0 0.5rem 0;
  color: #1a1a1a;
}

.empty-state p {
  color: #666;
  margin: 0 0 2rem 0;
}

@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    align-items: stretch;
  }

  .projects-grid {
    grid-template-columns: 1fr;
  }
}
</style>
