<?php

use PHPUnit\Framework\TestCase;

/**
 * Live Cloudinary API tests must never rely on committed secrets.
 * Set CLOUDINARY_CLOUD_NAME, CLOUDINARY_API_KEY, and CLOUDINARY_API_SECRET in the environment
 * (e.g. export before running Pest, or CI secrets). phpunit.xml uses placeholders only.
 */
function assertCloudinaryLiveCredentialsOrSkip(TestCase $case): void
{
    $cloudName = env('CLOUDINARY_CLOUD_NAME');
    $apiKey = env('CLOUDINARY_API_KEY');
    $apiSecret = (string) env('CLOUDINARY_API_SECRET', '');

    $invalid = [null, '', 'cloudinary_cloud_name', 'cloudinary_api_key', 'cloudinary_api_secret'];

    if (
        in_array($cloudName, $invalid, true)
        || in_array($apiKey, $invalid, true)
        || in_array($apiSecret, $invalid, true)
    ) {
        $case->markTestSkipped(
            'Set CLOUDINARY_CLOUD_NAME, CLOUDINARY_API_KEY, and CLOUDINARY_API_SECRET in the environment to run integration tests against the live API.'
        );
    }

    if (strlen($apiSecret) < 20) {
        $case->markTestSkipped(
            'Integration requires a real CLOUDINARY_API_SECRET (typical length 27+). Short values from phpunit.xml placeholders are not used for live API calls.'
        );
    }
}
