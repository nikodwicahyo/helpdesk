/**
 * Centralized Date Formatting Utility
 * Provides consistent date formatting across the entire application
 */

/**
 * Format date string to consistent format: YYYY-MM-DD HH:mm
 * @param {string} dateString - The date string to format
 * @returns {string} Formatted date string or empty string if invalid
 */
export const formatDate = (dateString) => {
    if (!dateString) return '';

    try {
        const date = new Date(dateString);

        // Check if date is valid
        if (isNaN(date.getTime())) {
            return '';
        }

        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');

        return `${year}-${month}-${day} ${hours}:${minutes}`;
    } catch (error) {
        console.warn('Error formatting date:', error);
        return '';
    }
};

/**
 * Format date string to Indonesian locale format
 * @param {string} dateString - The date string to format
 * @returns {string} Formatted date string in Indonesian locale
 */
export const formatDateIndonesian = (dateString) => {
    if (!dateString) return '';

    try {
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return '';

        return date.toLocaleDateString('id-ID', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        });
    } catch (error) {
        console.warn('Error formatting Indonesian date:', error);
        return '';
    }
};

/**
 * Format time only from date string
 * @param {string} dateString - The date string to format
 * @returns {string} Formatted time string
 */
export const formatTime = (dateString) => {
    if (!dateString) return '';

    try {
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return '';

        return date.toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false
        });
    } catch (error) {
        console.warn('Error formatting time:', error);
        return '';
    }
};

/**
 * Format time with seconds
 * @param {string} dateString - The date string to format
 * @returns {string} Formatted time string with seconds
 */
export const formatTimeWithSeconds = (dateString) => {
    if (!dateString) return '';

    try {
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return '';

        return date.toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false
        });
    } catch (error) {
        console.warn('Error formatting time with seconds:', error);
        return '';
    }
};

/**
 * Format duration in minutes to human readable format
 * @param {number} minutes - Duration in minutes
 * @returns {string} Formatted duration string
 */
export const formatDuration = (minutes) => {
    if (!minutes || minutes <= 0) return '0 minutes';

    try {
        const totalMinutes = Math.round(minutes);
        const hours = Math.floor(totalMinutes / 60);
        const remainingMinutes = totalMinutes % 60;

        if (hours > 0) {
            if (remainingMinutes > 0) {
                return `${hours} hour${hours > 1 ? 's' : ''} ${remainingMinutes} minute${remainingMinutes > 1 ? 's' : ''}`;
            } else {
                return `${hours} hour${hours > 1 ? 's' : ''}`;
            }
        } else {
            return `${remainingMinutes} minute${remainingMinutes > 1 ? 's' : ''}`;
        }
    } catch (error) {
        console.warn('Error formatting duration:', error);
        return `${Math.round(minutes)} minutes`;
    }
};

/**
 * Format session time remaining
 * @param {number} minutes - Minutes remaining
 * @returns {string} Formatted time remaining string
 */
export const formatTimeRemaining = (minutes) => {
    if (!minutes || minutes <= 0) return 'Expired';

    try {
        const totalMinutes = Math.round(minutes);

        if (totalMinutes <= 1) {
            return 'Less than 1 minute';
        } else if (totalMinutes < 60) {
            return `${totalMinutes} minute${totalMinutes > 1 ? 's' : ''}`;
        } else {
            const hours = Math.floor(totalMinutes / 60);
            const remainingMinutes = totalMinutes % 60;

            if (remainingMinutes > 0) {
                return `${hours} hour${hours > 1 ? 's' : ''} ${remainingMinutes} min${remainingMinutes > 1 ? 's' : ''}`;
            } else {
                return `${hours} hour${hours > 1 ? 's' : ''}`;
            }
        }
    } catch (error) {
        console.warn('Error formatting time remaining:', error);
        return `${Math.round(minutes)} minutes`;
    }
};

/**
 * Format ISO date string to local format
 * @param {string} isoString - ISO date string
 * @returns {string} Formatted local date string
 */
export const formatToLocal = (isoString) => {
    if (!isoString) return '';

    try {
        const date = new Date(isoString);
        if (isNaN(date.getTime())) return '';

        return date.toLocaleString('en-US', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            hour12: false
        });
    } catch (error) {
        console.warn('Error formatting to local:', error);
        return '';
    }
};

/**
 * Get relative time (e.g., "2 hours ago")
 * @param {string} dateString - The date string to format
 * @returns {string} Relative time string
 */
export const formatTimeAgo = (dateString) => {
    if (!dateString) return '';

    try {
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return '';

        const now = new Date();
        const diffInSeconds = Math.floor((now - date) / 1000);

        if (diffInSeconds < 60) return 'just now';
        if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)} minutes ago`;
        if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)} hours ago`;
        if (diffInSeconds < 604800) return `${Math.floor(diffInSeconds / 86400)} days ago`;

        return formatDate(dateString);
    } catch (error) {
        console.warn('Error formatting time ago:', error);
        return '';
    }
};

/**
 * Format API duration from milliseconds to readable format
 * @param {string} durationMs - Duration string in milliseconds (e.g., "119ms")
 * @returns {string} Formatted duration string
 */
export const formatApiDuration = (durationMs) => {
    if (!durationMs) return '0ms';

    try {
        // Extract numeric value from string like "119ms"
        const numericValue = parseFloat(durationMs.replace(/[^\d.]/g, ''));

        if (isNaN(numericValue)) return durationMs;

        if (numericValue < 1000) {
            return `${Math.round(numericValue)}ms`;
        } else if (numericValue < 60000) {
            const seconds = (numericValue / 1000).toFixed(2);
            return `${seconds}s`;
        } else {
            const minutes = (numericValue / 60000).toFixed(2);
            return `${minutes}m`;
        }
    } catch (error) {
        console.warn('Error formatting API duration:', error);
        return durationMs;
    }
};

/**
 * Format API timestamp from ISO format to local format
 * @param {string} isoTimestamp - ISO timestamp string (e.g., "2025-11-11T17:02:22.226Z")
 * @returns {string} Formatted local timestamp
 */
export const formatApiTimestamp = (isoTimestamp) => {
    // Reuse existing formatToLocal function
    return formatToLocal(isoTimestamp);
};

/**
 * Format API response timestamp to Indonesian format
 * @param {string} isoTimestamp - ISO timestamp string
 * @returns {string} Formatted Indonesian timestamp
 */
export const formatApiTimestampIndonesian = (isoTimestamp) => {
    if (!isoTimestamp) return '';

    try {
        const date = new Date(isoTimestamp);
        if (isNaN(date.getTime())) return '';

        // Combine Indonesian date format with time format
        const indonesianDate = date.toLocaleDateString('id-ID', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        });

        const time = formatTimeWithSeconds(isoTimestamp);

        return `${indonesianDate}, ${time}`;
    } catch (error) {
        console.warn('Error formatting API timestamp Indonesian:', error);
        return isoTimestamp;
    }
};

/**
 * Format API response data with consistent timestamp and duration formatting
 * @param {Object} apiResponse - API response object with timestamp and duration
 * @returns {Object} Formatted API response data
 */
export const formatApiResponse = (apiResponse) => {
    if (!apiResponse || typeof apiResponse !== 'object') {
        return apiResponse;
    }

    const formatted = { ...apiResponse };

    if (formatted.timestamp) {
        formatted.formattedTimestamp = formatApiTimestamp(formatted.timestamp);
        formatted.formattedTimestampIndonesian = formatApiTimestampIndonesian(formatted.timestamp);
    }

    if (formatted.duration) {
        formatted.formattedDuration = formatApiDuration(formatted.duration);
    }

    return formatted;
};

/**
 * Default export with main formatting function
 */
export default {
    formatDate,
    formatDateIndonesian,
    formatTime,
    formatTimeWithSeconds,
    formatDuration,
    formatTimeRemaining,
    formatToLocal,
    formatTimeAgo,
    formatApiDuration,
    formatApiTimestamp,
    formatApiTimestampIndonesian,
    formatApiResponse
};