<template>
    <div class="relative" ref="dropdown">
        <button
            @click="isOpen = !isOpen"
            class="flex items-center space-x-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-lg transition-colors duration-200"
            :title="$t('language.switchLanguage')"
        >
            <!-- Flag Icon -->
            <span class="hidden sm:inline">{{ currentLanguage === 'id' ? 'ID' : 'EN' }}</span>
            <svg 
                class="w-4 h-4 transition-transform duration-200"
                :class="{ 'rotate-180': isOpen }"
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <!-- Dropdown -->
        <transition
            enter-active-class="transition ease-out duration-100"
            enter-from-class="transform opacity-0 scale-95"
            enter-to-class="transform opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="transform opacity-100 scale-100"
            leave-to-class="transform opacity-0 scale-95"
        >
            <div
                v-show="isOpen"
                class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50"
            >
                <button
                    @click="changeLanguage('id')"
                    class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors duration-200"
                    :class="{ 'bg-indigo-50 text-indigo-600': currentLanguage === 'id' }"
                >
                    <span class="text-sm mr-3">ID</span>
                    <span>Bahasa Indonesia</span>
                    <svg
                        v-if="currentLanguage === 'id'"
                        class="w-5 h-5 ml-auto text-indigo-600"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                    >
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                </button>

                <button
                    @click="changeLanguage('en')"
                    class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors duration-200"
                    :class="{ 'bg-indigo-50 text-indigo-600': currentLanguage === 'en' }"
                >
                    <span class="text-sm mr-3">EN</span>
                    <span>English</span>
                    <svg
                        v-if="currentLanguage === 'en'"
                        class="w-5 h-5 ml-auto text-indigo-600"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                    >
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
        </transition>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const page = usePage();
const { locale, t } = useI18n();
const isOpen = ref(false);
const dropdown = ref(null);

// Get current language from i18n
const currentLanguage = computed(() => {
    return locale.value;
});

// Change language
const changeLanguage = (language) => {
    if (language === currentLanguage.value) {
        isOpen.value = false;
        return;
    }

    // Update i18n locale immediately for instant UI response
    locale.value = language;
    localStorage.setItem('user_language', language);

    // Send request to update language preference in backend
    router.post('/language/switch', {
        language: language
    }, {
        preserveState: false, // Reload to apply new language everywhere
        preserveScroll: true,
        onSuccess: () => {
            isOpen.value = false;
        },
        onError: () => {
            alert(t('message.operationFailed'));
        }
    });
};

// Close dropdown when clicking outside
const handleClickOutside = (event) => {
    if (dropdown.value && !dropdown.value.contains(event.target)) {
        isOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<style scoped>
.rotate-180 {
    transform: rotate(180deg);
}
</style>
