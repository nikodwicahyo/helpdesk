<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition ease-out duration-300"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition ease-in duration-200"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div v-if="show" class="fixed inset-0 bg-black/40 backdrop-blur-sm transition-opacity z-50">
        <div class="fixed inset-0 overflow-y-auto">
          <div class="flex min-h-full items-center justify-center p-4 text-center">
            <Transition
              enter-active-class="transition ease-out duration-300"
              enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
              enter-to-class="opacity-100 translate-y-0 sm:scale-100"
              leave-active-class="transition ease-in duration-200"
              leave-from-class="opacity-100 translate-y-0 sm:scale-100"
              leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            >
              <div
                v-if="show"
                class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6"
              >
                <div class="absolute right-0 top-0 pr-4 pt-4">
                  <button
                    type="button"
                    class="rounded-md bg-white text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    @click="close"
                  >
                    <span class="sr-only">Close</span>
                    <XMarkIcon class="h-6 w-6" />
                  </button>
                </div>

                <div class="sm:flex sm:items-start">
                  <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                    <BookmarkIcon class="h-6 w-6 text-indigo-600" />
                  </div>
                  <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                    <h3 class="text-lg font-medium leading-6 text-gray-900">
                      Save Search
                    </h3>
                    <div class="mt-2">
                      <p class="text-sm text-gray-500">
                        Save your current search filters for quick access later.
                      </p>
                    </div>
                  </div>
                </div>

                <form @submit.prevent="handleSubmit" class="mt-4">
                  <div>
                    <label for="search-name" class="block text-sm font-medium leading-6 text-gray-900">
                      Search Name
                    </label>
                    <div class="mt-2">
                      <input
                        id="search-name"
                        ref="searchNameInput"
                        v-model="searchName"
                        type="text"
                        required
                        maxlength="100"
                        class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                        placeholder="e.g., High Priority Login Issues"
                      />
                      <p class="mt-1 text-xs text-gray-500">
                        Give this search a memorable name so you can easily find it later.
                      </p>
                    </div>
                  </div>

                  <!-- Search Summary -->
                  <div v-if="searchSummary" class="mt-4 p-3 bg-gray-50 rounded-md">
                    <h4 class="text-sm font-medium text-gray-700 mb-2">This search includes:</h4>
                    <div class="flex flex-wrap gap-1">
                      <span
                        v-for="item in searchSummary"
                        :key="item.label"
                        class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800"
                      >
                        {{ item.label }}
                      </span>
                    </div>
                  </div>

                  <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                    <button
                      type="submit"
                      :disabled="isSubmitting || !searchName.trim()"
                      class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:opacity-50 disabled:cursor-not-allowed sm:ml-3 sm:w-auto"
                    >
                      <BookmarkIcon v-if="!isSubmitting" class="h-4 w-4 mr-2" />
                      <svg
                        v-else
                        class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                      >
                        <circle
                          class="opacity-25"
                          cx="12"
                          cy="12"
                          r="10"
                          stroke="currentColor"
                          stroke-width="4"
                        ></circle>
                        <path
                          class="opacity-75"
                          fill="currentColor"
                          d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                        ></path>
                      </svg>
                      {{ isSubmitting ? 'Saving...' : 'Save Search' }}
                    </button>
                    <button
                      type="button"
                      class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto"
                      @click="close"
                    >
                      Cancel
                    </button>
                  </div>
                </form>
              </div>
            </Transition>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch, nextTick } from 'vue'
import { BookmarkIcon, XMarkIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  currentFilters: {
    type: Object,
    default: () => ({})
  }
})

const emit = defineEmits(['update:modelValue', 'save'])

// Local state
const searchName = ref('')
const isSubmitting = ref(false)
const searchNameInput = ref(null)

// Computed
const show = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

const searchSummary = computed(() => {
  const summary = []
  const filters = props.currentFilters

  if (filters.query) {
    summary.push({ label: `Query: "${filters.query}"` })
  }

  if (filters.status && filters.status.length > 0) {
    summary.push({ label: `${filters.status.length} Status${filters.status.length > 1 ? 'es' : ''}` })
  }

  if (filters.priority && filters.priority.length > 0) {
    summary.push({ label: `${filters.priority.length} Priorit${filters.priority.length > 1 ? 'ies' : 'y'}` })
  }

  if (filters.aplikasi_id) {
    summary.push({ label: 'Application' })
  }

  if (filters.kategori_masalah_id) {
    summary.push({ label: 'Category' })
  }

  if (filters.date_from || filters.date_to) {
    summary.push({ label: 'Date Range' })
  }

  if (filters.user_nip) {
    summary.push({ label: 'User Filter' })
  }

  if (filters.assigned_teknisi_nip) {
    summary.push({ label: 'Technician Filter' })
  }

  const advancedFilters = ['is_escalated', 'has_attachments', 'has_rating', 'is_overdue', 'is_unassigned']
  const activeAdvanced = advancedFilters.filter(key => filters[key] === true)

  if (activeAdvanced.length > 0) {
    summary.push({ label: `${activeAdvanced.length} Advanced Filter${activeAdvanced.length > 1 ? 's' : ''}` })
  }

  return summary.slice(0, 6) // Limit display
})

// Methods
const close = () => {
  show.value = false
  resetForm()
}

const resetForm = () => {
  searchName.value = ''
  isSubmitting.value = false
}

const handleSubmit = async () => {
  if (!searchName.value.trim() || isSubmitting.value) return

  isSubmitting.value = true

  try {
    await emit('save', searchName.value.trim())
    close()
  } catch (error) {
    console.error('Error saving search:', error)
  } finally {
    isSubmitting.value = false
  }
}

// Focus input when modal opens
watch(show, (newValue) => {
  if (newValue) {
    nextTick(() => {
      searchNameInput.value?.focus()
    })
  }
})
</script>