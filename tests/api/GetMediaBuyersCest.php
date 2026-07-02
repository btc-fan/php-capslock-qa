<?php

declare(strict_types=1);

namespace Tests\Api;

use Tests\ApiTester;
use Tests\Support\Factory\MediaBuyerFactory;

/**
 * GET /api/mediabuyers — list contract (acceptance criteria G1–G7).
 */
final class GetMediaBuyersCest
{
    /**
     * G1: HTTP 200 + Content-Type application/json.
     * G2: body conforms to the list schema.
     * G3: `data` is an array (schema "type": "array"; holds for an empty list).
     *
     * @group smoke
     */
    public function listReturns200AndMatchesSchema(ApiTester $I): void
    {
        $I->sendGet('/api/mediabuyers');

        $I->seeResponseCodeIs(200);
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseIsJson();
        $I->seeResponseMatchesJsonType(['data' => 'array']); // G3: data is an array, never null/404
        $I->seeResponseMatchesJsonSchema('get-media-buyers-schema.json');
    }

    /**
     * G4: every item exposes all required fields.
     * G6: active is an integer 0/1 (schema enum), never boolean/string.
     * Seeds a buyer so the list is non-empty, then re-validates the schema.
     *
     * @group regression
     */
    public function listItemsExposeAllRequiredFieldsWithValidTypes(ApiTester $I): void
    {
        $buyer = MediaBuyerFactory::valid();
        $I->sendPost('/api/mediabuyers', $buyer);
        $I->seeResponseCodeIs(200);

        $I->sendGet('/api/mediabuyers');

        $I->seeResponseCodeIs(200);
        $I->seeResponseMatchesJsonSchema('get-media-buyers-schema.json');
        $I->seeResponseContainsJson(['data' => [['mbId' => $buyer['mbId'], 'active' => 1]]]);
    }

    /**
     * G5: every email in the list is a syntactically valid address.
     * (The schema declares "format": "email", but format assertions are not
     * enforced by the validator by default, so this is checked explicitly.)
     *
     * @group regression
     */
    public function listEmailsAreSyntacticallyValid(ApiTester $I): void
    {
        $I->sendPost('/api/mediabuyers', MediaBuyerFactory::valid());
        $I->seeResponseCodeIs(200);

        $I->sendGet('/api/mediabuyers');
        $I->seeResponseCodeIs(200);

        /** @var array<int, mixed> $emails */
        $emails = $I->grabDataFromResponseByJsonPath('$.data[*].email');
        $I->assertNotEmpty($emails);
        foreach ($emails as $email) {
            $I->assertNotFalse(
                filter_var((string) $email, FILTER_VALIDATE_EMAIL),
                "Invalid email in list: {$email}"
            );
        }
    }

    /**
     * G7: id values are unique across the response.
     *
     * @group regression
     */
    public function listIdsAreUnique(ApiTester $I): void
    {
        $I->sendPost('/api/mediabuyers', MediaBuyerFactory::valid());
        $I->seeResponseCodeIs(200);
        $I->sendPost('/api/mediabuyers', MediaBuyerFactory::valid());
        $I->seeResponseCodeIs(200);

        $I->sendGet('/api/mediabuyers');
        $I->seeResponseCodeIs(200);

        /** @var array<int, mixed> $ids */
        $ids = $I->grabDataFromResponseByJsonPath('$.data[*].id');
        $I->assertNotEmpty($ids);
        $I->assertCount(count(array_unique($ids)), $ids, 'Duplicate id values in list');
    }
}
