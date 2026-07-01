<?php

declare(strict_types=1);

namespace Tests\Support\Factory;

use Faker\Factory as Faker;
use Faker\Generator;

/**
 * Builds MediaBuyer request payloads for POST /api/mediabuyers.
 * No hard-coded JSON permitted inside test methods — construct via this factory.
 */
final class MediaBuyerFactory
{
    private static ?Generator $faker = null;

    /**
     * A valid, schema-conformant payload. Every field can be overridden.
     *
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    public static function valid(array $overrides = []): array
    {
        $faker = self::faker();

        $base = [
            'mbId' => (string) $faker->numberBetween(1_000_000, 9_999_999),
            'initials' => strtoupper($faker->lexify('??')),
            'name' => substr($faker->firstName() . ' ' . $faker->lastName(), 0, 30),
            'email' => $faker->unique()->safeEmail(),
            'slackUserId' => 'U' . strtoupper($faker->bothify('#######')),
            'active' => true,
        ];

        return array_merge($base, $overrides);
    }

    /**
     * A valid payload with one field removed — for missing-required-field cases.
     *
     * @return array<string, mixed>
     */
    public static function missing(string $field): array
    {
        $payload = self::valid();
        unset($payload[$field]);

        return $payload;
    }

    private static function faker(): Generator
    {
        if (self::$faker === null) {
            self::$faker = Faker::create();
        }

        return self::$faker;
    }
}
