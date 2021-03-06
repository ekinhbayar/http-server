<?php

namespace Amp\Http\Server\Test;

use Amp\Http\Server\Internal\Host;
use Amp\Socket\Certificate;
use Amp\Socket\ServerTlsContext;
use PHPUnit\Framework\TestCase;

class HostTest extends TestCase {
    public function getHost(): Host { // we do not want to add to definitions, that's for the Bootstrapper test.
        return (new \ReflectionClass(Host::class))->newInstanceWithoutConstructor();
    }

    public function testGenericInterface() {
        $host = $this->getHost();
        $host->expose("*", 1025);
        $interfaces = $host->getAddresses();
        if (Host::separateIPv4Binding()) {
            $this->assertEquals(["tcp://0.0.0.0:1025", "tcp://[::]:1025"], $interfaces);
        } else {
            $this->assertEquals(["tcp://[::]:1025"], $interfaces);
        }
    }

    public function testCryptoVhost() {
        $host = new Host;
        $host->expose("127.0.0.1", 8080);
        $host->encrypt((new ServerTlsContext)->withDefaultCertificate(new Certificate(__DIR__."/server.pem")));

        $this->assertTrue(isset($host->getTlsContext()["local_cert"]));
        $this->assertEquals(["tcp://127.0.0.1:8080"], array_values($host->getAddresses()));
    }

    /**
     * @expectedException \Error
     * @expectedExceptionMessage At least one interface must be specified
     */
    public function testNoInterfaces() {
        (new Host)->getAddresses();
    }

    /**
     * @expectedException \Error
     * @expectedExceptionMessage Invalid port number 0; integer in the range 1..65535 required
     */
    public function testBadPort() {
        (new Host)->expose("127.0.0.1", 0);
    }

    /**
     * @expectedException \Error
     * @expectedExceptionMessage Invalid IP address or unix domain socket path: rd.lo.wr.ey
     */
    public function testBadInterface() {
        (new Host)->expose("rd.lo.wr.ey", 1025);
    }

    /**
     * @expectedException \Error
     * @expectedExceptionMessage There must be no two identical interfaces for a same host: :: duplicated
     */
    public function testMultipleSameHosts() {
        $host = new Host;
        $host->expose("*", 80);
        $host->expose("::", 80);
    }

    /**
     * @expectedException \Error
     * @expectedExceptionMessage Invalid IP address or unix domain socket path: invalid.address
     */
    public function testInvalidAddress() {
        (new Host)->expose("invalid.address", 8080);
    }
}
