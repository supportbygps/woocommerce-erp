# WC Enterprise ERP — Technical Reference

Version 1.14.0 · For developers, integrators and the maintainer

This document describes how the plugin is built: architecture, data
model, the accounting engine, extension points, and setup from scratch.
For the operator/sales guide (packages, security strategy, SaaS
lifecycle) see `DOCUMENTATION.md`.

---

## 1. Architecture at a glance

```
wc-enterprise-erp.php            Bootstrap: constants, autoload-ish requires,
                                 activation/deactivation, multisite wiring
│
├─ includes/
│  ├─ class-erp-core.php         Loads modules, builds admin menu, enqueues
│  │                             assets, renders Modules & Settings
│  ├─ class-erp-installer.php    dbDelta schema (46 tables), account seeding,
│  │                             per-site install, new-site provisioning
│  ├─ class-erp-accounting.php   Double-entry engine: Accounting::post()
│  ├─ class-erp-wc-bridge.php    WooCommerce order → journal posting
│  ├─ class-erp-capabilities.php Roles & capabilities
│  ├─ class-erp-license.php      License client + SaaS tier resolution
│  ├─ class-erp-network.php      Multisite: company dashboard + Tenant Manager
│  ├─ class-erp-pdf.php          Lightweight PDF/label output
│  ├─ helpers.php                wceerp_* helper functions
│  ├─ abstracts/
│  │  └─ class-erp-module.php    Base class every module extends
│  └─ modules/                   32 self-contained feature modules
│
├─ assets/{css,js}/              Admin styles + progressive-enhancement JS
├─ tests/                        PHPUnit (accounting, helpers, barcode/pdf)
├─ languages/                    i18n .pot target
└─ templates/                    Overridable view fragments
```

**Design principles**

- **Modular monolith.** One plugin, 32 modules, each a subclass of
  `WCEERP_Module`. Modules are self-contained: a module file can be
  removed and the rest still loads. The registry in `class-erp-core.php`
  maps `id => ClassName`.
- **Ledger is the source of truth.** Every financial event (sale,
  purchase, payment, payroll, depreciation, POS shift variance) posts a
  balanced double-entry journal. Reports read the ledger, never
  recompute from orders. If the trial balance balances, the books are
  correct by construction.
- **Backward-compatible migrations.** Schema changes are additive:
  new tables via `dbDelta`, new columns default-safe, new accounts
  seeded idempotently on every activation. Deactivation never drops
  data.
- **Tier gating.** `WCEERP_License::tier_allows($tier)` decides what a
  module may do; the license (key-based) or SaaS assignment
  (platform-based on Multisite) sets the tier.

---

## 2. Bootstrap & load order

1. `wc-enterprise-erp.php` defines `WCEERP_VERSION`, `WCEERP_FILE`,
   `WCEERP_PATH`, `WCEERP_URL`.
2. On `plugins_loaded`: check WooCommerce is active, then require
   helpers, abstracts, core classes, and instantiate `WCEERP_Core`.
3. `WCEERP_Core` requires every module file, instantiates enabled +
   tier-allowed modules, and calls `init()` on each.
4. Each module registers its own hooks, admin-post handlers, REST
   routes, cron events and menu entry in `init()`.
5. On Multisite, `class-erp-network.php` boots the network admin
   screens.

Activation (`register_activation_hook`) runs `WCEERP_Installer::activate`,
which on network activation loops every site via `switch_to_blog()` and
installs each. `wp_initialize_site` provisions the ERP into any newly
created site.

---

## 3. The accounting engine

### `WCEERP_Accounting::post( $args, $lines )`

The single entry point for writing to the ledger. Returns the journal ID
on success or a `WP_Error`. It is atomic and self-validating — if debits
≠ credits, or an account is unknown, nothing is written.

```php
$journal = WCEERP_Accounting::post(
    array(
        'journal_date' => '2026-07-22',      // optional, defaults to today
        'ref_type'     => 'wc_order',        // free-form source tag
        'ref_id'       => 123,               // source record id
        'memo'         => 'Sale — order #123',
        'branch_id'    => 1,                 // optional, Multi-branch
    ),
    array(
        array( 'account' => '1000', 'debit'  => 1150, 'memo' => 'Cash in' ),
        array( 'account' => '4000', 'credit' => 1000, 'memo' => 'Sales'   ),
        array( 'account' => '2100', 'credit' => 150,  'memo' => 'Output VAT' ),
        // party_type/party_id optional, for AR/AP subledgers:
        // array( 'account' => '1200', 'debit' => 500,
        //        'party_type' => 'customer', 'party_id' => 42 ),
    )
);
```

- `account` accepts a code string (`'4000'`) or a numeric account id.
- Lines carry either `debit` or `credit` (not both meaningfully).
- `party_type` + `party_id` build the dues sub-ledgers (customer,
  dealer, supplier, employee).
- Stored across `wp_erp_journals` (header) and `wp_erp_journal_lines`.

### Chart of accounts (seeded, BDT-oriented)

| Code | Account | Type |
| --- | --- | --- |
| 1000 | Cash | asset |
| 1010 | Bank | asset |
| 1020/1021/1022 | bKash / Nagad / Rocket | asset |
| 1100 | Accounts Receivable | asset |
| 1110 | Courier COD Receivable | asset |
| 1120 | Staff Salary Advances | asset |
| 1200 | Inventory | asset |
| 1300 | Input VAT | asset |
| 1500 / 1510 | Fixed Assets / Accum. Depreciation | asset |
| 2000 | Accounts Payable | liability |
| 2100 | Output VAT | liability |
| 2200 | Salaries Payable | liability |
| 3000 / 3100 | Owner Capital / Drawings | equity |
| 4000 | Sales | income |
| 4100 | Other Income | income |
| 5000 | COGS | expense |
| 5100 / 5110 | Salaries / Bonus & Overtime | expense |
| 5200–5900 | Operating, courier, depreciation, loss on disposal | expense |

New accounts are added by extending `seed_upgrade_accounts()` — they
insert only if the code is missing, so upgrades are safe.

### WooCommerce bridge

`class-erp-wc-bridge.php` hooks `woocommerce_order_status_{processing,
completed,refunded,cancelled}`. On a paid order it posts revenue, the
money/AR debit (COD gateways → 1110, cash/wallet gateways → their
account), output VAT split from VAT-inclusive prices, and the COGS/
inventory entries from average cost. Idempotent per order via order meta.

---

## 4. Module anatomy

Every module extends `WCEERP_Module`:

```php
class WCEERP_Module_Example extends WCEERP_Module {
    protected $id         = 'example';       // menu slug: wceerp-example
    protected $title      = 'Example';
    protected $capability = 'wceerp_view_reports';
    protected $tier       = 'business';      // starter|business|enterprise
    protected $order      = 40;              // menu position

    public function init() {
        add_action( 'admin_post_wceerp_example_save', array( $this, 'handle_save' ) );
        // register REST routes, crons, WC hooks here
    }

    public function render_page() {
        // admin screen HTML
    }
}
```

Register it in `class-erp-core.php`'s `$registry` array. Public methods
of the base class: `get_id, get_title, get_capability, get_tier,
get_order, has_menu, is_active, init, render_page, register_menu`.

Conventions used throughout:
- Nonces: `$this->verify_nonce('action_name')` on every write.
- Capabilities: `wceerp_user_can('cap')`; caps live in
  `class-erp-capabilities.php` and are granted to admin + shop_manager
  on activation.
- Document numbers: `wceerp_next_doc_number('type')` (e.g. `'inv'`,
  `'po'`, `'pay'`).
- Tables: `wceerp_table('name')` → prefixed `wp_erp_name`.
- Money: `wceerp_money()` (formatted), `wceerp_dec()` (sanitize to
  decimal). Lakh grouping and Bangla digits are options.

---

## 5. Data model (46 tables)

Prefixed `{$wpdb->prefix}erp_`. Grouped by domain:

- **Accounting:** accounts, journals, journal_lines, payments
- **Inventory:** stock, stock_moves, warehouses, batches, serials
- **Purchasing:** suppliers, purchases, purchase_items,
  purchase_approvals, rfqs, rfq_items, rfq_quotes, rfq_quote_items
- **Sales/quotes:** quotations, quotation_items
- **Courier:** consignments, courier_payouts
- **POS:** pos_sales, pos_shifts, pos_refunds
- **HR:** employees, attendance, leaves, salary_advances, payroll_runs,
  payroll_items
- **CRM:** leads, crm_activities
- **Service:** service_projects, service_tickets
- **Assets:** assets, asset_depreciation
- **Manufacturing:** boms, bom_items, productions
- **Dealers/resellers:** dealers, resellers, commissions
- **Branches:** branches
- **Banking:** bank_statement_lines
- **System:** notifications, audit_log

`WCEERP_Module_Inventory::move_stock($product_id, $warehouse_id, $qty,
$direction, $ref_type, $ref_id, $unit_cost, $batch_id, $note)` is the
single entry point for stock changes; it maintains moving-average cost
and writes `stock_moves`.

---

## 6. REST API

Namespace `wceerp/v1`. Auth via WordPress Application Passwords; each
route has a permission callback checking the relevant ERP capability.
Selected endpoints:

```
GET  /me                      GET  /dashboard
GET  /products                GET  /stock
GET  /orders  GET /orders/{id}
GET  /customers               GET  /dues/{type}
POST /payments                GET  /accounts
GET  /reports/trial-balance   GET  /reports/pnl
GET  /consignments            GET  /notifications
POST /devices                 (push token registration)

# POS terminal (same namespace)
GET  /pos/catalog   POST /pos/sale    POST /pos/shift
GET  /pos/customers GET  /pos/order/{id}  POST /pos/refund
```

Extend by calling `register_rest_route('wceerp/v1', …)` from a module's
`init()`, guarding with a capability check in the permission callback.

---

## 7. Cron jobs

| Hook | Schedule | Purpose |
| --- | --- | --- |
| `wceerp_courier_sync` | hourly | Poll Steadfast/Pathao status |
| `wceerp_asset_depreciation` | daily | Post monthly straight-line depreciation |
| `wceerp_scheduled_import` | hourly | Pull product/stock CSV from a URL |
| `wceerp_crm_followups` | daily 08:00 | Follow-up reminders |
| `wceerp_service_invoices` | daily 03:00 | Raise recurring AMC/contract invoices |
| `wceerp_ai_digest` | daily 07:30 | Push assistant warnings to notifications |
| `wceerp_license_check` | twicedaily | Revalidate license / SaaS tier |

WP-Cron fires on traffic; on low-traffic sites add a real system cron
hitting `wp-cron.php` every 5 minutes.

---

## 8. Setup from scratch

### 8.1 Single shop (standalone)

1. LAMP/LEMP with PHP 7.4+ (8.1+ recommended), MySQL 5.7+/MariaDB 10.3+.
2. Install WordPress, then WooCommerce; complete the WC setup wizard
   (currency BDT, tax as needed).
3. Upload and activate `wc-enterprise-erp.zip`. Tables, roles and the
   chart of accounts install automatically.
4. **Settings → Permalinks → Post name → Save** (required for POS + API).
5. **ERP → Modules & Settings**: activate license (or set the endpoint),
   enable modules, save.
6. **ERP → POS**: set warehouse, receipt/invoice options (if Enterprise).
7. Verify: create a test order, mark it processing, check
   **ERP → Reports → Trial Balance** balances.

### 8.2 Cloud SaaS (Multisite)

1. Provision a VPS (start 4 vCPU / 8 GB). Install LEMP + WordPress in
   **Multisite** mode (subdomain install — see WP docs; add the
   `WP_ALLOW_MULTISITE` / `MULTISITE` constants).
2. Wildcard DNS `*.yourerp.com` → server IP; wildcard SSL for the
   provisioning subdomains.
3. Network-activate WooCommerce and WC Enterprise ERP. Each new site
   auto-installs a complete isolated ERP.
4. **Network Admin → ERP Companies → Tenants (SaaS)**: create tenants,
   assign packages, map custom domains (per-domain A record + SSL, then
   paste the domain in the tenant row).
5. Suspend = archive; expiry drops a tenant to Starter until renewed.
6. Backups: nightly whole-network (files + DB). Monitor DB size and
   PHP-FPM workers before scaling tenant count.

### 8.3 License server (your site only)

Install `wceerp-license-server.zip` on your store/marketing site.
**Tools → ERP Licenses**: create keys (tier, site limit, expiry); the
"Active sites" table shows every domain checking in. Client sites point
at `https://yoursite.com/wp-json/wceerp-ls/v1` (via the endpoint field
or the `WCEERP_LICENSE_ENDPOINT` constant).

---

## 9. Configuration constants (wp-config.php)

| Constant | Effect |
| --- | --- |
| `WCEERP_LICENSE_ENDPOINT` | Hard-lock the license server URL |
| `WCEERP_DEV_LICENSE` | Enable `TIER-XXXX` local activation (dev only) |
| `WCEERP_AGENCY_LOCK` | Freeze white-label branding, hide its settings |

---

## 10. Development workflow

- **Coding standard:** WordPress-Extra. Escape on output
  (`esc_html/esc_attr/esc_url`), sanitize on input, prepared SQL only,
  nonce + capability on every write.
- **Tests:** `tests/` holds PHPUnit for the accounting engine, helpers
  and barcode/PDF. Run `composer install` then `phpunit` against a WP
  test scaffold. The accounting tests assert that every posting balances.
- **Lint before shipping:** `php -l` each file; run PHPUnit; smoke-test
  on a staging clone. This build was structure-checked with a
  bracket/quote balancer, but a real `php -l` on the target PHP version
  is the authoritative gate.
- **i18n:** all strings wrapped in `__()/esc_html_e()` with text domain
  `wc-enterprise-erp`; regenerate the `.pot` in `languages/`.
- **Adding a module:** create `class-module-foo.php` extending
  `WCEERP_Module`, add any tables to the installer (additive!), seed any
  accounts via `seed_upgrade_accounts()`, register the class in the core
  registry, add its capability, bump the version.

---

## 11. Upgrade & data safety

- Upgrades are drop-in: replace the plugin folder; the installer runs on
  activation and applies additive migrations.
- Deactivation/deletion never drops ERP tables (data is the business).
- Always back up the database before a major version change — it is an
  accounting system of record.
