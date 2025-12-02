<template>
    <div class="flex items-center space-x-1">
        <button
            v-for="star in 5"
            :key="star"
            type="button"
            @click="setRating(star)"
            @mouseenter="hoverRating = star"
            @mouseleave="hoverRating = 0"
            :disabled="readonly"
            :class="[
                'focus:outline-none transition-all duration-200',
                readonly ? 'cursor-default' : 'cursor-pointer hover:scale-110',
            ]"
        >
            <svg
                :width="size"
                :height="size"
                viewBox="0 0 24 24"
                :class="[
                    'transition-colors duration-200',
                    hoverRating >= star || modelValue >= star
                        ? 'text-yellow-400 fill-current'
                        : 'text-gray-300 fill-current',
                ]"
            >
                <path
                    d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"
                />
            </svg>
        </button>
        <span v-if="showRating" class="ml-2 text-sm font-medium text-gray-700">
            {{ modelValue > 0 ? `${modelValue}/5` : t("ticket.noRating") }}
        </span>
    </div>
</template>

<script setup>
import { ref } from "vue";
import { useI18n } from "vue-i18n";

const { t } = useI18n();

const props = defineProps({
    modelValue: {
        type: Number,
        default: 0,
    },
    size: {
        type: Number,
        default: 24,
    },
    readonly: {
        type: Boolean,
        default: false,
    },
    showRating: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["update:modelValue"]);

const hoverRating = ref(0);

const setRating = (rating) => {
    if (!props.readonly) {
        emit("update:modelValue", rating);
    }
};
</script>
