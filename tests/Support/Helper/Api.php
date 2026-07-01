<?php

declare(strict_types=1);

namespace Tests\Support\Helper;

use Codeception\Module;
use JsonSchema\Validator;

/**
 * Custom module extension point.
 *
 * Responsibilities:
 * - seeResponseMatchesJsonSchema(string $schemaFile): validate the current REST
 *   response body against a JSON Schema in tests/schemas/.
 */
class Api extends Module
{
    /**
     * Assert the last REST response body validates against the named JSON Schema.
     *
     * @param string $schemaFile File name inside tests/schemas/ (e.g. "get-media-buyers-schema.json").
     */
    public function seeResponseMatchesJsonSchema(string $schemaFile): void
    {
        $schemaPath = dirname(__DIR__, 2) . '/schemas/' . $schemaFile;
        $this->assertFileExists($schemaPath, "JSON Schema not found: {$schemaFile}");

        $rawResponse = $this->getModule('REST')->grabResponse();
        $data = json_decode($rawResponse);
        $this->assertSame(
            JSON_ERROR_NONE,
            json_last_error(),
            'Response body is not valid JSON: ' . json_last_error_msg()
        );

        $schema = json_decode((string) file_get_contents($schemaPath));

        $validator = new Validator();
        $validator->validate($data, $schema);

        if (!$validator->isValid()) {
            $messages = array_map(
                static fn (array $error): string => sprintf('[%s] %s', $error['property'], $error['message']),
                $validator->getErrors()
            );
            $this->fail(
                "Response does not match schema {$schemaFile}:\n - " . implode("\n - ", $messages)
            );
        }
    }
}
