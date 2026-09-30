# WooCommerce ERP

A modular, commercial-grade **Enterprise ERP for WooCommerce**, built for Bangladeshi
businesses: inventory, purchasing, suppliers, multi-warehouse, double-entry
accounting, dues, resellers & commissions, barcode/batch/serial tracking,
manufacturing, VAT (NBR), reports, roles and import/export.

## Requirements

- WordPress 6.0+, WooCommerce 7.0+
- PHP 7.4+ (8.x recommended), MySQL 5.7+/MariaDB 10.3+

## Installation

1. Upload the `wc-enterprise-erp` folder to `wp-content/plugins/` (or install the zip).
2. Activate — tables (`wp_erp_*`) are created and a Bangladesh-ready chart of
   accounts, default warehouse and four ERP roles are seeded.
3. Open **ERP → Modules & Settings** to switch modules on/off and enter your
   company name, BIN and license key.

## Architecture

```
wc-enterprise-erp.php            Bootstrap, activation
includes/
  helpers.php                    ৳ lakh/crore money, Bangla digits, fiscal year, doc numbers
  class-erp-installer.php        dbDelta schema + seed data
  class-erp-capabilities.php     Capabilities + roles (Accountant, Store Keeper, …)
  class-erp-license.php          License tiers: starter / business / enterprise
  class-erp-accounting.php       Double-entry engine (single source of truth)
  class-erp-wc-bridge.php        WooCommerce order → stock + journal postings
  class-erp-core.php             Module registry, menu, settings, assets
  abstracts/class-erp-module.php Base class every module extends
  modules/class-module-*.php     One file per module (15 modules)
assets/css|js                    Admin UI
languages/                       Translation template (bn_BD ready)
```

**Accounting is the single source of truth.** Every financial event — purchase
receipt, WooCommerce sale, payment, stock adjustment, production overhead —
posts a balanced journal. Cash book, bank book, general ledger, trial balance,
P&L, balance sheet, customer due and supplier due are all *derived* from posted
journal lines, so the statements always reconcile.

**Stock** is tracked per product per warehouse at moving-average cost; the
summed quantity is synced back to WooCommerce stock automatically.

## Modules

| Module | What it does | Tier |
|---|---|---|
| Dashboard | KPIs, cash/bank/bKash balances, low stock, recent journals | starter |
| Inventory | Stock levels, adjustments, transfers, movement log | starter |
| Warehouses | Multi-warehouse master data | starter |
| Suppliers | BIN/TIN/NID, opening balances, per-supplier ledger | starter |
| Purchases | Purchase orders, AJAX product search, full & partial receiving, VAT input, batch creation | starter |
| Quotations | Price quotes (PDF + print) that convert into WooCommerce orders | starter |
| Accounting | Manual journal, general ledger, cash book, bank book, chart of accounts | starter |
| Dues | Customer due, supplier due, receive/pay with bKash/Nagad/Rocket | starter |
| VAT / GST | NBR rate presets, Mushak-6.3 numbering + PDF invoices, VAT summary (output − input) | starter |
| Reports | Trial balance, P&L, balance sheet, stock valuation, print-ready | starter |
| Resellers | Reseller registry, sales commission accrual, approve → pay out | business |
| Barcode & Serials | Code 39 label printing, batch expiry tracking, serial lifecycle | business |
| Import / Export | CSV in/out for suppliers, accounts, opening stock, journals, payments | business |
| Roles & Permissions | Capability matrix over all ERP capabilities | business |
| Assistant (AI) | Deterministic insight engine from your ledger (stock-out forecasts, supplier price gaps, profit swings, cash runway, COD aging, expense spikes) + optional bring-your-own-key LLM "ask your books" (Claude / OpenAI-compatible) | enterprise |
| Multi-company | WordPress Multisite: network-activate, each site = fully isolated company; auto-provisioning on site creation; Network Admin group comparison dashboard | enterprise |
| White label | Brand name, logo, colors, menu label, login logo, hide-credits, docs URL, wp-config agency lock | enterprise |
| HR & Payroll | Employees, attendance grid, leave approvals, ledger-backed salary advances, one-voucher monthly payroll paying each employee via their own channel (cash/bank/bKash/Nagad/Rocket) | enterprise |
| CRM | Lead pipeline board, activity log, wa.me WhatsApp links, daily follow-up reminders, funnel & source conversion reports | business |
| Service & Tickets | Projects/contracts/AMC with recurring WC-order invoicing, support tickets with priority, assignment & time logging | business |
| POS | Offline-capable full-screen terminal: service-worker shell, IndexedDB catalog + sale queue with idempotent sync into real WC orders, shifts with cash over/short journals, 80mm receipts, barcode scanning | enterprise |
| Mobile API | wceerp/v1 REST for Flutter/RN apps — application-password auth, capability-gated dashboard/products/orders/dues/payments/reports/consignments, push-token registry + wceerp_push hook | business |
| Branches | Multi-branch via warehouse mapping + journal dimension, branch P&L/stock comparison, inter-branch transfers | enterprise |
| Dealers | Wholesale dealer accounts: storefront discount %, credit-limit checkout gate, ledger-backed dues & statements, My Account portal tab | business |
| Procurement | RFQs, multi-supplier quote comparison, award-to-PO, threshold-based approval levels, supplier rating & performance | business |
| Analytics | 12-month revenue/expense/profit & cash-flow charts (server-rendered SVG, zero JS deps), inventory value, fast/slow movers, supplier spend | business |
| Courier & COD | Steadfast/Pathao/manual consignments, tracking sync, labels, truthful COD accounting & payout reconciliation | starter |
| Bank Reconciliation | Statement import, auto/manual matching, book-vs-bank report | business |
| Fixed Assets | Register, monthly straight-line depreciation, disposal with gain/loss | business |
| Audit Trail | Append-only activity log incl. logins, filterable viewer | business |
| Manufacturing | BOMs with wastage %, production runs with cost roll-up + overhead absorption | enterprise |
| Notifications | Low stock, PO received, commissions, expiring batches; daily cron | starter |

## Bangladesh-specific features

- **৳ money formatting** in lakh/crore grouping (1,23,45,678.00), optional
  **Bangla digits** (১২৩) everywhere amounts are shown.
- **July–June fiscal year** defaults on every report and the dashboard.
- **15% standard VAT** plus NBR reduced rates (10 / 7.5 / 5 / 0%); output VAT
  (a/c 2100) vs input VAT (a/c 1300) summary matching the Mushak-9.1 figure;
  sequential **Mushak-6.3** invoice numbers saved on each order, with a
  one-click **Mushak-6.3 PDF** button on the WooCommerce orders list.
- **bKash, Nagad, Rocket** ship as ledger accounts and payment methods; the
  bank book reconciles each mobile-banking channel separately.
- Supplier records carry **BIN, TIN and NID** fields.
- CSV exports include a UTF-8 BOM so Bangla text opens cleanly in Excel.

## Extending (for developers)

Filters:

- `wceerp_modules` — register your own module class (extend `WCEERP_Module`).
- `wceerp_gateway_account_map` — map payment gateways to ledger accounts.
- `wceerp_payment_methods` — add payment methods to the dues screens.
- `wceerp_sale_warehouse` — pick which warehouse a WooCommerce sale ships from.
- `wceerp_bd_vat_rates` — adjust the VAT rate presets.
- `wceerp_license_endpoint` — point license checks at your own server.

Actions:

- `wceerp_journal_posted( $journal_id, $args, $lines )`
- `wceerp_payment_recorded( $payment_id, $args )`
- `wceerp_stock_moved( $move_id, $product_id, $warehouse_id, $qty, $direction )`
- `wceerp_sale_posted( $order_id, $journal_id )`
- `wceerp_notification_added( $id, $type )`

## Commercial licensing

Three tiers are enforced per module: **starter**, **business**, **enterprise**.
The bundled **WCEERP License Server** plugin (separate zip) runs on *your*
store site and exposes a REST API:

```
POST /wp-json/wceerp-ls/v1/activate    { key, site }
POST /wp-json/wceerp-ls/v1/validate    { key, site }
POST /wp-json/wceerp-ls/v1/deactivate  { key, site }
```

Create keys under **Tools → ERP Licenses** (tier, site limit, expiry,
enable/disable). Point customer installs at the server via:

```php
define( 'WCEERP_LICENSE_ENDPOINT', 'https://your-store.com/wp-json/wceerp-ls/v1' );
```

The client revalidates twice daily by cron, with a 7-day grace period when
the server is unreachable, and falls back to Starter mode (never locking a
shop out of its data) if the license lapses. With no endpoint configured,
keys shaped `TIER-XXXX` activate in development mode.

## PDF output

`WCEERP_PDF` is a small dependency-free PDF writer (A4, Helvetica) used for
**Mushak-6.3 tax invoices** (button on each WooCommerce order) and
**quotation PDFs**. Base-14 fonts cannot render Bangla script — PDF text is
Latin (৳ becomes "Tk"); the on-screen print views keep full Bangla. For fully
localised Bangla PDFs, swap in dompdf/mPDF with a Bangla TTF.

## Automated tests

```
composer install
composer test        # or: vendor/bin/phpunit
```

Unit tests cover the lakh/crore formatter, Bangla digits, July–June fiscal
year boundaries, document numbering, the double-entry validator (unbalanced /
single-line / unknown-account journals are rejected; balanced ones persist),
Code 39 barcode generation and the PDF writer (envelope, xref offsets, text
escaping). They run against lightweight WP stubs (`tests/bootstrap.php`) — no
WordPress install needed. Integration tests against a live WP/WC stack belong
in wp-env and are the recommended next step before large-scale distribution.

## COD accounting — how the money is kept honest

WooCommerce marks a COD order "processing" long before you have the money.
This ERP refuses to lie about that:

```
Order ships (COD)      →  Dr 1110 Courier COD Receivable / Cr Sales + VAT
Courier delivers       →  parcel status "delivered", COD awaiting
Courier remits money   →  Dr Bank (net) + Dr 5600 Delivery charges
                          Cr 1110 Courier COD Receivable (gross)
```

The Courier & COD dashboard shows exactly how much cash couriers are holding
(a/c 1110) at any moment. Partial collections and multiple payouts per batch
are supported — each payout only credits what was actually collected.
Steadfast and Pathao book & track over their APIs; any other courier (RedX,
Paperfly, eCourier, Sundarban) plugs in via the `wceerp_courier_drivers`
filter or runs through the built-in manual driver.

## Roadmap (from the enterprise brief)

Shipped — Phase A (v1.2): Courier & Logistics, COD Accounting, Bank
Reconciliation, Fixed Assets, Audit Trail.
Shipped — Phase B (v1.3): Purchase approval workflow (RFQ → vendor
comparison → award → threshold approvals), Business Analytics dashboard,
Scheduled CSV/Google-Sheets imports.
Shipped — v1.96.0 (critical fix):
- Fixed the fatal error on /erp. Security headers were hooked to
  send_headers, which fires before $wp_query exists, so calling
  get_query_var() there crashed every front-end request. Moved to
  template_redirect with a guard on the query object.
- Added a Staff app card to the ERP dashboard and a Staff app menu entry,
  so the portal is actually reachable — previously it existed with no
  link to it anywhere.
- Defensive guards around module lookups so a partially-booted install
  degrades instead of white-screening.
- PWA theme colour, manifest background and status bar synced to the new
  palette so the installed app matches the interface.

Shipped — v1.29.0 — premium SaaS redesign:
- Portal home rebuilt as a modern mobile app: gradient plan banner with
  upgrade CTA, four colour-coded business metric cards, a colourful
  feature-module grid, and a recent-orders feed with status chips.
- Metrics are real, not decorative: sales, orders, customers and net
  profit come from the ledger and WooCommerce, with month-on-month
  deltas compared like-for-like (five days against five days, not five
  days against a full month). A delta is hidden rather than shown as
  a meaningless percentage when there is no prior baseline.
- New accent palette (indigo/violet) applied across the portal, app bar,
  PWA manifest, installed icon and status bar so the app looks like one
  product.
- Feature tiles now carry per-module colour chips so staff learn
  positions by hue; 4-up on phones, 6-up on tablets, 8-up on desktop.

Shipped — v1.28.1 (fixes):
- Fixed modules bouncing to the WordPress dashboard. The portal route
  pattern was greedy and also swallowed /erp/manifest.json and /erp/sw.js,
  so screens fell through to wp-admin and the app was never installable.
- Fixed the missing install option: the icon size was never registered as
  a query var, so icons 404'd and browsers silently refuse to offer
  installation without valid icons.
- Links inside module screens now stay in the portal instead of throwing
  you into wp-admin. Form actions still post to WordPress, as they must.
- A missing module now shows a proper message instead of silently
  redirecting, which looked exactly like being dumped out of the app.
- Rewrite self-heal is now keyed to plugin version and slug, so upgrades
  and slug changes re-flush rather than leaving routes broken behind a
  one-shot flag.
- Mobile: square tiles on a 3-up grid (2-up under 380px), labels clamped
  to two lines, summary cells that no longer leave a dead grey panel,
  full-width form fields, and horizontally scrolling tables.
- Login redesigned: deeper background, larger keypad with square touch
  targets, animated PIN dots, and a shake on a wrong PIN.

Shipped — v1.28.0 — installable app (PWA):
- The staff portal is now installable. Staff open /erp, tap Install, and
  get a home-screen icon that launches full-screen with no browser chrome.
- Manifest generated from the site name and brand colour, with app
  shortcuts (POS, Record expense) on long-press of the icon.
- Icons generated server-side from the brand colour and initials, so a
  fresh install is installable immediately; override with a real logo via
  the wceerp_pwa_icon_url filter.
- Service worker caches the shell so the app opens instantly. Pages are
  network-first on purpose — stale stock figures would be worse than
  none. Assets are cache-first and versioned, so an upgrade cannot leave
  a stale shell behind. POST requests are never intercepted.
- Branded offline screen that points staff to the POS, which keeps
  selling without a connection, and reloads automatically when the
  network returns.
- Install prompt with a one-week snooze, plus manual guidance on iOS
  where Safari offers no install event.

Shipped — v1.27.0 — security layer + portal UI:
- New security layer: 30-minute idle re-lock, access auditing for
  accounting/payroll/dues/reports, security headers on ERP screens
  (frame-blocking, nosniff, referrer policy), export throttling with
  logging, and a dashboard warning panel for weak settings (default PIN,
  portal without 2FA, missing vault key, no HTTPS).
- Portal rebuilt on a proper design system: sticky app bar, summary strip,
  responsive tile grid, safe-area handling for notched phones, large touch
  targets, and horizontal table scrolling on small screens instead of a
  broken layout.
- Audited every module for SQL injection, missing nonces and missing
  capability checks — all queries parameterised, all writes guarded.

Shipped — v1.26.0 — portal as full ERP + 2FA:
- The portal at /erp now renders **every module the plan includes**, inside
  its own shell, with no WordPress dashboard. Tiles are built from the live
  module registry and filtered by the signed-in user's permissions.
- Fixed a data leak: the hub previously showed today's takings to anyone
  holding the PIN. Figures now require a signed-in account.
- **Two-factor authentication** (RFC 6238 TOTP): set up on the WordPress
  profile with any authenticator app, with QR code, manual key, and 8
  single-use recovery codes. Codes cannot be replayed, a one-step clock
  window is tolerated, and portal attempt-limiting also covers 2FA.
- Operators can require 2FA for everyone using the portal, or leave it
  optional per person.

Shipped — v1.25.0:
- **Dues rebuilt**: customer / dealer / supplier tabs now show ageing
  buckets (0-30, 31-60, 61-90, 90+), contact details, and a one-tap
  WhatsApp reminder per party. The payment form lists parties with an
  outstanding balance instead of asking for a raw user ID.
- **Corporate dashboard band**: today's sales, orders this month, average
  order value, gross margin, working-capital position (receivables less
  payables) and COD still held by couriers.
- **Courier COD money tab**: per-courier position — booked, in transit,
  delivered-but-unpaid, collected, returned — plus net paid into bank and
  courier charges.
- Vendor-site detection: the ERP no longer treats the licence server's own
  site as a trial customer.
- Licence server: edit any licence (plan, expiry, limits, PIN, Agency),
  full subscription history per licence, WhatsApp capture.
- Staff portal at /erp with PIN login, attempt limiting and device tokens.

Shipped — v1.23.0 — trial visibility:
- Trial sites now check in with the licence server on trial start and
  once a day thereafter. Previously a trial site had no licence key, so
  it never contacted the server at all and was invisible to the vendor —
  the leads the trial exists to create could not be followed up.
- Licence server gains a **Free trials** table: domain, site name, admin
  email, days left (colour-coded), last seen, and a one-click WhatsApp
  contact link. Rows disappear automatically once that domain activates
  a licence.
- Only domain, site name and admin email are sent. No business data
  leaves the customer site, and the request is non-blocking so a customer
  never waits on it.

Shipped — v1.22.0 — commercial readiness:
- **3-day free trial**, starting the first time someone opens the ERP (not
  at activation, so an unopened staging install doesn't burn it). The
  start time is recorded once and never rewritten, so deactivating and
  reactivating cannot restart the clock. Trial runs at Starter level;
  when it ends the ERP locks.
- **My Plan screen**: current package, trial countdown, what's included,
  usage against limits, and a WhatsApp CTA (+8801729341345) to upgrade.
  A countdown banner appears on ERP screens during the trial.
- **Staff module** — create and manage ERP users without touching
  WordPress's own Users screens. Three plain-language access levels
  (Owner, Manager, Sales/POS staff), enforced against the package's user
  limit, with an upgrade prompt at the limit. Removing access never
  deletes the account, so ledger authorship survives.
- **Agency add-on**: Resellers, Mobile API, White Label and Roles &
  Permissions are now a separate add-on sold alongside any tier, not part
  of Enterprise. Toggle it per licence on the server.
- **Vendor-enforced PIN**: set a 4-digit PIN per site from the licence
  server. It overrides the customer's PIN, the default 1234 stops
  working, and they cannot change or reset it.
- **Installed sites view** on the licence server: every active domain with
  customer, plan, renewal countdown, last-seen heartbeat and a one-click
  WhatsApp contact link — plus counts of who's renewing soon or lapsed.
- The licence endpoint field is now **hidden from customers** by default.

Shipped — v1.21.0 — hard licence lockdown:
- An expired or deactivated licence now stops the ERP completely. No free
  tier, no read-only mode — every ERP screen is replaced by a single
  "activate your licence" page.
- All three entry points are sealed: admin screens, the POS terminal
  (front-end route) and the mobile REST API. Closing only the admin would
  have left the POS and API as open back doors.
- Revoked vs unreachable are treated differently, on purpose. If the
  server SAYS expired/deactivated, the install locks immediately. If the
  server merely cannot be REACHED, a 3-day offline grace applies before
  locking, so a paying shop is not shut down by a DNS blip or a vendor
  outage. Blocking the endpoint therefore buys nothing.
- Data is never touched. Tables, journals and stock remain intact, and
  everything returns the moment a valid key is entered. The WooCommerce
  accounting bridge deliberately keeps posting while locked, so a shop
  that keeps trading does not end up with a permanent hole in its books.

Shipped — v1.19.0 — SaaS hardening:
- **Encrypted credential vault**: connected-store API keys are now
  encrypted at rest with AES-256-GCM. The key comes from a
  WCEERP_VAULT_KEY constant in wp-config.php — outside the database — so
  a stolen DB dump alone cannot decrypt customer credentials. Existing
  plaintext keys are migrated automatically on first admin load.
- Secrets are never echoed back to the screen; the key field shows a
  masked hint and accepts a blank submit to keep the stored value.
- A clear warning appears when no dedicated vault key is configured, or
  when OpenSSL is unavailable.
- **Connection health tracking**: consecutive failure streaks and last
  successful sync are recorded, and the network Overview now shows each
  tenant's connection as Healthy / Stale / Failing with the error, so a
  dead connection is visible before the customer reports it.

Shipped — v1.18.0 — connected stores become operational:
- **Product mirror**: pull the remote catalogue into the ERP as local
  (private, hidden) products linked by remote id, so Inventory, stock
  valuation and POS work normally. Opening stock is seeded once from
  their quantity; after that the ERP's movements are authoritative.
- **Stock moves on sale**: imported orders now decrement ERP stock for
  mirrored products, so Inventory reflects what the connected store sold.
- **Manual stock push** (opt-in, per product): write ERP stock back to
  their store. Deliberately never automatic — an unattended push would
  race live sales and could oversell. Needs a Read/Write API key.
- **Courier & COD for connected orders**: remote orders now appear in the
  All Orders (COD) view with a "Connected" badge, and consignments can be
  created and booked for them. Consignments gained an order_source column
  and a customer snapshot so booking works without a local WC_Order.

Shipped — v1.17.1 (fix):
- Connected Store: fixed "Invalid parameter(s): after" — the sync sent an
  ISO date with a timezone offset, which the WooCommerce REST API
  rejects. Dates are now sent as Y-m-d\TH:i:s, and any bad value stored
  by the previous build is normalised automatically.
- A sync that fails no longer shows a green "Sync finished" success
  notice alongside the error.
- API errors now include WooCommerce's parameter detail, so a failure
  says which argument was wrong instead of just "invalid parameter".
- New "Reset sync position" action to re-scan from 30 days back (safe —
  already-imported orders are skipped, so revenue can't double-post).

Shipped — v1.17.0:
- New **Customers** module (Starter): ranked report of every customer by
  order count, total spent, average order value and last order date, with
  date-range, minimum-orders ("show me everyone with 500+"), sort and
  search filters. Counts cover online orders and POS sales together.
- **Order-count milestones**: set thresholds (default 10, 50, 100, 250,
  500, 1000). When a customer crosses one, an ERP notification is raised
  and a note is added to the order. Fires once per level per customer.
- Aggregation runs as a single SQL query and detects HPOS vs the classic
  order tables, so it stays fast on shops with tens of thousands of orders.

Shipped — v1.16.0:
- Courier: optional **auto-create consignments** — when an online COD
  order reaches Processing, a pending consignment is created
  automatically. Opt-in, COD-only, skips POS sales, never duplicates, and
  makes no courier API call (nothing is booked without you).
- Connected Store: **cost basis (COGS)** support. Import the remote
  catalogue and read a cost meta key from their store, or enter unit costs
  manually. Imported orders now post COGS, so Profit analysis is complete
  for connected tenants. Lines with unknown cost contribute nothing rather
  than a guessed figure.
- **Dashboard redesign** — hero profit panel with margin, quick-action
  cards, grouped money KPIs, and a needs-attention strip (low stock, COD
  held by couriers, customer dues) that only appears when relevant.

Shipped — v1.15.0:
- New **Connected Store** module (Business tier): pull orders from a
  customer's own, separately-hosted WooCommerce over its REST API and
  post them to this ERP's ledger. For the hosted-ERP model where the shop
  stays on the customer's server but the books live on yours. Idempotent
  (an order can never be posted twice), hourly cron + manual "Sync now",
  HTTPS enforced, connection test and error surfacing.
- Network Admin → **Overview (all)**: cross-tenant dashboard showing every
  company's income, expenses and profit for a date range, combined totals,
  connected-store status/last-sync, and a jump-into-ERP link.

Shipped — v1.14.0:
- License client: near-live enforcement. On ERP pages the license is
  re-checked when its cache is older than 5 minutes, so disabling a
  license on the server downgrades the site within minutes instead of
  waiting for the twice-daily cron. When the server reports the license
  disabled/expired, the site drops to the product's free tier AND paid
  entitlements (feature overrides, usage limits) are stripped — a full
  lock, not a stale paid state.
- The client now sends its product slug so one server can license many
  products.

Shipped — v1.13.0:
- Couriers: removed the "Manual" driver; Steadfast, Pathao and new
  **Bahok Courier** are the built-in drivers (Bahok settings + book/track
  scaffold in place; live API calls activate once its docs are wired).
- New **Expenses** module: record costs by category (rent, utilities,
  transport, marketing, salaries…). Every expense posts a real
  double-entry journal, so expenses now flow into the P&L and trial
  balance. Categories are editable and map to expense accounts.
- Reports → new **Profit analysis** tab: total product selling price vs a
  cost stack (product purchase/COGS + courier charges + other expenses),
  each line showing its share of sales in brackets, plus the net profit
  margin as a percentage.
- Modules & Settings now shows **only the features included in the current
  package**; higher-plan features are hidden with an upgrade hint, and
  saving no longer disturbs out-of-plan modules.

Shipped — v1.12.0:
- Order list: a clear "Create consignment" row action (and "View
  consignment" once one exists), plus a "Create courier consignments"
  bulk action — book a batch of manually-entered orders (Facebook /
  WhatsApp) as pending consignments in one click. HPOS + classic lists.
- PIN reset: an administrator can reset the ERP PIN back to the default
  1234 if it's forgotten (ERP), and the License Server console now has
  its own 4-digit PIN gate with change + reset.
- Rebranded to "WooCommerce ERP", developed by Get Pro Services.

Shipped — v1.11.0:
- Courier & COD → new "All Orders (COD)" tab: every WooCommerce order,
  POS and online, in one COD-tracking view. Each row shows its channel
  (POS vs Online badge), payment type (COD/prepaid), COD amount, linked
  courier, and COD collection status — not booked / awaiting courier
  payment / paid in. Money strip on top: COD held by couriers, net paid
  in this month, pending consignments.
- Filters focused on courier COD: by channel, payment type, COD status,
  courier, date range and search. Quick "Book" and "Collect" actions
  jump straight to the right tab. Existing consignment + payout
  accounting is untouched underneath.

Shipped — v1.10.0:
- ERP PIN gate: a shared 4-digit PIN (default 1234) locks the whole ERP.
  Any WordPress user who opens the ERP must enter it; leaving the ERP
  re-locks. Wrong/no PIN blocks entirely — accounting, profit, payroll
  and dues stay hidden. Change it (and clear the default warning) in
  Modules & Settings.
- POS is now a Starter-tier feature (was Enterprise); its backend screen
  and terminal work on every package.
- Key-based installs now receive usage limits + feature overrides from
  the license server (not just SaaS tenants), so a self-hosted Starter
  key can carry, e.g., POS-on and a product cap.

Shipped — v1.9.0:
- POS received-amount → live change/due; partial or fully-unpaid (khata)
  sales post the collected part to cash and the balance to the customer's
  AR (a due requires a customer). Received/change/due print on receipt + invoice.
- Quotation customer picker: search existing WooCommerce customers with
  auto-fill, or type a new one (shared AJAX lookup).
- Customizable invoice/document design: 3 presets (Classic/Modern/Bold)
  plus per-field settings (logo, colours, font, borders, stripes,
  signature, footer, terms) — POS A4 fully styled, quotation PDF content-aware.
- SaaS tenant packaging: per-package usage limits (users, products,
  orders/month) enforced with friendly notices, per-tenant feature
  overrides beyond the three tiers, and a usage strip on the tenant dashboard.

Shipped — v1.8.1: POS terminal 404 fix — the front-end route now
self-heals (flushes rewrite rules once if its rule is missing from the
compiled table) and re-flushes whenever modules are enabled/saved, so
enabling POS after licensing no longer strands /wceerp-pos. Query var
registered on its own hook.

Shipped — v1.8.0 cloud SaaS: Tenant Manager in Network Admin (create
tenant + assign package + map their own custom domain + suspend/expire),
platform-assigned packages that override key licensing on Multisite
(clients never touch a key), and a managed-subscription banner on the
client dashboard. Custom domains use native WP address mapping — no
sunrise.php or mapping plugin.

Shipped — v1.7.2 commercial hardening: dev-license backdoor closed
(TIER-XXXX now requires WCEERP_DEV_LICENSE), license endpoint settable
in UI and lockable by constant, directory index guards, dashboard
feature showcase with tier-locked upgrade hints, and full commercial
DOCUMENTATION.md (packages, security strategy, SaaS deployment).
Shipped — v1.7.1: product search fixed on Purchases/Quotations/RFQ
(WooCommerce enhanced-select load-order race), POS customer attach
(registered search online + walk-in name/phone offline), POS print
sizes (80mm receipt / A4 invoice / ask each time, with address & BIN),
license server "Active sites" heartbeat view showing every domain
running the plugin and when it last checked in.
Shipped — Phase F (v1.7): AI assistant (auditable rule-based insights +
optional BYO-key LLM chat over aggregates only), multi-company via
Multisite (per-site isolation by construction, network KPI dashboard,
auto-install on new sites; consolidation journals stay roadmap), white
label with WCEERP_AGENCY_LOCK. All six phases of the enterprise brief
are now shipped.
Shipped — Phase D (v1.6): HR & Payroll (attendance-aware payroll runs,
one balanced voucher per month with per-channel net pay, advances on a/c
1120), CRM (pipeline, activities, follow-up cron, wa.me chat links —
official WhatsApp Business API stays roadmap), Service management (AMC
recurring invoices as real WooCommerce orders, ticket time tracking).
Shipped — v1.5.2: POS product exchange (return credit + new items in
one transaction, settled net; refund and replacement sale post as linked
idempotent jobs so drawer and ledger move by exactly the difference).
Shipped — v1.5.1 POS hardening: returns/refunds (partial or full, WC
refund + ERP restock + reversal journal, idempotent offline queue),
X/Z shift reports printed from local tallies, held/parked carts, and
cash-refund-aware shift reconciliation.
Shipped — Phase E (v1.5): Offline POS (queue-and-sync terminal at
/wceerp-pos, shift cash management with over/short postings) and the
Mobile App REST API (application-password auth, per-route ERP
permissions, push-token wiring via the wceerp_push action).
Shipped — Phase C (v1.4): Multi-Branch (warehouse→branch mapping, branch
dimension on every journal, branch P&L/stock comparison; existing data
lands on "Head Office"), Dealer & Wholesale (auto wholesale pricing,
credit limits enforced at checkout, dealer dues in the ledger with admin
and My-Account statements).

Planned next, in recommended order — each is a genuine project and should be
built and tested as its own release rather than rushed together:

| Phase | Modules | Why this order |
|---|---|---|

Architecture note: the codebase intentionally keeps its `WCEERP_` prefixed-
class architecture rather than a mid-flight namespace/DI rewrite — that
refactor belongs in a major version with a compatibility layer, precisely
because the brief demands "no breaking changes with existing data".

## Honest production notes

Remaining gaps before large-scale launch: Bangla-script PDF rendering
(dompdf/mPDF + Bangla TTF), wp-env integration tests covering the WooCommerce
bridge and courier flows end-to-end, and live-credential testing against the
Steadfast/Pathao production APIs (their responses are mapped per current
public docs; the endpoint URLs and field mappings are filterable in case of
API changes).
