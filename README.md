# Media Buyers API — Codeception Test Suite

An API test suite for the **Media Buyers** REST resource (PHP 8.1 + Codeception 5),
built against the paper contract in `task/`. There is no live server — the point
is the shape of the tests, not a passing run: a clean boundary over HTTP,
payload builders instead of inline JSON, schema-driven response checks, and a
short path from a spec change to a test change. The base URL comes from `.env`,
so retargeting an environment is config, not code.

Part 2 answers are in [`WRITTEN_EVALUATION.md`](WRITTEN_EVALUATION.md).

## Setup

- **Suite:** `api` (Codeception 5)
- **Modules:** `REST` on `PhpBrowser`, `Asserts`, plus a small `Api` helper for
  JSON Schema validation
- **Payloads:** `MediaBuyerFactory` (Faker) — one valid template, overridden per test
- **Schemas:** `tests/schemas/*.json`, validated with `justinrainbow/json-schema`
- **Config:** `BASE_URL` from `.env`

## Layout

```
tests/
  api/
    GetMediaBuyersCest.php          # GET  /api/mediabuyers
    PostMediaBuyerCest.php          # POST /api/mediabuyers
  schemas/                          # response contracts (source of truth)
  Support/
    Factory/MediaBuyerFactory.php   # request payload builder
    Helper/Api.php                  # seeResponseMatchesJsonSchema()
```

## What is tested

Both endpoints, happy and unhappy paths. Boundary and validation cases are
data providers so each rule is a row, not a copy-pasted method. Every criterion
in the contract maps to at least one test:

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

## Why these choices

- **Schema validation on every success** catches field, type, and enum drift in
  one assertion — the cheapest early warning when the backend changes.
- **A factory** keeps the "valid buyer" defined once; negative cases only say
  what they change (`valid(['email' => 'not-an-email'])`).
- **Data providers** keep the boundary matrix readable as it grows.
- The list-level checks (empty array, valid emails, unique ids) are cheap and
  catch the regressions clients actually feel.

## Assumptions

- **Duplicate `mbId` (P11):** the contract allows 400 or 409, so the test asserts
  the 4xx class rather than a specific code.
- **Empty list (G3):** `{"data": []}`, so the schema permits an empty array.

## Scaling later

Once a real environment exists: wire the suite into CI (static gate on PRs, then
`codecept run` against a deployed/ephemeral env), add schema versioning and a
consumer-driven contract test (Pact) for drift detection, data setup/teardown,
parallel runs, and a reporting layer.

## Running

Not required (no live server). Structure and static checks need no backend:

```bash
composer install
composer run build      # generate the actor
composer run check      # php -l + PHPStan + codecept --dry-run
```
