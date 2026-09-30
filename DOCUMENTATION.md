# WooCommerce ERP — Documentation

Version 1.21.0 · Developed by Get Pro Services

A complete ERP for WooCommerce built for Bangladeshi businesses:
inventory, purchasing, double-entry accounting, POS, courier & COD,
expenses, HR/payroll, CRM and full financial reports — sold in three
packages and licensed from a single server.

This guide covers **packages & tiers**, **setup from scratch**, and the
**feature catalogue**. For the developer/architecture reference (data
model, accounting engine, REST API, extension points) see `TECHNICAL.md`.

---

## 1. Packages & tiers

There are three packages. Each higher package **includes everything in
the packages below it**, plus its own features. A site always runs at
one tier. Disabling or expiring its licence stops the ERP entirely (see
§2.4) — there is no free tier fallback.

| | **Starter** | **Business** | **Enterprise** |
| --- | --- | --- | --- |
| Staff users | 2 | 8 | Unlimited |
| Products | 500 | 5,000 | Unlimited |
| Orders / month | 500 | 5,000 | Unlimited |
| Positioning | Single shop getting its books in order | Growing multi-staff business | Multi-branch / franchise / SaaS |

Limits are enforced with friendly notices ("upgrade to add more —
nothing is deleted"), never hard errors. They can be overridden
per-customer from the license server or the SaaS Tenant Manager.

### What's in each tier

**Starter** — the operational core:
Dashboard, Inventory, Warehouses, Suppliers, Purchases, Quotations,
Courier & COD, Expenses, Customers, Dues & Payments, POS, Reports, VAT/GST,
Notifications.

**Business** — adds management & automation (everything in Starter plus):
Accounting (full chart of accounts & journals), Analytics, Mobile API,
Fixed Assets & depreciation, Audit Trail, Bank Reconciliation, Barcode &
Serials, CRM, Dealers, Import/Export, Procurement & RFQ, Resellers &
commissions, Service & Tickets, Roles & Permissions.

**Enterprise** — adds scale & bespoke (everything in Business plus):
AI Assistant, Multi-Branch, HR & Payroll, Manufacturing (BOM/production),
White Label.

### Feature-to-tier map (quick reference)

| Feature | Tier |
| --- | --- |
| Dashboard | Starter |
| Inventory | Starter |
| Warehouses | Starter |
| Suppliers | Starter |
| Purchases | Starter |
| Quotations | Starter |
| Courier & COD | Starter |
| Customers (top customers & milestones) | Starter |
| Expenses | Starter |
| Dues & Payments | Starter |
| POS | Starter |
| Reports | Starter |
| VAT / GST (Mushak) | Starter |
| Notifications | Starter |
| Accounting | Business |
| Analytics | Business |
| Mobile API | Business |
| Fixed Assets | Business |
| Audit Trail | Business |
| Bank Reconciliation | Business |
| Barcode & Serials | Business |
| CRM | Business |
| Dealers | Business |
| Import / Export | Business |
| Procurement / RFQ | Business |
| Resellers | Business |
| Service & Tickets | Business |
| Roles & Permissions | Business |
| AI Assistant | Enterprise |
| Branches | Enterprise |
| HR & Payroll | Enterprise |
| Manufacturing | Enterprise |
| White Label | Enterprise |

Modules & Settings shows **only the features in the current package**;
higher-tier features are hidden with an upgrade hint.

---

## 2. Setup from scratch

### 2.1 Requirements

- WordPress 6.0+
- WooCommerce 7.0+ (must be installed and active first)
- PHP 7.4+ (8.1+ recommended)
- MySQL 5.7+ / MariaDB 10.3+

### 2.2 Install the ERP (single shop)

1. Install WordPress, then WooCommerce, and finish the WooCommerce setup
   wizard (set currency to BDT and configure tax if you use VAT).
2. Upload and activate `wc-enterprise-erp.zip`. On activation the plugin
   creates its tables, roles, the Bangladesh chart of accounts, and
   default expense categories automatically.
3. Go to **Settings → Permalinks → Post name → Save**. This is required
   for the POS terminal and the mobile API to work.
4. Open **ERP → Modules & Settings**:
   - Enter your **license key** (or set the license server endpoint), then
     save. Without a key the site runs in Starter mode.
   - Set your company name, BIN, address, fiscal year and VAT defaults.
   - Enable or disable individual modules as you wish.
5. Change the **ERP access PIN** from the default `1234` (see 2.5).
6. Verify: create a test WooCommerce order, mark it Processing, then open
   **ERP → Reports → Trial balance** and confirm it balances.

### 2.3 First-run checklist

- **Warehouses** — add at least one (POS and stock need it).
- **Suppliers** — add your main suppliers.
- **Inventory** — set opening stock and cost for your products.
- **Couriers** — under Courier & COD → Couriers & API keys, enter your
  Steadfast, Pathao and/or Bahok credentials.
- **Expenses** — review the seeded categories (Rent, Utilities, Transport
  & Fuel, Marketing, Office & Admin, Salaries, Miscellaneous); add your
  own and map each to an expense account.
- **POS** — set the receipt/invoice options and paper size.
- **Invoice design** — pick a preset (Classic / Modern / Bold) and tweak
  logo, colours, footer and terms.

### 2.4 Licensing

#### Pointing the ERP at your licence server

**In most cases nobody has to do anything.** The plugin ships with your
licence server address built in, so a fresh install already knows where
to validate. The customer installs it and enters a licence key — that's
all.

The endpoint is resolved in this order:

1. **`WCEERP_LICENSE_ENDPOINT` constant** in `wp-config.php`, if present
2. **The settings field**, if an operator has entered one
3. **The built-in default** that ships with the plugin

**When to use the constant.** Only on servers *you* control — that means
your SaaS Multisite, where you edit your own `wp-config.php` once and it
covers every tenant. It hard-locks the endpoint: the settings field turns
read-only and nobody, including a site administrator, can repoint the
plugin at a licence server of their own.

```php
define( 'WCEERP_LICENSE_ENDPOINT', 'https://yourstore.com/wp-json/wceerp-ls/v1' );
```

**Never ask a self-hosted customer to edit `wp-config.php`.** One typo
takes their entire site down. They should never need to: the built-in
default already points at your server, and the settings field is there if
they were given a different address.

| Deployment | Who edits wp-config | What to use |
| --- | --- | --- |
| Your hosted SaaS tenants | You, once, on your own server | The constant |
| Customer self-hosted | Nobody | Built-in default (or the settings field) |


#### How enforcement actually behaves

The licence is re-checked whenever its cached status is more than five
minutes old and an ERP page is loaded. So a licence disabled on your
server takes effect on that site within minutes.

When it does, the ERP **stops completely**:

- Every ERP admin screen is replaced by an "activate your licence" page.
- The POS terminal (`/wceerp-pos`) refuses to load.
- The mobile REST API returns a licence error.

There is no free tier and no read-only mode. **Your data is never
touched** — tables, journals and stock stay exactly as they are, and
everything returns the moment a valid key is entered.

#### Revoked versus unreachable

These are deliberately treated differently:

| Situation | Behaviour |
| --- | --- |
| Server **says** expired or disabled | Locks immediately — there is nothing to wait for |
| Server **cannot be reached** | 3-day offline grace, then locks anyway |

The grace exists so a DNS problem, an SSL renewal, or an outage on your
side doesn't shut down a paying shop mid-trading. Because it locks after
three days regardless, blocking the endpoint at a firewall gains nothing.

One deliberate exception: the WooCommerce accounting bridge keeps posting
while locked. If a shop carries on selling, stopping the ledger would
leave a permanent hole in its books that reactivation could never repair.
Locking the interface is a commercial lever; corrupting the ledger is
destroying records.

### 2.5 The ERP access PIN

A shared 4-digit PIN gate sits in front of the whole ERP, on top of the
WordPress login, so staff who share a browser or account can't see
accounting, profit, payroll or dues. Default is **1234** — change it in
Modules & Settings. Entering the PIN unlocks the ERP until you leave it;
loading any non-ERP page re-locks it. An administrator can reset the PIN
to the default if it's forgotten.

### 2.6 Hosted ERP for a customer's existing store (Connected Store)

If a customer already runs their own WordPress + WooCommerce and you want
to host their ERP, do **not** try to move their site into your network —
that is not what Multisite does. Instead:

1. Create a tenant site for them in your network (§2.7). This is their ERP.
2. Ask the customer to create REST API keys on **their** store:
   WooCommerce → Settings → Advanced → REST API → Add key, **Read**
   permission is enough.
3. In their tenant ERP, open **ERP → Connected Store**, paste their store
   URL (must be HTTPS) and the consumer key/secret, and save.
4. Use **Sync now** to pull existing orders; after that it syncs hourly.

Imported orders post revenue and VAT to that tenant's ledger. The same
order can never be posted twice. Note that COGS is not imported (this ERP
doesn't hold the remote store's cost basis), so stock valuation is
whatever you record locally.

Trade-offs to understand before selling this model: their financial data
lives on your server (your backup/security responsibility), sync depends
on their site staying reachable, and your hosting load grows with each
connected store.

### 2.7 Cloud SaaS (Multisite) — optional

For selling ERP-as-a-service to many tenants:
1. Install WordPress in Multisite mode with wildcard DNS + SSL.
2. Network-activate WooCommerce and the ERP; each new site auto-installs
   a complete, isolated ERP.
3. In **Network Admin → ERP Companies → Tenants**, create tenants, assign
   packages, set per-tenant limits and feature overrides, and map each
   tenant's own custom domain. Suspend = archive; expiry drops a tenant
   to Starter until renewed.

### 2.8 License server

Install `wceerp-license-server.zip` on **your** store/marketing site
(not customer sites). Under **Tools → ERP Licenses**:
- Add your **products** (this one server can license the ERP plus any
  future plugins/themes); each product has a free-tier fallback.
- Create keys per product (tier, site limit, expiry, optional per-key
  usage limits and feature overrides).
- The "Last seen" column is a live heartbeat; a site quiet for 2+ days is
  flagged.
- Set the console's own 4-digit PIN (default 1234) for a second lock.

---

## 3. Feature catalogue

### Core & operations (Starter)

- **Dashboard** — profit, income/expense, cash & bank balances, dues,
  stock value, low-stock and recent activity at a glance.
- **Inventory** — products, stock levels, moving-average cost, stock
  moves, low-stock thresholds.
- **Warehouses** — multi-warehouse stock with per-location quantities.
- **Suppliers** — supplier master data with BIN/TIN/NID and a per-supplier
  ledger.
- **Purchases** — record purchases/goods-received; stock in and supplier
  dues post automatically.
- **Quotations** — build quotes with a customer picker (search existing or
  add new), export to PDF, convert to orders.
- **Courier & COD** — book consignments with **Steadfast, Pathao and
  Bahok**; hourly status sync; COD tracked to a receivable account and
  reconciled on payout. An "All Orders (COD)" view unifies POS and online
  orders with channel/payment/COD-status filters. Create consignments from
  the order list (single or bulk for Facebook/WhatsApp orders).
- **Expenses** — record costs by category (rent, utilities, transport,
  marketing, salaries…). Every expense posts a double-entry journal, so it
  flows straight into the P&L.
- **Customers** — every customer ranked by order count, total spent,
  average order value and last order, with filters (date range, minimum
  order count, sort, search). Set order-count milestones (10/50/100/250/
  500/1000 by default) and get a notification when a customer crosses one.
  Counts include both online orders and POS sales.
- **Dues & Payments** — customer and supplier dues with payment recording.
- **POS** — offline-capable terminal (`/wceerp-pos`): barcode scanning,
  returns/exchanges, shifts with X/Z reports, received-amount to change/due
  (partial "khata" sales post the balance to the customer's receivable),
  80mm and A4 printing.
- **Reports** — Profit & Loss, **Profit Analysis** (selling price vs cost
  stack of purchase + courier + expenses, with margin % in brackets),
  Balance Sheet, Trial Balance, Stock Valuation.
- **VAT / GST** — Bangladesh Mushak-oriented VAT handling.
- **Notifications** — in-app alerts for low stock, follow-ups and more.

### Management & automation (Business)

- **Accounting** — full chart of accounts, journals, and the double-entry
  engine that underlies every report.
- **Analytics** — sales and performance charts.
- **Mobile API** — REST API (App Password auth) for mobile/other apps.
- **Fixed Assets** — asset register with automatic depreciation posting.
- **Audit Trail** — a record of who did what.
- **Bank Reconciliation** — match statement lines against the ledger.
- **Barcode & Serials** — barcode, batch and serial tracking.
- **CRM** — leads, pipeline, WhatsApp links, follow-up reminders.
- **Dealers** — B2B dealer accounts and pricing.
- **Import / Export** — bulk product/stock import-export, scheduled imports.
- **Procurement / RFQ** — requests for quotation, supplier quotes,
  approvals.
- **Resellers** — reseller accounts and commission tracking.
- **Service & Tickets** — service projects, tickets and recurring
  AMC/contract invoicing.
- **Roles & Permissions** — fine-grained capability control per role.

### Scale & bespoke (Enterprise)

- **AI Assistant** — a deterministic insight engine (optional bring-your-
  own-key LLM) that surfaces warnings and answers questions about your
  data.
- **Branches** — multi-branch operations with per-branch reporting.
- **HR & Payroll** — employees, attendance, leaves, advances, and monthly
  payroll posted as a balanced voucher per channel.
- **Manufacturing** — bills of materials and production runs.
- **White Label** — replace the ERP branding with your own (lockable via
  a constant for agencies).

---

## 4. Everyday use

- **Record a sale** — either through WooCommerce (online) or the POS
  terminal. Both post revenue, VAT and COGS to the ledger automatically.
- **Record an expense** — ERP → Expenses → Add expense; pick a category,
  amount and the account it was paid from.
- **Ship an order** — from the order list, "Create consignment" (or bulk),
  then book it with a courier under Courier & COD.
- **Collect COD** — when a courier pays out, record the payout; the money
  moves from Courier COD Receivable to your bank, net of fees.
- **Check profit** — Reports → Profit analysis shows selling price, the
  full cost stack, and your margin percentage.
- **Close the month** — confirm the Trial Balance balances; review P&L and
  the Balance Sheet.

---

## 5. Data safety

The ledger is the source of truth; every financial event posts a balanced
journal, so reports are correct by construction. Upgrades are drop-in and
migrations are additive — deactivating or deleting the plugin never drops
your ERP tables. Because it is an accounting system of record, always keep
regular database backups, and test any major upgrade on a staging copy
first.

---

*WooCommerce ERP — developed by Get Pro Services.
For technical/architecture details see TECHNICAL.md.*
