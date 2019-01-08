# pavlista/nette-error-handler
Jednoduché rozšíření Nette které zajištuje zobrazení názvu souboru se zalogovanou exception při zobrazení chyby 500 v produkčním režimu (viz. obrázek níže).

![Nový vzhled chybové stránky 500](https://raw.githubusercontent.com/MichaelPavlista/nette-error-handler/master/docs/imgs/error.500.png)

## Instalace
- Nainstalujte balíček přes příkaz: `composer require pavlista/nette-error-handler`.
- V konfiguraci nette zaregistrujte nový error presenter.
```neon
application:
    errorPresenter: ErrorHandler:Error
```
- Do souboru boostrap.php přidejte ihned pod `$configurator->enableTracy(__DIR__ . '/../log');` registraci error handleru:
```php
ErrorHandlerModule\ErrorHandler::register();
```
