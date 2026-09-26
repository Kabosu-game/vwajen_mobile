# Vwajèn — plateforme civique et sociale haïtienne

Laravel 13 · PHP 8.3+ · MySQL 8 · Blade + CSS/JS sans build (léger pour les faibles connexions).
Interface en **kreyòl**, **français** et **anglais**.

## Installation (WAMP / serveur)

```bash
composer install
cp .env.example .env          # puis ajuster DB_*, MAIL_*, APP_URL
php artisan key:generate
php artisan migrate --seed    # rôles, permissions, 13 thèmes, langues + données de démo (hors production)
php artisan storage:link
php artisan vwajen:admin vous@exemple.com --password=MotDePasse123   # super-administrateur
php artisan serve             # http://127.0.0.1:8000
```

Planificateur (rappels, notifications avant débats, expiration des badges, purge des comptes, nettoyage) :

```bash
* * * * * php /chemin/vers/artisan schedule:run      # cron (production)
php artisan schedule:work                             # développement
```

Sous WAMP, PHP se trouve dans `C:\wamp64\bin\php\php8.3.x\php.exe`.

### Comptes de démonstration (mot de passe `Vwajen2026!`)

| Compte | Rôle |
|---|---|
| `admin@vwajen.ht` | Super-administrateur (espace `/admin`) |
| `moderation@vwajen.ht` | Modératrice |
| `verification@vwajen.ht` | Vérificateur |
| `mariange_t@demo.vwajen.ht` | Candidate vérifiée (lives, tableau de bord, programme) |
| `jeanmarc@demo.vwajen.ht` | Citoyen |
| `depite@demo.vwajen.ht` | Élu (suivi des engagements) |

Toutes les personnes et organisations de démo sont **fictives**. En production, `DatabaseSeeder` n'exécute que les données de base.

## Services optionnels (`.env`)

| Fonction | Variables | Sans configuration |
|---|---|---|
| Connexion Google / Apple | `GOOGLE_*`, `APPLE_*` | boutons masqués |
| E-mails (vérification, notifications, newsletter) | `MAIL_*` | écrits dans `storage/logs` |
| SMS (vérification téléphone, notifications SMS) | `SMS_DRIVER=twilio`, `TWILIO_*` | codes écrits dans les logs |
| Notifications push | `php artisan vwajen:vapid` → `VAPID_*` | désactivées |
| Compression vidéo, qualités 240/360/720p, miniatures | `FFMPEG_PATH`, `FFPROBE_PATH` | vidéo originale servie |
| Lives WebRTC derrière des NAT stricts | `WEBRTC_TURN_*` | STUN public uniquement |
| IA : traduction, résumés de débats, modération assistée, recherche intelligente | `ANTHROPIC_API_KEY` (modèle `claude-opus-5`) | boutons désactivés |
| Transcription / sous-titres automatiques | `TRANSCRIPTION_URL` (compatible Whisper) + ffmpeg | désactivés |

L'état de chaque service est visible dans **Admin → Monitoring**.

## Correspondance avec le cahier des charges

| # | Section | Où |
|---|---|---|
| 1 | Comptes et utilisateurs | `Auth/*`, `SettingsController` (profil, sessions, appareils, export, suppression) |
| 2 | Réseau social « Vwa » | `PostController`, `InteractionController`, `CommentController`, `FeedService` (fils personnalisé / chronologique / populaires / récents) |
| 3 | Profils | `ProfileController` (personnel, candidat, organisation, élu) |
| 4–5 | Candidats, programmes | `CandidateController`, `ProgramController` (versions, archivage, documents), `ProposalController` |
| 6 | Comparaison informative | `/compare` — sans classement ni score |
| 7 | Kesyon pou kandida yo | `QuestionController` (soutiens, réponses texte/vidéo, statuts) |
| 8–9 | Débats, Vwajèn Live | `DebateController`, `LiveController`, `ChatController`, `public/js/live.js` |
| 10–11 | Shorts, vidéos | `ShortController`, `VideoController`, `UploadController` (téléversement reprenable) |
| 12–13 | Découvrir, recherche | `DiscoverController`, `SearchService` |
| 14 | Événements | `EventController` (fuseaux, carte, RSVP, rappels, .ics) |
| 15 | Notifications | `ActivityNotification` (app, e-mail, push, SMS), `NotificationController` |
| 16 | Messagerie | `MessageController` |
| 17 | Communautés | `CommunityController`, `DiscussionController` |
| 18–19 | Vwajèn Map, Vwajèn Mond | `MapController` (Leaflet/OSM), `MondController` |
| 20–21 | Modération, vérification | `ModerationService`, `Admin\ReportController`, `Admin\SanctionController`, `Admin\VerificationController` |
| 22–24 | Tableaux de bord, statistiques | `/candidate/dashboard`, `/admin`, `StatsService` |
| 25–26 | Sécurité, confidentialité | limites de débit, anti-spam, audit, appareils révoqués, cookies, données sensibles |
| 27 | Multilingue | `lang/ht.json`, `lang/en.json` (+ traductions modifiables dans Admin → Langues) |
| 28–29 | Faible connexion, accessibilité | mode économie de données, service worker, compression d'images, taille du texte, contraste |
| 30–31 | Sources, responsables publics | `SourceController`, `OfficialController` (engagements documentés) |
| 32–34 | Personnalisation, partage, admin système | paramètres, partage interne/externe, deep links, rôles & permissions, monitoring |
| 35 | Fonctionnalités futures | IA, API publique `/api/v1`, podcasts, Audio Spaces, newsletter, ambassadeurs, élections, observatoire, intégrations `/embed/*` |

## Tests

```bash
php vendor/bin/phpunit
```

Base `vwajen_test` (MySQL) : `SmokeTest` rend ~260 pages (invité, citoyen, candidat, admin) ; `FlowTest` couvre les actions principales.

## Limites connues

- **Lives** : la diffusion navigateur utilise WebRTC en maillage (chaque spectateur reçoit le flux directement de l'hôte). Au-delà de quelques dizaines de spectateurs, utilisez un serveur média (OBS → RTMP → HLS) et renseignez l'URL HLS du live.
- **Temps réel** : chat, messagerie et compteurs utilisent un rafraîchissement périodique (polling), pas de WebSocket.
- **Application mobile** : le site est une PWA installable ; les fichiers `public/.well-known/*` sont à compléter avec les identifiants de vos applications natives.
