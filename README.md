# Media Buyers API — Codeception Test Suite

An API test suite for the Media Buyers REST resource (PHP 8.1 + Codeception 5),
built against the paper contract in `task/`. There is no live server, so the
point is the shape of the tests rather than a passing run: a clean boundary over
HTTP, payload builders instead of inline JSON, schema-driven response checks, and
a short path from a spec change to a test change. The base URL is read from
`.env`, so retargeting an environment is config, not code.

Part 2 answers are in [`WRITTEN_EVALUATION.md`](WRITTEN_EVALUATION.md).

## Testing setup

For a project like this I keep a single `api` suite on Codeception 5:

- **`REST` on `PhpBrowser`** for the HTTP client and the request/response
  assertions (`sendGet`, `seeResponseCodeIs`, `seeResponseContainsJson`, and so on).
- **`Asserts`** for the checks the REST module doesn't cover, such as unique ids
  and valid emails.
- **A small `Api` helper** that adds `seeResponseMatchesJsonSchema()`, since the
  REST module ships type checks but not full JSON Schema validation.

Requests come from a factory, successful responses are validated against JSON
Schemas, and the base URL is read from `.env`.

## Repository layout

```
tests/
  api.suite.yml                     # suite config: modules + JSON headers
  api/
    GetMediaBuyersCest.php          # GET  /api/mediabuyers  (G1-G7)
    PostMediaBuyerCest.php          # POST /api/mediabuyers  (P1-P11)
  schemas/                          # response contracts, the source of truth
  Support/
    Factory/MediaBuyerFactory.php   # request payload builder
    Helper/Api.php                  # seeResponseMatchesJsonSchema()
codeception.yml                     # global config, namespace, .env params
composer.json                       # dependencies and Tests\ autoload
phpstan.neon.dist                   # static analysis config
.env.example                        # BASE_URL placeholder
```

## What is tested

Both endpoints, happy and unhappy paths. Boundary and validation cases are data
providers, so each rule is a row rather than a copy-pasted method. Every
criterion in the contract maps to at least one test:

| Criteria | Test |
|---|---|
| G1-G3 | `GetMediaBuyersCest::returnsListMatchingSchema` |
| G4, G6 | `GetMediaBuyersCest::listedBuyerExposesAllFields` |
| G5 | `GetMediaBuyersCest::listedEmailsAreValid` |
| G7 | `GetMediaBuyersCest::listedIdsAreUnique` |
| P1-P3 | `PostMediaBuyerCest::createsBuyerMatchingSchema` |
| P4 | `PostMediaBuyerCest::mapsActiveFlagToInteger` |
| P5 | `PostMediaBuyerCest::rejectsMissingRequiredField` |
| P6 | `PostMediaBuyerCest::rejectsInvalidEmail` |
| P7-P10 | `PostMediaBuyerCest::rejectsInvalidFieldValue` |
| P11 | `PostMediaBuyerCest::rejectsDuplicateMbId` |

## Why these scenarios

I chose to cover every acceptance criterion, weighted toward the checks that
catch real breakage. Schema conformance on each success handles field, type, and
enum drift in one assertion. The mapping details a plain "200 OK" misses get
their own tests (`active` to integer, server-assigned `id`). The validation paths
get the most attention, since that is where an API breaks for the clients calling
it. I left out load, auth (the contract is silent on it), and exhaustive email
fuzzing; one invalid case proves the path.

## Abstractions and what they buy at scale

- **`MediaBuyerFactory`** keeps the valid payload in one place. A new contract
  field is one edit here, not one per test, and negative cases only state what
  they change. At 80 tests that is the difference between one edit and eighty.
- **`Api::seeResponseMatchesJsonSchema()`** puts response validation behind the
  actor with the schemas as the source of truth, so a contract change is a schema
  edit rather than a test rewrite.
- **The `.env`-driven base URL** lets the same suite run against local, staging,
  or production by swapping configuration.
- **Data providers and `smoke`/`regression` groups** keep boundary cases flat and
  readable, and keep fast feedback separate from the full run.

## Assumptions

- **Duplicate `mbId` (P11):** the contract allows 400 or 409, so the test asserts
  the 4xx class rather than a specific code.
- **Empty list (G3):** `{"data": []}`, so the schema permits an empty array.

## Further improvements

Once a real environment exists: wire the suite into CI (a static gate on PRs,
then `codecept run` against a deployed or ephemeral environment), add per-test
data setup and teardown, run in parallel, version the schemas, add a
consumer-driven contract test (Pact) for drift detection, and surface a reporting
layer.

## Running

There is no live server, so running is optional. Structure and static checks need
no backend:

```bash
composer install
cp .env.example .env
composer run build      # generate the actor
composer run check      # php -l + PHPStan + codecept --dry-run
```
