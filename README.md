# Media Buyers API — Codeception Test Suite

Automated API test suite for the **Media Buyers** REST resource, written in
**PHP 8.1 + Codeception 5**. It encodes the acceptance criteria from the API
contract (`API_CONTRACT.md`, sourced from `task/QA_Recruitment_API_Test.pdf`)
as executable tests.

The contract has no live backend. To make the suite demonstrably runnable, a
tiny PHP-built-in-server mock (`tests/_data/mock-server/router.php`) implements
just enough of the contract for CI. The mock is **not** the deliverable — the
test code is. The URL is resolved from configuration (`.env` → `BASE_URL`), so
pointing the suite at a real environment is a one-line change.

## Testing setup

| Concern | Choice |
|---|---|
| Framework | Codeception 5 |
| Modules | `REST` (on `PhpBrowser`), `Asserts`, custom `Api` helper |
| Payloads | `MediaBuyerFactory` (Faker) — no inline JSON in tests |
| Response validation | JSON Schema via `justinrainbow/json-schema` |
| Config | `.env` (`BASE_URL`) — never hard-coded |
| Static gate | `php -l` + PHPStan (level 6) + `codecept --dry-run` |

## Repository layout

```
tests/
  api/                         # test suites (Cest classes)
    GetMediaBuyersCest.php      # GET /api/mediabuyers  — G1–G7
    PostMediaBuyerCest.php      # POST /api/mediabuyers — P1–P11
  schemas/                     # JSON Schemas (source of truth for responses)
  Support/
    Helper/Api.php             # seeResponseMatchesJsonSchema()
    Factory/MediaBuyerFactory.php  # valid()/missing() request builders
  _data/mock-server/router.php # optional mock for executability (not graded)
.github/workflows/ci.yml       # static gate → parallel tests → CTRF report
```

## Scenarios selected

Every acceptance criterion in the contract maps to at least one test:

- **GET (G1–G7)** — status/header/schema, `data` always an array, item-level
  required fields, valid email, `active` integer 0/1, unique ids. Item-level
  criteria are exercised by seeding a buyer via POST, then listing.
- **POST (P1–P11)** — happy path (schema + field echo + `active` mapping),
  missing-required-field (parameterized over each required field), invalid
  email, malformed field values (parameterized: initials length, name length,
  non-numeric `mbId`, non-boolean `active`), and duplicate-`mbId` uniqueness.

Negative and boundary cases use `@dataProvider` rather than duplicated methods.

## Abstractions and what they buy at scale

- **`MediaBuyerFactory`** — one place that knows a valid payload. When the
  contract adds a field, one edit fixes every test. Invalid variants are just
  `valid([...overrides])`, so negative cases stay one line.
- **`Api::seeResponseMatchesJsonSchema()`** — response validation lives behind
  the actor. Schemas are the source of truth; a contract change is a schema
  edit, not a test rewrite. This is the "spec change → test change" path.
- **Config-driven `BASE_URL`** — same suite runs against mock, staging, prod by
  swapping `.env`.
- **Groups (`smoke`/`regression`)** — `composer test:smoke` for fast signal,
  full run for regression.

## Assumptions (contract is silent)

- **P11 duplicate `mbId`**: the contract allows 400 or 409. The test asserts the
  4xx class (`seeResponseCodeIsClientError`); the mock returns 409.
- Empty list is `{"data": []}` (per G3), so the schema permits an empty array.

## Running

```bash
composer install
cp .env.example .env
composer run build          # generate Codeception actors
composer test               # run all
composer test:smoke         # @group smoke only
composer run check          # static gate: lint + phpstan + dry-run
```

The suite talks to `BASE_URL`. For local execution boot the mock first:

```bash
php -S 127.0.0.1:8080 tests/_data/mock-server/router.php
```

## CI

`.github/workflows/ci.yml` runs on every push/PR to `main`:

1. **Static** — lint + PHPStan + dry-run (fast fail).
2. **Tests** — a parallel matrix, one job per Cest file; each boots its own
   mock server, runs its shard, and uploads a JUnit report + debug log.
3. **Report** — merges the JUnit reports into CTRF and publishes to the job
   summary and an in-place PR comment.

## Future improvements

Schema-versioning and consumer-driven contract tests (Pact) for drift
detection; data setup/teardown against a real environment; wider
parallelization (sharding within a suite); coverage and flaky-test tracking in
the CTRF dashboard.
