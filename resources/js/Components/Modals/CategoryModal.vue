<template>
    <Teleport to="body">
        <Transition name="modal">
            <div v-if="mode" class="fixed inset-0 z-50 overflow-y-auto" @click.self="closeModal">
                <!-- Backdrop with blur effect -->
                <div class="fixed inset-0 bg-black/40 backdrop-blur-sm transition-opacity"></div>

                <!-- Modal Container -->
                <div class="flex min-h-full items-center justify-center p-4 py-6">
                    <div class="relative bg-white rounded-lg shadow-xl transform transition-all max-w-lg w-full flex flex-col">
                        <form @submit.prevent="submit" class="h-full flex flex-col">
                            <!-- Header -->
                            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 flex-shrink-0">
                                    <div class="sm:flex sm:items-start">
                                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-purple-100 sm:mx-0 sm:h-10 sm:w-10">
                                            <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                            </svg>
                                        </div>
                                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                            <h3 class="text-lg leading-6 font-medium text-gray-900">
                                                {{ mode === 'create' ? t('modal.categoryModal.createTitle') : t('modal.categoryModal.editTitle') }}
                                            </h3>
                                            <div class="mt-2">
                                                <p class="text-sm text-gray-500">
                                                    {{ mode === 'create' ? t('modal.categoryModal.createDescription') : t('modal.categoryModal.editDescription') }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            <!-- Body -->
                            <div class="flex-1 overflow-y-auto px-4 pt-5 pb-4 sm:p-6 sm:pt-4 max-h-[50vh]">
                                    <div class="space-y-4">
                                        <!-- Application Selection -->
                                        <div>
                                            <label for="aplikasi_id" class="block text-sm font-medium text-gray-700">
                                                {{ t('ticket.application') }}
                                            </label>
                                            <select
                                                id="aplikasi_id"
                                                v-model="form.aplikasi_id"
                                                required
                                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500 sm:text-sm"
                                                :class="form.errors?.aplikasi_id ? 'border-red-300' : ''"
                                                :disabled="mode === 'edit' && category"
                                            >
                                                <option value="">{{ t('modal.categoryModal.selectApplication') }}</option>
                                                <option
                                                    v-for="app in applications"
                                                    :key="app.id"
                                                    :value="app.id"
                                                >
                                                    {{ app.nama_aplikasi || app.name }}
                                                </option>
                                            </select>
                                            <p v-if="form.errors?.aplikasi_id" class="mt-1 text-sm text-red-600">{{ form.errors.aplikasi_id }}</p>
                                        </div>

                                        <!-- Category Name -->
                                        <div>
                                            <label for="name" class="block text-sm font-medium text-gray-700">
                                                {{ t('modal.categoryModal.categoryName') }}
                                            </label>
                                            <input
                                                id="name"
                                                v-model="form.name"
                                                type="text"
                                                required
                                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500 sm:text-sm"
                                                :class="form.errors?.name ? 'border-red-300' : ''"
                                                :placeholder="t('modal.categoryModal.namePlaceholder')"
                                            >
                                            <p v-if="form.errors?.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                                        </div>

                                        <!-- Description -->
                                        <div>
                                            <label for="description" class="block text-sm font-medium text-gray-700">
                                                {{ t('ticket.description') }}
                                            </label>
                                            <textarea
                                                id="description"
                                                v-model="form.description"
                                                rows="3"
                                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500 sm:text-sm"
                                                :class="form.errors?.description ? 'border-red-300' : ''"
                                                :placeholder="t('modal.categoryModal.descriptionPlaceholder')"
                                            ></textarea>
                                            <p v-if="form.errors?.description" class="mt-1 text-sm text-red-600">{{ form.errors.description }}</p>
                                        </div>

                                        <!-- Status -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">{{ t('ticket.status') }}</label>
                                            <div class="flex items-center space-x-4">
                                                <label class="flex items-center">
                                                    <input
                                                        type="radio"
                                                        v-model="form.status"
                                                        value="active"
                                                        class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300"
                                                    >
                                                    <span class="ml-2 text-sm text-gray-700">{{ t('status.active') }}</span>
                                                </label>
                                                <label class="flex items-center">
                                                    <input
                                                        type="radio"
                                                        v-model="form.status"
                                                        value="inactive"
                                                        class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300"
                                                    >
                                                    <span class="ml-2 text-sm text-gray-700">{{ t('status.inactive') }}</span>
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Additional Settings (for edit mode) -->
                                        <div v-if="mode === 'edit' && category">
                                            <div class="bg-gray-50 p-4 rounded-lg">
                                                <h4 class="text-sm font-medium text-gray-900 mb-2">{{ t('modal.categoryModal.statsTitle') }}</h4>
                                                <dl class="grid grid-cols-2 gap-2 text-sm">
                                                    <div>
                                                        <dt class="text-gray-500">{{ t('dashboard.totalTickets') }}:</dt>
                                                        <dd class="font-medium text-gray-900">{{ category.total_tickets || 0 }}</dd>
                                                    </div>
                                                    <div>
                                                        <dt class="text-gray-500">{{ t('dashboard.openTickets') }}:</dt>
                                                        <dd class="font-medium text-gray-900">{{ category.open_tickets || 0 }}</dd>
                                                    </div>
                                                    <div>
                                                        <dt class="text-gray-500">{{ t('common.created') }}:</dt>
                                                        <dd class="font-medium text-gray-900">{{ formatDate(category.created_at) }}</dd>
                                                    </div>
                                                    <div>
                                                        <dt class="text-gray-500">{{ t('common.lastUpdated') }}:</dt>
                                                        <dd class="font-medium text-gray-900">{{ formatDate(category.updated_at) }}</dd>
                                                    </div>
                                                </dl>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            <!-- Actions -->
                            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse flex-shrink-0 border-t border-gray-200">
                                    <button
                                        type="submit"
                                        :disabled="processing"
                                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-purple-600 text-base font-medium text-white hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
                                    >
                                        <svg v-if="processing" class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        {{ processing ? t('common.saving') : (mode === 'create' ? t('common.create') : t('common.update')) }}
                                    </button>
                                    <button
                                        type="button"
                                        @click="closeModal"
                                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                                    >
                                        {{ t('common.cancel') }}
                                    </button>
                          </div>
                        </form>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { computed, watch, ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    category: {
        type: Object,
        default: null
    },
    applications: {
        type: Array,
        default: () => []
    },
    mode: {
        type: String,
        required: true,
        validator: (value) => ['create', 'edit'].includes(value)
    }
});

const emit = defineEmits(['close', 'saved']);

const show = computed(() => !!props.mode);

const form = useForm({
    aplikasi_id: '',
    name: '',
    description: '',
    status: 'active'
});

const processing = ref(false);

// Initialize form with category data if in edit mode
watch(() => props.category, (cat) => {
    if (cat && props.mode === 'edit') {
        form.aplikasi_id = cat.aplikasi_id || cat.aplikasi?.id || '';
        form.name = cat.name || '';
        form.description = cat.description || '';
        form.status = cat.status || 'active';
        form.clearErrors();
    } else if (props.mode === 'create') {
        form.reset();
        form.status = 'active';
    }
}, { immediate: true });

const submit = async () => {
    processing.value = true;

    const url = props.mode === 'create'
        ? route('admin.categories.store')
        : route('admin.categories.update', props.category.id);

    const method = props.mode === 'create' ? 'POST' : 'PUT';

    try {
        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
            body: JSON.stringify(form.data()),
        });

        const data = await response.json();

        if (data.success) {
            emit('saved');
            closeModal();
            // Reload the page to show updated data
            router.reload({ preserveScroll: true });
        } else {
            // Handle validation errors
            if (data.errors) {
                Object.keys(data.errors).forEach(key => {
                    form.setError(key, data.errors[key][0]);
                });
            }
        }
    } catch (error) {
        console.error('Error submitting form:', error);
        alert(t('errors.formSubmitError'));
    } finally {
        processing.value = false;
    }
};

const closeModal = () => {
    form.reset();
    emit('close');
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const lang = document.documentElement.lang;
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    
    try {
        return new Intl.DateTimeFormat(lang, options).format(new Date(dateString));
    } catch (e) {
        return new Intl.DateTimeFormat('en-US', options).format(new Date(dateString));
    }
};

// Show/hide modal based on mode prop
watch(() => props.mode, (newMode) => {
    if (!newMode) {
        form.reset();
    }
});
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.25s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-active .relative.bg-white,
.modal-leave-active .relative.bg-white {
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

.modal-enter-from .relative.bg-white,
.modal-leave-to .relative.bg-white {
  transform: scale(0.96) translateY(-10px);
  opacity: 0;
}

/* Ensure backdrop blur works on all browsers */
.backdrop-blur-sm {
  backdrop-filter: blur(4px);
  -webkit-backdrop-filter: blur(4px);
}

/* Improve scrolling experience */
.overflow-y-auto {
  scrollbar-width: thin;
  scrollbar-color: rgb(156 163 175) rgb(243 244 246);
}

.overflow-y-auto::-webkit-scrollbar {
  width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
  background: rgb(243 244 246);
  border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
  background: rgb(156 163 175);
  border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background: rgb(107 114 128);
}
</style>
