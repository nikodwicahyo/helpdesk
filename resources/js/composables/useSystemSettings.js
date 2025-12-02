import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

/**
 * Composable for accessing system settings throughout the application
 * Settings are shared globally via HandleInertiaRequests middleware
 */
export function useSystemSettings() {
    const page = usePage();

    const settings = computed(() => page.props.systemSettings || {});

    const systemName = computed(() => settings.value.system_name || 'HelpDesk Kemlu');
    const systemEmail = computed(() => settings.value.system_email || 'support@kemlu.go.id');
    const timezone = computed(() => settings.value.timezone || 'Asia/Jakarta');
    const defaultLanguage = computed(() => settings.value.default_language || 'id');
    const itemsPerPage = computed(() => settings.value.items_per_page || 15);

    /**
     * Get page title with system name
     */
    const getPageTitle = (pageTitle) => {
        return pageTitle ? `${pageTitle} - ${systemName.value}` : systemName.value;
    };

    /**
     * Format date according to system timezone
     */
    const formatDate = (date, format = 'default') => {
        if (!date) return '';
        
        const dateObj = new Date(date);
        const options = {
            timeZone: timezone.value,
        };

        switch (format) {
            case 'short':
                return dateObj.toLocaleDateString(defaultLanguage.value === 'id' ? 'id-ID' : 'en-US', {
                    ...options,
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                });
            case 'long':
                return dateObj.toLocaleDateString(defaultLanguage.value === 'id' ? 'id-ID' : 'en-US', {
                    ...options,
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                });
            case 'time':
                return dateObj.toLocaleTimeString(defaultLanguage.value === 'id' ? 'id-ID' : 'en-US', {
                    ...options,
                    hour: '2-digit',
                    minute: '2-digit',
                });
            case 'datetime':
                return dateObj.toLocaleString(defaultLanguage.value === 'id' ? 'id-ID' : 'en-US', {
                    ...options,
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                });
            default:
                return dateObj.toLocaleDateString(defaultLanguage.value === 'id' ? 'id-ID' : 'en-US', options);
        }
    };

    return {
        // Settings
        settings,
        systemName,
        systemEmail,
        timezone,
        defaultLanguage,
        itemsPerPage,
        
        // Helper functions
        getPageTitle,
        formatDate,
    };
}
