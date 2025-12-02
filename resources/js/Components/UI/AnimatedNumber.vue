<template>
  <span>{{ displayValue }}{{ suffix }}</span>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';

const props = defineProps({
  target: {
    type: Number,
    default: 0
  },
  suffix: {
    type: String,
    default: ''
  },
  decimals: {
    type: Number,
    default: 0
  },
  duration: {
    type: Number,
    default: 2000
  },
  delay: {
    type: Number,
    default: 0
  }
});

const displayValue = ref(0);
const currentValue = ref(0);
const isAnimating = ref(false);

const easeOutQuart = (t) => {
  return 1 - Math.pow(1 - t, 4);
};

const animate = () => {
  if (isAnimating.value) return;

  // Handle undefined, null, or NaN target values
  const targetValue = Number(props.target);
  if (isNaN(targetValue)) {
    displayValue.value = '0';
    return;
  }

  isAnimating.value = true;
  const startTime = Date.now();
  const startValue = 0;

  const animateStep = () => {
    const now = Date.now();
    const elapsed = now - startTime;
    const progress = Math.min(elapsed / props.duration, 1);

    const easedProgress = easeOutQuart(progress);
    currentValue.value = startValue + (targetValue - startValue) * easedProgress;

    displayValue.value = props.decimals > 0
      ? currentValue.value.toFixed(props.decimals)
      : Math.round(currentValue.value).toString();

    if (progress < 1) {
      requestAnimationFrame(animateStep);
    } else {
      isAnimating.value = false;
    }
  };

  requestAnimationFrame(animateStep);
};

watch(() => props.target, (newTarget) => {
  if (currentValue.value !== newTarget) {
    animate();
  }
});

onMounted(() => {
  if (props.delay > 0) {
    setTimeout(() => {
      animate();
    }, props.delay);
  } else {
    animate();
  }
});
</script>