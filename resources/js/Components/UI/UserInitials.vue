<template>
  <div
    :class="[
      'flex items-center justify-center rounded-full font-semibold text-white',
      sizeClasses[size],
      formattedInitials.background_color
    ]"
    :title="user?.name || 'Guest'"
  >
    {{ formattedInitials.text }}
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  user: {
    type: Object,
    default: null,
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['xs', 'sm', 'md', 'lg', 'xl'].includes(value),
  },
});

const sizeClasses = {
  xs: 'w-6 h-6 text-xs',
  sm: 'w-8 h-8 text-sm',
  md: 'w-10 h-10 text-base',
  lg: 'w-12 h-12 text-lg',
  xl: 'w-16 h-16 text-xl',
};

const formattedInitials = computed(() => {
  // Handle null user case
  if (!props.user) {
    return {
      text: '?',
      background_color: 'bg-gray-500',
      text_color: 'text-white',
    };
  }

  // Calculate initials from user name
  const name = props.user.name || '';
  if (!name.trim()) {
    return {
      text: 'U',
      background_color: 'bg-gray-500',
      text_color: 'text-white',
    };
  }

  // Split name by spaces and get first letter of first two parts
  const words = name.trim().split(' ');
  let initials = '';

  for (const word of words.slice(0, 2)) {
    if (word.length > 0) {
      initials += word.charAt(0).toUpperCase();
    }
  }

  if (!initials) {
    initials = 'U';
  }

  // Limit to 2 characters
  initials = initials.substring(0, 2);

  // Generate background color based on name hash for consistency
  const colors = [
    'bg-blue-500', 'bg-green-500', 'bg-yellow-500', 'bg-red-500',
    'bg-purple-500', 'bg-pink-500', 'bg-indigo-500', 'bg-teal-500',
    'bg-orange-500', 'bg-cyan-500', 'bg-emerald-500', 'bg-lime-500',
    'bg-rose-500', 'bg-violet-500', 'bg-fuchsia-500', 'bg-sky-500'
  ];

  let colorIndex = 0;
  for (let i = 0; i < name.length; i++) {
    colorIndex += name.charCodeAt(i);
  }
  colorIndex = colorIndex % colors.length;

  return {
    text: initials,
    background_color: colors[colorIndex],
    text_color: 'text-white',
  };
});
</script>