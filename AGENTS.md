# Vwajèn — notes de projet

- Laravel 13, PHP 8.3 (WAMP : `C:\wamp64\bin\php\php8.3.28\php.exe`), MySQL `vwajen` / `vwajen_test`. Pas de build front : `public/css/app.css`, `public/js/*.js`.
- Tests : `php -d xdebug.mode=off vendor/bin/phpunit` (SmokeTest + FlowTest, cache `database` comme en production).
- Langue source des chaînes : français (`__('…')`). Toute nouvelle chaîne doit être ajoutée à `lang/ht.json` et `lang/en.json`.
- Cache : ne jamais mettre d'objets Eloquent en cache (`serializable_classes = false`) — uniquement des tableaux/scalaires.
- Types polymorphes : `App\Support\Morph::MAP` (clés courtes `post`, `video`, `live`…).
- Notifications : passer par `App\Services\Notifier` ; les paramètres préfixés `__:` sont traduits dans la langue du destinataire.
