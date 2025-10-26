<template>
  <div class="modal-overlay" @click.self="$emit('cancel')">
    <div class="modal confirm-modal">
      <div class="modal-icon">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
        </svg>
      </div>

      <div class="modal-content">
        <h2>{{ title }}</h2>
        <p>{{ message }}</p>
      </div>

      <div class="modal-actions">
        <button class="btn-cancel" @click="$emit('cancel')">
          Отмена
        </button>
        <button class="btn-confirm" @click="$emit('confirm')">
          {{ confirmText }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: {
    type: String,
    default: 'Подтвердите действие'
  },
  message: {
    type: String,
    required: true
  },
  confirmText: {
    type: String,
    default: 'Подтвердить'
  }
})

defineEmits(['confirm', 'cancel'])
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
  z-index: 2000;
  animation: fadeIn 0.2s ease;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.confirm-modal {
  background: white;
  border-radius: 12px;
  width: 90%;
  max-width: 450px;
  padding: 2rem;
  text-align: center;
  animation: slideUp 0.3s ease;
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

.modal-icon {
  width: 64px;
  height: 64px;
  margin: 0 auto 1.5rem;
  background: #fef2f2;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.modal-icon svg {
  width: 36px;
  height: 36px;
  color: #dc2626;
}

.modal-content h2 {
  margin: 0 0 1rem 0;
  font-size: 1.25rem;
  font-weight: 600;
  color: #1a1a1a;
}

.modal-content p {
  margin: 0 0 2rem 0;
  font-size: 0.95rem;
  color: #666;
  line-height: 1.5;
}

.modal-actions {
  display: flex;
  gap: 1rem;
  justify-content: center;
}

.btn-cancel,
.btn-confirm {
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-size: 0.95rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  min-width: 120px;
}

.btn-cancel {
  background: white;
  border: 2px solid #e0e0e0;
  color: #666;
}

.btn-cancel:hover {
  border-color: #2d3748;
  background: #fafafa;
}

.btn-confirm {
  background: #dc2626;
  border: 2px solid #dc2626;
  color: white;
}

.btn-confirm:hover {
  background: #b91c1c;
  border-color: #b91c1c;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
}

@media (max-width: 768px) {
  .confirm-modal {
    width: 95%;
    padding: 1.5rem;
  }

  .modal-actions {
    flex-direction: column;
  }

  .btn-cancel,
  .btn-confirm {
    width: 100%;
  }
}
</style>
