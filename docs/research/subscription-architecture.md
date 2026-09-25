# Research: subscriptions, landing site and super-admin — central vs per country

Written 2026-09-25, raised by the user: SLMS360 handles subscriptions on a
central "super" deployment that also hosts the landing website. Opsyra
runs one deployment per country, so where should the landing site,
sign-up, billing and the BrainStormers super-admin live?

## SLMS360 today

- **`main_front`** — a branch of the SLMS360 repo (local copy
  `Software/SLMS360-main-front-fixes`, read-only clone in
  `_reference/slms360-main-front` @ `4b0b374c`). It is the full SLMS360
  application plus central features: marketing site (blog, CMS, demo
  requests, affiliates, help tickets), sign-up with Stripe card payment and
  coupons (`Front/Onboarding/FrontClientOnboardingController`), automatic
  card charging (`billing:process-subscriptions`), invoice-mode billing for
  customers who pay manually, super-admin client management, and a central
  US tax table service.
- **Client registry** `our_clients`: one row per SLMS360 **installation**
  (its own URL `end_point`, a `webhook_secret`, Stripe ids, subscription
  dates).
- **Subscription bridge**: central pushes `subscription_ends_at` /
  `valid_subscription` to the installation's
  `POST /api/internal/subscription-update`, signed with HMAC-SHA256
  (`X-Subscription-Signature`). The installation stores it on its client
  row, and `CheckClientSubscriptionMiddleware` returns 402 with a link back
  to the central plans page once it lapses.
- Pricing is by **number of students**, with two tiers only: PKR for
  Pakistan, USD for everyone else.

## ADR-001's position

Per-country platform tables for tenants, plans, subscriptions and feature
flags, plus a tiny global directory (client code → country URL, no
personal data). It is silent on the landing site, sign-up, payments and the
BrainStormers admin panel.

## Options considered

| | A. Fully central | B. Fully per country | C. Hybrid |
|---|---|---|---|
| Landing site | one | four | one, country/pricing selector |
| Plans, pricing, payments, invoices | central | ×4 | central (prices may differ by country) |
| Super-admin | one | four | one |
| Enforcement when staff use the app | asks central | local | local copy pushed from central |
| Employee data crosses borders | risk grows | no | no — company, plan, billing and staff *counts* only |
| Central outage | every country stops | n/a | apps keep working; sign-up/payment pause |

Payments note (to verify before relying on it): Stripe does not, to our
knowledge, onboard merchants based in Pakistan or Oman, but a UK merchant
(BrainStormers) can take cards from customers anywhere. Local methods
(Pakistani bank transfer, JazzCash, Omani gateways) would be extra
central options. Country pricing and VAT/GST (UK 20%, Oman 5%, Pakistan
provincial sales tax on services) belong centrally too.

## Decisions (user, 2026-09-25)

1. **Hybrid (C).** One central system for the landing site, sign-up, plans,
   payments, invoices and super-admin. Each country deployment keeps its
   `plans`/`subscriptions`/feature-flag tables as the **local enforcement
   copy**, updated by a signed push from central (SLMS360's bridge
   pattern). Country deployments send central only aggregate usage (e.g.
   active staff count for per-seat billing), never personal data. This
   **amends ADR-001**: the per-country tables stay, but they are a copy,
   not the source of truth.
2. **A new "Opsyra Central" application**, not an extension of main_front.
   Port main_front's proven parts: Stripe sign-up and auto-charge, invoice
   mode, the signed bridge, super-admin client management, and later the
   US tax table service. main_front stays with SLMS360. Why: main_front is
   the whole school app, and its core ideas (per-student pricing, one
   installation per client) don't match Opsyra (per-employee pricing,
   companies inside country deployments).
3. **Timing:** after login and the frontend, alongside the test
   deployment, and before the first paying customer. Central's sign-up
   calls the country deployment's `TenantProvisioner` (via a signed
   internal endpoint to be added then) in place of the `tenants:create`
   operator command.
