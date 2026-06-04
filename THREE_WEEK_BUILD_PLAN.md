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

---

## 10-Day Extension Plan: Observability, Security, & Resiliency

This extension focuses on transitioning the bulk-order processing application from a functional MVP to a production-grade, highly observable, resilient, and secure system.

### Day 1: Laravel Telescope & Local Debugging
- **Goal:** Install and configure Laravel Telescope to monitor local application state.
- **Tasks:**
  - Install Telescope via Composer and run the migrations.
  - Configure route access policies (e.g., restrict dashboard access in local or production).
  - **Learning Focus:** How to inspect queries, requests, background queue jobs, and application logs in real-time.

### Day 2: Laravel Pulse & Real-time Metrics
- **Goal:** Install Laravel Pulse to monitor live application performance.
- **Tasks:**
  - Set up Pulse for real-time tracking of slow queries, slow requests, and queue delays.
  - Add custom Pulse recorders (e.g., monitor execution time or memory footprint of the bulk upload queue).
  - **Learning Focus:** Identifying resource bottlenecks and analyzing production performance metrics.

### Day 3: Advanced Redis Queue & Rate Limiting
- **Goal:** Manage queue workloads and rate limit outbound integration calls.
- **Tasks:**
  - Transition the queue driver to Redis and set up Laravel Horizon (if containerized) or optimize database queue workers.
  - Implement rate limiting for outbound ERP/Shopify API stubs (e.g., max 5 requests per second per customer).
  - **Learning Focus:** Managing background worker concurrency, queue isolation, and job rate-limit strategies.

### Day 4: Integration Resiliency (HTTP Client Retries & Backoff)
- **Goal:** Build a robust, fault-tolerant external API integration client.
- **Tasks:**
  - Implement Laravel Http Client retries for webhook and external sync API calls.
  - Configure exponential backoff with random jitter to avoid overloading remote endpoints.
  - **Learning Focus:** Gracefully handling network instability and preventing "thundering herd" problems.

### Day 5: Circuit Breaker Pattern
- **Goal:** Prevent cascading failures when external integrations go offline.
- **Tasks:**
  - Build a simple cache-based Circuit Breaker service for external integration endpoints.
  - Automatically trip the circuit to "open" after 5 consecutive request failures, preventing further external calls during cool-down.
  - Reflect integration health status (e.g., Active, Paused, Tripped) in the UI Reconciliation view.
  - **Learning Focus:** Implementing fail-fast mechanisms to protect application resources.

### Day 6: Webhook Security & Signature Verification
- **Goal:** Secure incoming integration callbacks and webhooks.
- **Tasks:**
  - Secure integration endpoints using HMAC SHA-256 signature verification.
  - Write custom middleware to validate incoming payload signatures against a shared secret.
  - Write integration tests simulating signed and unsigned payloads.
  - **Learning Focus:** API authentication best practices and security middleware patterns.

### Day 7: Advanced Data Caching Strategy
- **Goal:** Reduce database load on high-read domain records.
- **Tasks:**
  - Implement caching for customer-specific pricing structures, volume breaks, and catalog details.
  - Write Eloquent model observers to automatically invalidate/flush the cache when records are updated.
  - **Learning Focus:** Designing read-through/write-through cache strategies and maintaining cache consistency.

### Day 8: Database Query & Index Optimization
- **Goal:** Profile and optimize SQL query execution plans.
- **Tasks:**
  - Deep-dive into query logs using Telescope to identify N+1 query patterns.
  - Use `EXPLAIN` on database reads for validation errors, failed rows, and order lines.
  - Add compound indexes (e.g., `bulk_upload_id` + `row_number`) to accelerate retrieval of large error datasets.
  - **Learning Focus:** Query profiling, index selection, and database optimization techniques.

### Day 9: Stress Testing & Load Benchmarking
- **Goal:** Understand system boundaries under extreme load.
- **Tasks:**
  - Write a performance benchmark script to generate files with 5,000, 10,000, and 20,000 rows.
  - Monitor memory peaks, queue processing times, database locks, and CPU usage.
  - **Learning Focus:** Conducting load tests and identifying system capacity limits.

### Day 10: Portfolio Documentation & Wrap-up
- **Goal:** Compile telemetry findings and design choices into a portfolio case study.
- **Tasks:**
  - Create a "Senior Observability & Resiliency Report" highlighting decisions around Telescope/Pulse setup, the circuit breaker mechanism, and optimization results.
  - Update the repository README.md with instructions to configure Telescope/Pulse.
  - **Learning Focus:** Effectively demonstrating engineering decisions and metrics to prospective clients.
