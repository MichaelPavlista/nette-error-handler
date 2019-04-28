<?php

namespace ErrorHandlerModule\Logger;

use Tracy\ILogger;

/**
 * Interface IFilterLogger
 * @package ErrorHandlerModule
 */
interface IFilterLogger extends ILogger
{
    /**
     * Splňuje logovaná zpráva podmínku tohoto loggeru?
     * @param $message
     * @return bool
     */
    public function isMatch($message): bool;
}
