<?php
/*
 * (c) 2018, Dmitrijs Balabka
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require __DIR__ . '/vendor/autoload.php';

// This is a very simple HTTP server that just prints a message to each client that connects.
// It doesn't check whether the client sent an HTTP request.

// You might notice that your browser opens several connections instead of just one, even when only making one request.

use Amp\Loop;
use Amp\Socket\ServerSocket;
use function Amp\asyncCoroutine;

Loop::run(function () {
    $clientHandler = asyncCoroutine(function (ServerSocket $socket) {
        list($ip, $port) = explode(":", $socket->getRemoteAddress());

        $buffer = '';
        while (($chunk = yield $socket->read()) !== null) {
            $buffer .= $chunk;
            if (\substr($buffer, -4, 4) === "\r\n\r\n") {
                $date = \gmdate("D, d M Y H:i:s", \time()) . " GMT";
                $body = "Hello world!";
                $bodyLength = \strlen($body);
                yield $socket->write("HTTP/1.1 200 OK\r\nContent-Type: text/plain; Charset=utf-8\r\nX-Powered-By: AerysServer\r\nConnection: keep-alive\r\nContent-Length: ${bodyLength}\r\nKeep-Alive: timeout=10000\r\nDate: ${date}\r\n\r\n${body}\r\n\r\n\r\n\r\n");
            }
        }
    });

    $server = Amp\Socket\listen("0.0.0.0:8080");

    echo "Listening for new connections on " . $server->getAddress() . " ..." . PHP_EOL;

    while ($socket = yield $server->accept()) {
        $clientHandler($socket);
    }
});
