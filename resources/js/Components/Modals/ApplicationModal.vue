<template>
    <Modal @close="$emit('close')" :show="!!mode">
        <div class="bg-white rounded-lg shadow-xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center justify-center h-10 w-10 rounded-full bg-indigo-100">
                        <svg class="h-6 w-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900">
                            {{ mode === 'create' ? t('modal.applicationModal.createTitle') : t('modal.applicationModal.editTitle') }}
                        </h3>
                        <p class="text-sm text-gray-600 mt-1">
                            {{ mode === 'create' ? t('modal.applicationModal.createDescription') : t('modal.applicationModal.editDescription') }}
                        </p>
                    </div>
                </div>
            </div>

            <form @submit.prevent="submit">
                <div class="px-6 py-4 space-y-4">
                    <!-- Application Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                            {{ t('modal.applicationModal.applicationName') }} <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="name"
                            v-model="form.name"
                            type="text"
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            :class="form.errors.name ? 'border-red-300 focus:ring-red-500' : ''"
                            :placeholder="t('modal.applicationModal.namePlaceholder')"
                        >
                        <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                    </div>

                    <!-- Application Code -->
                    <div>
                        <label for="code" class="block text-sm font-medium text-gray-700 mb-2">
                            {{ t('modal.applicationModal.applicationCode') }} <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="code"
                            v-model="form.code"
                            type="text"
                            required
                            :disabled="mode === 'edit'"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent disabled:bg-gray-100"
                            :class="form.errors.code ? 'border-red-300 focus:ring-red-500' : ''"
                            :placeholder="t('modal.applicationModal.codePlaceholder')"
                        >
                        <p v-if="form.errors.code" class="mt-1 text-sm text-red-600">{{ form.errors.code }}</p>
                        <p v-if="mode === 'edit'" class="mt-1 text-xs text-gray-500">{{ t('modal.applicationModal.codeUnchangeable') }}</p>
                    </div>

                    <!-- Description -->
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                            {{ t('ticket.description') }}
                        </label>
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="3"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            :class="form.errors.description ? 'border-red-300 focus:ring-red-500' : ''"
                            :placeholder="t('modal.applicationModal.descriptionPlaceholder')"
                        ></textarea>
                        <p v-if="form.errors.description" class="mt-1 text-sm text-red-600">{{ form.errors.description }}</p>
                    </div>

                    <!-- Version -->
                    <div>
                        <label for="version" class="block text-sm font-medium text-gray-700 mb-2">
                            {{ t('modal.applicationModal.version') }}
                        </label>
                        <input
                            id="version"
                            v-model="form.version"
                            type="text"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            :class="form.errors.version ? 'border-red-300 focus:ring-red-500' : ''"
                            :placeholder="t('modal.applicationModal.versionPlaceholder')"
                        >
                        <p v-if="form.errors.version" class="mt-1 text-sm text-red-600">{{ form.errors.version }}</p>
                    </div>

                    <!-- Status -->
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                            {{ t('ticket.status') }} <span class="text-red-500">*</span>
                        </label>
                        <select
                            id="status"
                            v-model="form.status"
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            :class="form.errors.status ? 'border-red-300 focus:ring-red-500' : ''"
                        >
                            <option value="active">{{ t('status.active') }}</option>
                            <option value="inactive">{{ t('status.inactive') }}</option>
                            <option value="maintenance">{{ t('status.maintenance') }}</option>
                            <option value="deprecated">{{ t('status.deprecated') }}</option>
                        </select>
                        <p v-if="form.errors.status" class="mt-1 text-sm text-red-600">{{ form.errors.status }}</p>
                    </div>

                    <!-- Admin Aplikasi -->
                    <div>
                        <label for="admin_aplikasi_nip" class="block text-sm font-medium text-gray-700 mb-2">
                            {{ t('modal.applicationModal.adminAplikasi') }}
                        </label>
                        <select
                            id="admin_aplikasi_nip"
                            v-model="form.admin_aplikasi_nip"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            :class="form.errors.admin_aplikasi_nip ? 'border-red-300 focus:ring-red-500' : ''"
                        >
                            <option value="">{{ t('modal.applicationModal.selectAdmin') }}</option>
                            <option
                                v-for="admin in applications"
                                :key="admin.value"
                                :value="admin.value"
                            >
                                {{ admin.label }}
                            </option>
                        </select>
                        <p v-if="form.errors.admin_aplikasi_nip" class="mt-1 text-sm text-red-600">{{ form.errors.admin_aplikasi_nip }}</p>
                    </div>

                    <!-- Backup Admin -->
                    <div>
                        <label for="backup_admin_nip" class="block text-sm font-medium text-gray-700 mb-2">
                            {{ t('modal.applicationModal.backupAdmin') }}
                        </label>
                        <select
                            id="backup_admin_nip"
                            v-model="form.backup_admin_nip"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            :class="form.errors.backup_admin_nip ? 'border-red-300 focus:ring-red-500' : ''"
                        >
                            <option value="">{{ t('modal.applicationModal.selectBackupAdmin') }}</option>
                            <option
                                v-for="admin in applications"
                                :key="'backup-' + admin.value"
                                :value="admin.value"
                            >
                                {{ admin.label }}
                            </option>
                        </select>
                        <p v-if="form.errors.backup_admin_nip" class="mt-1 text-sm text-red-600">{{ form.errors.backup_admin_nip }}</p>
                    </div>

                    <!-- Additional Settings (for edit mode) -->
                    <div v-if="mode === 'edit' && application">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="text-sm font-medium text-gray-900 mb-2">{{ t('modal.applicationModal.statsTitle') }}</h4>
                            <dl class="grid grid-cols-2 gap-2 text-sm">
                                <div>
                                    <dt class="text-gray-500">{{ t('dashboard.totalTickets') }}:</dt>
                                    <dd class="font-medium text-gray-900">{{ application.total_tickets || 0 }}</dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500">{{ t('dashboard.openTickets') }}:</dt>
                                    <dd class="font-medium text-gray-900">{{ application.open_tickets || 0 }}</dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500">{{ t('nav.categories') }}:</dt>
                                    <dd class="font-medium text-gray-900">{{ application.total_categories || 0 }}</dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500">{{ t('common.created') }}:</dt>
                                    <dd class="font-medium text-gray-900">{{ formatDate(application.created_at) }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end space-x-3 rounded-b-lg">
                    <button
                        type="button"
                        @click="$emit('close')"
                        class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    >
                        {{ t('common.cancel') }}
                    </button>
                    <button
                        type="submit"
                        :disabled="processing"
                        class="px-4 py-2 border border-transparent rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
                    >
                        <svg v-if="processing" class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ processing ? t('common.saving') : (mode === 'create' ? t('modal.applicationModal.createButton') : t('modal.applicationModal.updateButton')) }}
                    </button>
                </div>
            </form>
        </div>
    </Modal>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { useI18n } from 'vue-i18n';
import Modal from '@/Components/Common/Modal.vue';

const { t } = useI18n();

const props = defineProps({
    application: {
        type: Object,
        default: null
    },
    mode: {
        type: String,
        required: true,
        validator: (value) => ['create', 'edit'].includes(value)
    },
    applications: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['close', 'saved']);

const processing = ref(false);

const form = useForm({
    name: '',
    code: '',
    description: '',
    version: '1.0.0',
    status: 'active',
    admin_aplikasi_nip: '',
    backup_admin_nip: ''
});

// Initialize form with application data if in edit mode
watch(() => props.application, (app) => {
    if (app && props.mode === 'edit') {
        form.name = app.name || '';
        form.code = app.code || '';
        form.description = app.description || '';
        form.version = app.version || '1.0.0';
        form.status = app.status || 'active';
        form.admin_aplikasi_nip = app.admin_aplikasi?.nip || '';
        form.backup_admin_nip = app.backup_admin?.nip || '';
        form.clearErrors();
    } else if (props.mode === 'create') {
        form.reset();
        form.version = '1.0.0';
        form.status = 'active';
        form.clearErrors();
    }
}, { immediate: true });

const submit = async () => {
    processing.value = true;

    const url = props.mode === 'create'
        ? route('admin.applications.store')
        : route('admin.applications.update', props.application.id);

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
            emit('close');
            // Reload the page to show updated data
            router.reload({ preserveScroll: true });
        } else {
            // Handle validation errors
            if (data.errors) {
                Object.keys(data.errors).forEach(key => {
                    form.setError(key, Array.isArray(data.errors[key]) ? data.errors[key][0] : data.errors[key]);
                });
            } else if (data.message) {
                alert(data.message);
            }
        }
    } catch (error) {
        console.error('Error submitting form:', error);
        alert(t('errors.formSubmitError'));
    } finally {
        processing.value = false;
    }
};

const formatDate = (dateString) => {
    if (!dateString) return t('common.notAvailable');
    const lang = document.documentElement.lang;
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    
    // Fallback to 'en-US' if the browser doesn't support the current language
    // for date formatting, to prevent errors.
    try {
        return new Intl.DateTimeFormat(lang, options).format(new Date(dateString));
    } catch (e) {
        return new Intl.DateTimeFormat('en-US', options).format(new Date(dateString));
    }
};
</script>
