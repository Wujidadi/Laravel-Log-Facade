<?php

namespace Wujidadi\LogFacade;

use Illuminate\Log\Logger as IlluminateLogger;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\HandlerInterface;
use Monolog\Logger as MonoLogger;

class MicrosecondFormatter
{
    public function __invoke(IlluminateLogger $logger): void
    {
        /** @var IlluminateLogger|MonoLogger $logger */
        foreach ($logger->getHandlers() as $handler) {
            /** @var HandlerInterface $handler */
            $handler->setFormatter(new LineFormatter(
                '[%datetime%] %level_name%: %message%' . "\n", // No channel、context 與 extra
                'Y-m-d H:i:s.u',
                true,
                true
            ));
        }
    }
}