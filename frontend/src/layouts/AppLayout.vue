<script setup lang="ts">
import {
  BusinessOutline,
  CheckboxOutline,
  CheckmarkDoneOutline,
  ChatbubblesOutline,
  DocumentTextOutline,
  PlayCircleOutline,
  ReceiptOutline,
  ListOutline,
  HomeOutline,
  LocationOutline,
  MoonOutline,
  PeopleOutline,
  PersonOutline,
  RocketOutline,
  SettingsOutline,
  StatsChartOutline,
  SunnyOutline,
  WalletOutline,
} from '@vicons/ionicons5'
import { NAvatar, NButton, NDropdown, NIcon, NLayout, NLayoutContent, NLayoutHeader, NLayoutSider, NMenu, type MenuOption } from 'naive-ui'
import { computed, h, ref, type Component } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import BrandMark from '@/components/BrandMark.vue'
import BranchSwitcher from '@/components/BranchSwitcher.vue'
import { HR_APPROVERS, PAYROLL_VIEWERS } from '@/router'
import { useAuthStore } from '@/stores/auth'
import { useUiStore } from '@/stores/ui'

const auth = useAuthStore()
const ui = useUiStore()
const route = useRoute()
const router = useRouter()
const collapsed = ref(false)

// The marketing site (Opsyra Central), for the demo banner's call to action.
const centralUrl = (import.meta.env.VITE_CENTRAL_URL as string | undefined)?.replace(/\/$/, '') ?? 'http://localhost:8100'

const icon = (component: Component) => () => h(NIcon, null, { default: () => h(component) })
const link = (name: string, label: string) => () => h(RouterLink, { to: { name } }, { default: () => label })

type Item = MenuOption & { permission?: string | string[]; children?: Item[] }

/**
 * Grouped by module, each module opening on its own dashboard (the Horilla
 * pattern). Only what the user's permissions allow is shown; a group with
 * nothing visible disappears. Modules add items here as screens are built.
 */
const menu = computed<MenuOption[]>(() => {
  const items: Item[] = [
    { key: 'dashboard', label: link('dashboard', 'Home'), icon: icon(HomeOutline) },
    { key: 'tasks', label: link('tasks', 'My tasks'), icon: icon(CheckboxOutline) },
    { key: 'my-payslips', label: link('my-payslips', 'My payslips'), icon: icon(DocumentTextOutline) },
    {
      key: 'hr',
      label: 'HR',
      icon: icon(PeopleOutline),
      children: [
        { key: 'hr-dashboard', label: link('hr-dashboard', 'Dashboard'), icon: icon(StatsChartOutline), permission: 'HRMS-StaffManagement' },
        { key: 'staff', label: link('staff', 'Staff'), icon: icon(PersonOutline), permission: 'HRMS-StaffManagement' },
        { key: 'approvals', label: link('approvals', 'Approvals'), icon: icon(CheckmarkDoneOutline), permission: HR_APPROVERS },
        { key: 'checklists', label: link('checklists', 'Checklists'), icon: icon(ListOutline), permission: 'HRMS-StaffManagement' },
        { key: 'branches', label: link('branches', 'Branches'), icon: icon(LocationOutline), permission: 'HRMS-StaffManagement' },
      ],
    },
    {
      key: 'payroll',
      label: 'Payroll',
      icon: icon(WalletOutline),
      children: [
        { key: 'payroll-dashboard', label: link('payroll-dashboard', 'Dashboard'), icon: icon(StatsChartOutline), permission: PAYROLL_VIEWERS },
        { key: 'payroll-runs', label: link('payroll-runs', 'Pay runs'), icon: icon(PlayCircleOutline), permission: PAYROLL_VIEWERS },
        { key: 'payroll-cheques', label: link('payroll-cheques', 'Cheques'), icon: icon(ReceiptOutline), permission: PAYROLL_VIEWERS },
        { key: 'payroll-queries', label: link('payroll-queries', 'Salary queries'), icon: icon(ChatbubblesOutline), permission: 'HRMS-SalaryManagement' },
      ],
    },
    {
      key: 'settings',
      label: 'Settings',
      icon: icon(SettingsOutline),
      children: [
        { key: 'setup', label: link('setup', 'Setup'), icon: icon(RocketOutline), permission: 'HRMS-CompanySettings' },
        { key: 'company-settings', label: link('company-settings', 'Company'), icon: icon(BusinessOutline), permission: 'HRMS-CompanySettings' },
      ],
    },
  ]

  const visible = (item: Item) => !item.permission || auth.canAny(item.permission)

  return items
    .map((item) => (item.children ? { ...item, children: item.children.filter(visible) } : item))
    .filter((item) => visible(item) && (!item.children || item.children.length > 0))
})

// Staff profile, add and edit pages highlight "Staff" in the menu; one
// checklist highlights "Checklists" for HR and "My tasks" for everyone else.
const activeKey = computed(() => {
  const name = String(route.name)
  if (name.startsWith('staff-')) return 'staff'
  if (name === 'checklist') return auth.can('HRMS-StaffManagement') ? 'checklists' : 'tasks'
  if (name === 'payroll-run') return 'payroll-runs'
  // A payslip: your own belongs under "My payslips"; anyone else's under Pay runs.
  if (name === 'payslip') return auth.canAny(PAYROLL_VIEWERS) ? 'payroll-runs' : 'my-payslips'
  return name
})

// Keep the current page's module open.
const openGroups = computed(() => {
  const name = String(route.name)
  if (name.startsWith('hr-') || name.startsWith('staff') || ['branches', 'approvals', 'checklists'].includes(name) || (name === 'checklist' && auth.can('HRMS-StaffManagement'))) return ['hr']
  if (name.startsWith('payroll-') || (name === 'payslip' && auth.canAny(PAYROLL_VIEWERS))) return ['payroll']
  if (name === 'setup' || name === 'company-settings') return ['settings']
  return []
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
      <NMenu :value="activeKey" :options="menu" :default-expanded-keys="openGroups" :collapsed="collapsed" :collapsed-width="64" />
    </NLayoutSider>

    <NLayout>
      <NLayoutHeader bordered class="app-header">
        <span class="company">{{ auth.me?.company.name }}</span>
        <div class="header-actions">
          <BranchSwitcher />
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
        <div v-if="auth.me?.company.is_demo" class="demo-banner" role="note">
          <span><strong>You’re in the live demo.</strong> Try anything — everything resets every night.</span>
          <a :href="centralUrl + '/get-started'" class="demo-cta">Start your own free trial →</a>
        </div>
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

.demo-banner {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 8px 16px;
  padding: 10px 24px;
  background: #e3f1ee;
  color: #0a5a50;
  font-size: 0.95rem;
}

.demo-cta {
  font-weight: 700;
  color: inherit;
}

@media (max-width: 640px) {
  .user-name {
    display: none;
  }
}
</style>
