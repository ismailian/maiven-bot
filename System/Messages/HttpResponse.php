<?php

namespace TeleBot\System\Messages;

use Exception;

class HttpResponse
{

    /**
     * set http status code
     *
     * @param int $code
     * @return self
     */
    public static function setStatusCode(int $code = 200): self
    {
        http_response_code($code);

        return new static();
    }

    /**
     * send response to client
     *
     * @param string|array|object $body
     * @param bool $asJson send as json
     * @return void
     * @throws Exception
     */
    public static function send(string|array|object $body, bool $asJson = false): void
    {
        if (is_array($body) || is_object($body)) {
            if (!$asJson) {
                throw new \Exception('cannot respond with ' . gettype($body) . ' as text/plain');
            }

            self::addHeader('Content-Type', 'application/json');
            $body = json_encode($body, JSON_UNESCAPED_SLASHES);
        }

        die($body);
    }

    /**
     * add http header
     *
     * @param string $key
     * @param string $value
     * @return self
     */
    public static function addHeader(string $key, string $value): self
    {
        header("$key: $value");

        return new static();
    }

    /**
     * terminate process
     *
     * @return void
     */
    public static function end(): void
    {
        die();
    }

    /**
     * close connection
     *
     * Helpful when you need to send a response without terminating the process
     *
     * @return void
     */
    public static function close(): void
    {
        if (is_callable('fastcgi_finish_request')) {
            session_write_close();
            fastcgi_finish_request();
            return;
        }

        ignore_user_abort(true);
        ob_start();

        header('HTTP/1.1 200 OK');
        header('Content-Encoding: none');
        header('Content-Length: ' . ob_get_length());
        header('Connection: close');

        ob_end_flush();
        ob_flush();
        flush();
    }
}