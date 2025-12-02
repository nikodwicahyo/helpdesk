<template>
    <AppLayout role="teknisi">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <Link
                        :href="route('teknisi.knowledge-base.index')"
                        class="text-gray-600 hover:text-gray-900"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </Link>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">{{ article.title }}</h1>
                        <p class="text-gray-600 mt-1">
                            <span v-if="article.author">By {{ article.author.name }}</span>
                            <span v-if="article.reading_time" class="ml-2">· {{ article.reading_time }} min read</span>
                        </p>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <span v-if="article.is_featured" class="px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                        ⭐ Featured
                    </span>
                    <span :class="[
                        'px-3 py-1 rounded-full text-sm font-medium',
                        article.status === 'published' ? 'bg-green-100 text-green-800' :
                        article.status === 'draft' ? 'bg-yellow-100 text-yellow-800' :
                        'bg-gray-100 text-gray-800'
                    ]">
                        {{ article.status_label }}
                    </span>
                </div>
            </div>
        </template>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Summary (if available) -->
                <div v-if="article.summary" class="bg-indigo-50 rounded-lg border-2 border-indigo-200 p-6">
                    <h2 class="text-lg font-semibold text-indigo-900 mb-2">Summary</h2>
                    <p class="text-gray-800">{{ article.summary }}</p>
                </div>

                <!-- Article Content -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="prose max-w-none text-gray-800 whitespace-pre-wrap">
                        {{ article.content }}
                    </div>
                </div>

                <!-- Helpful Actions -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Was this article helpful?</h3>
                    <div class="flex items-center space-x-4">
                        <button
                            @click="markAsHelpful"
                            :disabled="isMarkingHelpful || hasMarkedHelpful"
                            :class="[
                                'px-6 py-2 rounded-lg transition flex items-center',
                                hasMarkedHelpful 
                                    ? 'bg-green-100 text-green-800 cursor-default' 
                                    : 'bg-green-600 text-white hover:bg-green-700 disabled:opacity-50'
                            ]"
                        >
                            <svg v-if="isMarkingHelpful" class="animate-spin w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <svg v-else class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5" />
                            </svg>
                            {{ hasMarkedHelpful ? 'Thank you!' : 'Yes, helpful!' }}
                        </button>
                        <span class="text-sm text-gray-600">
                            {{ helpfulCount }} people found this helpful
                        </span>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Stats Card -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Article Stats</h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Views</span>
                            <span class="font-semibold text-gray-900">{{ article.view_count || 0 }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Helpful</span>
                            <span class="font-semibold text-gray-900">{{ helpfulCount }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Reading Time</span>
                            <span class="font-semibold text-gray-900">{{ article.reading_time }} min</span>
                        </div>
                    </div>
                </div>

                <!-- Details Card -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Details</h3>
                    <div class="space-y-3">
                        <div v-if="article.author">
                            <span class="text-sm text-gray-600">Author</span>
                            <p class="font-medium text-gray-900">{{ article.author.name }}</p>
                        </div>
                        <div v-if="article.aplikasi">
                            <span class="text-sm text-gray-600">Application</span>
                            <p class="font-medium text-gray-900">{{ article.aplikasi.name }}</p>
                        </div>
                        <div v-if="article.kategori_masalah">
                            <span class="text-sm text-gray-600">Category</span>
                            <p class="font-medium text-gray-900">{{ article.kategori_masalah.name }}</p>
                        </div>
                    </div>
                </div>

                <!-- Timeline Card -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Timeline</h3>
                    <div class="space-y-3">
                        <div>
                            <span class="text-sm text-gray-600">Published</span>
                            <p class="font-medium text-gray-900">{{ article.formatted_created_at }}</p>
                        </div>
                        <div>
                            <span class="text-sm text-gray-600">Last Updated</span>
                            <p class="font-medium text-gray-900">{{ article.formatted_updated_at }}</p>
                        </div>
                    </div>
                </div>

                <!-- Tags Card -->
                <div v-if="article.tags && article.tags.length > 0" class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Tags</h3>
                    <div class="flex flex-wrap gap-2">
                        <span
                            v-for="tag in article.tags"
                            :key="tag"
                            class="px-3 py-1 bg-indigo-100 text-indigo-800 text-sm rounded-full"
                        >
                            {{ tag }}
                        </span>
                    </div>
                </div>

                <!-- Edit Actions (for author) -->
                <div v-if="article.can_edit" class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Manage Article</h3>
                    <div class="space-y-3">
                        <Link
                            :href="route('teknisi.knowledge-base.index') + '?edit=' + article.id"
                            class="block w-full px-4 py-2 bg-indigo-600 text-white text-center rounded-lg hover:bg-indigo-700 transition"
                        >
                            Edit Article
                        </Link>
                        <button
                            @click="deleteArticle"
                            class="block w-full px-4 py-2 bg-red-600 text-white text-center rounded-lg hover:bg-red-700 transition"
                        >
                            Delete Article
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    article: {
        type: Object,
        required: true,
    },
});

const helpfulCount = ref(props.article.helpful_count || 0);
const isMarkingHelpful = ref(false);
const hasMarkedHelpful = ref(false);

const markAsHelpful = async () => {
    if (isMarkingHelpful.value || hasMarkedHelpful.value) return;
    
    isMarkingHelpful.value = true;
    
    try {
        const response = await axios.post(route('teknisi.knowledge-base.helpful', props.article.id));
        
        if (response.data.success) {
            helpfulCount.value = response.data.helpful_count;
            hasMarkedHelpful.value = true;
        }
    } catch (error) {
        console.error('Failed to mark as helpful:', error);
    } finally {
        isMarkingHelpful.value = false;
    }
};

const deleteArticle = () => {
    if (confirm(`Are you sure you want to delete this article? This action cannot be undone.`)) {
        router.delete(route('teknisi.knowledge-base.destroy', props.article.id));
    }
};
</script>

<style scoped>
.prose {
    max-width: none;
}
</style>
