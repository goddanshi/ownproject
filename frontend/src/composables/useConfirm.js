import { ref } from 'vue'

const confirmDialog = ref(null)
const resolvePromise = ref(null)
const rejectPromise = ref(null)

export function useConfirm() {
  const setDialogRef = (dialog) => {
    confirmDialog.value = dialog
  }

  const confirm = (options = {}) => {
    return new Promise((resolve, reject) => {
      if (!confirmDialog.value) {
        console.error('ConfirmDialog не инициализирован')
        reject(new Error('ConfirmDialog не инициализирован'))
        return
      }

      resolvePromise.value = resolve
      rejectPromise.value = reject

      // Настройки по умолчанию
      const defaultOptions = {
        title: 'Подтверждение',
        message: 'Вы уверены?',
        confirmText: 'Подтвердить',
        cancelText: 'Отмена',
        type: 'danger'
      }

      // Если передана строка, используем её как message
      const finalOptions = typeof options === 'string'
        ? { ...defaultOptions, message: options }
        : { ...defaultOptions, ...options }

      // Устанавливаем параметры и открываем диалог
      Object.assign(confirmDialog.value.$props, finalOptions)
      confirmDialog.value.open()
    })
  }

  const handleConfirm = () => {
    if (resolvePromise.value) {
      resolvePromise.value(true)
      resolvePromise.value = null
      rejectPromise.value = null
    }
  }

  const handleCancel = () => {
    if (rejectPromise.value) {
      rejectPromise.value(false)
      resolvePromise.value = null
      rejectPromise.value = null
    }
  }

  return {
    confirm,
    setDialogRef,
    handleConfirm,
    handleCancel
  }
}
