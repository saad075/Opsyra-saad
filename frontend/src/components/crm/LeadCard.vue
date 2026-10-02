<script setup lang="ts">
import { AlertCircleOutline } from '@vicons/ionicons5'
import { NIcon, NTooltip } from 'naive-ui'
import type { LeadRow } from '@/api/crmLeadsTypes'
import { formatMoney } from '@/lib/money'

defineProps<{ lead: LeadRow }>()
</script>

<template>
  <div class="card" :class="{ rotten: lead.is_rotten }">
    <strong class="title">{{ lead.title }}</strong>
    <div v-if="lead.lead_value" class="value">{{ formatMoney(Number(lead.lead_value), 'USD') }}</div>
    <div v-if="lead.contact_name" class="contact">{{ lead.contact_name }}</div>
    <div class="meta">
      <span v-if="lead.owner" class="owner">{{ lead.owner.name }}</span>
      <NTooltip v-if="lead.is_rotten" trigger="hover">
        <template #trigger>
          <NIcon class="rotten-icon" size="16"><AlertCircleOutline /></NIcon>
        </template>
        No movement in a while — worth a follow-up.
      </NTooltip>
    </div>
    <p v-if="lead.status === false && lead.lost_reason" class="lost-reason">{{ lead.lost_reason }}</p>
  </div>
</template>

<style scoped>
.card {
  background: var(--n-color, #fff);
  border: 1px solid var(--n-border-color, #e5e5e5);
  border-radius: 8px;
  padding: 10px 12px;
  margin-bottom: 8px;
  cursor: grab;
}

.card.rotten {
  border-left: 3px solid #f0a020;
}

.title {
  display: block;
  margin-bottom: 4px;
}

.value {
  font-weight: 600;
  font-size: 0.9em;
  margin-bottom: 2px;
}

.contact {
  font-size: 0.85em;
  opacity: 0.75;
}

.meta {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: 6px;
  font-size: 0.8em;
  opacity: 0.7;
}

.rotten-icon {
  color: #f0a020;
}

.lost-reason {
  margin: 6px 0 0;
  font-size: 0.8em;
  color: #d03050;
}
</style>
