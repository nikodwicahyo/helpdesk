import { getInertiaAuthProps } from "../utils/inertiaAuth";
import AuthManager from "./AuthManager"; // Import the central AuthManager
import axios from 'axios'; // Import axios for consistent request handling

class PollingService {
    constructor() {
        this.activePolls = new Map();
        this.defaultIntervals = {
            notifications: 10000,      // 10 seconds
            dashboard: 30000,          // 30 seconds
            tickets: 15000,            // 15 seconds
            system: 60000,             // 1 minute
            realTime: 5000             // 5 seconds for high-priority data
        };
        this.retryConfig = {
            maxRetries: 3,
            baseDelay: 1000,
            backoffMultiplier: 2
        };
    }

    /**
     * Get CSRF token
     * @returns {string} CSRF token
     */
    getCsrfToken() {
        // Fallback to meta tag
        try {
            const metaToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (metaToken && metaToken.length > 0) return metaToken;
        } catch (error) {
            console.warn('Failed to get CSRF token from meta tag:', error);
        }

        return '';
    }

    /**
     * Get authentication headers for API requests
     * @returns {Object} Authentication headers
     */
    getAuthHeaders() {
        // With axios, these are handled automatically, but we keep this for backward compatibility
        // or if headers need to be inspected
        const headers = {
            'X-CSRF-TOKEN': this.getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        };
        return headers;
    }

    /**
     * Start polling for a specific endpoint
     * @param {string} key - Unique identifier for this poll
     * @param {string} url - API endpoint to poll
     * @param {Object} options - Configuration options
     */
    startPolling(key, url, options = {}) {
        const config = {
            interval: options.interval || this.defaultIntervals.realTime,
            onUpdate: options.onUpdate || (() => {}),
            onError: options.onError || (() => {}),
            params: options.params || {},
            method: options.method || 'GET',
            headers: options.headers || {},
            immediate: options.immediate !== false,
            retryCount: 0,
            isActive: true
        };

        // Stop existing poll if it exists
        this.stopPolling(key);

        const poll = async () => {
            if (!config.isActive) return;

            try {
                const response = await this.makeRequest(url, config);
                // Check if response is valid data or axios response object
                const data = response.data || response;
                config.onUpdate(data, response);
                config.retryCount = 0; // Reset retry count on success
            } catch (error) {
                config.retryCount++;
                config.onError(error, config.retryCount);

                // Retry with exponential backoff
                if (config.retryCount <= this.retryConfig.maxRetries) {
                    const delay = this.retryConfig.baseDelay * 
                        Math.pow(this.retryConfig.backoffMultiplier, config.retryCount - 1);
                    setTimeout(poll, delay);
                    return;
                }
            }

            // Schedule next poll
            if (config.isActive) {
                config.timeoutId = setTimeout(poll, config.interval);
            }
        };

        // Store configuration
        this.activePolls.set(key, config);

        // Start polling
        if (config.immediate) {
            poll();
        } else {
            config.timeoutId = setTimeout(poll, config.interval);
        }

        return key;
    }

    /**
     * Stop polling for a specific key
     * @param {string} key - Unique identifier for the poll
     */
    stopPolling(key) {
        const poll = this.activePolls.get(key);
        if (poll) {
            poll.isActive = false;
            if (poll.timeoutId) {
                clearTimeout(poll.timeoutId);
            }
            this.activePolls.delete(key);
        }
    }

    /**
     * Stop all active polls
     */
    stopAllPolling() {
        for (const [key, poll] of this.activePolls) {
            this.stopPolling(key);
        }
    }

    /**
     * Update polling interval for an existing poll
     * @param {string} key - Unique identifier for the poll
     * @param {number} newInterval - New interval in milliseconds
     */
    updateInterval(key, newInterval) {
        const poll = this.activePolls.get(key);
        if (poll) {
            poll.interval = newInterval;
        }
    }

    /**
     * Pause polling for a specific key
     * @param {string} key - Unique identifier for the poll
     */
    pausePolling(key) {
        const poll = this.activePolls.get(key);
        if (poll) {
            poll.isActive = false;
            if (poll.timeoutId) {
                clearTimeout(poll.timeoutId);
            }
        }
    }

    /**
     * Resume polling for a specific key
     * @param {string} key - Unique identifier for the poll
     */
    resumePolling(key) {
        const poll = this.activePolls.get(key);
        if (poll && !poll.isActive) {
            poll.isActive = true;
            const pollFn = async () => {
                if (!poll.isActive) return;

                try {
                    const url = poll.url;
                    const response = await this.makeRequest(url, poll);
                    const data = response.data || response;
                    poll.onUpdate(data, response);
                    poll.retryCount = 0;
                } catch (error) {
                    poll.retryCount++;
                    poll.onError(error, poll.retryCount);
                }

                if (poll.isActive) {
                    poll.timeoutId = setTimeout(pollFn, poll.interval);
                }
            };

            poll.timeoutId = setTimeout(pollFn, 0);
        }
    }

    /**
     * Check if a poll is active
     * @param {string} key - Unique identifier for the poll
     * @returns {boolean}
     */
    isPolling(key) {
        const poll = this.activePolls.get(key);
        return poll ? poll.isActive : false;
    }

    /**
     * Get all active polls
     * @returns {Array} Array of active poll configurations
     */
    getActivePolls() {
        return Array.from(this.activePolls.entries()).map(([key, config]) => ({
            key,
            ...config,
            timeoutId: undefined // Remove timeoutId from response
        }));
    }

    /**
     * Check if authentication is valid using AuthManager
     */
    async checkAuthStatus() {
        return AuthManager.state.isAuthenticated;
    }

    /**
     * Wait for auth to be ready or for a timeout period
     * @param {number} timeoutMs - Timeout in milliseconds
     * @returns {boolean} True if auth is ready within timeout, otherwise false
     */
    async waitForAuthReady(timeoutMs = 10000) {
        if (AuthManager.state.isAuthenticated) return true;

        const startTime = Date.now();
        
        while (Date.now() - startTime < timeoutMs) {
            if (AuthManager.state.isAuthenticated) {
                return true;
            }
            await new Promise(resolve => setTimeout(resolve, 200));
        }
        
        return false;
    }

    /**
     * Make HTTP request with error handling and CSRF token support
     * Replaced fetch with axios for better consistency with the rest of the app
     * @param {string} url - Request URL
     * @param {Object} config - Request configuration
     * @returns {Promise} HTTP response
     */
    async makeRequest(url, config) {
        // Check authentication status using AuthManager
        const authReady = await this.waitForAuthReady(3000);
        if (!authReady) {
            console.warn('Authentication was not ready within timeout for API call to:', url);
            // Attempt explicit validation check
            await AuthManager.validateAuthStatus();
            if (!AuthManager.state.isAuthenticated) {
                throw new Error('Authentication not ready');
            }
        }

        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 30000);

        try {
            const response = await axios({
                method: config.method,
                url: url,
                params: config.method === 'GET' ? config.params : undefined,
                data: config.method !== 'GET' ? config.params : undefined,
                headers: config.headers, // axios merges with defaults
                signal: controller.signal
            });

            clearTimeout(timeoutId);
            
            // Axios automatically parses JSON
            return response;

        } catch (error) {
            clearTimeout(timeoutId);
            
            if (axios.isCancel(error)) {
                throw new Error('Request timeout');
            }
            
            if (error.response && error.response.status === 401) {
                console.warn('Authentication error (401)');
                // Trigger a check in AuthManager, which handles 401s
                await AuthManager.checkSession();
                throw new Error('Unauthorized - Authentication required');
            }

            if (error.response) {
                throw new Error(`HTTP ${error.response.status}: ${error.response.statusText}`);
            }

            throw error;
        }
    }

    // ... (rest of methods remain similar but use makeRequest)

    /**
     * Start polling for notifications
     */
    startNotificationPolling(onUpdate, onError) {
        return this.startPolling(
            'notifications',
            '/api/notifications',
            {
                interval: this.defaultIntervals.notifications,
                onUpdate,
                onError,
                params: { limit: 50, unread_only: true }
            }
        );
    }

    /**
     * Start polling for dashboard data
     */
    startDashboardPolling(onUpdate, onError) {
        return this.startPolling(
            'dashboard',
            '/api/dashboard/refresh',
            {
                interval: this.defaultIntervals.dashboard,
                onUpdate,
                onError
            }
        );
    }

    /**
     * Start polling for ticket updates
     */
    startTicketPolling(onUpdate, onError) {
        return this.startPolling(
            'tickets',
            '/api/tickets/refresh',
            {
                interval: this.defaultIntervals.tickets,
                onUpdate,
                onError
            }
        );
    }

    /**
     * Get polling status for debugging
     */
    getStatus() {
        const activePolls = this.getActivePolls();
        return {
            totalPolls: activePolls.length,
            activePolls: activePolls.length,
            polls: activePolls.map(poll => ({
                key: poll.key,
                interval: poll.interval,
                url: poll.url,
                method: poll.method
            }))
        };
    }
}

const pollingService = new PollingService();
export default pollingService;

if (typeof window !== 'undefined') {
    window.PollingService = pollingService;
    window.addEventListener('beforeunload', () => {
        pollingService.stopAllPolling();
    });
}