import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthManager from '../Services/AuthManager';
import { formatTimeRemaining } from '../utils/dateFormatter.js';

/**
 * useSession Composable (Refactored)
 *
 * Provides reactive session management functionality using AuthManager
 * Validates session against database user_sessions table via backend API
 */
export function useSession() {
  // Reactive state from AuthManager
  const sessionData = computed(() => AuthManager.state.session);
  const isExtending = ref(false);

  // Local state for warning/expired flags
  const sessionWarning = ref(false);
  const sessionExpired = ref(false);

  // Computed properties
  const shouldShowWarning = computed(() => {
    // Check if we have valid session data with minutes_remaining
    const minutes = sessionData.value?.minutes_remaining;
    if (minutes === undefined || minutes === null) return false;
    
    // Show warning if within 10 minutes of expiry but not yet expired
    const warningThresholdMinutes = AuthManager.config.warningThreshold / 60; // 10 minutes
    return minutes <= warningThresholdMinutes && minutes > 0 && !sessionExpired.value;
  });

  const timeUntilExpiry = computed(() => {
    const minutes = sessionData.value?.minutes_remaining;
    const seconds = sessionData.value?.seconds_remaining;
    
    if (minutes === undefined || minutes === null) {
      return { formatted: 'Unknown', minutes: 0, seconds: 0 };
    }

    const minutesVal = Math.max(0, Math.floor(minutes));
    const secondsVal = seconds !== undefined ? Math.max(0, seconds) : minutesVal * 60;

    return {
      formatted: formatTimeRemaining(minutesVal),
      minutes: minutesVal,
      seconds: secondsVal
    };
  });

  // Check if session is expired
  const isSessionExpired = computed(() => {
    const minutes = sessionData.value?.minutes_remaining;
    return minutes !== undefined && minutes <= 0;
  });

  // Event handlers
  const handleWarning = (event) => {
    console.log('[useSession] Session warning triggered:', event.detail);
    sessionWarning.value = true;
    sessionExpired.value = false;
  };

  const handleExpired = () => {
    console.log('[useSession] Session expired');
    sessionExpired.value = true;
    sessionWarning.value = false;
  };

  const handleExtended = (event) => {
    console.log('[useSession] Session extended:', event.detail);
    sessionWarning.value = false;
    sessionExpired.value = false;
  };

  // Methods
  const extendSession = async () => {
    try {
      isExtending.value = true;
      const result = await AuthManager.extendSession();

      if (result) {
        console.log('[useSession] Session extended successfully');
        sessionWarning.value = false;
        sessionExpired.value = false;
        return { success: true };
      } else {
        // Extension failed - session may have expired
        console.warn('[useSession] Session extension failed');
        return { success: false };
      }
    } catch (error) {
      console.error('[useSession] Session extension error:', error);
      throw error;
    } finally {
      isExtending.value = false;
    }
  };

  const logout = async () => {
    await AuthManager.logout();
  };

  // Lifecycle
  onMounted(() => {
    window.addEventListener('session:warning', handleWarning);
    window.addEventListener('session:expired', handleExpired);
    window.addEventListener('session:extended', handleExtended);
    
    // Initialize AuthManager if needed
    if (!AuthManager.state.initialized) {
        AuthManager.initialize();
    }
  });

  onUnmounted(() => {
    window.removeEventListener('session:warning', handleWarning);
    window.removeEventListener('session:expired', handleExpired);
    window.removeEventListener('session:extended', handleExtended);
  });

  return {
    // State
    sessionWarning,
    sessionExpired,
    sessionData,
    isExtending,

    // Computed
    shouldShowWarning,
    timeUntilExpiry,
    isSessionExpired,

    // Methods
    extendSession,
    logout
  };
}
