<template>
  <div class="w-full" :style="{ height: `${height}px` }">
    <canvas ref="chartCanvas"></canvas>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, onUnmounted } from 'vue'
import {
  Chart as ChartJS,
  ArcElement,
  Title,
  Tooltip,
  Legend,
} from 'chart.js'

ChartJS.register(
  ArcElement,
  Title,
  Tooltip,
  Legend
)

const props = defineProps({
  data: {
    type: Object,
    required: true
  },
  options: {
    type: Object,
    default: () => ({})
  },
  height: {
    type: Number,
    default: 300
  },
  cutout: {
    type: String,
    default: '60%'
  }
})

const chartCanvas = ref(null)
let chart = null

const defaultOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'right',
      labels: {
        padding: 15,
        usePointStyle: true,
        font: {
          size: 12
        }
      }
    },
    title: {
      display: false,
    },
    tooltip: {
      callbacks: {
        label: function(context) {
          const label = context.label || ''
          const value = context.parsed || 0
          const total = context.dataset.data.reduce((a, b) => a + b, 0)
          const percentage = ((value / total) * 100).toFixed(1)
          return `${label}: ${value} (${percentage}%)`
        }
      }
    }
  },
  cutout: props.cutout,
  animation: {
    animateRotate: true,
    animateScale: false,
    duration: 1000,
    easing: 'easeInOutQuart'
  }
}

onMounted(() => {
  if (chartCanvas.value) {
    const ctx = chartCanvas.value.getContext('2d')
    chart = new ChartJS(ctx, {
      type: 'doughnut',
      data: props.data,
      options: { ...defaultOptions, ...props.options, cutout: props.cutout }
    })
  }
})

watch(() => props.data, (newData) => {
  if (chart && newData) {
    chart.data = newData
    chart.update()
  }
}, { deep: true })

watch(() => props.options, (newOptions) => {
  if (chart && newOptions) {
    chart.options = { ...defaultOptions, ...newOptions, cutout: props.cutout }
    chart.update()
  }
}, { deep: true })

watch(() => props.cutout, (newCutout) => {
  if (chart) {
    chart.options.cutout = newCutout
    chart.update()
  }
})

onUnmounted(() => {
  if (chart) {
    chart.destroy()
  }
})
</script>

<style scoped>
canvas {
  height: v-bind(height + 'px');
}
</style>