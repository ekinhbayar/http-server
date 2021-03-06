<?php

namespace Amp\Http\Server;

use Amp\Promise;

interface HttpDriver {
    /**
     * HTTP methods that are *known*. Requests for methods not defined here or within Options should result in a 501
     * (not implemented) response.
     */
    const KNOWN_METHODS = ["GET", "HEAD", "POST", "PUT", "PATCH", "DELETE", "OPTIONS", "TRACE", "CONNECT"];

    /**
     * Data read from the client connection should be sent to the generator returned from this method. If the generator
     * yields a promise, no additional data is to be sent to the parser until the promise resolves. Each yield must be
     * prepared to receive additional client data, including those yielding promises.
     *
     * @param \Amp\Http\Server\Client $client The client associated with the data being sent to the returned generator.
     * @param callable $onMessage Invoked with an instance of Request when the returned parser has parsed a request.
     *    Returns a promise that is resolved once the response has been generated and written to the client.
     * @param callable $write Invoked with raw data to be written to the client connection. Returns a promise that is
     *     resolved when the data has been successfully written.
     *
     * @return \Generator Request parser.
     */
    public function setup(Client $client, callable $onMessage, callable $write): \Generator;

    /**
     * Returns a promise that resolves once the response has been written to the client. The writer should write the
     * given response to the client using the write callback provided to setup().
     *
     * @param \Amp\Http\Server\Response $response
     * @param \Amp\Http\Server\Request|null $request
     *
     * @return \Amp\Promise
     */
    public function writer(Response $response, Request $request = null): Promise;

    /**
     * @return int Number of requests that are being read by the parser.
     */
    public function pendingRequestCount(): int;
}
