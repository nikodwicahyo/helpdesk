<template>
  <div class="flex flex-col h-full">
    <!-- Sidebar Header (Mobile) -->
    <div class="flex items-center justify-between p-4 border-b border-gray-200 lg:hidden bg-white">
      <span class="text-lg font-semibold text-gray-900">{{ t('common.menu') }}</span>
      <button
        @click="$emit('close')"
        class="p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
    </div>

    <!-- Navigation Links -->
    <nav class="flex-1 overflow-y-auto p-4 space-y-1 bg-white">
        <Link
          v-for="item in menuItems"
          :key="item.name"
          :href="item.href"
          :class="[
            'flex items-center px-3 sm:px-4 py-2.5 sm:py-3 text-sm font-medium rounded-lg transition-all duration-200',
            isActive(item.href)
              ? 'bg-indigo-50 text-indigo-600 border-indigo-200'
              : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900 border-transparent'
          ]"
          class="border"
        >
          <span class="text-lg sm:text-xl mr-2.5 sm:mr-3 flex-shrink-0">{{ item.icon }}</span>
          <span class="truncate">{{ item.name }}</span>
          <span
            v-if="item.badge"
            class="ml-auto inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800"
          >
            {{ item.badge }}
          </span>
        </Link>

        <!-- Divider -->
        <div v-if="secondaryMenuItems.length > 0" class="border-t border-gray-200 my-4"></div>

        <!-- Secondary Menu Items -->
        <Link
          v-for="item in secondaryMenuItems"
          :key="item.name"
          :href="item.href"
          :class="[
            'flex items-center px-3 sm:px-4 py-2.5 sm:py-3 text-sm font-medium rounded-lg transition-all duration-200',
            isActive(item.href)
              ? 'bg-indigo-50 text-indigo-600'
              : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900'
          ]"
        >
          <span class="text-lg sm:text-xl mr-2.5 sm:mr-3 flex-shrink-0">{{ item.icon }}</span>
          <span class="truncate">{{ item.name }}</span>
        </Link>
      </nav>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  role: {
    type: String,
    required: true
  }
});

defineEmits(['close']);

const page = usePage();

const menuItems = computed(() => {
  const menus = {
    'user': [
      { name: t('nav.dashboard'), href: '/user/dashboard', icon: '🏠', badge: null },
      { name: t('nav.myTickets'), href: '/user/tickets', icon: '📋', badge: null },
      { name: t('nav.createTicket'), href: '/user/tickets/create', icon: '➕', badge: null },
      { name: t('nav.applications'), href: '/user/applications', icon: '💻', badge: null },
      { name: t('ticket.history'), href: '/user/history', icon: '📜', badge: null },
      { name: t('nav.notifications'), href: '/user/notifications', icon: '🔔', badge: null },
    ],
    'admin_helpdesk': [
      { name: t('nav.dashboard'), href: '/admin/dashboard', icon: '🏠', badge: null },
      { name: t('nav.ticketManagement'), href: '/admin/tickets-management', icon: '📋', badge: null },
      { name: t('nav.userManagement'), href: '/admin/users-management', icon: '👥', badge: null },
      { name: t('nav.applications'), href: '/admin/applications', icon: '💻', badge: null },
      { name: t('nav.categories'), href: '/admin/categories', icon: '📁', badge: null },
      { name: t('nav.notifications'), href: '/admin/notifications', icon: '🔔', badge: null },
      { name: t('nav.reports'), href: '/admin/reports', icon: '📊', badge: null },
      { name: t('nav.analytics'), href: '/admin/analytics', icon: '📈', badge: null },
      { name: t('nav.activityLog'), href: '/admin/activity-log', icon: '📝', badge: null },
      { name: t('nav.systemSettings'), href: '/admin/system-settings', icon: '⚙️', badge: null }
    ],
    'admin_aplikasi': [
      { name: t('nav.dashboard'), href: '/admin-aplikasi/dashboard', icon: '🏠', badge: null },
      { name: t('nav.applications'), href: '/admin-aplikasi/applications', icon: '💻', badge: null },
      { name: t('nav.categories'), href: '/admin-aplikasi/categories', icon: '📁', badge: null },
      { name: t('nav.notifications'), href: '/admin-aplikasi/notifications', icon: '🔔', badge: null },
      { name: t('nav.analytics'), href: '/admin-aplikasi/analytics', icon: '📊', badge: null }
    ],
    'teknisi': [
      { name: t('nav.dashboard'), href: '/teknisi/dashboard', icon: '🏠', badge: null },
      { name: t('nav.myTasks'), href: '/teknisi/tickets', icon: '📋', badge: null },
      { name: t('nav.notifications'), href: '/teknisi/notifications', icon: '🔔', badge: null },
      { name: t('nav.knowledgeBase'), href: '/teknisi/knowledge-base', icon: '📚', badge: null },
      { name: t('nav.reports'), href: '/teknisi/reports', icon: '📊', badge: null },
    ]
  };

  // Support for legacy role mapping
  const roleKey = props.role === 'admin' ? 'admin_helpdesk' : props.role;
  const items = menus[roleKey] || [];

  // Add dynamic badges for notifications (example for tickets)
  return items.map(item => {
    if (item.name.includes('Tickets') || item.name.includes('Tasks')) {
      // You could add logic here to show ticket counts
      return { ...item, badge: null };
    }
    return item;
  });
});

const secondaryMenuItems = computed(() => {
  // Remove Settings from secondary if it's already in main menu
  const mainItems = menuItems.value;
  const hasSettingsInMain = mainItems.some(item => item.name.toLowerCase().includes('settings'));

  const items = [];

  // Support for legacy role mapping
  const roleKey = props.role === 'admin' ? 'admin_helpdesk' : props.role;

  if (!hasSettingsInMain) {
    // Add settings based on role
    let settingsUrl;
    if (roleKey === 'admin_helpdesk') {
      settingsUrl = '/admin/system-settings';
    } else if (roleKey === 'admin_aplikasi') {
      settingsUrl = '/admin-aplikasi/profile';
    } else {
      settingsUrl = `/${roleKey}/profile`;
    }
    items.push({ name: t('nav.settings'), href: settingsUrl, icon: '⚙️' });
  }

  return items;
});

const isActive = (href) => {
  // Exact match for dashboard
  if (href === '/admin/dashboard' && page.url === '/admin/dashboard') {
    return true;
  }

  // For other routes, check if the current URL starts with the href
  // This handles cases like /admin/tickets-management vs /admin/tickets-management/123
  return page.url.startsWith(href);
};
</script>
