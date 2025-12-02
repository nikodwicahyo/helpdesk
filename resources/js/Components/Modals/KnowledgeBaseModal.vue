<template>
    <teleport to="body">
        <transition
            enter-active-class="transition-opacity ease-out duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity ease-in duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <!-- Background Overlay with blur -->
                    <div class="fixed inset-0 bg-black/40 backdrop-blur-sm transition-opacity" @click="closeModal"></div>

                    <!-- Modal Panel -->
                    <transition
                        enter-active-class="transition-all ease-out duration-300"
                        enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                        leave-active-class="transition-all ease-in duration-200"
                        leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                        leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    >
                        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                            <!-- Header -->
                            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                <div class="sm:flex sm:items-start">
                                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                                        <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                        </svg>
                                    </div>
                                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                                        <h3 class="text-lg leading-6 font-medium text-gray-900">
                                            {{ t('nav.knowledgeBase') }}
                                        </h3>
                                        <div class="mt-2">
                                            <p class="text-sm text-gray-500">
                                                {{ t('modal.knowledgeBase.searchDescription') }}
                                            </p>
                                        </div>
                                    </div>
                                    <button
                                        @click="closeModal"
                                        class="text-gray-400 hover:text-gray-600 transition"
                                    >
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Body -->
                            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pt-4">
                                <!-- Search -->
                                <div class="mb-6">
                                    <div class="relative">
                                        <input
                                            v-model="searchQuery"
                                            type="text"
                                            :placeholder="t('modal.knowledgeBase.searchPlaceholder')"
                                            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                            @input="searchArticles"
                                        >
                                        <svg class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                        </svg>
                                    </div>
                                </div>

                                <!-- Articles List -->
                                <div class="space-y-4 max-h-96 overflow-y-auto">
                                    <div
                                        v-for="article in filteredArticles"
                                        :key="article.id"
                                        class="border border-gray-200 rounded-lg p-4 hover:border-blue-300 hover:bg-blue-50 transition cursor-pointer"
                                        @click="selectArticle(article)"
                                    >
                                        <div class="flex items-start space-x-3">
                                            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                                                <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                                </svg>
                                            </div>
                                            <div class="flex-1">
                                                <h4 class="text-sm font-medium text-gray-900">{{ article.title }}</h4>
                                                <p class="text-sm text-gray-600 mt-1">{{ article.summary }}</p>
                                                <div class="flex items-center space-x-4 mt-2 text-xs text-gray-500">
                                                    <span>{{ t('ticket.category') }}: {{ article.category }}</span>
                                                    <span>{{ t('common.updated') }}: {{ formatDate(article.updated_at) }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- No Results -->
                                    <div v-if="filteredArticles.length === 0" class="text-center py-8">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                        </svg>
                                        <p class="mt-2 text-sm text-gray-500">
                                            {{ searchQuery ? t('modal.knowledgeBase.noArticlesFound') : t('modal.knowledgeBase.noArticlesAvailable') }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                <button
                                    type="button"
                                    @click="closeModal"
                                    class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:w-auto sm:text-sm"
                                >
                                    {{ t('common.close') }}
                                </button>
                            </div>
                        </div>
                    </transition>
                </div>
            </div>
        </transition>
    </teleport>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    articles: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['close', 'article-selected']);

const searchQuery = ref('');

const filteredArticles = computed(() => {
    if (!searchQuery.value) {
        return props.articles;
    }

    const search = searchQuery.value.toLowerCase();
    return props.articles.filter(article => {
        return article.title.toLowerCase().includes(search) ||
               article.summary.toLowerCase().includes(search) ||
               article.content.toLowerCase().includes(search) ||
               article.category.toLowerCase().includes(search);
    });
});

const searchArticles = () => {
    // Search is reactive via computed property
};

const selectArticle = (article) => {
    emit('article-selected', article);
    closeModal();
};

const closeModal = () => {
    searchQuery.value = '';
    emit('close');
};

const formatDate = (dateString) => {
    if (!dateString) return '';
    const lang = document.documentElement.lang;
    const options = { month: 'short', day: 'numeric', year: 'numeric' };
    try {
        return new Intl.DateTimeFormat(lang, options).format(new Date(dateString));
    } catch (e) {
        return new Intl.DateTimeFormat('en-US', options).format(new Date(dateString));
    }
};

// Reset search when modal closes
watch(() => props.show, (newShow) => {
    if (!newShow) {
        searchQuery.value = '';
    }
});
</script>
