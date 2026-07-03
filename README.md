# Media Buyers API — Codeception Test Suite

An API test suite for the **Media Buyers** REST resource (PHP 8.1 + Codeception 5),
built against the paper contract in `task/`. There is no live server — the point
is the shape of the tests, not a passing run: a clean boundary over HTTP,
payload builders instead of inline JSON, schema-driven response checks, and a
short path from a spec change to a test change. The base URL comes from `.env`,
so retargeting an environment is config, not code.

Part 2 answers are in [`WRITTEN_EVALUATION.md`](WRITTEN_EVALUATION.md).

## Testing setup

For a project like this I keep a single `api` suite on Codeception 5:

- **`REST` on `PhpBrowser`** — the HTTP client and the request/response
  assertions (`sendGet`, `seeResponseCodeIs`, `seeResponseContainsJson`, …).
- **`Asserts`** — plain PHPUnit-style assertions for the checks the REST module
  doesn't cover (unique ids, valid emails).
- **A small `Api` helper** — adds `seeResponseMatchesJsonSchema()`, since REST
  ships type checks but not full JSON Schema validation.

Requests come from a factory, successful responses are validated against JSON
Schemas, and the base URL is read from `.env`.

## Repository layout

```
tests/
  api.suite.yml                     # suite config: modules + JSON headers
  api/
    GetMediaBuyersCest.php          # GET  /api/mediabuyers  (G1–G7)
    PostMediaBuyerCest.php          # POST /api/mediabuyers  (P1–P11)
  schemas/                          # response contracts, the source of truth
  Support/
    Factory/MediaBuyerFactory.php   # request payload builder
    Helper/Api.php                  # seeResponseMatchesJsonSchema()
codeception.yml                     # global config, namespace, .env params
composer.json                       # dependencies + Tests\ autoload
phpstan.neon.dist                   # static analysis config
.env.example                        # BASE_URL placeholder
```

## What is tested

Both endpoints, happy and unhappy paths. Boundary and validation cases are data
providers, so each rule is a row rather than a copy-pasted method. Every
criterion in the contract maps to at least one test:

| Criteria | Test |
|---|---|
| G1–G3 | `GetMediaBuyersCest::returnsListMatchingSchema` |
| G4, G6 | `GetMediaBuyersCest::listedBuyerExposesAllFields` |
| G5 | `GetMediaBuyersCest::listedEmailsAreValid` |
| G7 | `GetMediaBuyersCest::listedIdsAreUnique` |
| P1–P3 | `PostMediaBuyerCest::createsBuyerMatchingSchema` |
| P4 | `PostMediaBuyerCest::mapsActiveFlagToInteger` |
| P5 | `PostMediaBuyerCest::rejectsMissingRequiredField` |
| P6 | `PostMediaBuyerCest::rejectsInvalidEmail` |
| P7–P10 | `PostMediaBuyerCest::rejectsInvalidFieldValue` |
| P11 | `PostMediaBuyerCest::rejectsDuplicateMbId` |

## Why these scenarios

I chose to cover every acceptance criterion, weighted toward the checks that
catch real breakage: schema conformance on each success (field, type, and enum
drift in one assertion), the mapping details a plain "200 OK" misses
(`active` → integer, server-assigned `id`), and the validation paths, since
that is where an API actually breaks for the clients calling it. I left out
load, auth (the contract is silent on it), and exhaustive email fuzzing — one
invalid case proves the path.

## Abstractions (and what they buy at scale)

- **`MediaBuyerFactory`** — the valid payload lives in one place. A new contract
  field is one edit here, not one per test; negative cases only state what they
  change. At 80 tests this is the difference between one edit and eighty.
- **`Api::seeResponseMatchesJsonSchema()`** — response validation sits behind the
  actor and the schemas are the source of truth, so a contract change is a schema
  edit, not a test rewrite.
- **`.env`-driven base URL** — the same suite runs against mock, staging, or prod
  by swapping configuration.
- **Data providers + `smoke`/`regression` groups** — boundary cases stay flat and
  readable, and fast feedback stays separate from the full run.

## Assumptions

- **Duplicate `mbId` (P11):** the contract allows 400 or 409, so the test asserts
  the 4xx class rather than a specific code.
- **Empty list (G3):** `{"data": []}`, so the schema permits an empty array.

## Further improvements

Once a real environment exists: wire the suite into CI (static gate on PRs, then
`codecept run` against a deployed or ephemeral environment), add per-test data
setup/teardown, run in parallel, version the schemas, add a consumer-driven
contract test (Pact) for drift detection, and surface a reporting layer.

## Running

Not required — there is no live server. Structure and static checks need no
backend:

```bash
composer install
composer run build      # generate the actor
composer run check      # php -l + PHPStan + codecept --dry-run
```
