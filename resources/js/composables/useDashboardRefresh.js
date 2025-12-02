/**
 * useDashboardRefresh.js
 * 
 * Vue composable for dashboard-specific polling and refresh functionality.
 * Replaces useLiveDashboard with manual refresh and automatic polling capabilities.
 * Provides optimized polling intervals and manual refresh controls for dashboard data.
 */

import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { usePolling } from './usePolling.js';

/**
 * Dashboard refresh composable that replaces live dashboard functionality
 * @param {Object} options - Dashboard configuration options
 * @param {string} options.dashboardType - Type of dashboard ('admin', 'user', 'teknisi', 'admin-aplikasi')
 * @param {boolean} options.autoRefresh - Whether to enable automatic polling
 * @param {number} options.refreshInterval - Polling interval in milliseconds
 * @param {Function} options.onRefresh - Callback when data is refreshed
 * @param {Function} options.beforeRefresh - Callback before refresh starts
 * @returns {Object} Dashboard refresh state and controls
 */
export function useDashboardRefresh(options = {}) {
    // Dashboard configuration
    const config = {
        dashboardType: options.dashboardType || 'admin',
        autoRefresh: options.autoRefresh !== false,
        refreshInterval: options.refreshInterval || 30000, // 30 seconds default
        onRefresh: options.onRefresh || (() => {}),
        beforeRefresh: options.beforeRefresh || (() => {}),
        showManualRefresh: options.showManualRefresh !== false
    };

    // Reactive state
    const isRefreshing = ref(false);
    const lastRefresh = ref(null);
    const refreshCount = ref(0);
    const autoRefreshEnabled = ref(config.autoRefresh);
    const manualRefreshInProgress = ref(false);
    const refreshError = ref(null);

    // Get dashboard-specific endpoint
    const getDashboardEndpoint = () => {
        const endpoints = {
            'admin': '/api/admin/dashboard/metrics',
            'user': '/api/user/dashboard/metrics',
            'teknisi': '/api/teknisi/dashboard/metrics',
            'admin-aplikasi': '/api/admin-aplikasi/dashboard/metrics'
        };

        return endpoints[config.dashboardType] || '/api/dashboard/refresh';
    };

    // Initialize polling with dashboard-specific settings
    const polling = usePolling({
        endpoint: getDashboardEndpoint(),
        interval: config.refreshInterval,
        autoStart: false, // Don't auto-start polling
        transform: (response) => {
            // Add metadata to the response
            return {
                ...response,
                _refreshMetadata: {
                    timestamp: new Date().toISOString(),
                    refreshCount: refreshCount.value,
                    dashboardType: config.dashboardType
                }
            };
        },
        onError: (err) => {
            refreshError.value = err.message || 'Refresh failed';
            console.error('Dashboard refresh error:', err);
        }
    });

    // Manual refresh function
    const refreshDashboard = async () => {
        if (isRefreshing.value || manualRefreshInProgress.value) {
            console.log('Refresh already in progress');
            return;
        }

        try {
            manualRefreshInProgress.value = true;
            isRefreshing.value = true;
            refreshError.value = null;

            // Call before refresh callback
            config.beforeRefresh();

            // Trigger refresh
            await polling.refresh();

            // Update metadata
            lastRefresh.value = new Date();
            refreshCount.value += 1;

            // Call after refresh callback
            config.onRefresh(polling.data.value);

            console.log(`Dashboard refresh completed (${refreshCount.value})`);
        } catch (error) {
            refreshError.value = error.message || 'Refresh failed';
            console.error('Manual dashboard refresh error:', error);
            throw error;
        } finally {
            manualRefreshInProgress.value = false;
            isRefreshing.value = false;
        }
    };

    // Auto-refresh functions
    const startAutoRefresh = () => {
        if (!autoRefreshEnabled.value) {
            autoRefreshEnabled.value = true;
            polling.startPolling();
        }
    };

    const stopAutoRefresh = () => {
        autoRefreshEnabled.value = false;
        polling.stopPolling();
    };

    const toggleAutoRefresh = () => {
        if (autoRefreshEnabled.value) {
            stopAutoRefresh();
        } else {
            startAutoRefresh();
        }
    };

    // Update refresh interval
    const updateRefreshInterval = (newInterval) => {
        polling.updateInterval(newInterval);
        console.log(`Dashboard refresh interval updated to ${newInterval}ms`);
    };

    // Get time until next refresh
    const timeUntilNextRefresh = computed(() => {
        if (!polling.isPolling.value || !lastRefresh.value) return null;
        
        const now = new Date();
        const nextRefresh = new Date(lastRefresh.value.getTime() + config.refreshInterval);
        const diff = Math.floor((nextRefresh - now) / 1000);
        
        return diff > 0 ? `${diff}s` : 'due';
    });

    // Auto-start polling if enabled
    onMounted(() => {
        if (autoRefreshEnabled.value) {
            polling.startPolling();
        }
    });

    // Stop polling on unmount
    onUnmounted(() => {
        polling.stopPolling();
    });

    // Return comprehensive dashboard refresh state and controls
    return {
        // Data from polling
        data: polling.data,
        isPolling: computed(() => polling.isPolling.value),
        isLoading: polling.isLoading,
        hasError: computed(() => polling.hasError.value),
        error: computed(() => polling.error.value),
        
        // Dashboard-specific state
        isRefreshing: computed(() => isRefreshing.value),
        manualRefreshInProgress: computed(() => manualRefreshInProgress.value),
        refreshError: computed(() => refreshError.value),
        lastRefresh: computed(() => lastRefresh.value),
        refreshCount: computed(() => refreshCount.value),
        autoRefreshEnabled: computed(() => autoRefreshEnabled.value),
        timeUntilNextRefresh: computed(() => timeUntilNextRefresh.value),
        showManualRefresh: config.showManualRefresh,
        
        // Control methods
        refreshDashboard,
        startAutoRefresh,
        stopAutoRefresh,
        toggleAutoRefresh,
        updateRefreshInterval,
        
        // Status helpers
        isReady: computed(() => !polling.isLoading.value && !manualRefreshInProgress.value),
        canRefresh: computed(() => !isRefreshing.value && !manualRefreshInProgress.value),
        needsRefresh: computed(() => {
            if (!lastRefresh.value) return true;
            const now = new Date();
            const timeSinceRefresh = now - lastRefresh.value;
            return timeSinceRefresh > (config.refreshInterval * 1.5); // 150% of interval
        })
    };
}

/**
 * Admin Dashboard refresh composable
 * Specialized for admin helpdesk dashboard with enhanced features
 * @param {Object} options - Admin dashboard options
 * @returns {Object} Admin dashboard refresh functionality
 */
export function useAdminDashboardRefresh(options = {}) {
    const dashboard = useDashboardRefresh({
        dashboardType: 'admin',
        refreshInterval: options.refreshInterval || 30000, // 30 seconds
        autoRefresh: options.autoRefresh !== false,
        showManualRefresh: options.showManualRefresh !== false,
        onRefresh: (data) => {
            console.log('Admin dashboard data updated:', data);
            // You could trigger specific admin dashboard updates here
        }
    });

    // Admin-specific refresh for different data sections
    const refreshTickets = async () => {
        try {
            const response = await fetch('/api/admin/tickets/refresh');
            if (response.ok) {
                return await response.json();
            }
        } catch (error) {
            console.error('Failed to refresh tickets:', error);
        }
    };

    const refreshStats = async () => {
        try {
            const response = await fetch('/api/admin/stats/refresh');
            if (response.ok) {
                return await response.json();
            }
        } catch (error) {
            console.error('Failed to refresh stats:', error);
        }
    };

    const refreshUsers = async () => {
        try {
            const response = await fetch('/api/admin/users/refresh');
            if (response.ok) {
                return await response.json();
            }
        } catch (error) {
            console.error('Failed to refresh users:', error);
        }
    };

    return {
        ...dashboard,
        refreshTickets,
        refreshStats,
        refreshUsers
    };
}

/**
 * User Dashboard refresh composable
 * For regular user dashboard functionality
 * @param {Object} options - User dashboard options
 * @returns {Object} User dashboard refresh functionality
 */
export function useUserDashboardRefresh(options = {}) {
    const dashboard = useDashboardRefresh({
        dashboardType: 'user',
        refreshInterval: options.refreshInterval || 60000, // 1 minute for users
        autoRefresh: options.autoRefresh !== false,
        onRefresh: (data) => {
            console.log('User dashboard data updated:', data);
        }
    });

    return {
        ...dashboard
    };
}

/**
 * Teknisi Dashboard refresh composable
 * For teknisi/support staff dashboard
 * @param {Object} options - Teknisi dashboard options
 * @returns {Object} Teknisi dashboard refresh functionality
 */
export function useTeknisiDashboardRefresh(options = {}) {
    const dashboard = useDashboardRefresh({
        dashboardType: 'teknisi',
        refreshInterval: options.refreshInterval || 15000, // 15 seconds for teknisi
        autoRefresh: options.autoRefresh !== false,
        onRefresh: (data) => {
            console.log('Teknisi dashboard data updated:', data);
        }
    });

    // Teknisi-specific refresh methods
    const refreshAssignedTickets = async () => {
        try {
            const response = await fetch('/api/teknisi/tickets/assigned/refresh');
            if (response.ok) {
                return await response.json();
            }
        } catch (error) {
            console.error('Failed to refresh assigned tickets:', error);
        }
    };

    const refreshWorkload = async () => {
        try {
            const response = await fetch('/api/teknisi/workload/refresh');
            if (response.ok) {
                return await response.json();
            }
        } catch (error) {
            console.error('Failed to refresh workload:', error);
        }
    };

    return {
        ...dashboard,
        refreshAssignedTickets,
        refreshWorkload
    };
}

/**
 * Admin Aplikasi Dashboard refresh composable
 * For application management dashboard
 * @param {Object} options - Admin Aplikasi dashboard options
 * @returns {Object} Admin Aplikasi dashboard refresh functionality
 */
export function useAdminAplikasiDashboardRefresh(options = {}) {
    const dashboard = useDashboardRefresh({
        dashboardType: 'admin-aplikasi',
        refreshInterval: options.refreshInterval || 60000, // 1 minute
        autoRefresh: options.autoRefresh !== false,
        onRefresh: (data) => {
            console.log('Admin Aplikasi dashboard data updated:', data);
        }
    });

    // Admin Aplikasi-specific refresh methods
    const refreshApplications = async () => {
        try {
            const response = await fetch('/api/admin-aplikasi/applications/refresh');
            if (response.ok) {
                return await response.json();
            }
        } catch (error) {
            console.error('Failed to refresh applications:', error);
        }
    };

    const refreshCategories = async () => {
        try {
            const response = await fetch('/api/admin-aplikasi/categories/refresh');
            if (response.ok) {
                return await response.json();
            }
        } catch (error) {
            console.error('Failed to refresh categories:', error);
        }
    };

    return {
        ...dashboard,
        refreshApplications,
        refreshCategories
    };
}

/**
 * Smart refresh composable that adapts refresh intervals based on activity
 * Automatically adjusts polling frequency based on user activity and data changes
 * @param {Object} options - Smart refresh options
 * @returns {Object} Smart refresh functionality
 */
export function useSmartDashboardRefresh(options = {}) {
    const dashboard = useDashboardRefresh({
        ...options,
        refreshInterval: options.baseInterval || 30000
    });

    // Smart refresh state
    const userActivity = ref('idle'); // idle, active, very-active
    const adaptiveInterval = ref(options.baseInterval || 30000);
    const activityTimer = ref(null);

    // Adjust refresh interval based on activity
    const adjustRefreshInterval = () => {
        const intervals = {
            'idle': options.baseInterval * 2 || 60000,      // 2x slower when idle
            'active': options.baseInterval || 30000,        // Normal when active
            'very-active': (options.baseInterval || 30000) * 0.5 || 15000 // 2x faster when very active
        };

        const newInterval = intervals[userActivity.value] || intervals.active;
        if (newInterval !== adaptiveInterval.value) {
            adaptiveInterval.value = newInterval;
            dashboard.updateRefreshInterval(newInterval);
            console.log(`Smart refresh interval adjusted to ${newInterval}ms (${userActivity.value})`);
        }
    };

    // Track user activity
    const trackUserActivity = () => {
        userActivity.value = 'active';
        
        clearTimeout(activityTimer.value);
        activityTimer.value = setTimeout(() => {
            userActivity.value = 'idle';
            adjustRefreshInterval();
        }, 30000); // 30 seconds without activity = idle

        // Detect very active behavior (frequent clicks, interactions)
        const veryActiveTimer = setTimeout(() => {
            userActivity.value = 'very-active';
            adjustRefreshInterval();
        }, 5000); // 5 seconds of activity = very active

        // Reset to active after 2 seconds
        setTimeout(() => {
            if (userActivity.value === 'very-active') {
                userActivity.value = 'active';
                adjustRefreshInterval();
            }
        }, 2000);
    };

    // Set up activity listeners
    onMounted(() => {
        const events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'];
        events.forEach(event => {
            document.addEventListener(event, trackUserActivity, true);
        });
    });

    onUnmounted(() => {
        const events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'];
        events.forEach(event => {
            document.removeEventListener(event, trackUserActivity, true);
        });
        clearTimeout(activityTimer.value);
    });

    // Start with base interval
    adjustRefreshInterval();

    return {
        ...dashboard,
        userActivity: computed(() => userActivity.value),
        adaptiveInterval: computed(() => adaptiveInterval.value)
    };
}

export default useDashboardRefresh;