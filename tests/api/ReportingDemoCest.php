<?php

declare(strict_types=1);

namespace Tests\Api;

use Tests\ApiTester;
use Tests\Support\Factory\MediaBuyerFactory;

/**
 * Demo suite for the CI reporting pipeline: three passing tests and one
 * INTENTIONALLY FAILING test, so the CTRF report / PR comment shows a red
 * result. Delete once the reporting flow has been verified.
 */
final class ReportingDemoCest
{
    /**
     * @group smoke
     */
    public function getListIsJsonArray(ApiTester $I): void
    {
        $I->sendGet('/api/mediabuyers');

        $I->seeResponseCodeIs(200);
        $I->seeResponseMatchesJsonSchema('get-media-buyers-schema.json');
    }

    /**
     * @group regression
     */
    public function getSendsJsonContentType(ApiTester $I): void
    {
        $I->sendGet('/api/mediabuyers');

        $I->seeHttpHeader('Content-Type', 'application/json');
    }

    /**
     * @group regression
     */
    public function createValidBuyerEchoesMbId(ApiTester $I): void
    {
        $buyer = MediaBuyerFactory::valid();

        $I->sendPost('/api/mediabuyers', $buyer);

        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['data' => ['mbId' => $buyer['mbId']]]);
    }

    /**
     * INTENTIONAL FAILURE — a valid POST returns 200, but this asserts 400.
     * Exists only to prove the failure path surfaces in the CTRF PR report.
     *
     * @group regression
     */
    public function intentionalFailureToDemoReporting(ApiTester $I): void
    {
        $I->sendPost('/api/mediabuyers', MediaBuyerFactory::valid());

        $I->seeResponseCodeIs(400);
    }
}
