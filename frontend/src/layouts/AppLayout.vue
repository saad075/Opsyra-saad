<script setup lang="ts">
import { BusinessOutline, HomeOutline, LocationOutline, MoonOutline, RocketOutline, SunnyOutline } from '@vicons/ionicons5'
import { NAvatar, NButton, NDropdown, NIcon, NLayout, NLayoutContent, NLayoutHeader, NLayoutSider, NMenu, type MenuOption } from 'naive-ui'
import { computed, h, ref, type Component } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import BrandMark from '@/components/BrandMark.vue'
import { useAuthStore } from '@/stores/auth'
import { useUiStore } from '@/stores/ui'

const auth = useAuthStore()
const ui = useUiStore()
const route = useRoute()
const router = useRouter()
const collapsed = ref(false)

const icon = (component: Component) => () => h(NIcon, null, { default: () => h(component) })
const link = (name: string, label: string) => () => h(RouterLink, { to: { name } }, { default: () => label })

/**
 * The sidebar shows only what the user's permissions allow. Items for
 * modules without screens yet are added as those screens are built.
 */
const menu = computed<MenuOption[]>(() => {
  const items: (MenuOption & { permission?: string })[] = [
    { key: 'dashboard', label: link('dashboard', 'Home'), icon: icon(HomeOutline) },
    { key: 'setup', label: link('setup', 'Setup'), icon: icon(RocketOutline), permission: 'HRMS-CompanySettings' },
    { key: 'company-settings', label: link('company-settings', 'Company'), icon: icon(BusinessOutline), permission: 'HRMS-CompanySettings' },
    { key: 'branches', label: link('branches', 'Branches'), icon: icon(LocationOutline), permission: 'HRMS-StaffManagement' },
  ]

  return items.filter((item) => !item.permission || auth.can(item.permission))
})

const initials = computed(() =>
  (auth.me?.user.name ?? '?')
    .split(' ')
    .map((part) => part[0])
    .slice(0, 2)
    .join('')
    .toUpperCase(),
)

const userMenu = [
  { key: 'change-password', label: 'Change password' },
  { key: 'logout', label: 'Sign out' },
]

async function onUserMenu(key: string) {
  if (key === 'change-password') return router.push({ name: 'change-password' })
  await auth.logout()
  await router.push({ name: 'login' })
}
</script>

<template>
  <NLayout has-sider class="app-shell">
    <NLayoutSider
      bordered
      collapse-mode="width"
      :collapsed-width="64"
      :width="232"
      :collapsed="collapsed"
      show-trigger
      :native-scrollbar="false"
      @collapse="collapsed = true"
      @expand="collapsed = false"
    >
      <div class="sider-brand" :class="{ dark: ui.isDark }">
        <BrandMark v-if="!collapsed" />
      </div>
      <NMenu :value="String(route.name)" :options="menu" :collapsed="collapsed" :collapsed-width="64" />
    </NLayoutSider>

    <NLayout>
      <NLayoutHeader bordered class="app-header">
        <span class="company">{{ auth.me?.company.name }}</span>
        <div class="header-actions">
          <NButton quaternary circle :aria-label="ui.isDark ? 'Switch to light mode' : 'Switch to dark mode'" @click="ui.toggle()">
            <template #icon>
              <NIcon><SunnyOutline v-if="ui.isDark" /><MoonOutline v-else /></NIcon>
            </template>
          </NButton>
          <NDropdown :options="userMenu" trigger="click" @select="onUserMenu">
            <NButton quaternary class="user-button">
              <NAvatar round size="small" class="avatar">{{ initials }}</NAvatar>
              <span class="user-name">{{ auth.me?.user.name }}</span>
            </NButton>
          </NDropdown>
        </div>
      </NLayoutHeader>

      <NLayoutContent class="app-content" :native-scrollbar="false">
        <RouterView />
      </NLayoutContent>
    </NLayout>
  </NLayout>
</template>

<style scoped>
.app-shell {
  height: 100vh;
}

.sider-brand {
  height: 60px;
  display: flex;
  align-items: center;
  padding: 0 20px;
}

.sider-brand.dark {
  --brand-accent: #5cc4b4;
}

.app-header {
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 16px 0 24px;
}

.company {
  font-family: 'Plus Jakarta Sans', 'Segoe UI', sans-serif;
  font-weight: 600;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 4px;
}

.user-button {
  gap: 8px;
}

.avatar {
  background: #0e6e62;
  color: #fff;
  font-size: 12px;
}

.app-content {
  height: calc(100% - 60px);
}

@media (max-width: 640px) {
  .user-name {
    display: none;
  }
}
</style>
