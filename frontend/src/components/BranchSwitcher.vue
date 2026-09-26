<script setup lang="ts">
import { LocationOutline } from '@vicons/ionicons5'
import { NButton, NCheckbox, NCheckboxGroup, NIcon, NPopover, NSpace, useMessage } from 'naive-ui'
import { onMounted, ref, watch } from 'vue'
import { errorMessage } from '@/api/client'
import { useBranchesStore } from '@/stores/branches'

/** "Viewing: …" in the top bar — which of your branches' staff you see. */
const branches = useBranchesStore()
const message = useMessage()
const open = ref(false)
const picked = ref<number[]>([])

watch(open, (isOpen) => {
  if (isOpen) picked.value = branches.selected.map((b) => b.id)
})

async function apply() {
  try {
    await branches.select(picked.value)
    open.value = false
  } catch (e) {
    message.error(errorMessage(e))
  }
}

onMounted(() => branches.load().catch(() => {}))
</script>

<template>
  <NPopover v-if="branches.mine.length > 1" v-model:show="open" trigger="click" placement="bottom-end">
    <template #trigger>
      <NButton quaternary size="small" class="switcher">
        <template #icon><NIcon><LocationOutline /></NIcon></template>
        {{ branches.label }}
      </NButton>
    </template>
    <div class="panel">
      <p class="title">Show staff from</p>
      <NCheckboxGroup v-model:value="picked">
        <NSpace vertical>
          <NCheckbox v-for="b in branches.mine" :key="b.id" :value="b.id" :label="b.name" />
        </NSpace>
      </NCheckboxGroup>
      <NSpace justify="end" class="actions">
        <NButton size="small" quaternary @click="picked = branches.mine.map((b) => b.id)">All</NButton>
        <NButton size="small" type="primary" :disabled="picked.length === 0" @click="apply">Apply</NButton>
      </NSpace>
    </div>
  </NPopover>
  <span v-else-if="branches.mine.length === 1" class="single">
    <NIcon><LocationOutline /></NIcon> {{ branches.mine[0].name }}
  </span>
</template>

<style scoped>
.switcher {
  max-width: 260px;
  overflow: hidden;
  text-overflow: ellipsis;
}

.panel {
  min-width: 200px;
}

.title {
  margin: 0 0 8px;
  font-weight: 600;
}

.actions {
  margin-top: 12px;
}

.single {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 0.9rem;
  opacity: 0.75;
}

@media (max-width: 720px) {
  .switcher,
  .single {
    display: none;
  }
}
</style>
