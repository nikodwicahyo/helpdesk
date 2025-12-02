/**
 * Authentication Error Codes
 *
 * Centralized list of backend error codes that should trigger auth reset and redirect.
 * Both app.js and IntegratedAuthManager should use these constants for consistency.
 */

export const AUTH_ERROR_CODES = {
  SESSION_EXPIRED: 'SESSION_EXPIRED',
  SESSION_HIJACKED: 'SESSION_HIJACKED',
  UNAUTHENTICATED: 'UNAUTHENTICATED'
};

/**
 * Check if an error response should trigger an auth reset
 */
export function shouldTriggerAuthReset(error) {
  if (!error) return false;

  // Check for explicit auth failure status codes
  if (error.response?.status === 401) {
    return true;
  }

  // Check for specific error codes
  const errorCode = error.response?.data?.error_code;
  if (errorCode && Object.values(AUTH_ERROR_CODES).includes(errorCode)) {
    return true;
  }

  // Check for unauthenticated message
  const message = error.response?.data?.message || '';
  if (message.toLowerCase().includes('unauthenticated')) {
    return true;
  }

  return false;
}

/**
 * Check if an error is a network/server error (should NOT trigger auth reset)
 */
export function isNetworkOrServerError(error) {
  if (!error) return false;

  // Network errors
  if (error.code === 'NETWORK_ERROR' ||
      error.message?.includes('Network') ||
      error.message?.includes('ECONNREFUSED') ||
      error.message?.includes('timeout') ||
      error.message?.includes('ERR_NETWORK')) {
    return true;
  }

  // Server errors (5xx)
  if (error.response?.status >= 500) {
    return true;
  }

  return false;
}