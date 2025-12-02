import { ref, reactive, computed, onMounted, onUnmounted, watch } from "vue";
import AuthManager from "../Services/AuthManager";
import axios from "axios";

/**
 * useAuth Composable (Refactored)
 * 
 * This composable now acts as a reactive adapter for the singleton AuthManager.
 * It maintains the existing API surface to ensure compatibility with existing components.
 */
export function useAuth() {
    // Reactive references to AuthManager state
    const user = computed(() => AuthManager.state.user);
    const isAuthenticated = computed(() => AuthManager.state.isAuthenticated);
    const isLoading = computed(() => AuthManager.state.isLoading);
    const role = computed(() => AuthManager.state.role);
    const permissions = computed(() => AuthManager.state.permissions);
    
    // Helper for session info
    const sessionInfo = computed(() => AuthManager.state.session);

    // Form states (local to the composable usage, typically in Login.vue)
    const loginForm = reactive({
        nip: "",
        password: "",
        remember: false,
        processing: false,
        errors: {},
        message: null // Added for compatibility with Login.vue template
    });

    // Computed properties
    const hasPermission = computed(() => (permission) => {
        return permissions.value.includes(permission);
    });

    const isSessionExpiring = computed(() => {
        if (!sessionInfo.value?.minutes_remaining) return false;
        return sessionInfo.value.minutes_remaining <= (AuthManager.config.warningThreshold / 60);
    });

    const timeUntilExpiry = computed(() => {
        if (!sessionInfo.value?.minutes_remaining) return 0;
        return Math.floor(sessionInfo.value.minutes_remaining);
    });

    // Initialize auth state
    const initializeAuth = async () => {
        await AuthManager.initialize();
    };

    // Login method
    const login = async (credentials = null) => {
        const loginData = credentials || {
            nip: loginForm.nip,
            password: loginForm.password,
            remember: loginForm.remember,
        };

        loginForm.processing = true;
        loginForm.errors = {};
        loginForm.message = null;

        try {
            const result = await AuthManager.login(loginData);
            
            // Handle redirect if successful
            if (result.success && result.redirect) {
                if (window.location.href !== result.redirect) {
                    try {
                        // Attempt Inertia navigation first
                        if (router) {
                            router.visit(result.redirect, { replace: true });
                        } else {
                            // Fallback if router is not available
                            window.location.href = result.redirect;
                        }
                    } catch (navError) {
                        console.warn("Inertia navigation failed, falling back to standard redirect:", navError);
                        window.location.href = result.redirect;
                    }
                }
            }
            
            return result;
        } catch (error) {
            let errorMessage = "Terjadi kesalahan saat login. Silakan coba lagi.";
            let errors = {};

            if (error.response?.data?.message) {
                errorMessage = error.response.data.message;
            } 
            
            if (error.response?.data?.errors) {
                errors = error.response.data.errors;
                loginForm.errors = errors;
            }
            
            // Handle specific error codes
             if (error.response?.data?.error_code === "ACCOUNT_LOCKED") {
                errorMessage = `Akun Anda terkunci. Coba lagi dalam ${error.response.data.lockout_time_remaining} menit.`;
            }

            return {
                success: false,
                message: errorMessage,
                errors: errors
            };
        } finally {
            loginForm.processing = false;
        }
    };

    // Logout method
    const logout = async () => {
        await AuthManager.logout();
    };

    // Refresh session
    const refreshSession = async () => {
        // Just trigger a check, or use extend if strictly needed
        await AuthManager.checkSession();
        return true;
    };
    
    // Extend session
    const extendSession = async () => {
        return await AuthManager.extendSession();
    };

    // Check NIP availability (Proxy to API)
    const checkNip = async (nip) => {
        try {
            const response = await axios.post("/api/check-nip", { nip });
            return response.data;
        } catch (error) {
            return { exists: false, role: null, active: false };
        }
    };

    // Get login attempts status (Proxy to API)
    const getAttemptsStatus = async (nip) => {
        try {
            const response = await axios.post("/api/attempts-status", { nip });
            return response.data;
        } catch (error) {
            return {
                is_locked_out: false,
                attempts_remaining: 5,
                lockout_time_remaining: 0,
            };
        }
    };

    // Form helpers
    const clearFormErrors = () => {
        loginForm.errors = {};
        loginForm.message = null;
    };

    const setFormError = (field, message) => {
        loginForm.errors[field] = message;
    };

    const validateForm = () => {
        const errors = {};

        if (!loginForm.nip.trim()) {
            errors.nip = "NIP wajib diisi";
        } else if (loginForm.nip.length !== 18) {
            errors.nip = "NIP harus terdiri dari 18 digit";
        } else if (!/^\d+$/.test(loginForm.nip)) {
            errors.nip = "NIP hanya boleh berisi angka";
        }

        if (!loginForm.password.trim()) {
            errors.password = "Password wajib diisi";
        } else if (loginForm.password.length < 6) {
            errors.password = "Password minimal 6 karakter";
        }

        loginForm.errors = errors;
        return Object.keys(errors).length === 0;
    };

    // Lifecycle
    onMounted(() => {
        // Ensure manager is initialized
        if (!AuthManager.state.initialized) {
            AuthManager.initialize();
        }
    });

    return {
        // State
        user,
        isAuthenticated,
        isLoading,
        sessionInfo,
        permissions,
        role,
        loginForm,
        
        // Computed
        hasPermission,
        isSessionExpiring,
        timeUntilExpiry,

        // Methods
        login,
        logout,
        refreshSession,
        extendSession,
        checkNip,
        getAttemptsStatus,
        initializeAuth,
        clearFormErrors,
        setFormError,
        validateForm,
        
        // Placeholder for active sessions if used by components (can be implemented in Manager if needed)
        activeSessions: ref([]), 
        getActiveSessions: async () => ({ success: true, sessions: [] }), 
        terminateSession: async () => ({ success: false, message: 'Not implemented' }),
        terminateAllSessions: async () => ({ success: false, message: 'Not implemented' }),
    };
}
