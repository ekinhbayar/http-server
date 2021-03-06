<?php

namespace Amp\Http\Server\Middleware;

use Amp\ByteStream\InMemoryStream;
use Amp\ByteStream\IteratorStream;
use Amp\Coroutine;
use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\Responder;
use Amp\Producer;
use Amp\Promise;
use cash\LRUCache;

class ZlibMiddleware implements Middleware {
    const MAX_CACHE_SIZE = 1024;

    /** @link http://webmasters.stackexchange.com/questions/31750/what-is-recommended-minimum-object-size-for-deflate-performance-benefits */
    const DEFAULT_MINIMUM_LENGTH = 860;
    const DEFAULT_CHUNK_SIZE = 8192;
    const DEFAULT_CONTENT_TYPE_REGEX = '#^(?:text/.*+|[^/]*+/xml|[^+]*\+xml|application/(?:json|(?:x-)?javascript))$#i';

    /** @var int Minimum body length before body is compressed. */
    private $minimumLength;

    /** @var string */
    private $contentRegex;

    /** @var int Minimum chunk size before being compressed. */
    private $chunkSize;

    /** @var LRUCache */
    private $contentTypeCache;

    public function __construct(
        int $minimumLength = self::DEFAULT_MINIMUM_LENGTH,
        int $chunkSize = self::DEFAULT_CHUNK_SIZE,
        string $contentRegex = self::DEFAULT_CONTENT_TYPE_REGEX
    ) {
        if ($minimumLength < 1) {
            throw new \Error("The minimum length must be positive");
        }

        if ($chunkSize < 1) {
            throw new \Error("The chunk size must be positive");
        }

        $this->contentTypeCache = new LRUCache(self::MAX_CACHE_SIZE);

        $this->minimumLength = $minimumLength;
        $this->chunkSize = $chunkSize;
        $this->contentRegex = $contentRegex;
    }

    public function process(Request $request, Responder $responder): Promise {
        return new Coroutine($this->deflate($request, $responder));
    }

    public function deflate(Request $request, Responder $responder): \Generator {
        /** @var \Amp\Http\Server\Response $response */
        $response = yield $responder->respond($request);

        $headers = $response->getHeaders();

        if (isset($headers["content-encoding"])) {
            return $response; // Another responder or middleware has already encoded the response.
        }

        $contentLength = $headers["content-length"][0] ?? null;

        if ($contentLength !== null) {
            if ($contentLength < $this->minimumLength) {
                return $response; // Content-Length too small, no need to compress.
            }
        }

        // We can't deflate if we don't know the content-type
        if (empty($headers["content-type"])) {
            return $response;
        }

        $contentType = $headers["content-type"][0];

        // @TODO Perform a more sophisticated check for gzip acceptance.
        // This check isn't technically correct as the gzip parameter
        // could have a q-value of zero indicating "never accept gzip."
        do {
            foreach ($request->getHeaderArray("accept-encoding") as $value) {
                if (\preg_match('/gzip|deflate/i', $value, $matches)) {
                    $encoding = \strtolower($matches[0]);
                    break 2;
                }
            }
            return $response;
        } while (false);

        $doDeflate = $this->contentTypeCache->get($contentType);

        if ($doDeflate === null) {
            $doDeflate = \preg_match($this->contentRegex, \trim(\strstr($contentType, ";", true) ?: $contentType));
            $this->contentTypeCache->put($contentType, $doDeflate);
        }

        if ($doDeflate === 0) {
            return $response;
        }

        $body = $response->getBody();
        $bodyBuffer = '';

        if ($contentLength === null) {
            do {
                $bodyBuffer .= $chunk = yield $body->read();

                if (isset($bodyBuffer[$this->minimumLength])) {
                    break;
                }

                if ($chunk === null) {
                    // Body is not large enough to compress.
                    $response->setHeader("content-length", \strlen($bodyBuffer));
                    $response->setBody(new InMemoryStream($bodyBuffer));
                    return $response;
                }
            } while (true);
        }

        switch ($encoding) {
            case "deflate":
                $mode = \ZLIB_ENCODING_RAW;
                break;

            case "gzip":
                $mode = \ZLIB_ENCODING_GZIP;
                break;

            default:
                throw new \RuntimeException("Invalid encoding type");
        }

        if (($resource = \deflate_init($mode)) === false) {
            throw new \RuntimeException(
                "Failed initializing deflate context"
            );
        }

        // Once we decide to compress output we no longer know what the
        // final Content-Length will be. We need to update our headers
        // according to the HTTP protocol in use to reflect this.
        $response->removeHeader("content-length");
        if ($request->getProtocolVersion() === "1.0") { // Cannot chunk 1.0 responses.
            $response->setHeader("connection", "close");
        }
        $response->setHeader("content-encoding", $encoding);
        $response->addHeader("vary", "accept-encoding");

        $iterator = new Producer(function (callable $emit) use ($resource, $body, $bodyBuffer) {
            do {
                if (isset($bodyBuffer[$this->chunkSize - 1])) {
                    if (false === $bodyBuffer = \deflate_add($resource, $bodyBuffer, \ZLIB_SYNC_FLUSH)) {
                        throw new \RuntimeException("Failed adding data to deflate context");
                    }

                    yield $emit($bodyBuffer);
                    $bodyBuffer = '';
                }

                $bodyBuffer .= $chunk = yield $body->read();
            } while ($chunk !== null);

            if (false === $bodyBuffer = \deflate_add($resource, $bodyBuffer, \ZLIB_FINISH)) {
                throw new \RuntimeException("Failed adding data to deflate context");
            }

            $emit($bodyBuffer);
        });

        $response->setBody(new IteratorStream($iterator));

        return $response;
    }
}
