<?php

namespace Database\Seeders;

use App\Models\Answer;
use App\Models\CandidateProfile;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Commitment;
use App\Models\Community;
use App\Models\CommunityMember;
use App\Models\Debate;
use App\Models\DebateParticipant;
use App\Models\DebateQuestion;
use App\Models\Election;
use App\Models\Event;
use App\Models\EventRsvp;
use App\Models\Follow;
use App\Models\Like;
use App\Models\Live;
use App\Models\LiveParticipant;
use App\Models\OfficialProfile;
use App\Models\OrganizationProfile;
use App\Models\Poll;
use App\Models\Post;
use App\Models\Program;
use App\Models\PublicRecord;
use App\Models\Question;
use App\Models\Role;
use App\Models\User;
use App\Services\ContentService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Données de démonstration (personnes et organisations FICTIVES) pour explorer la plateforme.
 * Mot de passe de tous les comptes de démo : Vwajen2026!
 */
class DemoSeeder extends Seeder
{
    private const PASSWORD = 'Vwajen2026!';

    public function run(): void
    {
        $content = app(ContentService::class);
        $mk = function (array $a) {
            $u = User::create($a + ['password' => self::PASSWORD, 'locale' => 'ht', 'country' => 'HT', 'terms_accepted_at' => now()]);
            $u->forceFill(['email_verified_at' => now(), 'created_at' => now()->subDays(rand(20, 200))])->save();

            return $u;
        };

        // ---- Équipe (interface en français) ----
        $admin = $mk(['name' => 'Administrasyon Vwajèn', 'username' => 'vwajen', 'email' => 'admin@vwajen.ht', 'locale' => 'fr', 'bio' => 'Kont ofisyèl platfòm Vwajèn.']);
        $admin->forceFill(['is_verified' => true, 'verified_type' => 'public', 'verified_at' => now()])->save();
        $admin->roles()->attach(Role::where('name', 'superadmin')->value('id'));
        $mod = $mk(['name' => 'Modératrice Démo', 'username' => 'moderatris', 'email' => 'moderation@vwajen.ht', 'locale' => 'fr']);
        $mod->roles()->attach(Role::where('name', 'moderator')->value('id'));
        $ver = $mk(['name' => 'Vérificateur Démo', 'username' => 'verifikate', 'email' => 'verification@vwajen.ht', 'locale' => 'fr']);
        $ver->roles()->attach(Role::where('name', 'verifier')->value('id'));

        // ---- Citoyens ----
        $citizens = collect([
            ['Jean-Marc Pierre', 'jeanmarc', 'ouest', 'Port-au-Prince', 'HT'], ['Nadège Louis', 'nadege_l', 'nord', 'Cap-Haïtien', 'HT'],
            ['Wilner Joseph', 'wilnerj', 'artibonite', 'Gonaïves', 'HT'], ['Rose-Carmel Dorval', 'rosecarmel', 'sud', 'Les Cayes', 'HT'],
            ['Kervens Etienne', 'kervens', 'sud-est', 'Jacmel', 'HT'], ['Fabienne Charles', 'fabienne_c', 'centre', 'Hinche', 'HT'],
            ['Samuel Augustin', 'samaug', 'grand-anse', 'Jérémie', 'HT'], ['Mirlande Paul', 'mirlande', 'nippes', 'Miragoâne', 'HT'],
            ['Ricardo Baptiste', 'ricardo_b', null, 'Miami', 'US'], ['Guerline Noël', 'guerline', null, 'Montréal', 'CA'],
            ['Stanley Michel', 'stanleym', null, 'Paris', 'FR'], ['Darline Jean-Baptiste', 'darline', null, 'Santiago', 'CL'],
            ['Peterson Alexis', 'petersona', null, 'Boston', 'US'], ['Lovely Célestin', 'lovelyc', 'nord-ouest', 'Port-de-Paix', 'HT'],
        ])->map(fn ($c) => $mk(['name' => $c[0], 'username' => $c[1], 'email' => $c[1].'@demo.vwajen.ht', 'department' => $c[2], 'city' => $c[3],
            'country' => $c[4], 'is_diaspora' => $c[4] !== 'HT', 'location' => $c[3], 'locale' => $c[4] === 'HT' ? 'ht' : ($c[4] === 'US' ? 'en' : 'fr'),
            'bio' => 'Sitwayen angaje. Kont demo.', 'interests' => collect(['sante', 'education', 'economie', 'jeunesse', 'diaspora', 'securite'])->random(3)->values()->all()]));

        // ---- Candidats fictifs ----
        $candData = [
            ['Marie-Ange Toussaint', 'mariange_t', 'Mouvman Solidarite Demo', 'president', null, 'ouest', 'Économiste, ancienne directrice d\'une coopérative de microcrédit.'],
            ['Jacques-Edouard Belizaire', 'jebelizaire', 'Rasanbleman Pwogresis Demo', 'president', null, 'nord', 'Médecin de santé publique, 20 ans dans les hôpitaux communautaires.'],
            ['Nathalie Desrosiers', 'ndesrosiers', 'Indépendante', 'senator', 'Département de l\'Artibonite', 'artibonite', 'Agronome, spécialiste de l\'irrigation et des filières riz.'],
            ['Frantz Chéry', 'frantzchery', 'Platfòm Jèn Demo', 'deputy', 'Jacmel', 'sud-est', 'Entrepreneur numérique, fondateur d\'un incubateur de jeunes.'],
            ['Yolette Saint-Fleur', 'yolettesf', 'Mouvman Solidarite Demo', 'mayor', 'Cap-Haïtien', 'nord', 'Juriste, militante pour la gouvernance locale.'],
            ['Emmanuel Pierre-Louis', 'epierrelouis', 'Indépendant', 'senator', 'Département du Sud', 'sud', 'Enseignant et syndicaliste de l\'éducation.'],
        ];
        $categories = Category::all()->keyBy('slug');
        $candidates = collect();
        foreach ($candData as $i => [$name, $username, $party, $position, $constituency, $dep, $bio]) {
            $u = $mk(['name' => $name, 'username' => $username, 'email' => $username.'@demo.vwajen.ht', 'account_type' => 'candidate',
                'department' => $dep, 'bio' => $bio.' (Candidat fictif de démonstration.)']);
            $u->forceFill(['is_verified' => $i < 5, 'verified_type' => $i < 5 ? 'candidate' : null, 'verified_at' => now(), 'verified_until' => now()->addYear()])->save();
            $profile = CandidateProfile::create(['user_id' => $u->id, 'full_name' => $name, 'biography' => $bio, 'party' => $party,
                'position_sought' => $position, 'constituency' => $constituency, 'department' => $dep, 'election_year' => 2026,
                'career' => "2005–2012 : études et premières expériences professionnelles.\n2012–2020 : ".$bio."\n2020–aujourd'hui : engagement citoyen et politique.",
                'status' => $i < 5 ? 'verified' : 'pending', 'verified_at' => $i < 5 ? now() : null]);
            $profile->sources()->create(['name' => 'Déclaration de candidature (démo)', 'url' => 'https://example.org/candidature/'.$username,
                'published_on' => now()->subMonths(2), 'status' => $i % 2 ? 'provided' : 'verified', 'provided_by_candidate' => true]);

            // Programme publié avec propositions par thème
            $program = Program::create(['user_id' => $u->id, 'title' => 'Pwogram '.explode(' ', $name)[0].' 2026', 'summary' => 'Vizyon pou yon Ayiti ki pi jis, pi an sekirite e pi pwospè. (Programme fictif.)',
                'status' => 'published', 'published_at' => now()->subWeeks(3)]);
            $v1 = $program->versions()->create(['version_number' => 1, 'title' => $program->title, 'summary' => $program->summary, 'published_at' => now()->subWeeks(3), 'changelog' => 'Première version']);
            $themes = $categories->keys()->shuffle()->take(rand(5, 9));
            foreach ($themes as $pos => $slug) {
                $p = $v1->proposals()->create(['category_id' => $categories[$slug]->id, 'position' => $pos,
                    'title' => $this->proposalTitle($slug, $i), 'description' => $this->proposalText($slug),
                    'timeline' => ['6 mois', '1 an', '2 ans', 'Mandat complet'][rand(0, 3)], 'budget' => rand(0, 1) ? rand(5, 200).' millions USD (estimation)' : null]);
                if (rand(0, 1)) {
                    $p->sources()->create(['name' => 'Étude de référence (démo)', 'url' => 'https://example.org/etude/'.$slug, 'status' => ['provided', 'verified', 'unverified'][rand(0, 2)], 'provided_by_candidate' => true]);
                }
            }
            $program->update(['current_version_id' => $v1->id]);
            $candidates->push($u);
        }

        // ---- Organisation et élu fictifs ----
        $org = $mk(['name' => 'Radyo Sitwayen Demo', 'username' => 'radyositwayen', 'email' => 'radyo@demo.vwajen.ht', 'account_type' => 'organization', 'bio' => 'Média citoyen fictif (démo).']);
        $org->forceFill(['is_verified' => true, 'verified_type' => 'organization', 'verified_at' => now(), 'verified_until' => now()->addYear()])->save();
        OrganizationProfile::create(['user_id' => $org->id, 'legal_name' => 'Radyo Sitwayen Demo S.A.', 'org_type' => 'media', 'status' => 'verified']);
        $official = $mk(['name' => 'Député Démo Lafontant', 'username' => 'depite_demo', 'email' => 'depite@demo.vwajen.ht', 'account_type' => 'official', 'department' => 'ouest', 'bio' => 'Élu fictif de démonstration.']);
        $official->forceFill(['is_verified' => true, 'verified_type' => 'official', 'verified_at' => now(), 'verified_until' => now()->addYears(2)])->save();
        OfficialProfile::create(['user_id' => $official->id, 'full_name' => 'Démo Lafontant', 'office' => 'Député', 'institution' => 'Chambre des députés (démo)',
            'constituency' => 'Pétion-Ville', 'department' => 'ouest', 'mandate_start' => now()->subYear(), 'mandate_end' => now()->addYears(3)]);
        PublicRecord::create(['user_id' => $official->id, 'type' => 'declaration', 'title' => 'Déclaration sur l\'eau potable (démo)', 'body' => 'Engagement public à réhabiliter 3 fontaines.', 'occurred_on' => now()->subMonths(2)]);
        PublicRecord::create(['user_id' => $official->id, 'type' => 'activity', 'title' => 'Visite de l\'hôpital communal (démo)', 'occurred_on' => now()->subWeeks(3), 'location' => 'Pétion-Ville']);
        $c = Commitment::create(['user_id' => $official->id, 'title' => 'Réhabiliter 3 fontaines publiques', 'status' => 'in_progress', 'made_on' => now()->subMonths(2), 'due_on' => now()->addMonths(6), 'category_id' => $categories['infrastructures']->id]);
        $c->sources()->create(['name' => 'Discours public (démo)', 'url' => 'https://example.org/discours', 'status' => 'verified']);
        $c->updates()->create(['status' => 'in_progress', 'note' => 'Travaux commencés sur la première fontaine.']);

        $everyone = $citizens->merge($candidates)->push($org)->push($official)->push($admin);

        // ---- Abonnements ----
        foreach ($citizens as $cz) {
            foreach ($everyone->where('id', '!=', $cz->id)->random(8) as $target) {
                Follow::firstOrCreate(['follower_id' => $cz->id, 'following_id' => $target->id], ['status' => 'accepted']);
            }
        }
        foreach ($everyone as $u) {
            $u->forceFill(['followers_count' => Follow::where('following_id', $u->id)->count(), 'following_count' => Follow::where('follower_id', $u->id)->count()])->saveQuietly();
        }

        // ---- Publications ----
        $texts = [
            'Nou bezwen elektrisite 24/24 nan tout depatman yo! #Enèji #Ayiti',
            'Kilès nan kandida yo ki gen yon plan serye pou sekirite? #Sekirite #Eleksyon2026',
            'Magnifique journée de nettoyage à Jacmel avec les jeunes du quartier 🌱 #Anviwònman #Jènès',
            'Diaspora a dwe gen dwa vote! Sa se yon kesyon demokrasi. #Dyaspora #Vote',
            'La santé publique doit devenir une priorité budgétaire. #Sante #Budget2026',
            'Lekòl gratis e bon kalite pou tout timoun. Se sa nou mande! #Edikasyon',
            'Our farmers need roads to bring their products to market. #Agrikilti #Infrastructures',
            'Suivez le débat de ce soir sur Vwajèn Live ! #Debat #Eleksyon2026',
            'Transparans nan jesyon lajan leta a se yon obligasyon. #Gouvènans #Jistis',
            'Startup yo ka kreye travay pou jèn yo si nou envesti nan entènèt. #Nimerik #Travay',
        ];
        $posts = collect();
        foreach ($everyone as $u) {
            foreach (range(1, rand(1, 3)) as $k) {
                $post = Post::create(['user_id' => $u->id, 'body' => $texts[array_rand($texts)].' @'.$everyone->random()->username,
                    'visibility' => 'public', 'lang' => $u->locale, 'country' => $u->country, 'created_at' => now()->subHours(rand(1, 300))]);
                $content->syncHashtags($post, $post->body);
                $posts->push($post);
            }
            $u->forceFill(['posts_count' => Post::where('user_id', $u->id)->count()])->saveQuietly();
        }
        $pollPost = Post::create(['user_id' => $org->id, 'body' => 'Sondaj: Ki tèm ki pi enpòtan pou ou nan eleksyon 2026 yo? #Eleksyon2026', 'visibility' => 'public', 'lang' => 'ht']);
        $poll = Poll::create(['post_id' => $pollPost->id, 'ends_at' => now()->addDays(3)]);
        foreach (['Sekirite', 'Sante', 'Edikasyon', 'Ekonomi'] as $i => $label) {
            $poll->options()->create(['label' => $label, 'position' => $i, 'votes_count' => rand(5, 60)]);
        }
        $content->syncHashtags($pollPost, $pollPost->body);

        foreach ($posts->random(min(40, $posts->count())) as $post) {
            foreach ($citizens->random(rand(1, 6)) as $liker) {
                Like::firstOrCreate(['user_id' => $liker->id, 'likeable_type' => 'post', 'likeable_id' => $post->id]);
            }
            $post->update(['likes_count' => $post->likes()->count()]);
            foreach ($citizens->random(rand(0, 3)) as $commenter) {
                Comment::create(['user_id' => $commenter->id, 'commentable_type' => 'post', 'commentable_id' => $post->id,
                    'body' => ['Dakò nèt!', 'Bon pwen, men kijan pou finanse sa?', 'Tout à fait d\'accord.', 'Mwen pa dakò, men respè.', 'Merci pour le partage 🙏'][rand(0, 4)]]);
            }
            $post->update(['comments_count' => $post->comments()->count()]);
        }

        // ---- Questions aux candidats ----
        $qTexts = ['Ki plan ou genyen pou sekirite nan katye popilè yo?', 'Comment comptez-vous financer la gratuité scolaire ?', 'Kisa w ap fè pou dyaspora a ka patisipe?',
            'Quelle est votre position sur la décentralisation ?', 'Kijan w ap kreye travay pou jèn yo?', 'Comment lutter contre la corruption dans l\'administration ?'];
        foreach ($candidates as $cand) {
            foreach (array_slice($qTexts, 0, rand(2, 4)) as $qt) {
                $asker = $citizens->random();
                $q = Question::create(['user_id' => $asker->id, 'candidate_id' => $cand->id, 'title' => $qt, 'category_id' => $categories->random()->id,
                    'supports_count' => rand(1, 80), 'created_at' => now()->subDays(rand(1, 20))]);
                $q->supporters()->attach($citizens->random(3)->pluck('id'));
                if (rand(0, 1)) {
                    Answer::create(['question_id' => $q->id, 'user_id' => $cand->id, 'body' => 'Mèsi pou kesyon an. Pwogram nou an prevwa yon plan an 3 etap, ak finansman klè. Gade pwopozisyon nou yo pou plis detay. (Réponse fictive.)']);
                    $q->update(['status' => 'answered', 'answered_at' => now()->subDays(rand(0, 5)), 'answers_count' => 1]);
                }
            }
        }
        Question::create(['user_id' => $citizens->first()->id, 'title' => 'Ki kandida ki gen yon plan pou enèji renouvlab?', 'body' => 'Kesyon piblik pou tout kandida yo.', 'supports_count' => 42, 'category_id' => $categories['environnement']->id]);

        // ---- Communautés ----
        foreach ([['Jèn Ayisyen pou Chanjman', false, null, 'jeunesse'], ['Dyaspora Ayisyen Miami', true, 'US', 'diaspora'], ['Agrikiltè Latibonit', false, 'HT', 'agriculture'],
            ['Diaspora haïtienne de Montréal', true, 'CA', 'diaspora'], ['Sante pou Tout Moun', false, 'HT', 'sante']] as [$name, $dia, $country, $cat]) {
            $owner = $citizens->random();
            $com = Community::create(['owner_id' => $owner->id, 'name' => $name, 'slug' => Str::slug($name), 'description' => 'Kominote demo: '.$name,
                'rules' => "1. Respè youn pou lòt.\n2. Pa gen diskou rayisman.\n3. Pataje sous ou yo.", 'is_diaspora' => $dia, 'country' => $country,
                'city' => $country === 'US' ? 'Miami' : ($country === 'CA' ? 'Montréal' : null), 'category_id' => $categories[$cat]->id]);
            $members = $citizens->random(6)->push($owner)->unique('id');
            foreach ($members as $m) {
                CommunityMember::firstOrCreate(['community_id' => $com->id, 'user_id' => $m->id], ['role' => $m->id === $owner->id ? 'admin' : 'member']);
            }
            $com->update(['members_count' => $members->count()]);
            $com->discussions()->create(['user_id' => $owner->id, 'title' => 'Byenveni! Prezante tèt ou', 'body' => 'Di nou ki moun ou ye e sa w vle chanje.', 'is_pinned' => true]);
        }

        // ---- Événements (avec coordonnées pour la carte) ----
        $deps = config('vwajen.departments');
        foreach (array_slice(array_keys($deps), 0, 8) as $i => $dep) {
            $organizer = $candidates->random();
            $e = Event::create(['user_id' => $organizer->id, 'title' => ['Rasanbleman sitwayen', 'Forum sur la santé', 'Konferans jèn', 'Réunion publique'][$i % 4].' — '.$deps[$dep]['capital'],
                'description' => 'Evènman demo. Vini patisipe nan diskisyon an!', 'starts_at' => now()->addDays($i + 2)->setTime(15, 0)->utc(),
                'ends_at' => now()->addDays($i + 2)->setTime(18, 0)->utc(), 'timezone' => 'America/Port-au-Prince', 'location_name' => 'Place publique',
                'city' => $deps[$dep]['capital'], 'department' => $dep, 'country' => 'HT',
                'lat' => $deps[$dep]['lat'] + (rand(-50, 50) / 1000), 'lng' => $deps[$dep]['lng'] + (rand(-50, 50) / 1000),
                'category_id' => $categories->random()->id]);
            foreach ($citizens->random(4) as $att) {
                EventRsvp::firstOrCreate(['event_id' => $e->id, 'user_id' => $att->id], ['status' => rand(0, 2) ? 'going' : 'interested']);
            }
            $e->update(['going_count' => $e->rsvps()->where('status', 'going')->count(), 'interested_count' => $e->rsvps()->where('status', 'interested')->count()]);
        }
        Event::create(['user_id' => $citizens[8]->id, 'title' => 'Diaspora Town Hall — Miami', 'description' => 'Rencontre de la diaspora (démo).', 'starts_at' => now()->addDays(10)->utc(),
            'timezone' => 'America/New_York', 'city' => 'Miami', 'country' => 'US', 'lat' => 25.7617, 'lng' => -80.1918, 'location_name' => 'Little Haiti Cultural Center']);
        Event::create(['user_id' => $org->id, 'title' => 'Webinar: Kijan pou verifye yon enfòmasyon', 'description' => 'Fòmasyon sou verifikasyon enfòmasyon (démo).',
            'starts_at' => now()->addDays(4)->utc(), 'timezone' => 'America/Port-au-Prince', 'is_online' => true, 'online_url' => 'https://example.org/webinar', 'country' => 'HT']);

        // ---- Débat programmé ----
        $live = Live::create(['user_id' => $org->id, 'title' => 'Grand débat présidentiel (démo)', 'description' => 'Débat entre candidats fictifs.',
            'status' => 'scheduled', 'scheduled_at' => now()->addDays(5)->setTime(20, 0), 'stream_key' => Str::random(40), 'country' => 'HT']);
        $debate = Debate::create(['user_id' => $org->id, 'moderator_id' => $org->id, 'live_id' => $live->id, 'title' => 'Grand débat présidentiel (démo)',
            'description' => 'Sécurité, économie, éducation : les candidats répondent aux questions du public.', 'rules' => "Chaque candidat dispose de 2 minutes par question.\nAucune interruption.",
            'scheduled_at' => $live->scheduled_at, 'duration_minutes' => 120, 'category_id' => $categories['gouvernance']->id]);
        foreach ($candidates->take(3) as $i => $cand) {
            DebateParticipant::create(['debate_id' => $debate->id, 'user_id' => $cand->id, 'status' => 'accepted', 'speaking_order' => $i + 1]);
            LiveParticipant::create(['live_id' => $live->id, 'user_id' => $cand->id, 'role' => 'guest', 'status' => 'accepted']);
        }
        foreach ($citizens->random(4) as $cz) {
            DebateQuestion::create(['debate_id' => $debate->id, 'user_id' => $cz->id, 'body' => $qTexts[array_rand($qTexts)], 'votes_count' => rand(0, 30)]);
        }

        Live::create(['user_id' => $candidates->first()->id, 'title' => 'Chita pale ak sitwayen yo', 'description' => 'Live Q&A (démo).', 'status' => 'scheduled',
            'scheduled_at' => now()->addDays(2)->setTime(19, 0), 'stream_key' => Str::random(40), 'country' => 'HT', 'city' => 'Port-au-Prince', 'lat' => 18.5392, 'lng' => -72.3350]);

        // ---- Archives électorales (fictives) ----
        $el = Election::create(['name' => 'Élection démo 2021 — 1er tour', 'type' => 'presidential', 'held_on' => '2021-11-07', 'round' => 1,
            'description' => 'Données entièrement fictives pour démonstration.', 'source_name' => 'Données de démonstration', 'results_published' => true]);
        foreach ([['Candidat A (fictif)', 'Parti A', 412000, 38.2, true], ['Candidat B (fictif)', 'Parti B', 318000, 29.5, false], ['Candidat C (fictif)', 'Parti C', 150000, 13.9, false]] as [$n, $p, $v, $pc, $el2]) {
            $el->results()->create(['candidate_name' => $n, 'party' => $p, 'votes' => $v, 'percentage' => $pc, 'elected' => $el2]);
        }
    }

    private function proposalTitle(string $slug, int $i): string
    {
        $titles = [
            'sante' => ['Un centre de santé par commune', 'Assurance maladie universelle de base', 'Former 5 000 infirmières'],
            'education' => ['École fondamentale gratuite et de qualité', 'Cantine scolaire pour chaque élève', 'Formation continue des enseignants'],
            'economie' => ['Crédit à taux réduit pour les PME', 'Réforme de la fiscalité douanière', 'Soutien à la production locale'],
            'emploi' => ['Programme national de travaux publics', 'Apprentissage en entreprise pour les jeunes', 'Guichet unique de l\'emploi'],
            'securite' => ['Renforcement de la police de proximité', 'Désarmement et réinsertion', 'Éclairage public solaire'],
            'agriculture' => ['Réhabiliter les systèmes d\'irrigation', 'Banques de semences locales', 'Routes agricoles'],
            'environnement' => ['Reboisement de 10 % du territoire', 'Gestion des déchets municipaux', 'Protection des bassins versants'],
            'justice' => ['Réduire la détention préventive prolongée', 'Tribunaux mobiles', 'Indépendance du CSPJ'],
            'numerique' => ['Internet dans chaque lycée', 'Identité numérique citoyenne', 'Services publics en ligne'],
            'infrastructures' => ['Électricité 24h/24 dans les chefs-lieux', 'Eau potable dans chaque section communale', 'Réseau routier national'],
            'jeunesse' => ['Fonds d\'entrepreneuriat jeunesse', 'Centres culturels et sportifs', 'Service civique volontaire'],
            'diaspora' => ['Vote de la diaspora', 'Facilitation des investissements de la diaspora', 'Guichet consulaire numérique'],
            'gouvernance' => ['Décentralisation budgétaire', 'Publication des dépenses publiques', 'Lutte contre la corruption'],
        ];

        return $titles[$slug][$i % 3];
    }

    private function proposalText(string $slug): string
    {
        return "Objectif : améliorer concrètement la situation dans le domaine « $slug ».\n\nMesures principales :\n• Diagnostic et concertation avec les acteurs locaux.\n• Mise en œuvre progressive par département.\n• Suivi public des résultats sur Vwajèn.\n\n(Proposition fictive de démonstration.)";
    }
}
