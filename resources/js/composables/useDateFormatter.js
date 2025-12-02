/**
 * Vue Composable for Date Formatting
 * Provides reactive date formatting utilities across all Vue components
 */

import {
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
} from '@/utils/dateFormatter';

export function useDateFormatter() {
    return {
        // Main formatting functions
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
        formatApiResponse,

        // Convenience aliases for common use cases
        formatDateTime: formatDate,
        formatFullDateTime: formatToLocal,
        formatRelativeTime: formatTimeAgo,
        formatCreationTime: formatTimeAgo,
        formatUpdateTime: formatTimeAgo,
    };
}

// Export as default for easier imports
export default useDateFormatter;
