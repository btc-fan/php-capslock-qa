<?php

declare(strict_types=1);

namespace Tests\Api;

use Codeception\Attribute\Group;
use Tests\ApiTester;
use Tests\Support\Factory\MediaBuyerFactory;

/**
 * GET /api/mediabuyers — the list endpoint (criteria G1–G7).
 */
final class GetMediaBuyersCest
{
    #[Group('smoke')]
    public function returnsListMatchingSchema(ApiTester $I): void
    {
        $I->sendGet('/api/mediabuyers');

        $I->seeResponseCodeIs(200);
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseMatchesJsonType(['data' => 'array']); // data is always an array (G3)
        $I->seeResponseMatchesJsonSchema('get-media-buyers-schema.json');
    }

    public function listedBuyerExposesAllFields(ApiTester $I): void
    {
        $buyer = MediaBuyerFactory::valid();
        $I->sendPost('/api/mediabuyers', $buyer);

        $I->sendGet('/api/mediabuyers');

        $I->seeResponseCodeIs(200);
        $I->seeResponseMatchesJsonSchema('get-media-buyers-schema.json');
        $I->seeResponseContainsJson(['data' => [['mbId' => $buyer['mbId'], 'active' => 1]]]);
    }

    public function listedEmailsAreValid(ApiTester $I): void
    {
        $I->sendPost('/api/mediabuyers', MediaBuyerFactory::valid());

        $I->sendGet('/api/mediabuyers');
        $I->seeResponseCodeIs(200);
        $I->seeResponseMatchesJsonSchema('get-media-buyers-schema.json');

        $emails = $I->grabDataFromResponseByJsonPath('$.data[*].email');
        foreach ($emails as $email) {
            $I->assertTrue(filter_var((string) $email, FILTER_VALIDATE_EMAIL) !== false, "Invalid email: {$email}");
        }
    }

    public function listedIdsAreUnique(ApiTester $I): void
    {
        $I->sendPost('/api/mediabuyers', MediaBuyerFactory::valid());
        $I->sendPost('/api/mediabuyers', MediaBuyerFactory::valid());

        $I->sendGet('/api/mediabuyers');
        $I->seeResponseCodeIs(200);
        $I->seeResponseMatchesJsonSchema('get-media-buyers-schema.json');

        $ids = $I->grabDataFromResponseByJsonPath('$.data[*].id');
        $I->assertSame($ids, array_values(array_unique($ids)), 'Ids are not unique');
    }
}
