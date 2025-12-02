import { ref, computed, onMounted, onUnmounted } from 'vue';
import pollingService from '../Services/PollingService.js';
import AuthManager from '../Services/AuthManager.js';
import axios from 'axios';

/**
 * Main polling composable that replaces useRealtime
 * @param {Object} options - Configuration options
 * @param {string} options.endpoint - API endpoint to poll
 * @param {number} options.interval - Polling interval in milliseconds
 * @param {boolean} options.autoStart - Whether to start polling immediately
 * @param {Function} options.transform - Data transformation function
 * @param {Function} options.onError - Error handler
 * @returns {Object} Polling reactive state and controls
 */
export function usePolling(options = {}) {
    // Reactive state
    const isConnected = ref(false);
    const isPolling = ref(false);
    const isLoading = ref(false);
    const hasError = ref(false);
    const error = ref(null);
    const lastUpdated = ref(null);
    const data = ref(null);
    const pollKey = ref(null);
    
    // Configuration
    const config = {
        endpoint: options.endpoint || '',
        interval: options.interval || 10000,
        autoStart: options.autoStart !== false,
        transform: options.transform || ((response) => response),
        onError: options.onError || ((err) => console.error('Polling error:', err)),
        retryCount: ref(0),
        maxRetries: options.maxRetries || 3
    };

    // Computed properties
    const connectionStatus = computed(() => {
        if (hasError.value) return 'error';
        if (isPolling.value) return 'connected';
        return 'disconnected';
    });

    const timeSinceLastUpdate = computed(() => {
        if (!lastUpdated.value) return null;
        const now = new Date();
        const diff = Math.floor((now - lastUpdated.value) / 1000);
        
        if (diff < 60) return `${diff}s ago`;
        if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
        return `${Math.floor(diff / 3600)}h ago`;
    });

    const canRetry = computed(() => {
        return config.retryCount.value < config.maxRetries;
    });

    // Data update handler
    const handleDataUpdate = (response, originalResponse) => {
        try {
            const transformedData = config.transform(response, originalResponse);
            data.value = transformedData;
            lastUpdated.value = new Date();
            hasError.value = false;
            error.value = null;
            config.retryCount.value = 0;
            isLoading.value = false;
            
            // Only log in debug mode or for errors
            // console.log(`Polling update received for ${config.endpoint}`, transformedData);
        } catch (err) {
            console.error('Data transformation error:', err);
            handleError(err);
        }
    };

    // Error handler
    const handleError = (err, retryCount = 0) => {
        hasError.value = true;
        error.value = err?.message || 'Unknown error';
        isLoading.value = false;
        config.retryCount.value = retryCount;

        config.onError(err, retryCount);
        console.error(`Polling error for ${config.endpoint}:`, err);
    };

    // Helper function to wait for auth to be ready
    const waitForAuthReady = async (timeoutMs = 3000) => {
        if (AuthManager.state.isAuthenticated) return true;

        const startTime = Date.now();
        
        while (Date.now() - startTime < timeoutMs) {
            if (AuthManager.state.isAuthenticated) {
                return true;
            }
            await new Promise(resolve => setTimeout(resolve, 100));
        }
        
        return false;
    };

    // Start polling
    const startPolling = async () => {
        if (!config.endpoint) {
            console.warn('Cannot start polling: no endpoint specified');
            return;
        }

        // Wait for authentication to be ready before starting polling
        const authReady = await waitForAuthReady(5000);
        if (!authReady) {
            // Try explicit check
            await AuthManager.validateAuthStatus();
            if (!AuthManager.state.isAuthenticated) {
                console.warn('Authentication not ready, but starting polling for:', config.endpoint);
            }
        }

        try {
            if (pollKey.value) {
                pollingService.stopPolling(pollKey.value);
            }

            pollKey.value = pollingService.startPolling(
                `polling_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`,
                config.endpoint,
                {
                    interval: config.interval,
                    onUpdate: handleDataUpdate,
                    onError: handleError,
                    immediate: true
                }
            );

            isConnected.value = true;
            isPolling.value = true;
            isLoading.value = true;
            
        } catch (err) {
            handleError(err);
        }
    };

    // Stop polling
    const stopPolling = () => {
        if (pollKey.value) {
            pollingService.stopPolling(pollKey.value);
            pollKey.value = null;
        }
        
        isPolling.value = false;
        isConnected.value = false;
    };

    // Helper function to get CSRF token
    const getCsrfToken = () => {
        return pollingService.getCsrfToken();
    };

    // Helper function to check if user is authenticated
    const isUserAuthenticated = () => {
        return AuthManager.state.isAuthenticated;
    };

    // Manual refresh
    const refresh = async () => {
        if (!config.endpoint) return;

        isLoading.value = true;
        hasError.value = false;
        
        try {
            const response = await axios.get(config.endpoint);

            // Axios response.data contains the body
            const result = response.data;
            handleDataUpdate(result, response);
        } catch (err) {
            if (err.response && err.response.status === 401) {
                console.warn('Authentication error (401) during refresh');
                
                // Try to refresh auth state
                await AuthManager.checkSession();
                
                // Check if auth is now valid
                if (!isUserAuthenticated()) {
                    handleError(new Error('Unauthorized - Authentication required'));
                    return;
                }
            }
            
            // Handle axios error object
            const errorMsg = err.response ? 
                `HTTP ${err.response.status}: ${err.response.statusText}` : 
                err.message;
                
            handleError(new Error(errorMsg));
        }
    };

    // Pause polling
    const pausePolling = () => {
        if (pollKey.value) {
            pollingService.pausePolling(pollKey.value);
            isPolling.value = false;
        }
    };

    // Resume polling
    const resumePolling = () => {
        if (pollKey.value) {
            pollingService.resumePolling(pollKey.value);
            isPolling.value = true;
        }
    };

    // Retry after error
    const retry = () => {
        if (canRetry.value) {
            config.retryCount.value++;
            // Clear the error state and restart polling
            hasError.value = false;
            error.value = null;
            isLoading.value = true;
            startPolling();
        } else {
            console.warn('Maximum retry attempts reached');
        }
    };

    // Update polling interval
    const updateInterval = (newInterval) => {
        config.interval = newInterval;
        if (pollKey.value) {
            pollingService.updateInterval(pollKey.value, newInterval);
        }
    };

    // Helper function to handle async start on mount
    const startPollingOnMount = async () => {
        if (config.autoStart) {
            await startPolling();
        }
    };

    // Auto-start polling if configured
    onMounted(() => {
        if (config.autoStart) {
            // Use nextTick to ensure the component is fully ready
            setTimeout(() => {
                startPollingOnMount();
            }, 0);
        }
    });

    // Cleanup on unmount
    onUnmounted(() => {
        stopPolling();
    });

    // Return reactive state and methods
    return {
        // State
        data: computed(() => data.value),
        isConnected: computed(() => isConnected.value),
        isPolling: computed(() => isPolling.value),
        isLoading: computed(() => isLoading.value),
        hasError: computed(() => hasError.value),
        error: computed(() => error.value),
        lastUpdated: computed(() => lastUpdated.value),
        connectionStatus: computed(() => connectionStatus.value),
        timeSinceLastUpdate: computed(() => timeSinceLastUpdate.value),
        canRetry: computed(() => canRetry.value),
        retryCount: computed(() => config.retryCount.value),
        
        // Methods
        startPolling,
        stopPolling,
        refresh,
        pausePolling,
        resumePolling,
        retry,
        updateInterval
    };
}

/**
 * Polling composable for notifications
 * Replaces real-time notification functionality
 * @param {Object} options - Notification polling options
 * @returns {Object} Notification polling state and controls
 */
export function useNotificationPolling(options = {}) {
    const polling = usePolling({
        endpoint: options.endpoint || '/api/notifications',
        interval: options.interval || 10000,
        transform: (response) => {
            // Handle multiple response formats for compatibility
            let notifications = [];
            let unreadCount = 0;
            let totalCount = 0;

            // Try primary structure first
            if (response.data?.data?.notifications) {
                notifications = response.data.data.notifications;
                unreadCount = response.data.data.unread_count || 0;
                totalCount = response.data.data.total_count || 0;
            }
            // Try secondary structure (data with pagination)
            else if (response.data?.data && Array.isArray(response.data.data)) {
                notifications = response.data.data;
                unreadCount = response.data.unread_count || 0;
                totalCount = response.data.total_count || 0;
            }
            // Try direct notifications array
            else if (response.data?.notifications) {
                notifications = response.data.notifications;
                unreadCount = response.data.unread_count || response.unreadCount || 0;
                totalCount = response.data.total_count || response.totalCount || 0;
            }
            // Try direct response
            else if (response.notifications) {
                notifications = response.notifications;
                unreadCount = response.unread_count || response.unreadCount || 0;
                totalCount = response.total_count || response.totalCount || 0;
            }
            // Try response with data field that has notifications field
            else if (response.data && response.data.notifications) {
                notifications = response.data.notifications;
                unreadCount = response.data.unread_count || response.data.unreadCount || 0;
                totalCount = response.data.total_count || response.data.totalCount || 0;
            }
            // Fallback to empty array
            else {
                notifications = [];
                unreadCount = 0;
                totalCount = 0;
            }

            // Map notifications to have is_read property for compatibility
            notifications = notifications.map(notification => ({
                ...notification,
                is_read: notification.is_read || notification.read || false
            }));

            return {
                notifications,
                unreadCount,
                totalCount
            };
        },
        onError: (err) => {
            console.error('Notification polling error:', err);
            // You could trigger a global notification here
        }
    });

    // Add notification-specific methods
    const markAsRead = async (notificationId) => {
        try {
            // Use axios for proper CSRF handling
            await axios.post(`/api/notifications/${notificationId}/mark-read`);
            
            // Refresh notifications after marking as read
            polling.refresh();
        } catch (err) {
            console.error('Error marking notification as read:', err);
        }
    };

    const markAllAsRead = async () => {
        try {
            await axios.post('/api/notifications/mark-all-read');
            
            // Refresh notifications after marking all as read
            polling.refresh();
        } catch (err) {
            console.error('Error marking all notifications as read:', err);
        }
    };

    // Add bulk mark as read method
    const bulkMarkAsRead = async (notificationIds) => {
        try {
            await axios.post('/api/notifications/bulk-mark-read', { 
                notification_ids: notificationIds 
            });

            // Refresh notifications after bulk operation
            polling.refresh();
        } catch (err) {
            console.error('Error bulk marking notifications as read:', err);
        }
    };

    // Add get recent notifications method
    const getRecentNotifications = async (hours = 24) => {
        try {
            const response = await axios.get(`/api/notifications/recent?hours=${hours}`);
            return response.data;
        } catch (err) {
            console.error('Error getting recent notifications:', err);
            return { success: false, error: err.message };
        }
    };

    // Add delete notification method
    const deleteNotification = async (notificationId) => {
        try {
            await axios.delete(`/api/notifications/${notificationId}`);

            // Refresh notifications after deletion
            polling.refresh();
        } catch (err) {
            console.error('Error deleting notification:', err);
        }
    };

    return {
        ...polling,
        markAsRead,
        markAllAsRead,
        bulkMarkAsRead,
        getRecentNotifications,
        deleteNotification
    };
}

/**
 * Dashboard polling composable
 * Replaces live dashboard functionality with polling
 * @param {Object} options - Dashboard polling options
 * @returns {Object} Dashboard polling state and controls
 */
export function useDashboardPolling(options = {}) {
    const polling = usePolling({
        endpoint: options.endpoint || '/api/dashboard/metrics/refresh',
        interval: options.interval || 30000,
        transform: (response) => response.data || response,
        onError: (err) => {
            console.error('Dashboard polling error:', err);
        }
    });

    // Add dashboard-specific refresh for full page reloads
    const refreshDashboard = () => {
        // Use Inertia's reload functionality if available
        if (window.location.href.includes('/dashboard')) {
            window.location.reload();
        } else {
            polling.refresh();
        }
    };

    return {
        ...polling,
        refreshDashboard
    };
}

/**
 * Ticket polling composable
 * Replaces real-time ticket updates
 * @param {Object} options - Ticket polling options
 * @returns {Object} Ticket polling state and controls
 */
export function useTicketPolling(options = {}) {
    const polling = usePolling({
        endpoint: options.endpoint || '/api/tickets/refresh',
        interval: options.interval || 15000,
        transform: (response) => response.data || response,
        onError: (err) => {
            console.error('Ticket polling error:', err);
        }
    });

    return {
        ...polling
    };
}

/**
 * System health polling composable
 * Polls system status and health metrics
 * @param {Object} options - System polling options
 * @returns {Object} System health polling state
 */
export function useSystemHealthPolling(options = {}) {
    const polling = usePolling({
        endpoint: options.endpoint || '/api/system/health',
        interval: options.interval || 60000,
        transform: (response) => response.data || response,
        onError: (err) => {
            console.error('System health polling error:', err);
        }
    });

    const getHealthStatus = computed(() => {
        const healthData = polling.data.value;
        if (!healthData) return 'unknown';
        
        if (healthData.status === 'healthy') return 'healthy';
        if (healthData.status === 'warning') return 'warning';
        if (healthData.status === 'error') return 'error';
        return 'unknown';
    });

    return {
        ...polling,
        healthStatus: getHealthStatus
    };
}

export default usePolling;