<template>
    <Modal @close="$emit('close')" :show="show">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">
                    {{ mode === 'create' ? t('modal.userModal.createTitle') : t('modal.userModal.editTitle') }}
                </h3>
                <p class="text-sm text-gray-600 mt-1">
                    {{ mode === 'create' ? t('modal.userModal.createDescription') : t('modal.userModal.editDescription') }}
                </p>
            </div>

            <form @submit.prevent="submit">
                <div class="px-6 py-4 space-y-6">
                    <!-- Basic Information -->
                    <div>
                        <h4 class="text-md font-medium text-gray-900 mb-4">{{ t('modal.userModal.basicInfo') }}</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="nama_lengkap" class="block text-sm font-medium text-gray-700 mb-2">
                                    {{ t('modal.userModal.fullName') }} <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="nama_lengkap"
                                    v-model="form.nama_lengkap"
                                    type="text"
                                    required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                    :placeholder="t('modal.userModal.fullNamePlaceholder')"
                                >
                                <p v-if="form.errors.nama_lengkap" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.nama_lengkap }}
                                </p>
                            </div>

                            <div>
                                <label for="nip" class="block text-sm font-medium text-gray-700 mb-2">
                                    {{ t('modal.userModal.nipLong') }} <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="nip"
                                    v-model="form.nip"
                                    type="text"
                                    required
                                    :disabled="mode === 'edit'"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent disabled:bg-gray-100"
                                    :placeholder="t('modal.userModal.nipPlaceholder')"
                                >
                                <p v-if="form.errors.nip" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.nip }}
                                </p>
                                <p v-if="mode === 'edit'" class="mt-1 text-xs text-gray-500">
                                    {{ t('modal.userModal.nipUnchangeable') }}
                                </p>
                            </div>

                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                    {{ t('user.email') }} <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                    :placeholder="t('modal.userModal.emailPlaceholder')"
                                >
                                <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.email }}
                                </p>
                            </div>

                            <div>
                                <label for="no_telepon" class="block text-sm font-medium text-gray-700 mb-2">
                                    {{ t('user.phone') }}
                                </label>
                                <input
                                    id="no_telepon"
                                    v-model="form.no_telepon"
                                    type="tel"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                    :placeholder="t('modal.userModal.phonePlaceholder')"
                                >
                                <p v-if="form.errors.no_telepon" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.no_telepon }}
                                </p>
                            </div>

                            <div>
                                <label for="departemen" class="block text-sm font-medium text-gray-700 mb-2">
                                    {{ t('user.department') }}
                                </label>
                                <select
                                    id="departemen"
                                    v-model="form.departemen"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                >
                                    <option value="">{{ t('modal.userModal.selectDepartment') }}</option>
                                    <option value="IT">{{ t('departments.it') }}</option>
                                    <option value="HR">{{ t('departments.hr') }}</option>
                                    <option value="Finance">{{ t('departments.finance') }}</option>
                                    <option value="Operations">{{ t('departments.operations') }}</option>
                                    <option value="Administration">{{ t('departments.administration') }}</option>
                                </select>
                                <p v-if="form.errors.departemen" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.departemen }}
                                </p>
                            </div>

                            <div>
                                <label for="jabatan" class="block text-sm font-medium text-gray-700 mb-2">
                                    {{ t('user.position') }}
                                </label>
                                <input
                                    id="jabatan"
                                    v-model="form.jabatan"
                                    type="text"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                    :placeholder="t('modal.userModal.positionPlaceholder')"
                                >
                                <p v-if="form.errors.jabatan" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.jabatan }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Role Assignment -->
                    <div>
                        <h4 class="text-md font-medium text-gray-900 mb-4">{{ t('modal.userModal.roleAssignment') }}</h4>
                        <div class="space-y-3">
                            <label class="flex items-center">
                                <input
                                    v-model="form.role"
                                    type="radio"
                                    value="user"
                                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
                                >
                                <span class="ml-3">
                                    <span class="font-medium text-gray-900">{{ t('roles.user') }}</span>
                                    <span class="block text-sm text-gray-500">{{ t('roles.userDescription') }}</span>
                                </span>
                            </label>

                            <label class="flex items-center">
                                <input
                                    v-model="form.role"
                                    type="radio"
                                    value="teknisi"
                                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
                                >
                                <span class="ml-3">
                                    <span class="font-medium text-gray-900">{{ t('roles.teknisi') }}</span>
                                    <span class="block text-sm text-gray-500">{{ t('roles.teknisiDescription') }}</span>
                                </span>
                            </label>

                            <label class="flex items-center">
                                <input
                                    v-model="form.role"
                                    type="radio"
                                    value="admin_aplikasi"
                                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
                                >
                                <span class="ml-3">
                                    <span class="font-medium text-gray-900">{{ t('roles.adminAplikasi') }}</span>
                                    <span class="block text-sm text-gray-500">{{ t('roles.adminAplikasiDescription') }}</span>
                                </span>
                            </label>

                            <label class="flex items-center">
                                <input
                                    v-model="form.role"
                                    type="radio"
                                    value="admin_helpdesk"
                                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
                                >
                                <span class="ml-3">
                                    <span class="font-medium text-gray-900">{{ t('roles.adminHelpdesk') }}</span>
                                    <span class="block text-sm text-gray-500">{{ t('roles.adminHelpdeskDescription') }}</span>
                                </span>
                            </label>
                        </div>
                        <p v-if="form.errors.role" class="mt-1 text-sm text-red-600">
                            {{ form.errors.role }}
                        </p>
                    </div>

                    <!-- Teknisi Specific Fields -->
                    <div v-if="form.role === 'teknisi'">
                        <h4 class="text-md font-medium text-gray-900 mb-4">{{ t('modal.userModal.teknisiInfo') }}</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="keahlian" class="block text-sm font-medium text-gray-700 mb-2">
                                    {{ t('modal.userModal.expertise') }} <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="keahlian"
                                    v-model="form.keahlian"
                                    type="text"
                                    required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                    :placeholder="t('modal.userModal.expertisePlaceholder')"
                                >
                                <p v-if="form.errors.keahlian" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.keahlian }}
                                </p>
                            </div>

                            <div>
                                <label for="pengalaman" class="block text-sm font-medium text-gray-700 mb-2">
                                    {{ t('modal.userModal.experience') }}
                                </label>
                                <input
                                    id="pengalaman"
                                    v-model.number="form.pengalaman"
                                    type="number"
                                    min="0"
                                    step="0.5"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                    :placeholder="t('modal.userModal.experiencePlaceholder')"
                                >
                                <p v-if="form.errors.pengalaman" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.pengalaman }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Password (only for create mode) -->
                    <div v-if="mode === 'create'">
                        <h4 class="text-md font-medium text-gray-900 mb-4">{{ t('user.password') }}</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                                    {{ t('user.password') }} <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="password"
                                    v-model="form.password"
                                    type="password"
                                    required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                    :placeholder="t('modal.userModal.passwordPlaceholder')"
                                >
                                <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.password }}
                                </p>
                            </div>

                            <div>
                                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">
                                    {{ t('user.confirmPassword') }} <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="password_confirmation"
                                    v-model="form.password_confirmation"
                                    type="password"
                                    required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                    :placeholder="t('modal.userModal.confirmPasswordPlaceholder')"
                                >
                            </div>
                        </div>
                    </div>

                    <!-- Status -->
                    <div v-if="mode === 'edit'">
                        <h4 class="text-md font-medium text-gray-900 mb-4">{{ t('modal.userModal.accountStatus') }}</h4>
                        <div class="flex items-center space-x-4">
                            <label class="flex items-center">
                                <input
                                    v-model="form.status"
                                    type="radio"
                                    value="active"
                                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
                                >
                                <span class="ml-2 text-sm text-gray-700">{{ t('status.active') }}</span>
                            </label>
                            <label class="flex items-center">
                                <input
                                    v-model="form.status"
                                    type="radio"
                                    value="inactive"
                                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
                                >
                                <span class="ml-2 text-sm text-gray-700">{{ t('status.inactive') }}</span>
                            </label>
                            <label class="flex items-center">
                                <input
                                    v-model="form.status"
                                    type="radio"
                                    value="suspended"
                                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
                                >
                                <span class="ml-2 text-sm text-gray-700">{{ t('status.suspended') }}</span>
                            </label>
                        </div>
                        <p v-if="form.errors.status" class="mt-1 text-sm text-red-600">
                            {{ form.errors.status }}
                        </p>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="px-6 py-4 border-t border-gray-200">
                    <div class="flex items-center justify-end space-x-3">
                        <button
                            type="button"
                            @click="$emit('close')"
                            class="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 transition font-medium"
                        >
                            {{ t('common.cancel') }}
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span v-if="form.processing" class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ mode === 'create' ? t('common.creating') : t('common.updating') }}
                            </span>
                            <span v-else>{{ mode === 'create' ? t('modal.userModal.createUser') : t('modal.userModal.updateUser') }}</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </Modal>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { useI18n } from 'vue-i18n';
import Modal from '@/Components/Common/Modal.vue';

const { t } = useI18n();

const props = defineProps({
    user: {
        type: Object,
        default: null,
    },
    mode: {
        type: String,
        required: true,
        validator: (value) => ['create', 'edit'].includes(value),
    },
    role: {
        type: String,
        default: 'user',
    },
    show: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['close', 'saved']);

const form = useForm({
    nama_lengkap: props.user?.nama_lengkap || '',
    nip: props.user?.nip || '',
    email: props.user?.email || '',
    no_telepon: props.user?.no_telepon || '',
    departemen: props.user?.departemen || '',
    jabatan: props.user?.jabatan || '',
    role: props.user?.role || props.role,
    keahlian: props.user?.keahlian || '',
    pengalaman: props.user?.pengalaman || '',
    password: '',
    password_confirmation: '',
    status: props.user?.status || 'active',
});

const submit = () => {
    if (props.mode === 'create') {
        form.post(route('admin.users.store'), {
            onSuccess: () => {
                emit('saved');
            },
        });
    } else {
        form.put(route('admin.users.update', props.user.nip), {
            onSuccess: () => {
                emit('saved');
            },
        });
    }
};

// Watch for role changes to clear teknisi-specific fields if not teknisi
watch(() => form.role, (newRole) => {
    if (newRole !== 'teknisi') {
        form.keahlian = '';
        form.pengalaman = '';
    }
});
</script>
