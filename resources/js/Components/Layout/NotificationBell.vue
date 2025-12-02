<template>
  <div class="relative" ref="notificationDropdown">
    <!-- Bell Icon Button -->
    <button
      @click="showNotifications = !showNotifications"
      class="relative p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition focus:outline-none"
    >
      <span class="text-2xl">🔔</span>
      
      <!-- Badge -->
      <span
        v-if="notificationsUnreadCount > 0"
        class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full animate-pulse"
      >
        {{ notificationsUnreadCount > 99 ? '99+' : notificationsUnreadCount }}
      </span>

      <!-- Polling Status Indicator -->
      <div
        v-if="showNotifications"
        :class="[
          'absolute -bottom-1 -right-1 w-3 h-3 rounded-full border-2 border-white',
          polling.isPolling ? 'bg-green-500' : polling.hasError ? 'bg-red-500' : 'bg-gray-400'
        ]"
        :title="pollingStatusTooltip"
      ></div>
    </button>

    <!-- Notifications Dropdown -->
    <Transition name="dropdown">
      <div
        v-if="showNotifications"
        class="absolute right-0 mt-2 w-96 bg-white rounded-lg shadow-xl border border-gray-200 overflow-hidden z-50"
      >
        <!-- Header -->
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 bg-gray-50">
          <div class="flex items-center space-x-2">
            <h3 class="text-sm font-semibold text-gray-900">Notifications</h3>
            <div
              :class="[
                'w-2 h-2 rounded-full',
                polling.isPolling ? 'bg-green-500' : polling.hasError ? 'bg-red-500' : 'bg-gray-400'
              ]"
              :title="pollingStatusTooltip"
            ></div>
            <span v-if="polling.timeSinceLastUpdate" class="text-xs text-gray-500">
              {{ polling.timeSinceLastUpdate }}
            </span>
          </div>
        </div>

        <!-- Notifications List -->
        <div class="max-h-96 overflow-y-auto">
          <div v-if="allNotifications.length === 0" class="px-4 py-12 text-center">
            <!-- Modern Empty State -->
            <div class="relative mb-6">
              <div class="absolute inset-0 bg-gradient-to-br from-indigo-100 to-purple-100 rounded-full opacity-50 blur-xl transform scale-150"></div>
              <div class="relative bg-white w-24 h-24 mx-auto rounded-full shadow-lg flex items-center justify-center">
                <svg class="w-12 h-12 text-indigo-500 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
                <div class="absolute top-0 right-0 w-6 h-6 bg-green-400 border-4 border-white rounded-full"></div>
              </div>
            </div>
            
            <h3 class="text-base font-semibold text-gray-900 mb-1">No New Notifications</h3>
            <p class="text-sm text-gray-500 mb-6 max-w-[200px] mx-auto">
              You're all caught up! We'll notify you when something important happens.
            </p>

            <!-- Status Badge -->
            <div class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-50 border border-gray-200 text-gray-600">
              <span v-if="polling.isPolling" class="flex items-center">
                <span class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"></span>
                Live updates active
              </span>
              <span v-else-if="polling.hasError" class="flex items-center text-red-600">
                <span class="w-2 h-2 bg-red-500 rounded-full mr-2"></span>
                Connection interrupted
              </span>
              <span v-else class="flex items-center">
                <span class="w-2 h-2 bg-gray-400 rounded-full mr-2"></span>
                Updates paused
              </span>
            </div>

            <!-- Action buttons -->
            <div class="mt-6 flex justify-center space-x-3" v-if="!polling.isPolling && polling.hasError">
              <button
                @click="polling.retry"
                class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200"
              >
                <svg class="w-3 h-3 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.001 0 01-15.357-2m15.357 2H15" />
                </svg>
                Retry Connection
              </button>
            </div>
            
            <!-- View History Link -->
            <div class="mt-4">
              <button
                @click="$emit('view-all')"
                class="text-xs font-medium text-indigo-600 hover:text-indigo-800 transition-colors duration-200 group inline-flex items-center"
              >
                View notification history
                <svg class="w-3 h-3 ml-1 transform group-hover:translate-x-0.5 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
              </button>
            </div>

            <!-- Error details (Hidden for cleaner UI) -->
            <div v-if="false && polling.hasError && polling.error" class="mt-4 p-2 bg-red-50 rounded border border-red-100 text-left">
              <p class="text-[10px] text-red-600 font-mono break-all">{{ polling.error || 'Unknown error occurred' }}</p>
            </div>
          </div>

          <div
            v-for="notification in allNotifications"
            :key="`${notification.id}-${notification.source}`"
            @click="handleNotificationClick(notification)"
            :class="[
              'px-4 py-3 border-b border-gray-100 cursor-pointer transition relative',
              notification.is_read || notification.read ? 'bg-white hover:bg-gray-50' : 'bg-blue-50 hover:bg-blue-100'
            ]"
          >
            <!-- Notification indicator -->
            <div
              v-if="notification.source === 'polling'"
              class="absolute top-2 right-2 w-2 h-2 bg-blue-500 rounded-full"
              title="Polling notification"
            ></div>

            <div class="flex items-start space-x-3">
              <!-- Icon -->
              <div class="flex-shrink-0">
                <div :class="[
                  'w-10 h-10 rounded-full flex items-center justify-center',
                  getNotificationColor(notification.type)
                ]">
                  <span class="text-lg">{{ getNotificationIcon(notification.type) }}</span>
                </div>
              </div>

              <!-- Content -->
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 line-clamp-1">{{ notification.title }}</p>
                <p class="text-sm text-gray-600 mt-1 line-clamp-2">{{ notification.message }}</p>

                <!-- Priority indicator -->
                <div v-if="notification.priority" class="flex items-center space-x-2 mt-1">
                  <span :class="[
                    'text-xs px-2 py-1 rounded-full font-medium',
                    getPriorityClass(notification.priority)
                  ]">
                    {{ notification.priority_label || notification.priority }}
                  </span>
                  <p class="text-xs text-gray-500">{{ formatTime(notification.created_at || notification.timestamp) }}</p>
                </div>

                <!-- Action info -->
                <div v-if="notification.data && notification.data.ticket_number" class="flex items-center space-x-2 mt-1">
                  <span class="text-xs text-gray-500">
                    Ticket: {{ notification.data.ticket_number }}
                  </span>
                  <span v-if="notification.data.user_name" class="text-xs text-gray-500">
                    by {{ notification.data.user_name }}
                  </span>
                </div>

                <!-- Source indicator -->
                <div class="flex items-center space-x-2 mt-1">
                  <p class="text-xs text-gray-500">{{ formatTime(notification.created_at || notification.timestamp) }}</p>
                  <span
                    v-if="notification.source === 'polling'"
                    class="text-xs text-blue-600 font-medium"
                  >
                    Live
                  </span>
                  <span
                    v-if="notification.triggered_by && notification.triggered_by.name"
                    class="text-xs text-gray-500"
                  >
                    {{ notification.triggered_by.name }}
                  </span>
                </div>
              </div>

              <!-- Unread Indicator -->
              <div v-if="!notification.is_read && !notification.read" class="flex-shrink-0">
                <div class="w-2 h-2 bg-blue-600 rounded-full"></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">
          <!-- Quick actions -->
          <div class="flex items-center mb-2">
            <button
              @click="$emit('view-all')"
              class="text-xs text-indigo-600 hover:text-indigo-800 font-medium"
            >
              View all →
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { useNotificationPolling } from '../../composables/usePolling.js';
import { useDateFormatter } from '@/composables/useDateFormatter';

const emit = defineEmits(['view-all', 'notification-clicked']);

// Use centralized date formatter
const { formatTimeAgo } = useDateFormatter();

const showNotifications = ref(false);
const notificationDropdown = ref(null);

// Initialize polling-based notifications
const polling = useNotificationPolling({
  endpoint: '/api/notifications',
  interval: 10000,
  autoStart: true
});

// Extract polling data with fallback to empty arrays
const allNotifications = computed(() => {
  const data = polling.data.value;
  if (!data) {
    console.log('NotificationBell: No polling data available');
    return [];
  }

  // Try multiple paths to get notifications
  const notifications = data.data?.notifications ||
                       data.notifications ||
                       [];

  const result = notifications
    .map(n => ({ ...n, source: 'polling' }))
    .sort((a, b) => new Date(b.created_at || b.timestamp) - new Date(a.created_at || b.timestamp))
    .slice(0, 20); // Show only latest 20

  console.log(`NotificationBell: Found ${result.length} notifications`);
  return result;
});

const notificationsUnreadCount = computed(() => {
  // Try multiple paths to get the unread count
  const data = polling.data.value;
  if (!data) {
    console.log('NotificationBell: No data for unread count');
    return 0;
  }

  const count = data.data?.unread_count ||
                data.unread_count ||
                data.unreadCount ||
                data.data?.unreadCount ||
                0;

  console.log(`NotificationBell: Unread count: ${count}`);
  return count;
});

// Polling status tooltip
const pollingStatusTooltip = computed(() => {
  if (polling.hasError.value) {
    return `Connection error: ${polling.error.value || 'Unknown error'}`;
  }
  if (polling.isPolling.value) {
    return `Polling active - Updated ${polling.timeSinceLastUpdate.value || 'just now'}`;
  }
  if (polling.isLoading.value) {
    return 'Loading notifications...';
  }
  return 'Notifications paused';
});

const getNotificationIcon = (type) => {
  const icons = {
    'ticket_created': '📝',
    'ticket_assigned': '👤',
    'status_updated': '🔄',
    'comment_added': '💬',
    'ticket_resolved': '✅',
    'ticket_urgent': '🚨',
    'rating_received': '⭐'
  };
  return icons[type] || '📢';
};

const getNotificationColor = (type) => {
  const colors = {
    'ticket_created': 'bg-blue-100',
    'ticket_assigned': 'bg-indigo-100',
    'status_updated': 'bg-yellow-100',
    'comment_added': 'bg-green-100',
    'ticket_resolved': 'bg-green-100',
    'ticket_urgent': 'bg-red-100',
    'rating_received': 'bg-purple-100'
  };
  return colors[type] || 'bg-gray-100';
};

// Use formatTimeAgo from centralized date formatter
const formatTime = formatTimeAgo;

const getPriorityClass = (priority) => {
  const classes = {
    'low': 'bg-gray-100 text-gray-700',
    'medium': 'bg-blue-100 text-blue-700',
    'high': 'bg-orange-100 text-orange-700',
    'urgent': 'bg-red-100 text-red-700'
  };
  return classes[priority] || 'bg-gray-100 text-gray-700';
};

const handleNotificationClick = (notification) => {
  if (!notification.is_read && !notification.read) {
    // Mark as read using polling composable
    polling.markAsRead(notification.id);
  }

  // Emit event to open modal
  emit('notification-clicked', notification.id);

  showNotifications.value = false;
};


const handleClickOutside = (event) => {
  if (notificationDropdown.value && !notificationDropdown.value.contains(event.target)) {
    showNotifications.value = false;
  }
};

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});
</script>

<style scoped>
.dropdown-enter-active,
.dropdown-leave-active {
  transition: all 0.2s ease;
}

.dropdown-enter-from,
.dropdown-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
</style>
