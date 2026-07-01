# API Contract — Media Buyers Resource

Base URL: `{{BASE_URL}}/api` (resolved from `.env` `BASE_URL`, never hard-coded).

Conventions: `Content-Type: application/json` + `Accept: application/json` on
every request. Success responses wrap in top-level `data`; validation errors
wrap in top-level `errors` array.

## MediaBuyer fields
| Field | Type | Description |
|---|---|---|
| id | integer | Server-generated primary key. Read-only. |
| mbId | string | Business identifier (numeric string). Must be unique. |
| initials | string | Two uppercase letters. |
| name | string | Full display name, 2–30 characters. |
| email | string | RFC-compliant email address. |
| slackUserId | string | Slack workspace user ID, 1–32 characters. |
| active | integer | 1 if active, 0 if inactive. |

## GET /api/mediabuyers
Returns the full list. No params, no body.

Acceptance criteria:
- **G1** HTTP 200, `Content-Type: application/json`.
- **G2** Body conforms to `get-media-buyers-schema.json`.
- **G3** `data` is always an array, even empty (`{"data": []}`, never 404/null).
- **G4** Every item includes all required fields.
- **G5** `email` values are syntactically valid.
- **G6** `active` is always integer 0 or 1 — never boolean, never string.
- **G7** `id` values are unique across the response.

## POST /api/mediabuyers
Creates one media buyer.

Request body validation rules:
| Field | Required | Rule |
|---|---|---|
| mbId | Yes | Non-empty digit string, positive integer. |
| initials | No | If present, exactly 2 characters. |
| name | Yes | 2–30 characters. |
| email | Yes | Strict RFC-compliant pattern. |
| slackUserId | No | If present, 1–32 characters. |
| active | Yes | Boolean (`true`/`false`). |

Success: 200, body conforms to `post-media-buyer-schema.json`.
Validation failure: 400, `errors` array of `{"detail": "..."}`.

Acceptance criteria:
- **P1** Valid request → 200, `application/json`, schema-valid body.
- **P2** `data.id` is server-generated; never supplied in the request.
- **P3** Returned `mbId`, `initials`, `name`, `email`, `slackUserId` match the request.
- **P4** `active: true` → `data.active === 1`; `active: false` → `data.active === 0`.
- **P5** Missing any required field (`mbId`, `name`, `email`, `active`) → 400, `errors` names each missing field.
- **P6** `email: "not-an-email"` → 400, error mentions the invalid email.
- **P7** `initials` > 2 characters → 400, "The initials must be exactly 2 characters long."
- **P8** `name` < 2 or > 30 characters → 400, length-related error.
- **P9** `mbId: "abc"` → 400.
- **P10** `active: "yes"` (non-boolean) → 400.
- **P11** Duplicate `mbId` on second creation → error (400 or 409 — open question, document the assumption).

## JSON Schemas
Canonical copies live in `tests/schemas/get-media-buyers-schema.json` and
`tests/schemas/post-media-buyer-schema.json`. This document is a reference
summary — the schema files are the enforced source of truth.
