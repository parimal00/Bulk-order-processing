# Bulk Upload Contract v1

## 1) Purpose
Upload bulk order lines from a CSV file, validate rows asynchronously, and persist results with row-level errors.

## 2) Supported File Type
- Allowed: `.csv`
- Max file size: `10MB`
- Max rows: `10,000` (excluding header)

## 3) Customer Context
- Customer is selected in UI (`customer_id`) at upload time.
- Customer is not provided per row in v1.

## 4) Required CSV Format
- Header row is required.
- Required columns:
  - `sku`
  - `quantity`
- Optional columns:
  - `requested_date`
  - `note`

## 5) Header Normalization
- Trim leading/trailing spaces.
- Case-insensitive matching.
- Example accepted header variants:
  - `SKU`, `sku`, `Sku`
  - `Quantity`, `quantity`, `QTY`

## 6) Column Mapping
- System attempts auto-map by normalized headers.
- User can manually adjust mapping before validation.
- Final mapping is stored in `bulk_uploads.column_mapping` JSON.

## 7) Validation Rules (Row-Level)
- `sku`:
  - required
  - must exist in `products.sku`
  - product must be active (`products.is_active = true`)
- `quantity`:
  - required
  - integer
  - greater than `0`
  - must satisfy product MOQ (`quantity >= products.moq`)
- Duplicate detection (within same upload):
  - same `sku` appearing more than once is invalid in v1

## 8) Processing Behavior
- Validation runs asynchronously via queue job.
- Partial acceptance enabled:
  - valid rows proceed
  - invalid rows are recorded in `validation_errors`
- File does not fail as a whole due to some invalid rows.

## 9) Upload Status Lifecycle
- `uploaded`: file stored, pending processing
- `validating`: validation job in progress
- `valid`: validation completed with zero invalid rows
- `invalid`: validation completed with one or more invalid rows

## 10) Persistence Expectations
- `bulk_uploads` stores:
  - file metadata
  - status
  - row counters (`total_rows`, `valid_rows`, `invalid_rows`)
  - mapping JSON
  - timing (`started_at`, `finished_at`)
- `validation_errors` stores:
  - `bulk_upload_id`
  - `row_number`
  - `column_name`
  - `error_code`
  - `error_message`
  - `raw_value`
  - optional `context` JSON

## 11) API-Level Validation (Request)
- `customer_id`: required, exists in `customers`
- `file`: required, file, mimes `csv,txt`, max `10240` KB

## 12) Out of Scope for v1
- XLSX support
- Multi-customer rows in one file
- Price calculation
- Inventory allocation
- Approval flow
