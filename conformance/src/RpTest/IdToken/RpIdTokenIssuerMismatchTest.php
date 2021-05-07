<?php

declare(strict_types=1);

namespace Facile\OpenIDClient\ConformanceTest\RpTest\IdToken;

use function Facile\OpenIDClient\base64url_encode;
use Facile\OpenIDClient\ConformanceTest\RpTest\AbstractRpTest;
use Facile\OpenIDClient\ConformanceTest\TestInfo;
use Facile\OpenIDClient\Service\AuthorizationService;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\AssertionFailedError;
use function random_bytes;
use Throwable;

/**
 * Request an ID token and verify its 'iss' value.
 *
 * Identify the incorrect 'iss' value and reject the ID Token after doing ID Token validation.
 */
class RpIdTokenIssuerMismatchTest extends AbstractRpTest
{
    public function getTestId(): string
    {
        return 'rp-id_token-issuer-mismatch';
    }

    public function execute(TestInfo $testInfo): void
    {
        $client = $this->registerClient($testInfo);

        // Get authorization redirect uri
        $authorizationService = new AuthorizationService();
        $uri = $authorizationService->getAuthorizationUri($client, [
            'response_type' => $testInfo->getResponseType(),
            'nonce' => base64url_encode(random_bytes(32)),
        ]);

        // Simulate a redirect and create the server request
        $serverRequest = $this->simulateAuthRedirect($uri);

        $params = $authorizationService->getCallbackParams($serverRequest, $client);

        try {
            $authorizationService->callback($client, $params);
            throw new AssertionFailedError('No assertion');
        } catch (Throwable $e) {
            Assert::assertSame('Invalid token provided', $e->getMessage());
            Assert::assertRegExp('/Unknown issuer/', $e->getPrevious()->getMessage());
        }
    }
}
