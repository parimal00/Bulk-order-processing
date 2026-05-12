# 3-Week Build Plan: Bulk Order Processing

Start Date: May 12, 2026  
Target Completion: June 1, 2026

## Project Outcome

Build a production-style Laravel + Inertia B2B bulk ordering system with:
- Bulk upload and validation
- Pricing and quantity rules
- Async processing and failure recovery
- Approval workflow
- Ops dashboard and audit trail

## Week 1: Core Ingestion and Domain (May 12 - May 18)

### Milestone
Upload, parse, validate, and persist bulk orders end-to-end.

### Build Tasks
- Define domain models: `customers`, `products`, `bulk_uploads`, `orders`, `order_lines`, `validation_errors`.
- CSV/XLSX upload flow with column-mapping UI.
- Validation pipeline (required fields, SKU existence, quantity constraints, duplicate detection).
- Status lifecycle: `uploaded -> validating -> valid/invalid`.
- Basic queue setup for async validation.
- Seed realistic demo data (50-200 products, multiple customer tiers).

### Portfolio Deliverables
- 60-90 sec screen recording: upload -> mapping -> validation results.
- ERD diagram and short architecture note.
- GitHub README section: "Ingestion and Validation Design".
- Test proof: feature tests for upload/validation edge cases.

## Week 2: Processing Engine and Approvals (May 19 - May 25)

### Milestone
Convert valid uploads into priced, allocatable orders with approval flow.

### Build Tasks
- Pricing engine: customer-specific pricing + volume breaks + MOQ checks.
- Inventory allocation logic: full fill, partial fill, backorder split.
- Idempotent job pipeline (retry-safe processing).
- Approval workflow: `pending_review -> approved/rejected` with actor + timestamp logs.
- Exception queue for failed rows with actionable reasons.
- Role-based access (ops, approver, admin).

### Portfolio Deliverables
- Sequence diagram for processing pipeline.
- Screen recording: valid upload -> pricing -> approval -> order creation.
- Failure handling write-up (retries, idempotency, dead-letter strategy).
- Test proof: job tests for retries and duplicate-submit prevention.

## Week 3: Integrations, Observability, and Portfolio Polish (May 26 - June 1)

### Milestone
Production-style operational visibility and polished portfolio package.

### Build Tasks
- Outbound integration stub (Shopify/ERP-style API + webhook callbacks).
- Reconciliation view: internal vs external sync status.
- Ops dashboard: throughput, failure rate, avg processing time, pending approvals.
- Audit log explorer with filters by upload/order/user.
- Performance pass (indexes, eager loading, queue concurrency tuning).
- CI checks and stable demo environment setup.

### Portfolio Deliverables
- Final demo video (3-5 min) with business scenario narrative.
- Metrics snapshot (example run: 10k lines processed, error percent, timing).
- Architecture diagram v2 (components + data flow).
- Senior decisions doc:
  - Tradeoffs made
  - Reliability patterns used
  - What you would scale next
- Polished README with setup, screenshots, and feature map.

## Acceptance Criteria (End of Week 3)

- Can process large uploads asynchronously without duplicate orders.
- Failed rows are recoverable and visible in UI.
- Approval and audit trail are complete and queryable.
- Integration and reconciliation flow is demonstrable.
- Portfolio artifacts are publish-ready.
