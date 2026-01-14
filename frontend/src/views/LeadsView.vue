<template>
  <DashboardLayout>
    <template #header-left>
      <h1>Лиды</h1>
    </template>

    <div class="leads-view">
      <div class="page-header">
        <div class="header-info">
          <h2>Управление лидами</h2>
          <p class="subtitle">Всего лидов: {{ leads.length }}</p>
        </div>
        <button @click="openCreateModal" class="btn-primary">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
          </svg>
          Добавить лид
        </button>
      </div>

    <div v-if="loading" class="loading">
      <div class="spinner"></div>
      <p>Загрузка...</p>
    </div>

    <div v-else class="kanban-board">
      <div
        v-for="status in statuses"
        :key="status.id"
        class="kanban-column"
      >
        <div class="column-header">
          <h3>{{ status.label }}</h3>
          <span class="count">{{ getLeadsByStatus(status.id).length }}</span>
        </div>

        <div class="column-content">
          <div
            v-for="lead in getLeadsByStatus(status.id)"
            :key="lead.id"
            class="lead-card"
            @click="openDetailsModal(lead)"
          >
            <div class="lead-header">
              <div class="lead-date">{{ formatDate(lead.date) }}</div>
              <div
                v-if="lead.contact_date"
                :class="['contact-date-badge', getContactDateClass(lead)]"
              >
                {{ formatDate(lead.contact_date) }}
              </div>
            </div>

            <div class="lead-body">
              <div v-if="lead.website" class="lead-website">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5a17.92 17.92 0 0 1-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418" />
                </svg>
                {{ lead.website }}
              </div>

              <div class="lead-contact">
                <span class="contact-type-badge">{{ lead.contact_type_label }}</span>
                <span class="contact-value">{{ lead.contact_value }}</span>
              </div>

              <div class="lead-statuses">
                <span :class="['status-badge', lead.audit_status === 'ready' ? 'ready' : 'not-ready']">
                  Аудит: {{ lead.audit_status_label }}
                </span>
                <span :class="['status-badge', lead.proposal_status === 'ready' ? 'ready' : 'not-ready']">
                  КП: {{ lead.proposal_status_label }}
                </span>
              </div>

              <div v-if="lead.price" class="lead-price">
                {{ formatPrice(lead.price) }}
              </div>

              <div v-if="lead.comment" class="lead-comment">
                {{ lead.comment }}
              </div>
            </div>

            <div class="lead-footer">
              <div class="lead-creator">
                {{ lead.creator.name }} {{ lead.creator.surname }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Модальное окно создания/редактирования -->
    <LeadModal
      v-if="showModal"
      :lead="selectedLead"
      @close="closeModal"
      @saved="handleSaved"
    />

    <!-- Модальное окно просмотра деталей -->
    <LeadDetailsModal
      v-if="showDetailsModal"
      :lead-id="selectedLeadId"
      @close="closeDetailsModal"
      @edit="handleEdit"
      @delete="handleDelete"
    />
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import DashboardLayout from '../layouts/DashboardLayout.vue'
import leadsApi from '../services/leads'
import LeadModal from './Leads/LeadModal.vue'
import LeadDetailsModal from './Leads/LeadDetailsModal.vue'

const leads = ref([])
const loading = ref(false)
const showModal = ref(false)
const showDetailsModal = ref(false)
const selectedLead = ref(null)
const selectedLeadId = ref(null)

const statuses = [
  { id: 1, label: 'Новый' },
  { id: 2, label: 'Ждем ответа' },
  { id: 3, label: 'Работаем' },
  { id: 4, label: 'Слился' },
  { id: 5, label: 'Холодные' }
]

const loadLeads = async () => {
  try {
    loading.value = true
    const response = await leadsApi.getLeads()
    if (response.success) {
      leads.value = response.leads
    }
  } catch (error) {
    console.error('Ошибка загрузки лидов:', error)
  } finally {
    loading.value = false
  }
}

const getLeadsByStatus = (statusId) => {
  return leads.value.filter(lead => lead.status === statusId)
}

const openCreateModal = () => {
  selectedLead.value = null
  showModal.value = true
}

const openDetailsModal = (lead) => {
  selectedLeadId.value = lead.id
  showDetailsModal.value = true
}

const closeModal = () => {
  showModal.value = false
  selectedLead.value = null
}

const closeDetailsModal = () => {
  showDetailsModal.value = false
  selectedLeadId.value = null
}

const handleSaved = () => {
  closeModal()
  loadLeads()
}

const handleEdit = (lead) => {
  closeDetailsModal()
  selectedLead.value = lead
  showModal.value = true
}

const handleDelete = () => {
  closeDetailsModal()
  loadLeads()
}

const formatDate = (timestamp) => {
  if (!timestamp) return ''
  const date = new Date(timestamp * 1000)
  return date.toLocaleDateString('ru-RU')
}

const formatPrice = (price) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0
  }).format(price)
}

const getContactDateClass = (lead) => {
  if (lead.contact_date_today) {
    return 'today'
  }
  if (lead.contact_date_expired) {
    return 'expired'
  }
  return 'future'
}

onMounted(() => {
  loadLeads()
})
</script>

<style scoped>
.leads-view {
  display: flex;
  flex-direction: column;
  height: 100%;
  min-height: 0;
  overflow: hidden;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  flex-shrink: 0;
}

.header-info h2 {
  margin: 0 0 0.25rem 0;
  font-size: 1.5rem;
  font-weight: 600;
}

.subtitle {
  margin: 0;
  color: #666;
  font-size: 0.9rem;
}

.btn-primary {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  background: #2d3748;
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 500;
  transition: background 0.2s;
}

.btn-primary svg {
  width: 20px;
  height: 20px;
}

.btn-primary:hover {
  background: #1a202c;
}

.loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem;
  color: #666;
}

.spinner {
  width: 48px;
  height: 48px;
  border: 4px solid #f3f3f3;
  border-top: 4px solid #2d3748;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.kanban-board {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 1.5rem;
  flex: 1;
  min-height: 0;
  overflow-x: auto;
}

.kanban-column {
  display: flex;
  flex-direction: column;
  background: #f9fafb;
  border-radius: 12px;
  padding: 1rem;
  min-height: 0;
  overflow: hidden;
}

.column-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
  padding-bottom: 0.75rem;
  border-bottom: 2px solid #e5e7eb;
}

.column-header h3 {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 600;
  color: #1f2937;
}

.count {
  background: #e5e7eb;
  color: #6b7280;
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.875rem;
  font-weight: 600;
}

.column-content {
  flex: 1;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.lead-card {
  background: white;
  border-radius: 8px;
  padding: 1rem;
  cursor: pointer;
  transition: all 0.2s;
  border: 1px solid #e5e7eb;
}

.lead-card:hover {
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  transform: translateY(-2px);
}

.lead-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
}

.lead-date {
  font-size: 0.875rem;
  color: #6b7280;
  font-weight: 500;
}

.contact-date-badge {
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 600;
}

.contact-date-badge.today {
  background: #d1fae5;
  color: #065f46;
}

.contact-date-badge.expired {
  background: #fee2e2;
  color: #991b1b;
}

.contact-date-badge.future {
  background: #e0e7ff;
  color: #4338ca;
}

.lead-body {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.lead-website {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  color: #2563eb;
  font-weight: 500;
}

.lead-website svg {
  width: 16px;
  height: 16px;
}

.lead-contact {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
}

.contact-type-badge {
  background: #dbeafe;
  color: #1e40af;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 600;
}

.contact-value {
  color: #374151;
}

.lead-statuses {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.status-badge {
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 600;
}

.status-badge.ready {
  background: #d1fae5;
  color: #065f46;
}

.status-badge.not-ready {
  background: #fee2e2;
  color: #991b1b;
}

.lead-price {
  font-size: 1rem;
  font-weight: 700;
  color: #059669;
  margin-top: 0.25rem;
}

.lead-comment {
  font-size: 0.875rem;
  color: #6b7280;
  font-style: italic;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.lead-footer {
  margin-top: 0.75rem;
  padding-top: 0.75rem;
  border-top: 1px solid #e5e7eb;
  font-size: 0.75rem;
  color: #9ca3af;
}

@media (max-width: 1400px) {
  .kanban-board {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 768px) {
  .kanban-board {
    grid-template-columns: 1fr;
  }
}
</style>
