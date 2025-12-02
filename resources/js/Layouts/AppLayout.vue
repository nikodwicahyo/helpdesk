<template>
  <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100">
    <!-- Global Notification Component -->
    <Notification />

    <!-- Session Warning Modal -->
    <SessionWarning
      :show="showSessionWarning"
      @close="handleWarningClose"
      @extended="handleSessionExtended"
      @logout="handleSessionLogout"
    />

    <!-- Session Status Indicator -->
    <SessionStatus />

    <!-- Mobile Menu Overlay -->
    <div
      v-show="sidebarOpen"
      class="fixed inset-0 z-40 lg:hidden"
      @click="sidebarOpen = false"
    >
      <div class="absolute inset-0 bg-gray-600 opacity-75"></div>
    </div>

    <!-- Navbar -->
    <Navbar
      :user="$page.props.auth?.user ?? null"
      :role="$page.props.auth?.role ?? null"
      :notifications="notifications"
      :unread-count="unreadCount"
      @toggle-sidebar="toggleSidebar"
    />

    <div class="flex h-screen">
      <!-- Sidebar -->
      <aside
        :class="[
          'fixed inset-y-0 left-0 z-50 w-72 bg-white shadow-xl transform transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-0',
          sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
        ]"
      >
        <Sidebar
          :is-open="sidebarOpen"
          :role="$page.props.auth?.role ?? null"
          @close="sidebarOpen = false"
        />
      </aside>

      <!-- Main Content Area -->
      <main class="flex-1 overflow-y-auto">
        <!-- Mobile Top Bar (when sidebar is open) -->
        <div
          v-if="sidebarOpen"
          class="lg:hidden bg-white border-b border-gray-200 px-4 py-3 flex items-center justify-between"
        >
          <h2 class="text-lg font-semibold text-gray-900">Menu</h2>
          <button
            @click="sidebarOpen = false"
            class="p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500"
          >
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="px-4 sm:px-6 lg:px-8 py-2 sm:py-4 lg:py-6">
          <!-- Responsive Container -->
          <div class="mx-auto max-w-7xl">
            <!-- Breadcrumb -->
            <nav v-if="breadcrumbs && breadcrumbs.length > 0" class="mb-4 sm:mb-6">
              <ol class="flex items-center space-x-1 sm:space-x-2 text-xs sm:text-sm text-gray-600 flex-wrap">
                <li v-for="(crumb, index) in breadcrumbs" :key="index" class="flex items-center">
                  <Link
                    v-if="crumb.href"
                    :href="crumb.href"
                    class="hover:text-indigo-600 transition-colors duration-200 flex items-center"
                  >
                    <svg v-if="index === 0 && crumb.icon" class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="crumb.icon" />
                    </svg>
                    {{ crumb.label }}
                  </Link>
                  <span v-else class="text-gray-900 font-medium">{{ crumb.label }}</span>
                  <svg
                    v-if="index < breadcrumbs.length - 1"
                    class="w-3 h-3 sm:w-4 sm:h-4 mx-1 sm:mx-2 text-gray-400 flex-shrink-0"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                  >
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                  </svg>
                </li>
              </ol>
            </nav>

            <!-- Page Header -->
            <div v-if="$slots.header" class="mb-4 sm:mb-6">
              <slot name="header"></slot>
            </div>

            <!-- Enhanced Flash Messages -->
            <div class="space-y-3 mb-4">
              <!-- Success Message -->
              <div
                v-if="$page.props.flash?.success"
                class="bg-green-50 border-l-4 border-green-400 text-green-800 p-4 rounded-lg shadow-sm transform transition-all duration-300 ease-in-out animate-slideInDown"
              >
                <div class="flex items-center justify-between">
                  <div class="flex items-center">
                    <div class="flex-shrink-0">
                      <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                      </svg>
                    </div>
                    <div class="ml-3">
                      <p class="text-sm font-medium">{{ $page.props.flash.success }}</p>
                    </div>
                  </div>
                  <button
                    @click="$page.props.flash.success = null"
                    class="ml-auto flex-shrink-0 bg-green-50 rounded-md p-1 hover:bg-green-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-green-50 focus:ring-green-600 transition-colors duration-200"
                  >
                    <span class="sr-only">Dismiss</span>
                    <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                  </button>
                </div>
              </div>

              <!-- Error Message -->
              <div
                v-if="$page.props.flash?.error"
                class="bg-red-50 border-l-4 border-red-400 text-red-800 p-4 rounded-lg shadow-sm transform transition-all duration-300 ease-in-out animate-slideInDown"
              >
                <div class="flex items-center justify-between">
                  <div class="flex items-center">
                    <div class="flex-shrink-0">
                      <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                      </svg>
                    </div>
                    <div class="ml-3">
                      <p class="text-sm font-medium">{{ $page.props.flash.error }}</p>
                    </div>
                  </div>
                  <button
                    @click="$page.props.flash.error = null"
                    class="ml-auto flex-shrink-0 bg-red-50 rounded-md p-1 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-red-50 focus:ring-red-600 transition-colors duration-200"
                  >
                    <span class="sr-only">Dismiss</span>
                    <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                  </button>
                </div>
              </div>

              <!-- Warning Message -->
              <div
                v-if="$page.props.flash?.warning"
                class="bg-yellow-50 border-l-4 border-yellow-400 text-yellow-800 p-4 rounded-lg shadow-sm transform transition-all duration-300 ease-in-out animate-slideInDown"
              >
                <div class="flex items-center justify-between">
                  <div class="flex items-center">
                    <div class="flex-shrink-0">
                      <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                      </svg>
                    </div>
                    <div class="ml-3">
                      <p class="text-sm font-medium">{{ $page.props.flash.warning }}</p>
                    </div>
                  </div>
                  <button
                    @click="$page.props.flash.warning = null"
                    class="ml-auto flex-shrink-0 bg-yellow-50 rounded-md p-1 hover:bg-yellow-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-yellow-50 focus:ring-yellow-600 transition-colors duration-200"
                  >
                    <span class="sr-only">Dismiss</span>
                    <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                  </button>
                </div>
              </div>
            </div>

            <!-- Main Content Slot -->
            <div class="animate-fadeIn">
              <slot></slot>
            </div>
          </div>
        </div>
      </main>
    </div>

    <!-- Enhanced Loading Overlay -->
    <Loading
      v-if="$page.props.loading"
      :show="true"
      fullscreen
      overlay
      text="Loading..."
      backdrop-blur
    />

    <!-- Notification Detail Modal -->
    <NotificationDetailModal
      :show="showNotificationModal"
      :notification-id="selectedNotificationId"
      :role="$page.props.auth?.role || 'user'"
      @close="closeNotificationModal"
      @notification-updated="handleNotificationUpdated"
    />

  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import Navbar from '@/Components/Layout/Navbar.vue';
import Sidebar from '@/Components/Layout/Sidebar.vue';
import Loading from '@/Components/Common/Loading.vue';
import Notification from '@/Components/Common/Notification.vue';
import SessionWarning from '@/Components/SessionWarning.vue';
import SessionStatus from '@/Components/SessionStatus.vue';
import NotificationDetailModal from '@/Components/Notifications/NotificationDetailModal.vue';

const props = defineProps({
  breadcrumbs: {
    type: Array,
    default: () => []
  }
});

const page = usePage();
const sidebarOpen = ref(false);
const showNotificationModal = ref(false);
const selectedNotificationId = ref(null);
const showSessionWarning = ref(false);

// Handle responsive sidebar behavior
const isMobile = ref(false);

const checkMobile = () => {
  isMobile.value = window.innerWidth < 1024; // lg breakpoint
  if (!isMobile.value) {
    sidebarOpen.value = false;
  }
};

const toggleSidebar = () => {
  sidebarOpen.value = !sidebarOpen.value;
};

// Close sidebar on escape key
const handleEscape = (event) => {
  if (event.key === 'Escape' && sidebarOpen.value) {
    sidebarOpen.value = false;
  }
};

// Session Warning Handling
const handleSessionWarning = (event) => {
  console.log('Session warning event received:', event.detail);
  showSessionWarning.value = true;
};

const handleWarningClose = () => {
  showSessionWarning.value = false;
};

const handleSessionExtended = () => {
  showSessionWarning.value = false;
  console.log('Session extended successfully');
};

const handleSessionLogout = () => {
  showSessionWarning.value = false;
  // Logout will be handled by useSession composable
};

// Notification Modal Handling
const openNotificationModal = (event) => {
  if (event.detail && event.detail.notificationId) {
    selectedNotificationId.value = event.detail.notificationId;
    showNotificationModal.value = true;
  }
};

const closeNotificationModal = () => {
  showNotificationModal.value = false;
  selectedNotificationId.value = null;
};

const handleNotificationUpdated = (data) => {
  // If we need to reload a different notification (related notification clicked)
  if (data.loadNotificationId) {
    selectedNotificationId.value = data.loadNotificationId;
    return;
  }
  
  // If we're on the notifications page, we might want to refresh the list
  // This can be done by reloading the page or emitting an event if the page component is listening
  if (window.location.pathname.includes('/notifications') && !data.loadNotificationId) {
    router.reload({ only: ['notifications', 'unreadNotifications', 'stats'] });
  }
};

onMounted(() => {
  checkMobile();
  window.addEventListener('resize', checkMobile);
  window.addEventListener('keydown', handleEscape);
  
  // Listen for global notification open events (e.g. from Navbar/Bell)
  window.addEventListener('open-notification-modal', openNotificationModal);
  
  // Listen for session warning events
  window.addEventListener('session:warning', handleSessionWarning);
});

onUnmounted(() => {
  window.removeEventListener('resize', checkMobile);
  window.removeEventListener('keydown', handleEscape);
  window.removeEventListener('open-notification-modal', openNotificationModal);
  window.removeEventListener('session:warning', handleSessionWarning);
});

const notifications = computed(() => {
  const notificationsData = page.props.notifications || [];
  
  // Check if notificationsData is a pagination object (has 'data' property with array)
  if (notificationsData && Array.isArray(notificationsData.data)) {
    // If it's a pagination object, return the data array
    return notificationsData.data;
  } else if (Array.isArray(notificationsData)) {
    // If it's already an array, return as is
    return notificationsData;
  } else {
    // Fallback to empty array
    return [];
  }
});

const unreadCount = computed(() => {
  return page.props.unreadNotifications || 0;
});
</script>
