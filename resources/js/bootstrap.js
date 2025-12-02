import axios from "axios";
window.axios = axios;

// Configure base settings
window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";
window.axios.defaults.timeout = 30000; // 30 seconds timeout
window.axios.defaults.withCredentials = true; // Include cookies for session management

// Configure CSRF token for axios
// We rely on the XSRF-TOKEN cookie which Laravel sets automatically.
// Axios automatically adds the X-XSRF-TOKEN header when withCredentials is true.
// We only set the X-CSRF-TOKEN header from meta tag if the cookie is missing,
// to avoid conflicts where the meta tag is stale but the cookie is fresh.

const getCookie = (name) => {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
};

if (!getCookie('XSRF-TOKEN')) {
    let token = document.head.querySelector('meta[name="csrf-token"]');
    if (token) {
        window.axios.defaults.headers.common["X-CSRF-TOKEN"] = token.content;
    } else {
        console.error(
            "CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token"
        );
    }
} else {
    // If we have the cookie, ensure we don't send the meta-tag header to prevent conflicts
    delete window.axios.defaults.headers.common["X-CSRF-TOKEN"];
}

// Request interceptor for authentication and logging
window.axios.interceptors.request.use(
    async (config) => {
        // Add request timestamp for debugging
        config.metadata = { startTime: new Date() };

        // Add auth headers if available - using standardized sessionStorage shape
        const sessionData = sessionStorage.getItem('auth_session');
        if (sessionData) {
            try {
                const authData = JSON.parse(sessionData);
                // Standardized shape: user_id, user_role, user_name, etc.
                if (authData.user_id) {
                    config.headers['X-User-ID'] = authData.user_id;
                }
                if (authData.user_role) {
                    config.headers['X-User-Role'] = authData.user_role;
                }
                // Legacy support for session_id
                if (authData.session_id) {
                    config.headers['X-Session-ID'] = authData.session_id;
                }
            } catch (e) {
                console.warn('Failed to parse auth session data:', e);
            }
        }



        // Log request in development
        if (import.meta.env.DEV) {
            console.log('🚀 API Request:', {
                method: config.method?.toUpperCase(),
                url: config.url,
                headers: config.headers,
                timestamp: new Date().toISOString()
            });
        }

        return config;
    },
    (error) => {
        console.error('❌ Request Error:', error);
        return Promise.reject(error);
    }
);

// Response interceptor for error handling and session management
window.axios.interceptors.response.use(
    (response) => {
        // Calculate response time
        const duration = new Date() - response.config.metadata.startTime;

        // Log successful response in development
        if (import.meta.env.DEV) {
            console.log('✅ API Response:', {
                status: response.status,
                url: response.config.url,
                duration: `${duration}ms`,
                timestamp: new Date().toISOString()
            });
        }

        // Handle authentication responses
        // Note: Primary auth data storage is handled by IntegratedAuthManager
        // This only stores session-specific metadata if needed
        if (response.data?.session_info) {
            // Get existing auth data to supplement with session info
            try {
                const existingSessionData = sessionStorage.getItem('auth_session');
                let authData = existingSessionData ? JSON.parse(existingSessionData) : {};

                // Update with session info while preserving user data
                authData.session_id = response.data.session_info.session_id;
                // Note: user_role and permissions should come from IntegratedAuthManager

                sessionStorage.setItem('auth_session', JSON.stringify(authData));
            } catch (error) {
                console.warn('Failed to update session storage:', error.message);
            }
        }

        return response;
    },
    (error) => {
        const duration = new Date() - error.config?.metadata?.startTime || 0;

        // Enhanced error logging with better error object handling
        console.error('❌ API Error:', {
            status: error.response?.status,
            statusText: error.response?.statusText,
            url: error.config?.url,
            method: error.config?.method?.toUpperCase(),
            duration: duration ? `${duration}ms` : 'unknown',
            data: error.response?.data,
            message: error.message,
            stack: error.stack,
            timestamp: new Date().toISOString()
        });

        // Handle specific error types
        if (error.response?.status === 401) {
            // Unauthorized - clear auth data and redirect to login
            sessionStorage.removeItem('auth_session');

            // Only redirect if not already on login page
            if (window.location.pathname !== '/login') {
                // Dispatch event for auth composable to handle
                window.dispatchEvent(new CustomEvent('auth:unauthorized'));

                // Fallback redirect after a short delay
                setTimeout(() => {
                    if (window.location.pathname !== '/login') {
                        window.location.href = '/login';
                    }
                }, 1000);
            }
        } else if (error.response?.status === 404) {
            // Resource not found - log specific error but don't redirect
            console.error('❌ 404 Error: Resource not found', {
                url: error.config?.url,
                timestamp: new Date().toISOString()
            });
            
            // Dispatch event for UI handling
            window.dispatchEvent(new CustomEvent('resource:notfound', {
                detail: {
                    message: 'Resource tidak ditemukan. Silakan periksa kembali permintaan Anda.'
                }
            }));
        } else if (error.response?.status === 419) {
            // CSRF token mismatch - reload page to get fresh token
            console.warn('CSRF token mismatch, reloading page...');
            window.location.reload();
        } else if (error.response?.status === 409) {
            // Inertia asset versioning mismatch - anticipated behavior
            // Do not log as error, let Inertia handle the reload
            return Promise.reject(error);
        } else if (error.response?.status >= 500) {
            // Server error - show user-friendly message with more details
            const errorDetails = error.response?.data || {};
            const errorMessage = errorDetails.message || errorDetails.error || 'Terjadi kesalahan server. Silakan coba lagi dalam beberapa saat.';

            console.error('Server error occurred:', {
                status: error.response.status,
                statusText: error.response.statusText,
                url: error.config?.url,
                details: errorDetails,
                timestamp: new Date().toISOString()
            });

            // Dispatch event for global error handling
            window.dispatchEvent(new CustomEvent('server:error', {
                detail: {
                    status: error.response.status,
                    statusText: error.response.statusText,
                    message: errorMessage,
                    url: error.config?.url,
                    timestamp: new Date().toISOString(),
                    details: errorDetails
                }
            }));
        } else if (error.code === 'NETWORK_ERROR' || error.message.includes('Network')) {
            // Network error
            window.dispatchEvent(new CustomEvent('network:error', {
                detail: {
                    message: 'Terjadi kesalahan koneksi. Silakan periksa koneksi internet Anda dan coba lagi.'
                }
            }));
        } else if (error.code === 'TIMEOUT' || error.message.includes('timeout')) {
            // Timeout error
            window.dispatchEvent(new CustomEvent('timeout:error', {
                detail: {
                    message: 'Waktu koneksi habis. Silakan coba lagi.'
                }
            }));
        } else if (error.code === 'ERR_CANCELED' && axios.isCancel(error)) {
            // Request was canceled
            console.debug('Request was canceled:', error.message);
        } else {
            // Some other error occurred
            console.error('❌ Other API Error:', error.message, {
                code: error.code,
                config: error.config,
                timestamp: new Date().toISOString()
            });
        }

        return Promise.reject(error);
    }
);

// Global error handlers
window.addEventListener('auth:unauthorized', () => {
    // Clear any cached auth data
    sessionStorage.removeItem('auth_session');
    localStorage.removeItem('auth_preferences');

    // Show notification if available
    if (window.dispatchEvent) {
        window.dispatchEvent(new CustomEvent('show-notification', {
            detail: {
                type: 'error',
                title: 'Sesi Berakhir',
                message: 'Sesi Anda telah berakhir. Silakan login kembali.',
                duration: 5000
            }
        }));
    }
});

window.addEventListener('server:error', (event) => {
    if (window.dispatchEvent) {
        window.dispatchEvent(new CustomEvent('show-notification', {
            detail: {
                type: 'error',
                title: 'Kesalahan Server',
                message: event.detail.message,
                duration: 5000
            }
        }));
    }
});

window.addEventListener('network:error', (event) => {
    if (window.dispatchEvent) {
        window.dispatchEvent(new CustomEvent('show-notification', {
            detail: {
                type: 'warning',
                title: 'Kesalahan Koneksi',
                message: event.detail.message,
                duration: 5000
            }
        }));
    }
});

window.addEventListener('timeout:error', (event) => {
    if (window.dispatchEvent) {
        window.dispatchEvent(new CustomEvent('show-notification', {
            detail: {
                type: 'warning',
                title: 'Waktu Habis',
                message: event.detail.message,
                duration: 5000
            }
        }));
    }
});

window.addEventListener('resource:notfound', (event) => {
    if (window.dispatchEvent) {
        window.dispatchEvent(new CustomEvent('show-notification', {
            detail: {
                type: 'error',
                title: 'Resource Tidak Ditemukan',
                message: event.detail.message,
                duration: 5000
            }
        }));
    }
});

// WebSocket initialization is now handled by IntegratedAuthManager
// No duplicate initialization needed here

// Import Ziggy for route helper
import { route } from "ziggy-js";
import { Ziggy } from "./ziggy";

// Configure Ziggy globally
window.Ziggy = Ziggy;

// Make route function globally available
window.route = route;

// Export axios instance for use in composables
export { axios };
