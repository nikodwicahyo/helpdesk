<template>
    <Teleport to="body">
        <Transition name="modal">
            <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto" @click.self="closeModal">
                <!-- Backdrop with blur effect -->
                <div class="fixed inset-0 bg-black/40 backdrop-blur-sm transition-opacity"></div>

                <!-- Modal Container -->
                <div class="flex min-h-screen items-center justify-center p-4">
                    <div class="relative bg-white rounded-lg shadow-xl transform transition-all max-w-2xl w-full max-h-[90vh] overflow-hidden flex flex-col">
                        <form @submit.prevent="submit" class="h-full flex flex-col">
                            <!-- Header -->
                            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 flex-shrink-0">
                                    <div class="sm:flex sm:items-start">
                                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-green-100 sm:mx-0 sm:h-10 sm:w-10">
                                            <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                            </svg>
                                        </div>
                                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                                            <h3 class="text-lg leading-6 font-medium text-gray-900">
                                                {{ t('modal.assignTeknisi.title') }}
                                            </h3>
                                            <div class="mt-2">
                                                <p class="text-sm text-gray-500" v-html="t('modal.assignTeknisi.description', { appName: application?.name })" />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            <!-- Body -->
                            <div class="flex-1 overflow-y-auto px-4 pt-5 pb-4 sm:p-6 sm:pt-4">
                                    <!-- Currently Assigned -->
                                    <div v-if="currentlyAssigned.length > 0" class="mb-6">
                                        <h4 class="text-sm font-medium text-gray-900 mb-3">{{ t('modal.assignTeknisi.currentlyAssigned') }}</h4>
                                        <div class="flex flex-wrap gap-2">
                                            <span
                                                v-for="teknisi in currentlyAssigned"
                                                :key="teknisi.nip"
                                                class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-blue-100 text-blue-800"
                                            >
                                                {{ teknisi.name }}
                                                <button
                                                    type="button"
                                                    @click="removeTeknisi(teknisi.nip)"
                                                    class="ml-2 text-blue-600 hover:text-blue-800"
                                                >
                                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                    </svg>
                                                </button>
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Available Teknisi -->
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-900 mb-3">{{ t('modal.assignTeknisi.availableTeknisi') }}</h4>
                                        <div class="max-h-60 overflow-y-auto border border-gray-200 rounded-lg">
                                            <!-- Search -->
                                            <div class="p-3 border-b border-gray-200">
                                                <input
                                                    v-model="searchTerm"
                                                    type="text"
                                                    :placeholder="t('modal.assignTeknisi.searchPlaceholder')"
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                                >
                                            </div>

                                            <!-- Teknisi List -->
                                            <div class="divide-y divide-gray-200">
                                                <label
                                                    v-for="teknisi in filteredTeknisis"
                                                    :key="teknisi.nip"
                                                    class="flex items-center p-3 hover:bg-gray-50 cursor-pointer"
                                                >
                                                    <input
                                                        type="checkbox"
                                                        :checked="isTeknisiSelected(teknisi.nip)"
                                                        @change="toggleTeknisi(teknisi.nip)"
                                                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                                    >
                                                    <div class="ml-3 flex-1">
                                                        <div class="text-sm font-medium text-gray-900">{{ teknisi.name }}</div>
                                                        <div class="text-sm text-gray-500">{{ teknisi.email || teknisi.nip }}</div>
                                                        <div v-if="teknisi.specializations && teknisi.specializations.length > 0" class="text-xs text-gray-400 mt-1">
                                                            {{ teknisi.specializations.join(', ') }}
                                                        </div>
                                                    </div>
                                                    <div class="ml-3">
                                                        <span
                                                            v-if="teknisi.is_available !== false"
                                                            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800"
                                                        >
                                                            {{ t('modal.assignTeknisi.available') }}
                                                        </span>
                                                        <span
                                                            v-else
                                                            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800"
                                                        >
                                                            {{ t('modal.assignTeknisi.busy') }}
                                                        </span>
                                                    </div>
                                                </label>
                                            </div>

                                            <!-- No Results -->
                                            <div v-if="filteredTeknisis.length === 0" class="p-6 text-center text-gray-500">
                                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                                </svg>
                                                <p class="mt-2 text-sm">{{ t('modal.assignTeknisi.noTeknisiFound') }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Selected Count -->
                                    <div v-if="selectedTeknisis.length > 0" class="mt-4 p-3 bg-indigo-50 rounded-lg">
                                        <p class="text-sm text-indigo-800" v-html="t('modal.assignTeknisi.assignmentConfirmation', { count: selectedTeknisis.length })" />
                                    </div>
                                </div>

                        <!-- Actions -->
                            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse flex-shrink-0 border-t border-gray-200">
                                    <button
                                        type="submit"
                                        :disabled="processing || selectedTeknisis.length === 0"
                                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
                                    >
                                        <svg v-if="processing" class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        {{ processing ? t('ticket.assigning') : t('modal.assignTeknisi.assignButton') }}
                                    </button>
                                    <button
                                        type="button"
                                        @click="closeModal"
                                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
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
import { ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    application: {
        type: Object,
        required: true
    },
    teknisis: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['close', 'assigned']);

const show = computed(() => !!props.application);

const processing = ref(false);
const searchTerm = ref('');
const selectedTeknisis = ref([]);
const currentlyAssigned = ref([]);

const form = useForm({
    teknisi_nips: []
});

// Initialize with currently assigned teknisi
watch(() => props.application, (app) => {
    if (app) {
        currentlyAssigned.value = app.assigned_teknisis || [];
        selectedTeknisis.value = (app.assigned_teknisis || []).map(t => t.nip);
    }
}, { immediate: true });

const filteredTeknisis = computed(() => {
    if (!searchTerm.value) {
        return props.teknisis.filter(t => !selectedTeknisis.value.includes(t.nip));
    }

    const search = searchTerm.value.toLowerCase();
    return props.teknisis.filter(teknisi => {
        const isNotSelected = !selectedTeknisis.value.includes(teknisi.nip);
        const matchesSearch =
            teknisi.name.toLowerCase().includes(search) ||
            (teknisi.email && teknisi.email.toLowerCase().includes(search)) ||
            (teknisi.nip && teknisi.nip.toLowerCase().includes(search)) ||
            (teknisi.specializations && teknisi.specializations.some(spec => spec.toLowerCase().includes(search)));

        return isNotSelected && matchesSearch;
    });
});

const isTeknisiSelected = (teknisiNip) => {
    return selectedTeknisis.value.includes(teknisiNip);
};

const toggleTeknisi = (teknisiNip) => {
    const index = selectedTeknisis.value.indexOf(teknisiNip);
    if (index > -1) {
        selectedTeknisis.value.splice(index, 1);
    } else {
        selectedTeknisis.value.push(teknisiNip);
    }
};

const removeTeknisi = (teknisiNip) => {
    const index = selectedTeknisis.value.indexOf(teknisiNip);
    if (index > -1) {
        selectedTeknisis.value.splice(index, 1);
    }

    const currentlyIndex = currentlyAssigned.value.findIndex(t => t.nip === teknisiNip);
    if (currentlyIndex > -1) {
        currentlyAssigned.value.splice(currentlyIndex, 1);
    }
};

const submit = () => {
    // Removed validation to allow removing all assignments if empty array is sent?
    // Backend handles empty array by detaching all.
    // But frontend UI button is disabled if length == 0.
    // The UI requirement says "disabled=selectedTeknisis.length === 0".
    // So we can't unassign everyone using this modal if button is disabled.
    // However, removing from "currently assigned" removes from "selectedTeknisis".
    // If I remove everyone, selectedTeknisis is empty. Button disabled.
    // This means I can't save "no assignments".
    // This seems to be a flaw in the original modal design or intended behavior (must have at least one?)
    // The backend supports empty array.
    // I will respect the existing UI logic for now (button disabled if empty).
    if (selectedTeknisis.value.length === 0) return;

    processing.value = true;
    form.teknisi_nips = selectedTeknisis.value;

    form.post(route('admin-aplikasi.applications.assign-teknisi', props.application.id), {
        onSuccess: () => {
            processing.value = false;
            emit('assigned');
        },
        onError: () => {
            processing.value = false;
        }
    });
};

const closeModal = () => {
    searchTerm.value = '';
    selectedTeknisis.value = [];
    currentlyAssigned.value = [];
    form.reset();
    emit('close');
};

// Watch for application changes
watch(() => props.application, (app) => {
    if (!app) {
        closeModal();
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
</style>
