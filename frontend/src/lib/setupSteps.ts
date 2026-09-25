/**
 * Where each Setup wizard step is done in the app. Steps whose screens
 * haven't been built yet map to null and show as "coming soon"; add the
 * route here when the screen lands. Keys match the API's SetupStep keys.
 */
export const setupStepRoutes: Record<string, string | null> = {
  company_details: 'company-settings',
  branches: 'branches',
  departments: null,
  designations: null,
  roles: null,
  approvers: null,
  staff: null,
  departure_reasons: null,
  checklist_templates: null,
  pay_components: null,
  salaries: null,
  bank_accounts: null,
}
