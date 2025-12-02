<template>
    <TransitionRoot as="template" :show="show">
        <Dialog as="div" class="relative z-50" @close="$emit('close')">
            <TransitionChild
                as="template"
                enter="ease-out duration-300"
                enter-from="opacity-0"
                enter-to="opacity-100"
                leave="ease-in duration-200"
                leave-from="opacity-100"
                leave-to="opacity-0"
            >
                <div class="fixed inset-0 bg-black/40 backdrop-blur-sm transition-opacity" />
            </TransitionChild>

            <div class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <TransitionChild
                        as="template"
                        enter="ease-out duration-300"
                        enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        enter-to="opacity-100 translate-y-0 sm:scale-100"
                        leave="ease-in duration-200"
                        leave-from="opacity-100 translate-y-0 sm:scale-100"
                        leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    >
                        <DialogPanel class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-4xl">
                            <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                                <div class="flex items-center justify-between mb-6">
                                    <DialogTitle as="h3" class="text-2xl font-semibold leading-6 text-gray-900">
                                        {{ mode === 'create' ? 'Create New Article' : 'Edit Article' }}
                                    </DialogTitle>
                                    <button
                                        @click="$emit('close')"
                                        class="text-gray-400 hover:text-gray-500 focus:outline-none"
                                    >
                                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>

                                <form @submit.prevent="handleSubmit" class="space-y-6">
                                    <!-- Title -->
                                    <div>
                                        <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                                            Title <span class="text-red-500">*</span>
                                        </label>
                                        <input
                                            v-model="form.title"
                                            type="text"
                                            id="title"
                                            required
                                            maxlength="255"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                            placeholder="Enter article title..."
                                        />
                                        <p v-if="errors.title" class="mt-1 text-sm text-red-600">{{ errors.title }}</p>
                                    </div>

                                    <!-- Summary -->
                                    <div>
                                        <label for="summary" class="block text-sm font-medium text-gray-700 mb-2">
                                            Summary
                                        </label>
                                        <textarea
                                            v-model="form.summary"
                                            id="summary"
                                            rows="2"
                                            maxlength="500"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                            placeholder="Brief summary of the article (optional)..."
                                        />
                                        <p class="mt-1 text-xs text-gray-500">{{ form.summary?.length || 0 }}/500 characters</p>
                                    </div>

                                    <!-- Application and Category -->
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label for="aplikasi_id" class="block text-sm font-medium text-gray-700 mb-2">
                                                Application
                                            </label>
                                            <select
                                                v-model="form.aplikasi_id"
                                                @change="filterCategories"
                                                id="aplikasi_id"
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                            >
                                                <option :value="null">Select Application</option>
                                                <option v-for="app in applications" :key="app.id" :value="app.id">
                                                    {{ app.name }}
                                                </option>
                                            </select>
                                        </div>

                                        <div>
                                            <label for="kategori_masalah_id" class="block text-sm font-medium text-gray-700 mb-2">
                                                Category
                                            </label>
                                            <select
                                                v-model="form.kategori_masalah_id"
                                                id="kategori_masalah_id"
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                                :disabled="!form.aplikasi_id && filteredCategories.length === 0"
                                            >
                                                <option :value="null">Select Category</option>
                                                <option v-for="cat in filteredCategories" :key="cat.id" :value="cat.id">
                                                    {{ cat.name }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Rich Text Editor for Content -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Content <span class="text-red-500">*</span>
                                        </label>
                                        <div class="border border-gray-300 rounded-lg overflow-hidden">
                                            <!-- Simple Rich Text Toolbar -->
                                            <div class="bg-gray-50 border-b border-gray-300 px-3 py-2 flex items-center space-x-2">
                                                <button
                                                    type="button"
                                                    @click="formatText('bold')"
                                                    class="p-2 hover:bg-gray-200 rounded"
                                                    title="Bold"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 12h12M6 6h12M6 18h12" />
                                                    </svg>
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="formatText('italic')"
                                                    class="p-2 hover:bg-gray-200 rounded"
                                                    title="Italic"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                                                    </svg>
                                                </button>
                                                <span class="text-gray-300">|</span>
                                                <button
                                                    type="button"
                                                    @click="insertList('ul')"
                                                    class="p-2 hover:bg-gray-200 rounded"
                                                    title="Bullet List"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                                    </svg>
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="insertList('ol')"
                                                    class="p-2 hover:bg-gray-200 rounded"
                                                    title="Numbered List"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                                    </svg>
                                                </button>
                                            </div>
                                            <textarea
                                                v-model="form.content"
                                                ref="contentEditor"
                                                required
                                                rows="12"
                                                class="w-full px-4 py-3 focus:ring-0 focus:outline-none border-0"
                                                placeholder="Write your article content here... You can use basic HTML formatting."
                                            />
                                        </div>
                                        <p v-if="errors.content" class="mt-1 text-sm text-red-600">{{ errors.content }}</p>
                                        <p class="mt-1 text-xs text-gray-500">
                                            Estimated reading time: {{ estimatedReadingTime }} minutes
                                        </p>
                                    </div>

                                    <!-- Tags Input -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Tags
                                        </label>
                                        <div class="flex items-center space-x-2 mb-2">
                                            <input
                                                v-model="tagInput"
                                                @keydown.enter.prevent="addTag"
                                                @keydown.comma.prevent="addTag"
                                                type="text"
                                                class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                                placeholder="Type tag and press Enter or comma..."
                                                maxlength="50"
                                            />
                                            <button
                                                type="button"
                                                @click="addTag"
                                                class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700"
                                            >
                                                Add
                                            </button>
                                        </div>
                                        <div v-if="form.tags.length > 0" class="flex flex-wrap gap-2">
                                            <span
                                                v-for="(tag, index) in form.tags"
                                                :key="index"
                                                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-indigo-100 text-indigo-800"
                                            >
                                                {{ tag }}
                                                <button
                                                    type="button"
                                                    @click="removeTag(index)"
                                                    class="ml-2 hover:text-indigo-600"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </button>
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Status and Featured -->
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                                                Status <span class="text-red-500">*</span>
                                            </label>
                                            <select
                                                v-model="form.status"
                                                id="status"
                                                required
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                            >
                                                <option value="draft">Draft</option>
                                                <option value="published">Published</option>
                                                <option v-if="mode === 'edit'" value="archived">Archived</option>
                                            </select>
                                        </div>

                                        <div class="flex items-end">
                                            <label class="flex items-center space-x-2 cursor-pointer">
                                                <input
                                                    v-model="form.is_featured"
                                                    type="checkbox"
                                                    class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                                                />
                                                <span class="text-sm font-medium text-gray-700">Mark as Featured Article</span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Error Messages -->
                                    <div v-if="Object.keys(errors).length > 0" class="rounded-lg bg-red-50 p-4">
                                        <div class="flex">
                                            <div class="flex-shrink-0">
                                                <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </div>
                                            <div class="ml-3">
                                                <h3 class="text-sm font-medium text-red-800">
                                                    Please correct the following errors:
                                                </h3>
                                                <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                                                    <li v-for="(error, key) in errors" :key="key">{{ error }}</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Form Actions -->
                                    <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                                        <button
                                            v-if="mode === 'edit' && form.status !== 'draft'"
                                            type="button"
                                            @click="saveDraft"
                                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
                                            :disabled="submitting"
                                        >
                                            Save as Draft
                                        </button>
                                        <div v-else></div>

                                        <div class="flex items-center space-x-3">
                                            <button
                                                type="button"
                                                @click="$emit('close')"
                                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
                                                :disabled="submitting"
                                            >
                                                Cancel
                                            </button>
                                            <button
                                                type="submit"
                                                class="px-6 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed"
                                                :disabled="submitting"
                                            >
                                                <span v-if="!submitting">
                                                    {{ mode === 'create' ? 'Create Article' : 'Update Article' }}
                                                </span>
                                                <span v-else class="flex items-center">
                                                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                    </svg>
                                                    Saving...
                                                </span>
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>

<script setup>
import { ref, computed, watch, onMounted, nextTick } from 'vue';
import { router } from '@inertiajs/vue3';
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue';

const props = defineProps({
    show: {
        type: Boolean,
        required: true,
    },
    article: {
        type: Object,
        default: null,
    },
    categories: {
        type: Array,
        default: () => [],
    },
    applications: {
        type: Array,
        default: () => [],
    },
    mode: {
        type: String,
        default: 'create', // 'create' or 'edit'
    },
});

const emit = defineEmits(['close', 'saved']);

const form = ref({
    title: '',
    content: '',
    summary: '',
    kategori_masalah_id: null,
    aplikasi_id: null,
    tags: [],
    status: 'draft',
    is_featured: false,
});

const tagInput = ref('');
const errors = ref({});
const submitting = ref(false);
const contentEditor = ref(null);

// Filtered categories based on selected application
const filteredCategories = computed(() => {
    if (!form.value.aplikasi_id) {
        return props.categories;
    }
    return props.categories.filter(cat => cat.aplikasi_id === form.value.aplikasi_id);
});

// Estimated reading time based on content word count
const estimatedReadingTime = computed(() => {
    if (!form.value.content) return 0;
    const wordCount = form.value.content.split(/\s+/).length;
    return Math.max(1, Math.ceil(wordCount / 200)); // 200 words per minute
});

// Initialize form with article data if editing
watch(() => props.article, (newArticle) => {
    if (newArticle && props.mode === 'edit') {
        form.value = {
            title: newArticle.title || '',
            content: newArticle.content || '',
            summary: newArticle.summary || '',
            kategori_masalah_id: newArticle.kategori_masalah?.id || null,
            aplikasi_id: newArticle.aplikasi?.id || null,
            tags: newArticle.tags || [],
            status: newArticle.status || 'draft',
            is_featured: newArticle.is_featured || false,
        };
    }
}, { immediate: true });

// Reset form when modal is closed
watch(() => props.show, (newShow) => {
    if (!newShow) {
        resetForm();
    }
});

const resetForm = () => {
    if (props.mode === 'create') {
        form.value = {
            title: '',
            content: '',
            summary: '',
            kategori_masalah_id: null,
            aplikasi_id: null,
            tags: [],
            status: 'draft',
            is_featured: false,
        };
        tagInput.value = '';
    }
    errors.value = {};
};

const filterCategories = () => {
    // Reset category if it doesn't belong to selected application
    if (form.value.kategori_masalah_id) {
        const categoryExists = filteredCategories.value.some(
            cat => cat.id === form.value.kategori_masalah_id
        );
        if (!categoryExists) {
            form.value.kategori_masalah_id = null;
        }
    }
};

const addTag = () => {
    const tag = tagInput.value.trim().replace(',', '');
    if (tag && !form.value.tags.includes(tag) && form.value.tags.length < 10) {
        form.value.tags.push(tag);
        tagInput.value = '';
    }
};

const removeTag = (index) => {
    form.value.tags.splice(index, 1);
};

const formatText = (format) => {
    const textarea = contentEditor.value;
    if (!textarea) return;

    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const selectedText = form.value.content.substring(start, end);

    if (!selectedText) return;

    let formattedText = '';
    if (format === 'bold') {
        formattedText = `<strong>${selectedText}</strong>`;
    } else if (format === 'italic') {
        formattedText = `<em>${selectedText}</em>`;
    }

    form.value.content = 
        form.value.content.substring(0, start) +
        formattedText +
        form.value.content.substring(end);

    // Restore cursor position
    nextTick(() => {
        textarea.focus();
        textarea.setSelectionRange(start + formattedText.length, start + formattedText.length);
    });
};

const insertList = (type) => {
    const textarea = contentEditor.value;
    if (!textarea) return;

    const start = textarea.selectionStart;
    const listTag = type === 'ul' ? '<ul>\n<li>Item 1</li>\n<li>Item 2</li>\n</ul>' : '<ol>\n<li>Item 1</li>\n<li>Item 2</li>\n</ol>';

    form.value.content = 
        form.value.content.substring(0, start) +
        '\n' + listTag + '\n' +
        form.value.content.substring(start);

    nextTick(() => {
        textarea.focus();
    });
};

const saveDraft = () => {
    form.value.status = 'draft';
    handleSubmit();
};

const handleSubmit = async () => {
    errors.value = {};
    submitting.value = true;

    try {
        const url = props.mode === 'create'
            ? '/teknisi/knowledge-base'
            : `/teknisi/knowledge-base/${props.article.id}`;

        const method = props.mode === 'create' ? 'post' : 'put';

        const response = await axios[method](url, form.value);

        if (response.data.success) {
            emit('saved', response.data.article);
            emit('close');
            
            // Show success notification
            window.toast?.success(response.data.message || 'Article saved successfully');
            
            // Reload the page to reflect changes
            router.reload({ only: ['articles', 'stats'] });
        }
    } catch (error) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors || {};
        } else {
            errors.value = { general: error.response?.data?.message || 'An error occurred. Please try again.' };
        }
        
        window.toast?.error('Failed to save article. Please check the form and try again.');
    } finally {
        submitting.value = false;
    }
};

onMounted(() => {
    // Initialize form if editing
    if (props.article && props.mode === 'edit') {
        form.value = {
            title: props.article.title || '',
            content: props.article.content || '',
            summary: props.article.summary || '',
            kategori_masalah_id: props.article.kategori_masalah?.id || null,
            aplikasi_id: props.article.aplikasi?.id || null,
            tags: props.article.tags || [],
            status: props.article.status || 'draft',
            is_featured: props.article.is_featured || false,
        };
    }
});
</script>

<style scoped>
/* Custom styles for rich text editor */
textarea {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
}
</style>
