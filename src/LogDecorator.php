<?php

namespace Wujidadi\LogFacade;

use Carbon\Carbon;
use GuzzleHttp\Psr7\Header;
use Illuminate\Support\Arr;
use Psr\Http\Message\ResponseInterface;

/**
 * Log decorator class.
 */
class LogDecorator
{
    private array $arr;

    private ?ResponseInterface $response;

    /**
     * Returns an array of the decorated log message.
     *
     * @param array $arr
     * @param ResponseInterface|null $response
     * @return array
     */
    public function decorate(array $arr, ?ResponseInterface $response): array
    {
        $this->arr = $arr;
        $this->response = $response;

        $this->decorateTime();
        $this->decorateReqBody();
        if ($this->response) {
            $this->decorateResBody();
        }

        return $this->arr;
    }

    /**
     * Add the time in current timezone to the log message.
     *
     * @return void
     */
    private function decorateTime(): void
    {
        $this->arr['time'] = Carbon::parse($this->arr['time'])
            ->timezone(config('app.timezone'))
            ->toDateTimeString();
    }

    /**
     * Decode the request body in the log message.
     *
     * @return void
     */
    private function decorateReqBody(): void
    {
        $this->arr['req_body'] = Unicode::unescape(mb_convert_encoding($this->arr['req_body'], 'UTF-8'));
    }

    /**
     * Decode the response body in the log message.
     *
     * @return void
     */
    private function decorateResBody(): void
    {
        if (!array_key_exists('res_body', $this->arr)) {
            return;
        }
        $type = $this->response->getHeader('content-type');
        $parsed = Header::parse($type);
        $from_encoding = Arr::get($parsed, '0.charset') ?: 'UTF-8';
        $this->arr['res_body'] = Unicode::unescape(mb_convert_encoding($this->arr['res_body'], 'UTF-8', $from_encoding));
    }
}
