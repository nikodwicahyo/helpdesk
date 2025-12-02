import './bootstrap';
import AuthManager from './Services/AuthManager'; // Centralized Auth Manager
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { useAuth } from './composables/useAuth';
import { shouldTriggerAuthReset } from './constants/auth.js';
import i18n from './i18n'; // Import i18n configuration

const pages = import.meta.glob('./Pages/**/*.vue');

// Initialize AuthManager immediately
AuthManager.initialize();

createInertiaApp({
  title: (title) => `${title}HelpDesk Kemlu`,
  resolve: (name) => {
    const page = pages[`./Pages/${name}.vue`];
    if (!page) {
      console.error(`Page component "./Pages/${name}.vue" not found`);
      // Dynamically import the NotFound component
      return import('./Pages/NotFound.vue').then(module => module.default);
    }
    return page().then(module => module.default);
  },
  setup({ el, App, props, plugin }) {
    const app = createApp({ render: () => h(App, props) })
      .use(plugin)
      .use(i18n); // Use i18n plugin
    
    // Update i18n locale based on priority: localStorage > systemSettings > default
    const savedLanguage = localStorage.getItem('user_language');
    if (savedLanguage) {
      // User preference takes priority
      i18n.global.locale.value = savedLanguage;
    } else if (props.initialPage?.props?.systemSettings?.default_language) {
      // Fall back to system settings
      i18n.global.locale.value = props.initialPage.props.systemSettings.default_language;
    }

    // Make route function available globally in Vue components
    app.config.globalProperties.$route = window.route;
    app.config.globalProperties.route = window.route;

    // Global error handler with centralized auth error detection
    app.config.errorHandler = (error, instance, info) => {
      console.error('Global Vue error:', error, info);

      // Use centralized auth error detection - only redirect on explicit auth failures
      if (shouldTriggerAuthReset(error)) {
        console.log('🚨 Global: Auth error detected, redirecting to login');

        // Use Inertia navigation instead of hard redirect
        if (window.location.pathname !== '/login' && window.router) {
          window.router.visit('/login', { replace: true });
        } else if (window.location.pathname !== '/login') {
          window.location.href = '/login';
        }
      }
    };

    // Global mixin for stable auth state
    app.mixin({
      computed: {
        $auth() {
          return useAuth();
        },
        $isAuthenticated() {
          // Access the reactive value properly
          return this.$auth.isAuthenticated.value;
        },
        $user() {
          return this.$auth.user.value;
        },
        $userRole() {
          return this.$auth.role.value;
        },
        $userPermissions() {
          return this.$auth.permissions.value;
        }
      },
      methods: {
        $can(permission) {
          // Access the computed function
          return this.$auth.hasPermission.value(permission);
        },
        $hasRole(role) {
          return this.$auth.role.value === role;
        }
      }
    });

    // Add global notification system
    app.config.globalProperties.$notify = {
      success(message, duration = 5000) {
        this.show('success', message, duration);
      },
      error(message, duration = 5000) {
        this.show('error', message, duration);
      },
      warning(message, duration = 5000) {
        this.show('warning', message, duration);
      },
      info(message, duration = 5000) {
        this.show('info', message, duration);
      },
      show(type, message, duration) {
        // Dispatch custom event for notification system
        window.dispatchEvent(new CustomEvent('show-notification', {
          detail: { type, message, duration }
        }));
      }
    };

    // Add session management helpers
    app.config.globalProperties.$session = {
      refresh() {
        return useAuth().refreshSession();
      },
      getTimeUntilExpiry() {
        return useAuth().timeUntilExpiry.value;
      },
      isExpiring() {
        return useAuth().isSessionExpiring.value;
      }
    };

    // Global event listeners for server and network errors
    window.addEventListener('server:error', (event) => {
      const { status, statusText, message, url, details } = event.detail;
      console.error('Global server error handler:', { status, statusText, message, url, details });

      // Show user-friendly notification
      if (app.config.globalProperties.$notify) {
        app.config.globalProperties.$notify.error(
          `${message} (${status})${url ? ' - ' + url : ''}`
        );
      } else {
        alert(`Server Error: ${message} (${status})`);
      }
    });

    window.addEventListener('network:error', (event) => {
      const { message } = event.detail;
      console.error('Global network error handler:', message);

      // Show user-friendly notification
      if (app.config.globalProperties.$notify) {
        app.config.globalProperties.$notify.error(message);
      } else {
        alert(`Network Error: ${message}`);
      }
    });

    
    return app.mount(el);
  },
});
