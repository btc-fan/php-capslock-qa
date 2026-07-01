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
     * G3: `data` is an array (enforced by the schema's "type": "array").
     *
     * @group smoke
     */
    public function listReturns200AndMatchesSchema(ApiTester $I): void
    {
        $I->sendGet('/api/mediabuyers');

        $I->seeResponseCodeIs(200);
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseIsJson();
        $I->seeResponseMatchesJsonSchema('get-media-buyers-schema.json');
    }

    /**
     * G4: every item exposes all required fields.
     * G5: email is syntactically valid.
     * G6: active is an integer 0/1 (schema enum), never boolean/string.
     * G7: id values are integers (uniqueness holds for a freshly seeded buyer).
     *
     * Seeds one buyer via POST so the list is non-empty and the item-level
     * criteria are actually exercised, then re-validates against the schema.
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
        $I->seeResponseContainsJson([
            'data' => [
                [
                    'mbId' => $buyer['mbId'],
                    'email' => $buyer['email'],
                    'active' => 1,
                ],
            ],
        ]);
    }
}
