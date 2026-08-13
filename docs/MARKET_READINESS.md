# CyraAgroLink — Market Readiness Roadmap

Enterprise agricultural commerce, investment, and digital ecosystem platform by **CYRA-TECH LTD**.

This document turns the product vision into an executable plan: what to ship first, what to defer, what to partner for, and rough timelines/budget buckets to reach a credible market launch.

---

## 1. Strategic summary

| Lens | Assessment |
|------|------------|
| Societal value | High — targets real African agri-value-chain failures |
| Product ambition | Very high — near full national agri-stack |
| Engineering progress | Strong Laravel web platform with many working modules |
| Market readiness today | Pilot-capable demo; not yet regulated commercial launch |
| Biggest risk | Building every module deeply before proving one paying loop |
| Biggest opportunity | Become the OS for farm-to-market commerce + agri-finance |

**North-star for launch**

> Farmers and buyers in a defined beachhead (recommend: Nigeria, 1–2 states, 1–2 staple crops) can list, discover, pay for, and move produce with a trusted digital wallet — in local language.

Everything else is an expansion module sold after that loop works with real money and real users.

---

## 2. Societal meaning (why this matters)

Agriculture employs a large share of Africans, yet income and food often leak through opaque pricing, weak logistics, limited credit, and informal trade.

| Stakeholder | Platform contribution |
|-------------|----------------------|
| Farmers | Market access, price discovery, investment, insurance, advisory, academy |
| Buyers / consumers | Transparent listings, exchange, consumer checkout, receipts |
| Investors / diaspora | Structured farm opportunities and portfolio tracking |
| Banks / MFIs | Digitized loan application and repayment workflows |
| Government | Subsidies, policy tracking, food-security visibility |
| Cooperatives | Savings, internal loans, voting, collective strength |
| Society | Food security, rural income, formalization, climate tools |

---

## 3. Current product inventory

### Already built (web modules present)

| Domain | Modules |
|--------|---------|
| Identity | Auth, roles, profile/avatar, Google OAuth, locales (EN/FR/HA/YO/IG) |
| Farm ops | Farm registration, crops, farmer dashboard, AI assistant, academy |
| Commerce | Smart marketplace, spot exchange, consumer marketplace, auctions, futures |
| Capital | Investments/portfolio, wallet, cooperatives, FI loans, subsidies |
| Risk | Insurance, risk intelligence, weather |
| Physical chain | Logistics, warehouse, supply chain, smart-city distribution, export hub |
| Advanced | Precision / digital twin, carbon credits, food processing, BI, reporting |
| Platform | Admin, messaging/inbox, market intelligence, arbitrage |

### Known demo / simulation gaps (must close before real money)

- ~~Wallet deposit/withdraw currently simulated for local/demo ops~~ → **Paystack funding** (withdraw still ledger-only / bank payout TBD)
- ~~SMS/email delivery often simulated~~ → **SMS: log/Termii**; email via Laravel mailer
- ~~Several services seed demo catalogs/applications outside strict production use~~ → **`DemoSeeding` production-off by default**
- Public marketing stats can overstate live scale
- API v1 is thin vs the web surface (auth/profile/health first)
- Google OAuth requires real Cloud Console credentials in `.env`

---

## 4. Module matrix — Keep / Defer / Partner

Use this when prioritizing engineering and business development.

| Module | Code status | Decision | Rationale |
|--------|-------------|----------|-----------|
| Auth + roles + profile | Strong | **Keep** | Core for any launch |
| Locales | Strong | **Keep** | Inclusion; expand copy coverage |
| Google OAuth | Ready (needs creds) | **Keep** | Lower signup friction |
| Farmer dashboard + farms + crops | Strong | **Keep** | Core farmer loop |
| Smart marketplace | Strong | **Keep** | Core commerce |
| Commodity exchange (spot) | Live matching + wallet | **Keep** | Differentiator; needs real funding rails |
| Digital wallet | Functional ledger, simulated funding | **Keep + integrate PSP** | Blocking dependency for market |
| Consumer marketplace | Strong | **Keep (pilot optional)** | B2C path; can follow B2B |
| Logistics | Workflow present | **Partner** | Needs real fleets/3PL |
| Warehouse | Workflow present | **Partner / soft-launch** | Needs physical SLAs |
| Supply chain tracking | Workflow present | **Defer polish** | Valuable after logistics partners |
| Investments / portfolio | Strong demo catalog | **Defer public offers** | Securities/compliance risk |
| Farm insurance | Workflow present | **Partner** | Needs underwriter |
| FI loans | Demo-capable | **Partner** | Needs bank/MFI MoU + disbursement |
| Government subsidies | Demo-capable | **Partner** | Needs ministry process/API |
| Cooperatives | Present | **Keep (pilot with co-ops)** | Natural rural GTM channel |
| Learning academy | Present | **Keep light** | Trust + retention; thin content OK at pilot |
| AI assistant | Present (local/OpenAI) | **Keep light** | Support tool; not launch blocker |
| Futures / auctions | Present | **Defer** | Complexity + regulatory load |
| Carbon credits | Present | **Defer** | Needs verifiers/buyers |
| Precision / digital twin | Present | **Defer** | Hardware/IoT dependency |
| Export hub | Present | **Defer** | Cross-border later |
| Food processing network | Present | **Defer** | Secondary value chain |
| Smart-city distribution | Present | **Defer** | Urban ops later |
| Food security dashboard | Present | **Partner (gov)** | Institutional sale, not farmer MVP |
| BI / reporting | Present | **Keep internal** | Ops metrics for Cyra team first |
| Market intelligence / arbitrage | Present + demo seed | **Defer public claims** | Avoid fake “signals” in production |
| Messaging / SMS / email | Partial / simulated | **Partner (Twilio/Termii/etc.)** | Must be real for pilot ops |
| Mobile native app | Preview only | **Defer → PWA first** | Ship mobile web, then Android |
| Public API breadth | Thin | **Keep expanding** | Required for agents/mobile/partners |

**Legend**

- **Keep** — invest engineering now for beachhead MVP  
- **Defer** — freeze feature growth until after pilot proof  
- **Partner** — productize workflow, but real-world fulfillment via contracts/APIs  

---

## 5. Phased roadmap

### Phase 0 — Stabilize (2–4 weeks)

**Goal:** Safe demo + credentialed integrations for stakeholders.

| Workstream | Deliverables |
|------------|--------------|
| Config | Real `GOOGLE_*`, production-ready `.env` templates |
| Hygiene | Disable demo seeding when `APP_ENV=production` |
| Docs | This roadmap + pilot scope one-pager |
| Security baseline | `APP_DEBUG=false` checklist, HTTPS plan, backups plan |

**Exit criteria:** Stakeholder demo on staging without placeholder OAuth; no synthetic depth pretending to be live liquidity.

---

### Phase 1 — Beachhead pilot MVP (3–6 months)

**Goal:** Real users, real (small) money, one geography.

**In scope**

1. Farmer onboarding (KYC-lite) + farm/crop basics  
2. Marketplace list/buy + spot exchange for selected commodities  
3. **Real wallet funding & payouts** (Paystack/Flutterwave or equivalent)  
4. Order status + basic dispute/support process  
5. Logistics handoff (manual partner workflow OK)  
6. Local language for critical screens  
7. Admin support tools + audit logs  

**Out of scope for Phase 1**

Futures, carbon, digital twin, export hub, public investment offers, government production programs (unless a signed MoU forces inclusion).

**Exit criteria**

- ≥ 50 active farmers and ≥ 5 buyers/aggregators  
- ≥ 100 completed paid orders or matched trades  
- Payment success rate ≥ 95%  
- Documented NPS / interviews from pilot cohort  

---

### Phase 2 — Commercial launch (3–6 months after pilot)

**Goal:** Paid product in one country with supportable ops.

| Workstream | Deliverables |
|------------|--------------|
| Growth | Agent model, cooperative partnerships, field playbooks |
| Mobile | PWA or Android MVP on expanded API |
| Trust | Stronger KYC tiers, fraud rules, refunds |
| Logistics | Contracted 3PL/fleet integration |
| Monetization | Commission, listing fees, or institutional SaaS |
| Compliance | NDPR, ToS/privacy, tax invoicing basics |

**Exit criteria:** Month-over-month retained GMV; support SLAs; legal pack reviewed.

---

### Phase 3 — Institutional & capital layer (6–12 months)

**Goal:** Expand from commerce OS to agri-finance OS.

- Insurance underwriter partnership  
- MFI/bank loan disbursement integration  
- Government subsidy pilots under MoU  
- Controlled investment products (only with counsel)  
- Warehouse receipt / inventory financing experiments  

---

### Phase 4 — Multi-country scale (12–24 months)

- Multi-currency wallets  
- Country payment methods (MoMo, etc.)  
- Regional deployments / data residency  
- Export trade with customs partners  
- Hardened performance and observability  

---

## 6. Budget buckets (indicative)

Figures are **planning ranges** in USD for a Nigeria-first launch team. Adjust to local costs and scope.

| Bucket | Phase 0–1 (pilot) | Phase 2 (launch) | Notes |
|--------|-------------------|------------------|-------|
| Engineering (web/API/mobile) | $40k–$120k | $80k–$200k | Core product + PSP + KYC |
| Cloud / DevOps / monitoring | $3k–$10k | $10k–$30k | Hosting, Redis, backups, Sentry |
| Payments / PSP setup | $2k–$8k + fees | Variable % of GMV | Paystack/Flutterwave fees dominate |
| Comms (SMS/email/WhatsApp) | $1k–$5k | $5k–$20k | Termii/Twilio/Africa's Talking |
| Field ops / agents | $15k–$50k | $40k–$120k | Often the largest real cost |
| Legal / compliance | $5k–$25k | $15k–$60k | Higher if investments go live |
| Brand / GTM | $5k–$20k | $20k–$80k | Radio, co-ops, events |
| Contingency (15%) | — | — | Always reserve |

**Pilot all-in planning range:** roughly **$70k–$250k** depending on team location and how much field work is in-house vs partner-led.

**Do not fund** deep futures/carbon/IoT R&D until Phase 1 exit criteria are met.

---

## 7. Beachhead recommendation

| Choice | Recommendation |
|--------|----------------|
| Country | Nigeria |
| States | Start with 1–2 (e.g. Oyo + Kaduna or similar production corridors) |
| Crops | Maize + one other high-liquidity staple (rice or cassava) |
| Primary users | Farmers + aggregators/buyers (+ cooperative as channel) |
| Payment | NGN wallet via local PSP |
| Success metric | Completed paid trades / week |

---

## 8. Technical checklist before production money

- [ ] `APP_ENV=production`, `APP_DEBUG=false`, HTTPS only  
- [ ] Real `GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET` / redirect URI  
- [ ] PSP webhooks verified; deposits/withdrawals no longer simulated  
- [ ] Demo seeders disabled in production request paths  
- [ ] Queue workers + scheduler supervised  
- [ ] DB backups + restore drill  
- [ ] Error tracking + uptime monitoring  
- [ ] Rate limits, session security, secrets rotation  
- [ ] Privacy policy, terms, NDPR basics  
- [ ] Support mailbox / escalation path  
- [ ] Load test critical trade + wallet endpoints  

See also [DEPLOYMENT.md](DEPLOYMENT.md) and [ENVIRONMENT.md](ENVIRONMENT.md).

---

## 9. Team shape (pilot)

Minimum effective team:

| Role | Focus |
|------|-------|
| Product lead | Scope control, pilot metrics |
| Backend engineer | Wallet/PSP, exchange, marketplace |
| Frontend engineer | Farmer/buyer UX, mobile web |
| Field lead | Cooperative & aggregator onboarding |
| Support / ops | Disputes, KYC review |
| Counsel (fractional) | Payments + agri-commerce terms |

---

## 10. KPI framework

### Pilot KPIs

- Active farmers / buyers (weekly)  
- Listings created  
- Paid orders + matched exchange volume (NGN)  
- Payment success / failure rate  
- Time-to-first-trade for new farmers  
- Support tickets per 100 trades  

### Launch KPIs

- Retained GMV (month 2 cohort)  
- Take rate / gross margin after PSP fees  
- Fraud loss rate  
- Agent productivity (farmers onboarded / agent / month)  

---

## 11. Decision rules (to avoid scope creep)

1. If a feature does not increase **paid trades** or **pilot retention** in 90 days → defer.  
2. If fulfillment needs trucks, warehouses, underwriters, or ministries → **partner**, do not simulate.  
3. If capital formation looks like a security → **legal gate** before marketing returns.  
4. Demo data must never appear as live liquidity or live impact stats in production.  

---

## 12. Immediate next actions (this quarter)

1. Configure real Google OAuth credentials and verify login.  
2. Lock beachhead: state(s), crops, target co-op/aggregator.  
3. Select PSP and implement real wallet funding + webhooks.  
4. Production-guard all `ensureDemo*` paths.  
5. Write pilot one-pager + farmer/buyer onboarding scripts.  
6. Recruit 1 field lead + 2–5 agents or a cooperative partner.  
7. Run a 8–12 week paid pilot and publish an internal readout against Section 10 KPIs.  

---

## 13. Evidence-based progress audit (codebase review)

Audited against the live Laravel codebase (services, routes, tests, migrations). Prefer understatement over hype.

### Scale signals

| Signal | Count (approx.) |
|--------|-----------------|
| Service domains (`app/Services/*`) | 40 |
| Controllers | 57 |
| Models | 82 |
| Migrations | 49 |
| Feature tests | 52 |
| Blade views | 218 |
| UI locales | 5 (en, fr, ha, yo, ig) |

### Progress scorecard

| Lens | Estimate | Why |
|------|----------|-----|
| Product vision / module **breadth** | **70–80%** | Nearly every roadmap domain exists as service + web UI + Feature tests |
| Beachhead MVP (Phase 1) | **40–50%** | Farms, marketplace, exchange, **Paystack wallet funding** present; KYC/disputes still thin |
| Production / market readiness | **35–45%** | Paystack + webhooks; demo seeding production-guarded; SMS Termii/log; OAuth still needs real console creds |
| Full regulated “agri OS” depth | **40–50% demo depth** | Workflows are present; partner fulfillment + compliance not |

### Justification of key MARKET_READINESS claims

| Claim in this doc | Audit result | Evidence |
|-------------------|--------------|----------|
| Wallet deposit simulated | **Resolved for deploy** | Paystack initialize/verify/callback/webhook; simulated credit blocked in production without keys |
| SMS/email simulated | **Resolved for deploy** | `MESSAGING_SMS_DRIVER=log\|termii`; email uses Laravel `Mail` |
| Demo seeding outside production | **Resolved** | `App\Support\DemoSeeding` + `CYRA_ALLOW_DEMO_SEEDING` gates request-time catalogs |
| Thin API v1 | **Confirmed** | `routes/api.php` ≈ health + auth + profile only |
| Google OAuth needs real creds | **Confirmed** | Controller blocks if unset; `.env` placeholders common in local setups |
| Spot exchange is live match + wallet | **Confirmed** | `CommodityExchangeService` matches, records `exchange_trades`, settles wallet; manual fill removed |
| Marketing stats hardcoded | **Nuanced** | Home stats often live DB counts, but can be inflated by demo seeds / simulated txs |
| Modules Keep/Defer/Partner | **Mostly justified** | Maturity is “Working-demo” for Keep modules; Partner/Defer modules exist but are not launch-critical |

### Modules present but under-emphasized in §4 matrix

- Equipment marketplace  
- Buyer dashboard (distinct from consumer shop)  
- Weather intelligence (separate from risk)  
- Cyra AI Command Center  

### Phase readiness (honest)

| Phase | Status | Note |
|-------|--------|------|
| Phase 0 Stabilize | **Largely done** | Demo hygiene, Paystack rails, SMS drivers, deployment env docs |
| Phase 1 Beachhead MVP | **In progress** | Real money path available once Paystack live keys + pilot ops are set |
| Phase 2–4 | **Not started** as market programs | Code scaffolding ahead of ops |

### Bottom line

CyraAgroLink has gone **very far as a product showcase and architecture**: a large multi-module Laravel platform with real exchange matching and unusually broad Feature tests. Relative to **market launch**, it is now **exiting Phase 0 into Phase 1** — next hard work is live Paystack credentials, KYC-lite, a paid field pilot, and partner SLAs — not more module screens.

---

## Related docs

- [ARCHITECTURE.md](ARCHITECTURE.md) — engineering layers  
- [API.md](API.md) — current API surface  
- [DATABASE.md](DATABASE.md) — data model notes  
- [ENVIRONMENT.md](ENVIRONMENT.md) — env vars  
- [DEPLOYMENT.md](DEPLOYMENT.md) — production deploy checklist  
