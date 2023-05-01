<?php

namespace Wujidadi\LogFacade;

use GuzzleHttp\MessageFormatter;
use GuzzleHttp\MessageFormatterInterface;
use GuzzleHttp\Psr7\Message;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use stdClass;
use Throwable;

/**
 * Log formatter, re-written on the base of GuzzleHttp\MessageFormatter.
 *
 * @see \GuzzleHttp\MessageFormatter
 */
class LogFormatter implements MessageFormatterInterface
{
    /**
     * Apache Common Log Format.
     *
     * @link https://httpd.apache.org/docs/2.4/logs.html#common
     * @var string
     */
    public const CLF = "{hostname} {req_header_User-Agent} - [{date_common_log}] \"{method} {target} HTTP/{version}\" {code} {res_header_Content-Length}";
    public const DEBUG = ">>>>>>>>\n{request}\n<<<<<<<<\n{response}\n--------\n{error}";
    public const SHORT = '[{ts}] "{method} {target} HTTP/{version}" {code}';

    /**
     * @var string Template used to format log messages
     */
    private string $template;

    /**
     * The delimiter, a string hard to appear in the log message.
     *
     * @var string
     */
    private const DELIMITER = '-0-0-0-0-0-0-0-0-0-';

    public function __construct()
    {
        $arr = [
            'method' => '{method}',
            'uri' => '{uri}',
            'time' => '{ts}',
            'status' => '{code}',
            'req_body' => '{req_body}',
            'res_body' => '{res_body}',
            'error' => '{error}',
        ];
        $this->template = collect($arr)
            ->map(function ($value, $key) {
                return "$key,$value";
            })
            ->implode(self::DELIMITER);
    }

    /**
     * Returns a decorated message string.
     *
     * @param RequestInterface $request
     * @param ResponseInterface|null $response
     * @param Throwable|null $error
     * @return string
     */
    public function format(RequestInterface $request, ?ResponseInterface $response = null, ?Throwable $error = null): string
    {
        $format = $this->buildFormat($request, $response, $error);
        $request->getBody()->rewind();
        if (!$error) {
            $response->getBody()->rewind();
        }
        $value = explode(self::DELIMITER, $format);
        $arr = collect($value)
            ->mapWithKeys(function ($value) {
                $pair = $this->buildMessage($value);
                return [$pair->key => $pair->value];
            })
            ->all();
        return json_encode(
            resolve(LogDecorator::class)->decorate($arr, $response),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
    }

    /**
     * Returns a formatted message string.
     *
     * @param RequestInterface       $request  Request that was sent
     * @param ResponseInterface|null $response Response that was received
     * @param Throwable|null         $error    Exception that was received
     */
    protected function buildFormat(RequestInterface $request, ?ResponseInterface $response = null, ?Throwable $error = null): string
    {
        $cache = [];

        /** @var string */
        return preg_replace_callback(
            '/{\s*([A-Za-z_\-.0-9]+)\s*}/',
            function (array $matches) use ($request, $response, $error, &$cache) {
                if (isset($cache[$matches[1]])) {
                    return $cache[$matches[1]];
                }

                $result = '';
                switch ($matches[1]) {
                    case 'request':
                        $result = Message::toString($request);
                        break;
                    case 'response':
                        $result = $response ? Message::toString($response) : '';
                        break;
                    case 'req_headers':
                        $result = trim($request->getMethod()
                                . ' ' . $request->getRequestTarget())
                            . ' HTTP/' . $request->getProtocolVersion() . "\r\n"
                            . $this->headers($request);
                        break;
                    case 'res_headers':
                        $result = $response ?
                            sprintf(
                                'HTTP/%s %d %s',
                                $response->getProtocolVersion(),
                                $response->getStatusCode(),
                                $response->getReasonPhrase()
                            ) . "\r\n" . $this->headers($response)
                            : 'NULL';
                        break;
                    case 'req_body':
                        $result = $request->getBody()->__toString();
                        break;
                    case 'res_body':
                        if (!$response instanceof ResponseInterface) {
                            $result = 'NULL';
                            break;
                        }

                        $body = $response->getBody();

                        if (!$body->isSeekable()) {
                            $result = 'RESPONSE_NOT_LOGGEABLE';
                            break;
                        }

                        $result = $response->getBody()->__toString();
                        break;
                    case 'ts':
                    case 'date_iso_8601':
                        $result = gmdate('c');
                        break;
                    case 'date_common_log':
                        $result = date('d/M/Y:H:i:s O');
                        break;
                    case 'method':
                        $result = $request->getMethod();
                        break;
                    case 'version':
                    case 'req_version':
                        $result = $request->getProtocolVersion();
                        break;
                    case 'uri':
                    case 'url':
                        $result = $request->getUri()->__toString();
                        break;
                    case 'target':
                        $result = $request->getRequestTarget();
                        break;
                    case 'res_version':
                        $result = $response
                            ? $response->getProtocolVersion()
                            : 'NULL';
                        break;
                    case 'host':
                        $result = $request->getHeaderLine('Host');
                        break;
                    case 'hostname':
                        $result = gethostname();
                        break;
                    case 'code':
                        $result = $response ? $response->getStatusCode() : 'NULL';
                        break;
                    case 'phrase':
                        $result = $response ? $response->getReasonPhrase() : 'NULL';
                        break;
                    case 'error':
                        $result = $error ? $error->getMessage() : 'NULL';
                        break;
                    default:
                        // handle prefixed dynamic headers
                        if (str_starts_with($matches[1], 'req_header_')) {
                            $result = $request->getHeaderLine(substr($matches[1], 11));
                        } elseif (str_starts_with($matches[1], 'res_header_')) {
                            $result = $response
                                ? $response->getHeaderLine(substr($matches[1], 11))
                                : 'NULL';
                        }
                }

                $cache[$matches[1]] = $result;
                return $result;
            },
            $this->template
        );
    }

    /**
     * Gets headers from message as string
     *
     * @param MessageInterface $message
     * @return string
     */
    private function headers(MessageInterface $message): string
    {
        $result = '';
        foreach ($message->getHeaders() as $name => $values) {
            $result .= $name . ': ' . implode(', ', $values) . "\r\n";
        }
        return trim($result);
    }

    /**
     * Builds a message object for advanced decorations.
     *
     * @param string $str
     * @return stdClass
     */
    protected function buildMessage(string $str): stdClass
    {
        $arr = explode(',', $str);
        $key = array_shift($arr);
        $value = implode(',', $arr);
        return (object) compact('key', 'value');
    }
}
