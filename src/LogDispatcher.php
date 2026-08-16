<?php declare(strict_types=1);

namespace ErrorHandlerModule;

use ErrorHandlerModule\Logger\IFilterLogger;
use Tracy;

/**
 * Class LogDispatcher
 */
class LogDispatcher extends Tracy\Logger
{
    /** @var IFilterLogger[] */
    private array $filterLoggers = [];


    /**
     * Zaregistruje nový filtr pro filtrování logů aplikace (spustí se první vyhovují filtr)
     */
    public function registerFilterLogger(IFilterLogger $filterLogger): self
    {
        $this->filterLoggers[] = $filterLogger;

        return $this;
    }

    /**
     * Logs message or exception
     * @param mixed $message
     * @param string $level one of constant ILogger::INFO, WARNING, ERROR, EXCEPTION, CRITICAL
     * @return string|null logged error filename
     */
    public function log(mixed $message, string $level = self::INFO): ?string
    {
        foreach($this->filterLoggers as $filterLogger)
        {
            if($filterLogger->isMatch($message))
            {
                // Tracy\ILogger::log() nemá deklarovaný návratový typ, potomci Tracy\Logger vrací název souboru s chybou
                $logFile = $filterLogger->log($message, $level);

                return is_string($logFile) ? $logFile : null;
            }
        }

        return parent::log($message, $level);
    }
}
