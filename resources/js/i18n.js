import { createI18n } from 'vue-i18n';

// Import translation files
import id from './locales/id.json';
import en from './locales/en.json';

// Get initial locale with priority: localStorage > systemSettings > default
const getInitialLocale = () => {
    // Priority 1: Check localStorage for user preference
    if (typeof window !== 'undefined') {
        const savedLanguage = localStorage.getItem('user_language');
        if (savedLanguage) {
            return savedLanguage;
        }
    }
    
    // Priority 2: Check if we have systemSettings from Inertia
    if (typeof window !== 'undefined' && window._inertiaInitialPage) {
        const props = window._inertiaInitialPage.props;
        if (props.systemSettings && props.systemSettings.default_language) {
            return props.systemSettings.default_language;
        }
    }
    
    // Priority 3: Fall back to default Indonesian
    return 'id';
};

const i18n = createI18n({
    legacy: false, // Use Composition API mode
    locale: getInitialLocale(),
    fallbackLocale: 'id',
    messages: {
        id,
        en
    },
    // Additional options
    globalInjection: true, // Inject $t, $tc, etc. globally
    silentTranslationWarn: true, // Disable warnings in development
    silentFallbackWarn: true,
});

export default i18n;
