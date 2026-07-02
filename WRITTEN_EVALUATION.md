# Written Evaluation — Media Buyers API Automation

## 1. Which scenarios did you select, and why? What did you leave out?

I encoded **every acceptance criterion in the contract** (G1–G7, P1–P11) as at
least one test, because the contract is the source of truth and each criterion
is an explicit, testable promise. Priorities:

- **Schema conformance on every success path** (G2, P1) — the single highest-
  value check: it guards field presence, types, and enums (`active` ∈ {0,1}) in
  one assertion, and it is the contract-drift tripwire.
- **Type/marshalling correctness** (P4 `active` boolean→integer, P2 server-side
  `id`) — the kind of subtle mapping bug that passes a naive "200 OK" check.
- **Negative and boundary paths** (P5–P11) — validation is where APIs actually
  break for clients; these are parameterized so each field's rule is one row.
- **List invariants** (G3 empty-array, G5 valid emails, G7 unique ids) —
  cheap to assert, catch real regressions (null instead of `[]`, dup ids).

**Left out intentionally:** load/performance, authn/authz (contract is silent
on both), pagination/filtering (endpoint takes no params), and exhaustive
fuzzing of the email RFC (one invalid case proves the path; the rest is
diminishing returns). These are noted rather than guessed at in code.

## 2. The abstractions, and what they buy from 8 → 80 tests

- **`MediaBuyerFactory`** — one definition of a valid payload. Adding a contract
  field is a one-line change instead of editing every test. Negative cases are
  `valid([...override])`, so a boundary case stays a single line. At 80 tests
  this is the difference between one edit and eighty.
- **`Api::seeResponseMatchesJsonSchema()` helper** — response validation lives
  behind the actor. Schemas (not test code) are the source of truth, so a spec
  change is a schema edit that ripples to every test automatically — the
  explicit "spec change → test change" path.
- **Config-driven `BASE_URL`** (`.env`) — the same suite runs against mock,
  staging, and prod by swapping configuration; no code change to retarget.
- **`@dataProvider`-driven negatives** — new boundary cases are new rows, not
  new methods. Keeps the suite flat and the intent readable as it grows.
- **`@group` tags (smoke/regression)** — fast feedback (`smoke`) vs. full
  coverage; at 80 tests this keeps PR signal quick and CI shardable.

## 3. Contract-drift detection

Goal: a backend change to this API automatically forces test review.

- **Schema as the contract artifact.** The JSON Schemas are the machine-readable
  contract. Every success response is validated against them, so a shape change
  fails the suite immediately.
- **Provider-side contract tests.** Adopt **consumer-driven contracts (Pact)**
  or an **OpenAPI spec** committed to the provider repo. A provider CI job
  verifies responses against the published schema/pact on every backend PR — the
  break surfaces on *their* PR, not in production.
- **Schema versioning + diff gate.** Keep schemas under version control; a CI
  step diffs the live/spec schema against the committed one and fails (or opens
  a PR) on drift, tagging the QA owners for review.
- **Process:** backend PR → contract verification job → on mismatch, block merge
  and auto-notify QA → test/schema updated in the same change set.

## 4. Tooling (including AI) for the QA process

- **Generation:** OpenAPI/schema-driven scaffolding for the boilerplate;
  **AI (LLM)** to draft boundary/negative cases and data providers from the
  contract, with a human review gate — fast first draft, human-owned correctness.
- **Maintenance:** factories + schema helper (this repo) to localize churn;
  static analysis (**PHPStan**) to catch drift in test code itself.
- **Flakiness detection:** run history in a reporting dashboard (**CTRF**),
  retry-with-quarantine for intermittent tests, and trend tracking to spot
  newly-flaky specs rather than blanket-retrying.
- **Reporting:** Codeception native JUnit/HTML → **CTRF** → GitHub Actions job
  summary + PR comment, with the HTML report published to GitHub Pages. This is
  exactly the pipeline wired in this repo.

Rationale: keep humans owning intent and correctness; let tooling/AI remove
boilerplate and surface signal (drift, flakiness) early.

## 5. Most challenging API / E2E automation situation

> **TODO — personalize before submitting.** Replace with a real experience:
> the situation, the technical root cause, what you tried, and how you resolved
> it (e.g. a race condition in async E2E flows fixed with deterministic waits /
> idempotent test data; or a flaky third-party dependency isolated behind a
> contract mock). One short paragraph, first person.
