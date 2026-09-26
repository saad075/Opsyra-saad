/**
 * Where each Setup wizard step is done in the app. A step without a screen
 * maps to null; a module adding a setup step adds its route here. Keys
 * match the API's SetupStep keys.
 */
export const setupStepRoutes: Record<string, string | null> = {
  company_details: 'company-settings',
  branches: 'branches',
  departments: 'departments',
  designations: 'departments',
  roles: 'roles',
  approvers: 'approvers',
  staff: 'staff',
  departure_reasons: 'leaving-reasons',
  checklist_templates: 'checklist-templates',
  pay_components: 'payroll-components',
  salaries: 'payroll-salaries',
  bank_accounts: 'payroll-salaries',
}
