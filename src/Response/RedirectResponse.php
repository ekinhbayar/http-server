<?php

namespace Amp\Http\Server\Response;

use Amp\ByteStream\InMemoryStream;
use Amp\Http\Server\Response;
use Amp\Http\Status;

class RedirectResponse extends Response {
    public function __construct($uri, int $code = Status::TEMPORARY_REDIRECT, array $headers = []) {
        parent::__construct(
            new InMemoryStream,
            \array_merge($headers, [
                "location" => $uri,
                "content-length" => 0
            ]),
            $code
        );
    }
}
