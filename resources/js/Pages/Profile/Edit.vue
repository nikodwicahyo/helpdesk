<template>
    <AppLayout :role="role">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 bg-indigo-600 rounded-lg flex items-center justify-center shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">{{ t('nav.myProfile') }}</h1>
                        <p class="text-gray-600">{{ t('profile.updatePersonalInfo') }}</p>
                    </div>
                </div>
            </div>
        </template>

        <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <!-- Success Message -->
            <div v-if="$page.props.flash?.success"
                 class="mb-6 p-4 bg-green-50 border border border-green-200 rounded-lg">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-green-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <p class="text-green-800 font-medium">{{ $page.props.flash.success }}</p>
                </div>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit" class="space-y-8">
                <div class="bg-white shadow-lg rounded-lg p-8">
                    <h2 class="text-2xl font-semibold text-gray-900 mb-6">{{ t('profile.personalInformation') }}</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Full Name -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                {{ t('user.name') }} <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="name"
                                v-model="form.name"
                                type="text"
                                required
                                class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                :class="{ 'border-red-500 focus:border-red-500': errors.name }"
                            />
                            <p v-if="errors.name" class="mt-1 text-sm text-red-600">{{ errors.name }}</p>
                        </div>

                        <!-- Email -->
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                {{ t('user.email') }} <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="email"
                                v-model="form.email"
                                type="email"
                                required
                                class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                :class="{ 'border-red-500 focus:border-red-500': errors.email }"
                            />
                            <p v-if="errors.email" class="mt-1 text-sm text-red-600">{{ errors.email }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                        <!-- Phone -->
                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                                {{ t('user.phone') }}
                            </label>
                            <input
                                id="phone"
                                v-model="form.phone"
                                type="tel"
                                class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                :placeholder="t('profile.placeholderPhone')"
                            />
                            <p v-if="errors.phone" class="mt-1 text-sm text-red-600">{{ errors.phone }}</p>
                        </div>

                        <!-- Department -->
                        <div>
                            <label for="department" class="block text-sm font-medium text-gray-700 mb-2">
                                {{ t('user.department') }}
                            </label>
                            <input
                                id="department"
                                v-model="form.department"
                                type="text"
                                class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                :placeholder="t('profile.placeholderDepartment')"
                            />
                            <p v-if="errors.department" class="mt-1 text-sm text-red-600">{{ errors.department }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                        <!-- Position -->
                        <div>
                            <label for="position" class="block text-sm font-medium text-gray-700 mb-2">
                                {{ t('user.position') }}
                            </label>
                            <input
                                id="position"
                                v-model="form.position"
                                type="text"
                                class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                :placeholder="t('profile.placeholderPosition')"
                            />
                            <p v-if="errors.position" class="mt-1 text-sm text-red-600">{{ errors.position }}</p>
                        </div>

                        <!-- NIP (Readonly) -->
                        <div>
                            <label for="nip" class="block text-sm font-medium text-gray-700 mb-2">
                                {{ t('user.nip') }}
                            </label>
                            <input
                                id="nip"
                                v-model="props.user.nip"
                                type="text"
                                readonly
                                class="mt-1 block w-full px-4 py-3 bg-gray-100 border-gray-200 rounded-lg"
                            />
                        </div>
                    </div>

                    <div class="mt-8 flex justify-end space-x-4">
                        <Link
                            :href="`/${rolePrefix}/profile`"
                            class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        >
                            {{ t('common.cancel') }}
                        </Link>
                        <button
                            type="submit"
                            :disabled="processing"
                            class="px-6 py-3 bg-indigo-600 border border-transparent rounded-lg text-white font-medium hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span v-if="!processing">{{ t('common.saveChanges') }}</span>
                            <span v-else>{{ t('common.processing') }}</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n'
import { usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const { t } = useI18n()

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
    role: {
        type: String,
        required: true,
    },
});

// Convert role to URL prefix
const getRolePrefix = (role) => {
    const roleMap = {
        'admin_helpdesk': 'admin',
        'admin_aplikasi': 'admin-aplikasi',
        'teknisi': 'teknisi',
        'user': 'user'
    };
    return roleMap[role] || 'user';
};

const rolePrefix = getRolePrefix(props.role);

const form = reactive({
    name: props.user.nama_lengkap || props.user.name || '',
    email: props.user.email || '',
    phone: props.user.phone || '',
    department: props.user.department || '',
    position: props.user.position || '',
});

const processing = ref(false);
const errors = ref({});

const submit = async () => {
    processing.value = true;
    errors.value = {};

    try {
        await router.put(`/${rolePrefix}/profile`, form, {
            onSuccess: () => {
                router.visit(`/${rolePrefix}/profile`);
            },
            onError: (serverErrors) => {
                errors.value = serverErrors;
            },
        });
    } finally {
        processing.value = false;
    }
};
</script>