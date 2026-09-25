<script setup lang="ts">
import {
  ArcElement,
  BarElement,
  CategoryScale,
  Chart as ChartJS,
  Filler,
  Legend,
  LinearScale,
  LineElement,
  PointElement,
  Tooltip,
  type ChartData,
  type ChartOptions,
} from 'chart.js'
import { computed } from 'vue'
import { Bar, Doughnut, Line } from 'vue-chartjs'
import { useUiStore } from '@/stores/ui'

ChartJS.register(ArcElement, BarElement, CategoryScale, LinearScale, LineElement, PointElement, Filler, Tooltip, Legend)

/**
 * One chart component for every dashboard, in Opsyra's palette and
 * following light/dark mode. Pass labels and one or more series.
 */
const props = defineProps<{
  type: 'bar' | 'line' | 'doughnut'
  labels: string[]
  series: { label: string; data: number[] }[]
  height?: number
  stacked?: boolean
}>()

const ui = useUiStore()

// Teal first, then colours that stay distinct from it and from each other.
const PALETTE_LIGHT = ['#0E6E62', '#5CC4B4', '#2F6FB0', '#C17D11', '#8B4FA8', '#A23B45', '#4B8B3B', '#6B7785']
const PALETTE_DARK = ['#5CC4B4', '#2A9D8F', '#7FB2E5', '#E2B35C', '#C29BD6', '#EE8C96', '#8FC57F', '#9AA6B2']

const palette = computed(() => (ui.isDark ? PALETTE_DARK : PALETTE_LIGHT))
const text = computed(() => (ui.isDark ? '#C9D1D9' : '#2C3A47'))
const grid = computed(() => (ui.isDark ? '#2A3440' : '#E6EAEE'))

const data = computed(() => {
  const isDoughnut = props.type === 'doughnut'
  return {
    labels: props.labels,
    datasets: props.series.map((s, i) => ({
      label: s.label,
      data: s.data,
      backgroundColor: isDoughnut ? palette.value : props.type === 'line' ? `${palette.value[i]}22` : palette.value[i],
      borderColor: isDoughnut ? (ui.isDark ? '#182029' : '#FFFFFF') : palette.value[i],
      borderWidth: isDoughnut ? 2 : props.type === 'line' ? 2.5 : 0,
      borderRadius: props.type === 'bar' ? 6 : 0,
      fill: props.type === 'line',
      tension: 0.35,
      pointRadius: 3,
      maxBarThickness: 48,
    })),
  }
})

const options = computed(() => {
  const base = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        display: props.type === 'doughnut' || props.series.length > 1,
        position: props.type === 'doughnut' ? ('right' as const) : ('top' as const),
        labels: { color: text.value, font: { family: '"Source Sans 3", sans-serif', size: 13 }, boxWidth: 12 },
      },
      tooltip: { titleFont: { family: '"Source Sans 3"' }, bodyFont: { family: '"Source Sans 3"' } },
    },
  }

  if (props.type === 'doughnut') return { ...base, cutout: '62%' }

  return {
    ...base,
    scales: {
      x: { stacked: props.stacked, ticks: { color: text.value }, grid: { display: false } },
      y: { stacked: props.stacked, beginAtZero: true, ticks: { color: text.value, precision: 0 }, grid: { color: grid.value } },
    },
  }
})
</script>

<template>
  <div :style="{ height: `${height ?? 260}px`, position: 'relative' }">
    <Bar v-if="type === 'bar'" :data="data as ChartData<'bar'>" :options="options as ChartOptions<'bar'>" />
    <Line v-else-if="type === 'line'" :data="data as ChartData<'line'>" :options="options as ChartOptions<'line'>" />
    <Doughnut v-else :data="data as ChartData<'doughnut'>" :options="options as ChartOptions<'doughnut'>" />
  </div>
</template>
