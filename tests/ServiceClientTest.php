<?php

namespace Ekkinox\PocLtiClient\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use OAT\Library\Lti1p3Ags\Service\Score\Client\ScoreServiceClient;
use OAT\Library\Lti1p3Ags\Tests\Traits\AgsDomainTestingTrait;
use OAT\Library\Lti1p3Core\Service\Client\LtiServiceClient;
use OAT\Library\Lti1p3Core\Tests\Traits\DomainTestingTrait;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Throwable;

class ServiceClientTest extends TestCase
{
    use DomainTestingTrait;
    use AgsDomainTestingTrait;

    /** @var array */
    private $history;

    /** @var ScoreServiceClient */
    private $client;

    protected function setUp(): void
    {
        $this->history = [];

        $handlerStack = HandlerStack::create();

        $handlerStack->push(
            Middleware::mapRequest(function (RequestInterface $request) {
                return $request->withHeader('X-Foo', 'bar');
            })
        );
        $handlerStack->push(
            Middleware::history($this->history)
        );

        $guzzleClient = new Client(['handler' => $handlerStack]);
        $ltiServiceClient = new LtiServiceClient(null, $guzzleClient);
        $this->client = new ScoreServiceClient($ltiServiceClient);
    }

    public function testHeaders(): void
    {
        $registration = $this->createTestRegistration();
        $lineItem = $this->createTestLineItem();
        $score = $this->createTestScore();

        try {
            $this->client->publishScore($registration, $score, $lineItem->getUrl());
        } catch (Throwable $exception) {
            // noop
        }

        /** @var Request $sentRequest */
        $sentRequest = current($this->history)['request'];

        $this->assertEquals('bar', $sentRequest->getHeaderLine('X-Foo'));
    }
}
