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
 * Request an ID token and verify its signature using the keys provided by the Issuer.
 *
 * Identify the invalid signature and reject the ID Token after doing ID Token validation.
 */
class RpIdTokenBadSigES256Test extends AbstractRpTest
{
    public function getTestId(): string
    {
        return 'rp-id_token-bad-sig-es256';
    }

    public function execute(TestInfo $testInfo): void
    {
        $client = $this->registerClient($testInfo, ['id_token_signed_response_alg' => 'ES256']);

        Assert::assertSame('ES256', $client->getMetadata()->get('id_token_signed_response_alg'));

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
            throw new AssertionFailedError('No assertions');
        } catch (Throwable $e) {
            Assert::assertSame('Invalid token provided', $e->getMessage());
            Assert::assertRegExp('/Invalid signature/', $e->getPrevious()->getMessage());
        }
    }
}
