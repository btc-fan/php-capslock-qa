<?php

declare(strict_types=1);

namespace Tests\Support\Helper;

use Codeception\Module;
use Codeception\Module\REST;
use JsonSchema\Validator;
use PHPUnit\Framework\Assert;

/**
 * Adds JSON Schema validation to the actor. The schema files under
 * tests/schemas/ are the source of truth for successful responses.
 */
class Api extends Module
{
    public function seeResponseMatchesJsonSchema(string $schemaFile): void
    {
        $schemaPath = dirname(__DIR__, 2) . '/schemas/' . $schemaFile;
        Assert::assertFileExists($schemaPath, "Schema not found: {$schemaFile}");

        /** @var REST $rest */
        $rest = $this->getModule('REST');
        $response = json_decode($rest->grabResponse());

        $schema = json_decode((string) file_get_contents($schemaPath));

        $validator = new Validator();
        $validator->validate($response, $schema);

        if (!$validator->isValid()) {
            $errors = array_map(
                static fn (array $error): string => "{$error['property']}: {$error['message']}",
                $validator->getErrors()
            );
            Assert::fail("Response does not match {$schemaFile}:\n- " . implode("\n- ", $errors));
        }
    }
}
