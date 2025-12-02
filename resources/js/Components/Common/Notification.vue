<template>
  <Teleport to="body">
    <div class="fixed top-4 right-4 z-50 space-y-2">
      <TransitionGroup name="notification">
        <div
          v-for="notification in notifications"
          :key="notification.id"
          :class="getNotificationClasses(notification.type)"
          class="max-w-sm w-full p-4 rounded-lg shadow-lg border animate-fade-in"
        >
          <div class="flex items-start">
            <div class="flex-shrink-0">
              <component :is="getIconComponent(notification.type)" class="h-6 w-6" />
            </div>
            <div class="ml-3 w-0 flex-1">
              <p v-if="notification.title" class="text-sm font-medium">
                {{ notification.title }}
              </p>
              <p class="mt-1 text-sm">
                {{ notification.message }}
              </p>
            </div>
            <div class="ml-4 flex-shrink-0 flex">
              <button
                @click="removeNotification(notification.id)"
                class="inline-flex text-current hover:opacity-75 focus:outline-none focus:opacity-50"
              >
                <span class="sr-only">Tutup</span>
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

// Reactive state
const notifications = ref([]);

// Notification types and their configurations
const notificationConfig = {
  success: {
    classes: 'bg-green-50 border-green-200 text-green-800',
    icon: 'CheckCircleIcon'
  },
  error: {
    classes: 'bg-red-50 border-red-200 text-red-800',
    icon: 'XCircleIcon'
  },
  warning: {
    classes: 'bg-yellow-50 border-yellow-200 text-yellow-800',
    icon: 'ExclamationTriangleIcon'
  },
  info: {
    classes: 'bg-blue-50 border-blue-200 text-blue-800',
    icon: 'InformationCircleIcon'
  }
};

// Methods
const addNotification = (type, message, options = {}) => {
  const id = Date.now() + Math.random();
  const notification = {
    id,
    type,
    message,
    title: options.title,
    duration: options.duration || 5000,
    persistent: options.persistent || false
  };

  notifications.value.push(notification);

  // Auto-remove after duration (unless persistent)
  if (!notification.persistent && notification.duration > 0) {
    setTimeout(() => {
      removeNotification(id);
    }, notification.duration);
  }

  return id;
};

const removeNotification = (id) => {
  const index = notifications.value.findIndex(n => n.id === id);
  if (index > -1) {
    notifications.value.splice(index, 1);
  }
};

const clearAllNotifications = () => {
  notifications.value = [];
};

const getNotificationClasses = (type) => {
  return notificationConfig[type]?.classes || notificationConfig.info.classes;
};

const getIconComponent = (type) => {
  // For now, return a simple div since we're not using Heroicons
  // In a real implementation, you'd import the appropriate icon component
  return 'div';
};

// Global event listeners
const handleShowNotification = (event) => {
  const { type, message, duration, title } = event.detail;
  addNotification(type, message, { title, duration });
};

const handleAuthUnauthorized = () => {
  addNotification('error', 'Sesi Anda telah berakhir. Silakan login kembali.', {
    title: 'Sesi Berakhir',
    duration: 5000
  });
};

const handleServerError = (event) => {
  addNotification('error', event.detail.message, {
    title: 'Kesalahan Server',
    duration: 5000
  });
};

const handleNetworkError = (event) => {
  addNotification('warning', event.detail.message, {
    title: 'Kesalahan Koneksi',
    duration: 5000
  });
};

const handleTimeoutError = (event) => {
  addNotification('warning', event.detail.message, {
    title: 'Waktu Habis',
    duration: 5000
  });
};

// Lifecycle hooks
onMounted(() => {
  // Listen for global notification events
  window.addEventListener('show-notification', handleShowNotification);
  window.addEventListener('auth:unauthorized', handleAuthUnauthorized);
  window.addEventListener('server:error', handleServerError);
  window.addEventListener('network:error', handleNetworkError);
  window.addEventListener('timeout:error', handleTimeoutError);

  // Make notification functions globally available
  window.$notify = {
    success: (message, options) => addNotification('success', message, options),
    error: (message, options) => addNotification('error', message, options),
    warning: (message, options) => addNotification('warning', message, options),
    info: (message, options) => addNotification('info', message, options),
    remove: removeNotification,
    clear: clearAllNotifications
  };
});

onUnmounted(() => {
  // Clean up event listeners
  window.removeEventListener('show-notification', handleShowNotification);
  window.removeEventListener('auth:unauthorized', handleAuthUnauthorized);
  window.removeEventListener('server:error', handleServerError);
  window.removeEventListener('network:error', handleNetworkError);
  window.removeEventListener('timeout:error', handleTimeoutError);
});

// Expose methods for external use
defineExpose({
  addNotification,
  removeNotification,
  clearAllNotifications
});
</script>

<style scoped>
/* Notification animations */
.notification-enter-active,
.notification-leave-active {
  transition: all 0.3s ease;
}

.notification-enter-from {
  opacity: 0;
  transform: translateX(100%);
}

.notification-leave-to {
  opacity: 0;
  transform: translateX(100%);
}

/* Custom scrollbar for notifications if needed */
::-webkit-scrollbar {
  width: 4px;
}

::-webkit-scrollbar-track {
  background: transparent;
}

::-webkit-scrollbar-thumb {
  background: rgba(0, 0, 0, 0.2);
  border-radius: 2px;
}
</style>