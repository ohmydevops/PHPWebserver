<?php
declare(strict_types=1);

namespace ClanCats\Station\PHPServer;

use ClanCats\Station\PHPServer\Exceptions\BindException;
use ClanCats\Station\PHPServer\Exceptions\Exception;
use ClanCats\Station\PHPServer\Exceptions\PortException;
use Socket;

class Server
{
    /**
     * The current host
     *
     * @var string|null
     */
    protected ?string $host;

    /**
     * The current port
     *
     * @var int|null
     */
    protected ?int $port;

    /**
     * The bounded socket
     *
     * @var Socket
     */
    protected Socket $socket;

    /**
     * The flag for server state
     *
     * @var bool
     */
    protected bool $canContinue;

    /**
     * Construct new Server instance
     *
     * @param string $host
     * @param int $port
     * @return void
     * @throws BindException|PortException
     */
    public function __construct(string $host, int $port)
    {
        $this->host = $host;
        $this->port = $port;
        $this->canContinue = true;

        // create a socket
        $this->createSocket();

        // bind the socket
        $this->bind();
    }

    /**
     * Destruct server instance
     */
    public function __destruct()
    {
        $this->shutdownServer();
    }

    /**
     *  Create new socket resource
     *
     * @return void
     */
    protected function createSocket()
    {
        $this->socket = socket_create(AF_INET, SOCK_STREAM, 0);
    }

    /**
     * Shutdown connections
     */
    public function shutdownServer()
    {
        echo("Shutting down server ..." . PHP_EOL);
        $this->canContinue = false;
        socket_shutdown($this->socket, 2);
    }

    /**
     * Bind the socket resource
     *
     * @return void
     * @throws BindException|PortException
     */
    protected function bind()
    {
        if($this->port > 65535 || $this->port < 1){
            throw new PortException('Invalid port number: ' . $this->port . '. Valid port numbers are between 1 to 65535');
        }
        if (!socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1)) {
            echo 'Unable to set option on socket: '. socket_strerror(socket_last_error()) . PHP_EOL;
        }
        if (!socket_bind($this->socket, $this->host, $this->port)) {
            throw new BindException('Could not bind: ' . $this->host . ':' . $this->port . ' - ' . socket_strerror(socket_last_error()));
        }
    }

    /**
     * Listen for requests
     *
     * @param callable $callback
     * @return void
     * @throws Exception
     */
    public function listen(callable $callback)
    {
        // check if the callback is valid
        if (!is_callable($callback)) {
            throw new Exception('The given argument should be callable.');
        }

        while ($this->canContinue) {
            // listen for connections
            socket_listen($this->socket);

            // try to get the client socket resource
            // if false we got an error close the connection and continue
            if (!$client = socket_accept($this->socket)) {
                socket_close($client);
                continue;
            }

            // create new request instance with the client header.
            // In the real world of course you cannot just fix the max size to 1024.
            $request = Request::getRequestWithHeaderString(socket_read($client, 1024));

            // execute the callback
            $response = call_user_func($callback, $request);

			// check if we really received a Response object
			// if not return a 404 response object
			if (!$response instanceof Response) {
				$response = Response::error(404);
			}

            // write the response to the client socket
            $finalResponse = $response->generateResponse();
            socket_write($client, $finalResponse, strlen($finalResponse));

            // close the connection, so we can accept new ones
            socket_close($client);
        }
    }
}
