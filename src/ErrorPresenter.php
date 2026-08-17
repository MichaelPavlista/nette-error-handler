<?php declare(strict_types=1);

namespace ErrorHandlerModule;

use Nette;
use Nette\Application\Responses;
use Nette\Http;
use Throwable;
use Tracy\ILogger;

/**
 * Class ErrorPresenter
 */
class ErrorPresenter implements Nette\Application\IPresenter
{
    use Nette\SmartObject;


    public function __construct(private readonly ILogger $logger)
    {
    }

    /**
     * Funkce umožňující modifikovat application request aby směřoval na zadaný presenter a akci
     */
    final public function modifyRequest(
        Nette\Application\Request $request,
        string $presenterName,
        string $action = '',
    ): Nette\Application\Request
    {
        $request->setPresenterName($presenterName);

        if($action)
        {
            $requestParameters = $request->getParameters();
            $requestParameters[Nette\Application\UI\Presenter::ActionKey] = $action;

            $request->setParameters($requestParameters);
        }

        return $request;
    }

    /**
     * Funkce zajišťující vyhodnocení a přesměrování na presenter se zobrazením chyby 4xx
     */
    public function handleBadRequestException(
        Nette\Application\BadRequestException $exception,
        Nette\Application\Request $request,
    ): Nette\Application\Response
    {
        if(PHP_SAPI === 'cli')
        {
            return new Responses\TextResponse('404: ' . $exception->getMessage() . "\n");
        }

        // Předáme požadavek na výchozí error presenter Nette
        $request->setPresenterName('Nette:Error');

        return new Responses\ForwardResponse($request);
    }

    /**
     * Vyhodnocení příkazu na error presenter
     */
    final public function run(Nette\Application\Request $request): Nette\Application\Response
    {
        $e = $request->getParameter('exception');

        // Chybějící nebo nevalidní parametr exception nesmí shodit error presenter - Nette by výjimku
        // už nezpracovalo (Application::run) a chyba by skončila neošetřená mimo aplikaci.
        if(!$e instanceof Throwable)
        {
            $e = new Nette\InvalidStateException(sprintf(
                'Error presenter was called without a Throwable in parameter "exception", %s given',
                get_debug_type($e),
            ));
        }

        // Pokud se jedná o očekávanou chybu 4xx
        if($e instanceof Nette\Application\BadRequestException)
        {
            // Zalogujeme informaci o volání neexistující nebo nepovolené stránky
            $this->logger->log("HTTP code {$e->getCode()}: {$e->getMessage()} in {$e->getFile()}:{$e->getLine()}", 'access');

            // Předáme funkci, která ma nastarosti vrátit forward request na zobrazení detailu http chyby
            return $this->handleBadRequestException($e, $request);
        }

        // Pokud se jedná o kritické selhání aplikace (error 500) vykreslíme chybovou stránku včetně názvu souboru s chybou
        try
        {
            $this->logger->log($e, ILogger::EXCEPTION);

            $logged = true;
        }
        catch(Throwable)
        {
            $logged = false;
        }

        return new Responses\CallbackResponse(
            static function (Http\IRequest $httpRequest, Http\IResponse $httpResponse) use ($e, $logged): void {
                // Zobrazujeme HTML chybovou stránku
                if(preg_match('#^text/html(?:;|$)#', (string) $httpResponse->getHeader('Content-Type')))
                {
                    ErrorHandler::renderError($e, $logged);
                }
                // Zobrazení kódu chyby v CLI
                elseif(PHP_SAPI === 'cli')
                {
                    echo sprintf("error 500: %s\n", $logged ? ErrorHandler::getErrorFile($e) : 'Tracy is unable to log error');
                }
            },
        );
    }
}
