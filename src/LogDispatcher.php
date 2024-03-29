<?php declare(strict_types=1);

namespace ErrorHandlerModule;

use ErrorHandlerModule\Logger\IFilterLogger;
use Tracy;

/**
 * Class Logger
 * @package ErrorHandlerModule
 */
class LogDispatcher extends Tracy\Logger
{
    /** @var IFilterLogger[] */
    private $filterLoggers = [];


    /**
     * Zaregistruje nový filtr pro filtrování logů aplikace (spustí se první vyhovují filtr)
     * @param IFilterLogger $filterLogger
     * @return self
     */
    public function registerFilterLogger(IFilterLogger $filterLogger): self
    {
        $this->filterLoggers[] = $filterLogger;

        return $this;
    }


    /**
     * Logs message or exception
     * @param  mixed  $message
     * @param  string  $priority one of constant ILogger::INFO, WARNING, ERROR, EXCEPTION, CRITICAL
     * @return string|null logged error filename
     */
    public function log($message, $priority = self::INFO)
    {
        if($this->filterLoggers)
        {
            foreach ($this->filterLoggers as $filterLogger)
            {
                if($filterLogger->isMatch($message))
                {
                    return $filterLogger->log($message, $priority);
                }
            }
        }

        return Tracy\Logger::log($message, $priority);
    }
}
