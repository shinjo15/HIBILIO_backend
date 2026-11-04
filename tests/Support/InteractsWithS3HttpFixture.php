<?php

declare(strict_types=1);

namespace Tests\Support;

use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\RejectedPromise;
use Illuminate\Support\Facades\Storage;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

trait InteractsWithS3HttpFixture
{
    /** @var list<RequestInterface> */
    private array $s3Requests = [];

    /** @param callable(RequestInterface): ResponseInterface $response */
    private function useS3HttpFixture(callable $response): void
    {
        $configuration = [
            'driver' => 's3',
            'version' => 'latest',
            'region' => 'us-east-1',
            'bucket' => 'fixture-bucket',
            'credentials' => ['key' => 'fixture-not-real', 'secret' => 'fixture-not-real'],
            'endpoint' => 'https://s3.fixture.invalid',
            'use_path_style_endpoint' => true,
            'retries' => 0,
            'throw' => true,
            'http_handler' => function (RequestInterface $request) use ($response): PromiseInterface {
                $this->s3Requests[] = $request;
                $result = $response($request);

                if ($result->getStatusCode() >= 300) {
                    return new RejectedPromise([
                        'exception' => new RuntimeException('Fixture HTTP failure'),
                        'response' => $result,
                        'connection_error' => false,
                    ]);
                }

                return new FulfilledPromise($result);
            },
        ];

        Storage::set('s3', $this->app['filesystem']->createS3Driver($configuration));
    }
}
