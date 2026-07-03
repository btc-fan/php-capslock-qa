# Written Evaluation

## 1. Which scenarios did you automate, and what did you leave out?

I covered every acceptance criterion in the contract, because each one is an
explicit promise the API makes and is cheap to assert. My priorities were:

- **Schema conformance on every success** (G2, P1) — a single check that guards
  field presence, types, and enums, and doubles as a contract-drift tripwire.
- **The mapping details** — `active` boolean → integer (P4) and the server-side
  `id` (P2) — the subtle bugs a plain "200 OK" check would miss.
- **Validation paths** (P5–P11) — this is where an API actually breaks for the
  clients calling it, so the negative cases carry real weight.

I left out load and performance, auth (the contract says nothing about it), and
exhaustive email-format fuzzing — one invalid case proves the path, more is
noise. If this were a real integration I'd add those once the contract covered
them, rather than guessing now.

## 2. The abstractions, and what they buy at 8 → 80 tests

- **`MediaBuyerFactory`** — the valid payload is defined once. A new contract
  field is one edit here instead of eighty across tests, and negative cases stay
  a single line (`valid(['name' => 'A'])`).
- **The schema-validation helper** — response checking sits behind the actor and
  the schema files are the source of truth. A spec change becomes a schema edit
  that ripples to every test, not a rewrite.
- **`.env`-driven base URL** — the same suite points at mock, staging, or prod
  without touching code.
- **Data providers + groups** — boundary cases are rows, and `smoke`/`regression`
  keep PR feedback fast while the suite grows.

## 3. Contract-drift detection

The schemas are the contract in machine-readable form, so any shape change fails
the suite immediately. To make drift surface on the *backend's* change rather
than in production, I'd add a provider-side contract check — an OpenAPI spec or a
Pact broker — verified in the backend's CI on every PR. Keep the schemas
versioned, diff the published spec against the committed one, and fail the build
(or open a PR and tag QA) on a mismatch. The point is that a breaking change
blocks its own merge and pulls a human in.

## 4. Tools I'd use to own this API's QA

Schema/OpenAPI-driven scaffolding for the boilerplate, with an LLM to draft the
boundary and negative cases from the contract behind a human review gate — fast
first draft, human-owned correctness. For maintenance, the factory and schema
helper localize churn and PHPStan catches drift in the test code itself. For
flakiness, I lean on run-history trends and quarantine over blanket retries so a
newly-flaky test is visible instead of hidden. For reporting, Codeception's
native JUnit/HTML output surfaced in CI as a job summary and PR comment.

## 5. The hardest API / E2E automation problem I've faced

> _To be written — a real experience, in my own words: the situation, the root
> cause, what I tried, and how I resolved it._
