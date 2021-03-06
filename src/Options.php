<?php

namespace Amp\Http\Server;

final class Options {
    private $debug = false;
    private $maxConnections = 10000;
    private $connectionsPerIP = 30; // IPv4: /32, IPv6: /56 (per RFC 6177)
    private $connectionTimeout = 15; // seconds

    private $socketBacklogSize = 128;
    private $maxConcurrentStreams = 20;
    private $maxFramesPerSecond = 60;
    private $minAverageFrameSize = 1024;
    private $allowedMethods = ["GET", "POST", "PUT", "PATCH", "HEAD", "OPTIONS", "DELETE"];

    private $maxBodySize = 131072;
    private $maxHeaderSize = 32768;
    private $ioGranularity = 8192;

    private $inputBufferSize = 8192;
    private $outputBufferSize = 8192;
    private $shutdownTimeout = 3000; // milliseconds

    private $allowHttp2Upgrade = false;

    /**
     * @return bool `true` if server is in debug mode, `false` if in production mode.
     */
    public function isInDebugMode(): bool {
        return $this->debug;
    }

    /**
     * Sets debug mode to `true`.
     *
     * @return \Amp\Http\Server\Options
     */
    public function withDebugMode(): self {
        $new = clone $this;
        $new->debug = true;

        return $new;
    }

    /**
     * Sets debug mode to `false`.
     *
     * @return \Amp\Http\Server\Options
     */
    public function withoutDebugMode(): self {
        $new = clone $this;
        $new->debug = false;

        return $new;
    }

    /**
     * @return int The maximum number of connections that can be handled by the server at a single time.
     */
    public function getMaxConnections(): int {
        return $this->maxConnections;
    }

    /**
     * @param int $count Maximum number of connections the server should accept at one time. Default is 10000.
     *
     * @return \Amp\Http\Server\Options
     *
     * @throws \Error If count is less than 1.
     */
    public function withMaxConnections(int $count): self {
        if ($count < 1) {
            throw new \Error(
                "Max connections setting must be greater than or equal to one"
            );
        }

        $new = clone $this;
        $new->maxConnections = $count;

        return $new;
    }

    /**
     * @return int The maximum number of connections allowed from a single IP.
     */
    public function getMaxConnectionsPerIp(): int {
        return $this->connectionsPerIP;
    }

    /**
     * @param int $count Maximum number of connections to allow from a single IP address. Default is 30.
     *
     * @throws \Error If the count is less than 1.
     */
    public function withMaxConnectionsPerIp(int $count): self {
        if ($count < 1) {
            throw new \Error(
                "Connections per IP maximum must be greater than or equal to one"
            );
        }

        $new = clone $this;
        $new->connectionsPerIP = $count;

        return $new;
    }

    /**
     * @return int Number of seconds a connection may be idle before it is automatically closed.
     */
    public function getConnectionTimeout(): int {
        return $this->connectionTimeout;
    }

    /**
     * @param int $seconds Number of seconds a connection may be idle before it is automatically closed. Default is 15.
     *
     * @return \Amp\Http\Server\Options
     *
     * @throws \Error If the number of seconds is less than 1.
     */
    public function withConnectionTimeout(int $seconds): self {
        if ($seconds < 1) {
            throw new \Error(
                "Keep alive timeout setting must be greater than or equal to one second"
            );
        }

        $new = clone $this;
        $new->connectionTimeout = $seconds;

        return $new;
    }

    /**
     * @return int Maximum backlog size of each listening server socket.
     */
    public function getSocketBacklogSize(): int {
        return $this->socketBacklogSize;
    }

    /**
     * @param int $backlog Maximum backlog size of each listening server socket. Default is 128.
     *
     * @return \Amp\Http\Server\Options
     *
     * @throws \Error If the backlog size is less than 16.
     */
    public function withSocketBacklogSize(int $backlog): self {
        if ($backlog < 16) {
            throw new \Error(
                "Socket backlog size setting must be greater than or equal to 16"
            );
        }

        $new = clone $this;
        $new->socketBacklogSize = $backlog;

        return $new;
    }

    /**
     * @return int Maximum request body size in bytes.
     */
    public function getMaxBodySize(): int {
        return $this->maxBodySize;
    }

    /**
     * @param int $bytes Default maximum request body size in bytes. Individual requests may be increased by calling
     *     Request::getBody($newMaximum). Default is 131072 (128k).
     *
     * @return \Amp\Http\Server\Options
     *
     * @throws \Error If the number of bytes is less than 0.
     */
    public function withMaxBodySize(int $bytes): self {
        if ($bytes < 0) {
            throw new \Error(
                "Max body size setting must be greater than or equal to zero"
            );
        }

        $new = clone $this;
        $new->maxBodySize = $bytes;

        return $new;
    }

    /**
     * @return int Maximum size of the request header section in bytes.
     */
    public function getMaxHeaderSize(): int {
        return $this->maxHeaderSize;
    }

    /**
     * @param int $bytes Maximum size of the request header section in bytes. Default is 32768 (32k).
     *
     * @return \Amp\Http\Server\Options
     * @throws \Error
     */
    public function withMaxHeaderSize(int $bytes): self {
        if ($bytes <= 0) {
            throw new \Error(
                "Max header size setting must be greater than zero"
            );
        }

        $new = clone $this;
        $new->maxHeaderSize = $bytes;

        return $new;
    }

    /**
     * @return int Maximum number of concurrent HTTP/2 streams.
     */
    public function getMaxConcurrentStreams(): int {
        return $this->maxConcurrentStreams;
    }

    /**
     * @param int $streams Maximum number of concurrent HTTP/2 streams. Default is 20.
     *
     * @return \Amp\Http\Server\Options
     *
     * @throws \Error If the stream count is less than 1.
     */
    public function withMaxConcurrentStreams(int $streams): self {
        if ($streams < 1) {
            throw new \Error(
                "Max number of concurrent streams setting must be greater than zero"
            );
        }

        $new = clone $this;
        $new->maxConcurrentStreams = $streams;

        return $new;
    }

    /**
     * @return int Minimum average frame size required if more than the maximum number of frames per second are
     *     received on an HTTP/2 connection.
     */
    public function getMinAverageFrameSize(): int {
        return $this->minAverageFrameSize;
    }

    /**
     * @param int $size Minimum average frame size required if more than the maximum number of frames per second are
     *     received on an HTTP/2 connection. Default is 1024 (1k).
     *
     * @return \Amp\Http\Server\Options
     *
     * @throws \Error If the size is less than 1.
     */
    public function withMinAverageFrameSize(int $size): self {
        if ($size < 1) {
            throw new \Error(
                "Minimum average frame size must be greater than zero"
            );
        }

        $new = clone $this;
        $new->minAverageFrameSize = $size;

        return $new;
    }

    /**
     * @return int Maximum number of HTTP/2 frames per second before the average length minimum is enforced.
     */
    public function getMaxFramesPerSecond(): int {
        return $this->maxFramesPerSecond;
    }

    /**
     * @param int $frames Maximum number of HTTP/2 frames per second before the average length minimum is enforced.
     *     Default is 60.
     *
     * @return \Amp\Http\Server\Options
     *
     * @throws \Error If the frame count is less than 1.
     */
    public function withMaxFramesPerSecond(int $frames): self {
        if ($frames < 1) {
            throw new \Error(
                "Max number of HTTP/2 frames per second setting must be greater than zero"
            );
        }

        $new = clone $this;
        $new->maxFramesPerSecond = $frames;

        return $new;
    }

    /**
     * @return int The maximum number of bytes to read from a client per read.
     */
    public function getIoGranularity(): int {
        return $this->ioGranularity;
    }

    /**
     * @param int $bytes The maximum number of bytes to read from a client per read. Larger numbers are better for
     *     performance but can increase memory usage. Default is 8192 (8k).
     *
     * @return \Amp\Http\Server\Options
     *
     * @throws \Error If the number of bytes is less than 1.
     */
    public function withIoGranularity(int $bytes): self {
        if ($bytes < 1) {
            throw new \Error(
                "IO granularity setting must be greater than zero"
            );
        }

        $new = clone $this;
        $new->ioGranularity = $bytes;

        return $new;
    }

    /**
     * @return string[] An array of allowed request methods.
     */
    public function getAllowedMethods(): array {
        return $this->allowedMethods;
    }

    /**
     * @param string[] $allowedMethods An array of allowed request methods. Default is GET, POST, PUT, PATCH, HEAD,
     *     OPTIONS, DELETE.
     *
     * @return \Amp\Http\Server\Options
     *
     * @throws \Error If the array contains non-strings, empty method names, or does not contain GET or HEAD.
     */
    public function withAllowedMethods(array $allowedMethods): self {
        foreach ($allowedMethods as $key => $method) {
            if (!\is_string($method)) {
                throw new \Error(
                    \sprintf(
                        "Invalid type at key %s of allowed methods array: %s",
                        $key,
                        \is_object($method) ? \get_class($method) : \gettype($method)
                    )
                );
            }

            if ($method === "") {
                throw new \Error(
                    "Invalid empty HTTP method at key {$key} of allowed methods array"
                );
            }
        }

        $allowedMethods = \array_unique($allowedMethods);

        if (!\in_array("GET", $allowedMethods, true)) {
            throw new \Error(
                "Servers must support GET as an allowed HTTP method"
            );
        }

        if (!\in_array("HEAD", $allowedMethods, true)) {
            throw new \Error(
                "Servers must support HEAD as an allowed HTTP method"
            );
        }

        $new = clone $this;
        $new->allowedMethods = $allowedMethods;

        return $new;
    }

    /**
     * @return int The minimum number of bytes to buffer in incoming bodies before emitting chunks to the responder.
     */
    public function getInputBufferSize(): int {
        return $this->inputBufferSize;
    }

    /**
     * @param int $bytes The minimum number of bytes to buffer in incoming bodies before emitting chunks to the
     *     responder. Default is 8192 (8k).
     *
     * @return \Amp\Http\Server\Options
     *
     * @throws \Error If the number of bytes is less than 1.
     */
    public function withInputBufferSize(int $bytes): self {
        if ($bytes < 1) {
            throw new \Error(
                "Input buffer size must be greater than zero bytes"
            );
        }

        $new = clone $this;
        $new->inputBufferSize = $bytes;

        return $new;
    }

    /**
     * @return int Number of body bytes to buffer before writes are made to the client.
     */
    public function getOutputBufferSize(): int {
        return $this->outputBufferSize;
    }

    /**
     * @param int $bytes Number of body bytes to buffer before writes are made to the client. Smaller numbers push data
     *     sooner, but decreases performance. Default is 8192 (8k).
     *
     * @return \Amp\Http\Server\Options
     * @throws \Error
     */
    public function withOutputBufferSize(int $bytes): self {
        if ($bytes <= 0) {
            throw new \Error(
                "Output buffer size must be greater than zero bytes"
            );
        }

        $new = clone $this;
        $new->outputBufferSize = $bytes;

        return $new;
    }

    /**
     * @return int Number of milliseconds to wait when shutting down the server before forcefully shutting down.
     */
    public function getShutdownTimeout(): int {
        return $this->shutdownTimeout;
    }

    /**
     * @param int $milliseconds Number of milliseconds to wait when shutting down the server before forcefully shutting
     *     down. Default is 3000.
     *
     * @return \Amp\Http\Server\Options
     *
     * @throws \Error If the timeout is less than 0.
     */
    public function withShutdownTimeout(int $milliseconds): self {
        if ($milliseconds < 0) {
            throw new \Error(
                "Shutdown timeout size must be greater than or equal to zero"
            );
        }

        $new = clone $this;
        $new->shutdownTimeout = $milliseconds;

        return $new;
    }

    /**
     * @return bool `true` if HTTP/2 requests may be established through upgrade requests or prior knowledge.
     *     Disabled by default.
     */
    public function isHttp2UpgradeAllowed(): bool {
        return $this->allowHttp2Upgrade;
    }

    /**
     * Enables unencrypted upgrade or prior knowledge requests to HTTP/2.
     *
     * @return \Amp\Http\Server\Options
     */
    public function withHttp2Upgrade(): self {
        $new = clone $this;
        $new->allowHttp2Upgrade = true;

        return $new;
    }

    /**
     * Disables unencrypted upgrade or prior knowledge requests to HTTP/2.
     *
     * @return \Amp\Http\Server\Options
     */
    public function withoutHttp2Upgrade(): self {
        $new = clone $this;
        $new->allowHttp2Upgrade = false;

        return $new;
    }
}
