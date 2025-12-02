import { reactive } from 'vue';
import axios from 'axios';
import { getInertiaAuthProps } from '../utils/inertiaAuth';
import { router } from '@inertiajs/vue3';

/**
 * AuthManager
 *
 * Centralized authentication and session management service.
 * Replaces StableAuthManager and SessionTimeoutManager.
 */
class AuthManager {
    constructor() {
        // Reactive state using Vue's reactive system
        this.state = reactive({
            user: null,
            isAuthenticated: false,
            role: null,
            permissions: [],
            session: {
                id: null,
                expires_at: null,
                warning_time: null,
                last_activity: null
            },
            isLoading: true,
            initialized: false
        });

        this.checkInterval = null;
        this.activityTimeout = null;
        this.loginTimestamp = null; // Track when login occurred for grace period
        this.sessionCheckFailCount = 0; // Track consecutive failures
        this.config = {
            checkInterval: 30000, // Check every 30 seconds (faster polling for better UX)
            warningThreshold: 600, // 10 minutes in seconds
            loginGracePeriod: 10000, // 10 seconds grace period after login
            maxSessionCheckFails: 3, // Allow 3 consecutive failures before logout
        };
        
        // Bind methods
        this.handleUserActivity = this.handleUserActivity.bind(this);
    }

    /**
     * Initialize the AuthManager
     */
    async initialize() {
        if (this.state.initialized) {
            return;
        }

        this.log('Initializing...');
        
        try {
            // 1. Try to get auth from Inertia props (Primary Source)
            const inertiaAuth = getInertiaAuthProps();
            
            if (inertiaAuth?.user?.id) {
                this.log('Authenticated via Inertia props');
                this.updateState({
                    user: inertiaAuth.user,
                    role: inertiaAuth.role,
                    permissions: inertiaAuth.permissions || [],
                    session: inertiaAuth.session || {}
                });
            } else {
                // 2. Fallback to API check if not found in props but might exist
                // only if we suspect a session exists (e.g. cookie)
                await this.validateAuthStatus();
            }
        } catch (error) {
            this.error('Initialization error', error);
        } finally {
            this.state.isLoading = false;
            this.state.initialized = true;
            this.setupEventListeners();
            this.startMonitoring();
        }
    }

    /**
     * Update the local reactive state
     */
    updateState(data) {
        if (data.user) {
            this.state.user = {
                id: data.user.id,
                name: data.user.name || data.user.nip,
                email: data.user.email,
                nip: data.user.nip
            };
            this.state.isAuthenticated = true;
            this.state.role = data.role;
            this.state.permissions = data.permissions || [];
            
            // Update session info if provided
            if (data.session) {
                this.state.session.id = data.session.id || data.session.session_id || this.state.session.id;
                this.state.session.expires_at = data.session.expires_at || this.state.session.expires_at;
                this.state.session.warning_time = data.session.warning_time || this.state.session.warning_time;
            }

            this.dispatch('auth:ready', this.getSnapshot());
        } else {
            this.clearState();
        }
    }

    /**
     * Clear local state (Logout)
     */
    clearState() {
        this.state.user = null;
        this.state.isAuthenticated = false;
        this.state.role = null;
        this.state.permissions = [];
        this.state.session = {
            id: null,
            expires_at: null,
            warning_time: null,
            last_activity: null
        };
        this.dispatch('auth:logout');
    }

    /**
     * Validate auth status with backend
     */
    async validateAuthStatus() {
        try {
            // Only check if we have a session cookie hint or are ensuring state
            const response = await axios.get('/api/auth/status');
            if (response.data.authenticated) {
                this.updateState(response.data);
                return true;
            } else {
                if (this.state.isAuthenticated) {
                    this.log('Session invalidated by backend');
                    this.clearState();
                }
                return false;
            }
        } catch (error) {
            // 401 means definitely not authenticated
            if (error.response?.status === 401) {
                if (this.state.isAuthenticated) {
                    this.clearState();
                }
            }
            return false;
        }
    }

    /**
     * Login action
     * Uses /login (web route) instead of /api/login for consistent session handling
     */
    async login(credentials) {
        try {
            const response = await axios.post('/login', credentials);
            
            if (response.data.success) {
                this.log('Login successful');
                
                // Set login timestamp for grace period
                this.loginTimestamp = Date.now();
                this.sessionCheckFailCount = 0; // Reset fail count on successful login
                
                this.updateState({
                    user: response.data.user,
                    role: response.data.role,
                    permissions: response.data.permissions,
                    session: response.data.session_info
                });
                
                return {
                    success: true,
                    redirect: response.data.redirect
                };
            }
            
            return { success: false, message: response.data.message };
        } catch (error) {
            this.error('Login failed', error);
            throw error;
        }
    }

    /**
     * Logout action
     */
    async logout() {
        try {
            await axios.post('/logout');
        } catch (error) {
            this.error('Logout API error', error);
        } finally {
            this.clearState();
            // Force reload to login page
            window.location.href = '/login';
        }
    }

    /**
     * Start session monitoring loop
     */
    startMonitoring() {
        if (this.checkInterval) clearInterval(this.checkInterval);
        
        // Initial check
        if (this.state.isAuthenticated) {
            this.checkSession();
        }

        this.checkInterval = setInterval(() => {
            if (this.state.isAuthenticated) {
                this.checkSession();
            }
        }, this.config.checkInterval);
    }

    /**
     * Check if we're still within login grace period
     */
    isWithinLoginGracePeriod() {
        if (!this.loginTimestamp) return false;
        return (Date.now() - this.loginTimestamp) < this.config.loginGracePeriod;
    }

    /**
     * Check session status (expiry, warning)
     * This is the primary session validation that polls the backend
     */
    async checkSession() {
        if (!this.state.isAuthenticated) return;

        // Skip session check during login grace period to allow session to be established
        if (this.isWithinLoginGracePeriod()) {
            this.log('Within login grace period, skipping session check');
            return;
        }

        try {
            const response = await axios.get('/api/session/status');
            
            // Check if response indicates session is still valid
            if (response.data.authenticated && response.data.session_active) {
                // Reset fail count on successful check
                this.sessionCheckFailCount = 0;
                
                // Update session timers from database-validated response
                const sessionData = response.data.session || {};
                this.state.session.expires_at = sessionData.expires_at || this.state.session.expires_at;
                this.state.session.minutes_remaining = sessionData.minutes_remaining;
                this.state.session.seconds_remaining = sessionData.seconds_remaining;
                this.state.session.last_activity = sessionData.last_activity;
                
                // Parse expires_at to timestamp if it's an ISO string
                if (typeof this.state.session.expires_at === 'string') {
                    this.state.session.expires_at = new Date(this.state.session.expires_at).getTime() / 1000;
                }
                
                // Check if session has actually expired (minutes_remaining <= 0)
                if (sessionData.minutes_remaining <= 0) {
                    this.log('Session expired based on minutes_remaining');
                    this.handleSessionExpired();
                    return;
                }
                
                // Check for warning threshold (10 minutes = 600 seconds)
                const warningThresholdMinutes = this.config.warningThreshold / 60;
                if (sessionData.minutes_remaining <= warningThresholdMinutes && sessionData.minutes_remaining > 0) {
                    this.log(`Session warning: ${sessionData.minutes_remaining} minutes remaining`);
                    this.dispatch('session:warning', {
                        minutes_remaining: sessionData.minutes_remaining,
                        seconds_remaining: sessionData.seconds_remaining || Math.floor(sessionData.minutes_remaining * 60)
                    });
                }
            } else {
                // Backend says session is not authenticated or not active
                this.sessionCheckFailCount++;
                this.log(`Session invalid (attempt ${this.sessionCheckFailCount}/${this.config.maxSessionCheckFails}): authenticated=${response.data.authenticated}, session_active=${response.data.session_active}`);
                
                // Only logout after multiple consecutive failures
                if (this.sessionCheckFailCount >= this.config.maxSessionCheckFails) {
                    this.handleSessionExpired();
                }
            }
        } catch (error) {
            // Handle 401 Unauthorized - session definitely expired
            if (error.response?.status === 401) {
                this.sessionCheckFailCount++;
                this.log(`Session check 401 (attempt ${this.sessionCheckFailCount}/${this.config.maxSessionCheckFails})`);
                
                // Only logout after multiple consecutive failures
                if (this.sessionCheckFailCount >= this.config.maxSessionCheckFails) {
                    this.handleSessionExpired();
                }
            } else {
                // Log other errors but don't force logout (could be network issue)
                this.error('Session check failed', error);
            }
        }
    }

    /**
     * Handle expired session - force logout and redirect to login
     */
    handleSessionExpired() {
        // Prevent multiple calls
        if (!this.state.isAuthenticated) {
            return;
        }
        
        this.log('Session expired - logging out user');
        
        // Stop monitoring immediately
        if (this.checkInterval) {
            clearInterval(this.checkInterval);
            this.checkInterval = null;
        }
        
        // Dispatch event for UI components (e.g., to close session warning modal)
        this.dispatch('session:expired');
        
        // Clear state
        this.clearState();
        
        // Force redirect to login page with full page reload
        // Using window.location.href ensures all state is cleared
        window.location.href = '/login?session_expired=1';
    }

    /**
     * Extend current session
     */
    async extendSession() {
        try {
            const response = await axios.post('/api/session/extend');
            if (response.data.success) {
                this.log('Session extended successfully');
                
                // Update session data with new expiry times
                const sessionData = response.data.session || {};
                this.state.session.expires_at = sessionData.expires_at;
                this.state.session.minutes_remaining = sessionData.minutes_remaining;
                this.state.session.seconds_remaining = sessionData.seconds_remaining;
                this.state.session.last_activity = sessionData.last_activity;
                
                // Parse expires_at to timestamp if it's an ISO string
                if (typeof this.state.session.expires_at === 'string') {
                    this.state.session.expires_at = new Date(this.state.session.expires_at).getTime() / 1000;
                }
                
                // Dispatch session extended event
                this.dispatch('session:extended', {
                    minutes_remaining: sessionData.minutes_remaining,
                    seconds_remaining: sessionData.seconds_remaining
                });
                
                return true;
            } else if (response.data.authenticated === false) {
                // Session was already expired
                this.handleSessionExpired();
                return false;
            }
        } catch (error) {
            // Handle 401 - session already expired
            if (error.response?.status === 401) {
                this.log('Cannot extend session - already expired');
                this.handleSessionExpired();
            } else {
                this.error('Failed to extend session', error);
            }
        }
        return false;
    }

    /**
     * Setup activity listeners
     */
    setupEventListeners() {
        const events = ['mousedown', 'keydown', 'touchstart', 'scroll'];
        events.forEach(evt => window.addEventListener(evt, this.handleUserActivity, { passive: true }));
        
        // Inertia navigation listener to re-sync auth state
        router.on('finish', () => {
            this.syncWithInertia();
        });
    }

    /**
     * Sync with new Inertia page props after navigation
     */
    syncWithInertia() {
        const inertiaAuth = getInertiaAuthProps();
        if (inertiaAuth?.user?.id) {
            // If we have user in props, ensure our state matches
            if (!this.state.isAuthenticated || this.state.user?.id !== inertiaAuth.user.id) {
                this.updateState({
                    user: inertiaAuth.user,
                    role: inertiaAuth.role,
                    permissions: inertiaAuth.permissions,
                    session: inertiaAuth.session
                });
            }
        } else if (this.state.isAuthenticated) {
            // If we are authenticated but props say otherwise, we might have lost session
            // Double check with API before clearing to be safe (prevent flicker)
            this.validateAuthStatus();
        }
    }

    /**
     * Debounced user activity handler
     */
    handleUserActivity() {
        if (!this.state.isAuthenticated) return;
        
        // Just update last activity timestamp locally
        // We don't spam the server; the session is typically kept alive by navigation or api calls
        this.state.session.last_activity = Date.now();
    }

    // --- Helpers ---

    getSnapshot() {
        return JSON.parse(JSON.stringify(this.state));
    }

    dispatch(eventName, detail = {}) {
        window.dispatchEvent(new CustomEvent(eventName, { detail }));
    }

    log(msg, ...args) {
        console.log(`[AuthManager] ${msg}`, ...args);
    }

    error(msg, ...args) {
        console.error(`[AuthManager] ${msg}`, ...args);
    }
}

// Singleton instance
export default new AuthManager();
