<script setup lang="ts">
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'

/**
 * A small map (Leaflet, BSD-2-Clause). Shows a place with its allowed
 * radius, plus any extra points (e.g. where a check-in happened). When
 * `editable`, clicking the map moves the place.
 *
 * Tiles come from VITE_MAP_TILE_URL, defaulting to OpenStreetMap's public
 * tiles (fine for light use; a busy production site should use its own
 * tile provider). Addresses are never sent anywhere to be looked up.
 */
export interface MapPoint {
  latitude: number
  longitude: number
  label?: string
  tone?: 'ok' | 'far' | 'neutral'
}

const props = withDefaults(defineProps<{ place: { latitude: number; longitude: number } | null; radius?: number | null; points?: MapPoint[]; editable?: boolean; height?: number }>(), {
  radius: null,
  points: () => [],
  editable: false,
  height: 260,
})
const emit = defineEmits<{ pick: [point: { latitude: number; longitude: number }] }>()

const el = ref<HTMLDivElement | null>(null)
let map: L.Map | null = null
let layer: L.LayerGroup | null = null

// `||`, not `??`: an empty setting (e.g. an unset CI variable) means the default.
const tiles = (import.meta.env.VITE_MAP_TILE_URL as string | undefined) || 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png'
const colours = { ok: '#0e6e62', far: '#a23b45', neutral: '#4b6584' }

function draw() {
  if (!map || !layer) return
  layer.clearLayers()
  const bounds: L.LatLngExpression[] = []

  if (props.place) {
    const at: L.LatLngExpression = [props.place.latitude, props.place.longitude]
    if (props.radius) L.circle(at, { radius: props.radius, color: colours.ok, weight: 1, fillOpacity: 0.08 }).addTo(layer)
    L.circleMarker(at, { radius: 8, color: '#fff', weight: 2, fillColor: colours.ok, fillOpacity: 1 }).addTo(layer).bindTooltip('The address')
    bounds.push(at)
  }

  for (const p of props.points) {
    const at: L.LatLngExpression = [p.latitude, p.longitude]
    L.circleMarker(at, { radius: 6, color: '#fff', weight: 2, fillColor: colours[p.tone ?? 'neutral'], fillOpacity: 1 }).addTo(layer).bindTooltip(p.label ?? '')
    bounds.push(at)
  }

  if (bounds.length > 1) map.fitBounds(L.latLngBounds(bounds), { padding: [30, 30], maxZoom: 17 })
  else if (bounds.length === 1) map.setView(bounds[0], props.radius && props.radius > 400 ? 14 : 16)
}

onMounted(() => {
  if (!el.value) return
  map = L.map(el.value, { scrollWheelZoom: false }).setView([20, 0], 2)
  L.tileLayer(tiles, { maxZoom: 19, attribution: '© OpenStreetMap contributors' }).addTo(map)
  layer = L.layerGroup().addTo(map)
  if (props.editable) {
    map.on('click', (e: L.LeafletMouseEvent) => emit('pick', { latitude: Number(e.latlng.lat.toFixed(6)), longitude: Number(e.latlng.lng.toFixed(6)) }))
  }
  draw()
  // The map may mount inside a drawer or modal that is still animating open.
  setTimeout(() => map?.invalidateSize(), 250)
})

watch(() => [props.place?.latitude, props.place?.longitude, props.radius, props.points], draw, { deep: true })
onBeforeUnmount(() => map?.remove())
</script>

<template>
  <div ref="el" class="omap" :class="{ editable }" :style="{ height: `${height}px` }" />
</template>

<style scoped>
.omap {
  width: 100%;
  border-radius: 8px;
  overflow: hidden;
  z-index: 0;
}

.editable {
  cursor: crosshair;
}
</style>
