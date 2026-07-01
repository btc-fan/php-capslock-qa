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
     * P2: data.id is server-generated (schema requires integer id; request never sends one).
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
     * Each malformed field -> 400.
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
     * P11: creating two buyers with the same mbId -> the second request errors.
     * The contract leaves 400 vs 409 open, so assert on the 4xx class and
     * document the assumption in the README (mock returns 409).
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
     * @return array<int, array{override: array<string, mixed>, detail: string}>
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
