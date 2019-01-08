<?php

namespace ErrorHandlerModule;

use LogicException, InvalidArgumentException, Throwable;
use Tracy\Debugger;
use Tracy\Logger;

/**
 * Class ErrorHandler
 * @package ErrorHandler
 */
final class ErrorHandler
{
    /** @const string cesta k výchozí šabloně chyby */
    const DEFAULT_ERROR_TEMPLATE = __DIR__ . '/error.500.phtml';

    /** @var bool byl error handler zaregistrován? */
    private static $registered = FALSE;

    /** @var string cesta k výchozí šabloně chyby */
    private static $errorTemplate = self::DEFAULT_ERROR_TEMPLATE;


    /**
     * Zaregistruje error handler do Nette
     * @param string $errorTemplate cesta k výchozí šabloně chyby
     */
    public static function register(string $errorTemplate = self::DEFAULT_ERROR_TEMPLATE): void
    {
        // Error handler je možné zaregistrovat do Nette pouze jednou
        if(self::$registered)
        {
            throw new LogicException('ErrorHandler is already registered');
        }

        // Kontrola a zaregistrování šablony výchozí chyby do Tracy
        if(!file_exists($errorTemplate) || !is_readable($errorTemplate))
        {
            throw new InvalidArgumentException(sprintf('Error template %s is missing or not readable', $errorTemplate));
        }

        Debugger::$errorTemplate = $errorTemplate;

        // Zaregistrování callbacku, který se zavolá po kritické chybě v aplikaci
        Debugger::$onFatalError[] = [__CLASS__, 'onFatalError'];

        // Error handler byl úspěšně inicializován
        self::$registered = TRUE;
    }


    /**
     * Vrací název souboru s uloženou chybou ve formátu html (přes Tracy)
     * Tato funkce soubor nevytváří!
     * @param Throwable $error
     * @return string
     */
    public static function getErrorFile(Throwable $error): string
    {
        $tracyLogger = Debugger::getLogger();

        if($tracyLogger instanceof Logger)
        {
            return basename($tracyLogger->getExceptionFile($error));
        }

        return '';
    }


    /**
     * Funkce která se zavolá po kritické chybě v aplikaci
     * @param Throwable $error
     * @internal
     * @throws
     */
    public static function onFatalError(Throwable $error)
    {
        if(Debugger::$errorTemplate !== self::$errorTemplate)
        {
            throw new LogicException('Tracy error template changed, error handler is disabled');
        }
    }


    /**
     * Ruční vykreslení zadané kritické chyby
     * @param Throwable $error
     * @param bool $logged
     */
    public static function renderError(Throwable $error, bool $logged)
    {
        self::onFatalError($error);

        $exception = $error;

        unset($error);

        require_once __DIR__ . '/error.500.phtml';
    }
}
