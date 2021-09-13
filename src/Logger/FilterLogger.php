<?php

namespace ErrorHandlerModule\Logger;

use Tracy\Logger;

/**
 * Class FilterLogger
 * @package ErrorHandlerModule\Loggers
 */
abstract class FilterLogger extends Logger implements IFilterLogger
{
    /** @var string|null přetížení priority logovaných zpráv přes tento logger */
    protected ?string $overridePriority = NULL;


    /**
     * Logs message or exception to file and sends email notification.
     * @param mixed $message
     * @param string $level one of constant ILogger::INFO, WARNING, ERROR (sends email), EXCEPTION (sends email), CRITICAL (sends email)
     * @return string|null logged error filename
     */
    public function log($message, $level = self::INFO): ?string
    {
        if($this->isMatch($message))
        {
            return parent::log($message, $this->overridePriority ?: $level);
        }

        return NULL;
    }
}
