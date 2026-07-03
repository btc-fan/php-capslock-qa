<?php

declare(strict_types=1);

namespace Tests\Support\Factory;

use Faker\Factory as Faker;
use Faker\Generator;

/**
 * Builds MediaBuyer request payloads. Tests never write JSON inline — they
 * start from valid() and override only the field under test.
 */
final class MediaBuyerFactory
{
    private static ?Generator $faker = null;

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    public static function valid(array $overrides = []): array
    {
        $faker = self::faker();

        return array_merge([
            'mbId' => (string) $faker->numberBetween(1_000_000, 9_999_999),
            'initials' => strtoupper($faker->lexify('??')),
            'name' => substr($faker->firstName() . ' ' . $faker->lastName(), 0, 30),
            'email' => $faker->unique()->safeEmail(),
            'slackUserId' => 'U' . strtoupper($faker->bothify('#######')),
            'active' => true,
        ], $overrides);
    }

    /**
     * A valid payload with one field removed.
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
        return self::$faker ??= Faker::create();
    }
}
