<?php

namespace ErrorHandlerModule;

use LogicException, InvalidArgumentException, Throwable;
use Tracy;

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

    /** @var LogDispatcher|null */
    private static $logDispatcher;


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

        Tracy\Debugger::$errorTemplate = $errorTemplate;

        // Zaregistrování callbacku, který se zavolá po kritické chybě v aplikaci
        Tracy\Debugger::$onFatalError[] = [__CLASS__, 'onFatalError'];

        // Error handler byl úspěšně inicializován
        self::$registered = TRUE;
    }


    /**
     * Aktivuje a integruje vylepšený error logger do Tracy který umožňuje definovat pro jakou chybu se použije jaký ILogger
     * @return LogDispatcher
     */
    public static function activateLogDispatcher(): LogDispatcher
    {
        if(!self::$logDispatcher)
        {
            self::$logDispatcher = new LogDispatcher(Tracy\Debugger::$logDirectory, Tracy\Debugger::$email, Tracy\Debugger::getBlueScreen());
            self::$logDispatcher->directory = &Tracy\Debugger::$logDirectory; // nette back compatibility
            self::$logDispatcher->email = &Tracy\Debugger::$email;

            // Přeneseme nastavení ze standartního Tracy Loggeru
            $tracyLogger = Tracy\Debugger::getLogger();

            if($tracyLogger instanceof Tracy\Logger)
            {
                self::$logDispatcher->fromEmail = $tracyLogger->fromEmail;
                self::$logDispatcher->emailSnooze = $tracyLogger->emailSnooze;
                self::$logDispatcher->mailer = $tracyLogger->mailer;
            }
        }

        Tracy\Debugger::setLogger(self::$logDispatcher);

        return self::$logDispatcher;
    }


    /**
     * Vrací název souboru s uloženou chybou ve formátu html (přes Tracy)
     * Tato funkce soubor nevytváří!
     * @param Throwable $error
     * @return string
     */
    public static function getErrorFile(Throwable $error): string
    {
        $tracyLogger = Tracy\Debugger::getLogger();

        if(($tracyLogger instanceof Tracy\Logger) && $tracyLogger->directory)
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
        if(Tracy\Debugger::$errorTemplate !== self::$errorTemplate)
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
