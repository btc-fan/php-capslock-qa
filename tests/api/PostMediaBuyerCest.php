<?php

declare(strict_types=1);

namespace Tests\Api;

use Codeception\Example;
use Tests\ApiTester;
use Tests\Support\Factory\MediaBuyerFactory;

/**
 * POST /api/mediabuyers — create contract (acceptance criteria P1–P11).
 */
final class PostMediaBuyerCest
{
    /**
     * P1: valid request -> 200 + schema-valid body.
     * P3: returned mbId/initials/name/email/slackUserId echo the request.
     * P4: active:true -> data.active === 1.
     *
     * @group smoke
     */
    public function createValidMediaBuyer(ApiTester $I): void
    {
        $buyer = MediaBuyerFactory::valid();

        $I->sendPost('/api/mediabuyers', $buyer);

        $I->seeResponseCodeIs(200);
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseMatchesJsonSchema('post-media-buyer-schema.json');
        $I->seeResponseContainsJson([
            'data' => [
                'mbId' => $buyer['mbId'],
                'initials' => $buyer['initials'],
                'name' => $buyer['name'],
                'email' => $buyer['email'],
                'slackUserId' => $buyer['slackUserId'],
                'active' => 1,
            ],
        ]);
    }

    /**
     * P2: data.id is a server-generated positive integer; the request omits id.
     *
     * @group regression
     */
    public function serverGeneratesPositiveIntegerId(ApiTester $I): void
    {
        $payload = MediaBuyerFactory::valid();
        $I->assertArrayNotHasKey('id', $payload, 'Request must never supply id');

        $I->sendPost('/api/mediabuyers', $payload);

        $I->seeResponseCodeIs(200);
        // JSONPath always returns a list of matches; a single field yields one element.
        /** @var array<int, mixed> $idMatches */
        $idMatches = $I->grabDataFromResponseByJsonPath('$.data.id');
        $id = $idMatches[0] ?? null;
        $I->assertIsInt($id);
        $I->assertGreaterThan(0, $id);
    }

    /**
     * P4: active:false -> data.active === 0.
     *
     * @group regression
     */
    public function createInactiveMediaBuyerMapsActiveToZero(ApiTester $I): void
    {
        $I->sendPost('/api/mediabuyers', MediaBuyerFactory::valid(['active' => false]));

        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['data' => ['active' => 0]]);
    }

    /**
     * P5: omitting any required field -> 400 + an error naming that field.
     *
     * @dataProvider requiredFields
     * @group regression
     */
    public function missingRequiredFieldReturns400(ApiTester $I, Example $example): void
    {
        $field = (string) $example['field'];

        $I->sendPost('/api/mediabuyers', MediaBuyerFactory::missing($field));

        $I->seeResponseCodeIs(400);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['errors' => [['detail' => "This field is missing: [{$field}]"]]]);
    }

    /**
     * P6: an invalid email -> 400 with an error that mentions the email.
     *
     * @group regression
     */
    public function invalidEmailReturns400(ApiTester $I): void
    {
        $I->sendPost('/api/mediabuyers', MediaBuyerFactory::valid(['email' => 'not-an-email']));

        $I->seeResponseCodeIs(400);
        $I->seeResponseContainsJson(['errors' => [['detail' => 'The email not-an-email is not a valid email.']]]);
    }

    /**
     * P7: initials longer than 2 chars.
     * P8: name shorter than 2 / longer than 30 chars.
     * P9: mbId that is not a positive integer string.
     * P10: active that is not a boolean.
     * Each malformed field -> 400 with the matching message.
     *
     * @dataProvider invalidFieldValues
     * @group regression
     */
    public function invalidFieldValueReturns400(ApiTester $I, Example $example): void
    {
        /** @var array<string, mixed> $override */
        $override = $example['override'];

        $I->sendPost('/api/mediabuyers', MediaBuyerFactory::valid($override));

        $I->seeResponseCodeIs(400);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['errors' => [['detail' => (string) $example['detail']]]]);
    }

    /**
     * P11: two buyers with the same mbId -> the second request errors.
     * Contract leaves 400 vs 409 open, so assert the 4xx class (mock returns 409;
     * assumption documented in the README).
     *
     * @group regression
     */
    public function duplicateMbIdReturnsClientError(ApiTester $I): void
    {
        $buyer = MediaBuyerFactory::valid();

        $I->sendPost('/api/mediabuyers', $buyer);
        $I->seeResponseCodeIs(200);

        $I->sendPost('/api/mediabuyers', $buyer);
        $I->seeResponseCodeIsClientError();
    }

    /**
     * Required fields per the POST contract (P5).
     *
     * @return array<int, array{field: string}>
     */
    protected function requiredFields(): array
    {
        return [
            ['field' => 'mbId'],
            ['field' => 'name'],
            ['field' => 'email'],
            ['field' => 'active'],
        ];
    }

    /**
     * Malformed single-field payloads and the error each should surface (P7–P10).
     *
     * @return array<string, array{override: array<string, mixed>, detail: string}>
     */
    protected function invalidFieldValues(): array
    {
        return [
            'initials too long' => [
                'override' => ['initials' => 'TOO LONG'],
                'detail' => 'The initials must be exactly 2 characters long.',
            ],
            'name too short' => [
                'override' => ['name' => 'A'],
                'detail' => 'The name must be between 2 and 30 characters.',
            ],
            'name too long' => [
                'override' => ['name' => str_repeat('a', 31)],
                'detail' => 'The name must be between 2 and 30 characters.',
            ],
            'mbId not numeric' => [
                'override' => ['mbId' => 'abc'],
                'detail' => 'The mbId abc is not a positive integer string.',
            ],
            'active not boolean' => [
                'override' => ['active' => 'yes'],
                'detail' => 'The active field must be a boolean.',
            ],
        ];
    }
}
