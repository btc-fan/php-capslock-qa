<?php

declare(strict_types=1);

namespace Tests\Api;

use Codeception\Attribute\DataProvider;
use Codeception\Attribute\Group;
use Codeception\Example;
use Tests\ApiTester;
use Tests\Support\Factory\MediaBuyerFactory;

/**
 * POST /api/mediabuyers — the create endpoint (criteria P1–P11).
 */
final class PostMediaBuyerCest
{
    #[Group('smoke')]
    public function createsBuyerMatchingSchema(ApiTester $I): void
    {
        $buyer = MediaBuyerFactory::valid();

        $I->sendPost('/api/mediabuyers', $buyer);

        $I->seeResponseCodeIs(200);
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseMatchesJsonSchema('post-media-buyer-schema.json');
        // The created buyer echoes the request (P3); id is added by the server (P2).
        $I->seeResponseContainsJson(['data' => [
            'mbId' => $buyer['mbId'],
            'initials' => $buyer['initials'],
            'name' => $buyer['name'],
            'email' => $buyer['email'],
            'slackUserId' => $buyer['slackUserId'],
        ]]);
    }

    /**
     * P4: the boolean active flag is stored as an integer.
     */
    #[DataProvider('activeFlags')]
    public function mapsActiveFlagToInteger(ApiTester $I, Example $example): void
    {
        $I->sendPost('/api/mediabuyers', MediaBuyerFactory::valid(['active' => $example['send']]));

        $I->seeResponseCodeIs(200);
        $I->seeResponseMatchesJsonSchema('post-media-buyer-schema.json');
        $I->seeResponseContainsJson(['data' => ['active' => $example['expect']]]);
    }

    /**
     * P5: a missing required field is rejected and named in the error.
     */
    #[DataProvider('requiredFields')]
    public function rejectsMissingRequiredField(ApiTester $I, Example $example): void
    {
        $field = (string) $example['field'];

        $I->sendPost('/api/mediabuyers', MediaBuyerFactory::missing($field));

        $I->seeResponseCodeIs(400);
        $I->seeResponseContainsJson(['errors' => [['detail' => "This field is missing: [{$field}]"]]]);
    }

    /**
     * P6: an address that is not a valid email is rejected.
     */
    public function rejectsInvalidEmail(ApiTester $I): void
    {
        $I->sendPost('/api/mediabuyers', MediaBuyerFactory::valid(['email' => 'not-an-email']));

        $I->seeResponseCodeIs(400);
        $I->seeResponseContainsJson(['errors' => [['detail' => 'The email not-an-email is not a valid email.']]]);
    }

    /**
     * P7–P10: field values that break a validation rule are rejected.
     */
    #[DataProvider('invalidFields')]
    public function rejectsInvalidFieldValue(ApiTester $I, Example $example): void
    {
        /** @var array<string, mixed> $override */
        $override = $example['override'];

        $I->sendPost('/api/mediabuyers', MediaBuyerFactory::valid($override));

        $I->seeResponseCodeIs(400);
        $I->seeResponseContainsJson(['errors' => [['detail' => (string) $example['detail']]]]);
    }

    /**
     * P11: mbId must be unique; the second create with the same id fails.
     * The contract leaves 400 vs 409 open, so we assert the 4xx class.
     */
    public function rejectsDuplicateMbId(ApiTester $I): void
    {
        $buyer = MediaBuyerFactory::valid();

        $I->sendPost('/api/mediabuyers', $buyer);
        $I->seeResponseCodeIs(200);

        $I->sendPost('/api/mediabuyers', $buyer);
        $I->seeResponseCodeIsClientError();
    }

    /**
     * @return array<int, array{send: bool, expect: int}>
     */
    protected function activeFlags(): array
    {
        return [
            ['send' => true, 'expect' => 1],
            ['send' => false, 'expect' => 0],
        ];
    }

    /**
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
     * @return array<string, array{override: array<string, mixed>, detail: string}>
     */
    protected function invalidFields(): array
    {
        return [
            'initials longer than 2 chars' => [
                'override' => ['initials' => 'TOO LONG'],
                'detail' => 'The initials must be exactly 2 characters long.',
            ],
            'name shorter than 2 chars' => [
                'override' => ['name' => 'A'],
                'detail' => 'The name must be between 2 and 30 characters.',
            ],
            'name longer than 30 chars' => [
                'override' => ['name' => str_repeat('a', 31)],
                'detail' => 'The name must be between 2 and 30 characters.',
            ],
            'mbId not a positive integer' => [
                'override' => ['mbId' => 'abc'],
                'detail' => 'The mbId abc is not a positive integer string.',
            ],
            'active not a boolean' => [
                'override' => ['active' => 'yes'],
                'detail' => 'The active field must be a boolean.',
            ],
        ];
    }
}
