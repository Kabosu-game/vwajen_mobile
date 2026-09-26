<?php

namespace Database\Seeders;

use App\Http\Controllers\Admin\SettingController;
use App\Models\Category;
use App\Models\Language;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Setting;
use Illuminate\Database\Seeder;

/** Données de base indispensables : rôles, permissions, catégories, langues, paramètres. */
class PlatformSeeder extends Seeder
{
    public const PERMISSIONS = [
        'users.view' => ['Voir les utilisateurs', 'users'],
        'users.manage' => ['Gérer les utilisateurs', 'users'],
        'roles.manage' => ['Gérer les rôles et permissions', 'users'],
        'candidates.manage' => ['Gérer les candidats et organisations', 'civic'],
        'verifications.manage' => ['Traiter les vérifications', 'civic'],
        'sources.verify' => ['Vérifier les sources', 'civic'],
        'officials.manage' => ['Documenter les responsables publics', 'civic'],
        'programs.manage' => ['Gérer les programmes', 'civic'],
        'debates.manage' => ['Organiser et gérer les débats', 'civic'],
        'elections.manage' => ['Gérer les archives électorales', 'civic'],
        'moderation.manage' => ['Modération (signalements, sanctions, appels)', 'moderation'],
        'content.delete' => ['Supprimer des contenus', 'moderation'],
        'lives.moderate' => ['Modérer les lives', 'moderation'],
        'communities.manage' => ['Gérer les communautés', 'moderation'],
        'stats.view' => ['Voir les statistiques', 'system'],
        'audit.view' => ['Voir le journal d\'audit', 'system'],
        'settings.manage' => ['Paramètres, catégories, langues, annonces', 'system'],
        'system.monitor' => ['Monitoring système', 'system'],
    ];

    public const ROLES = [
        'superadmin' => ['Super-administrateur', 'Toutes les permissions', ['*']],
        'admin' => ['Administrateur', 'Administration complète hors super-admin', ['*']],
        'moderator' => ['Modérateur', 'Modération des contenus et des comptes', ['users.view', 'moderation.manage', 'content.delete', 'lives.moderate', 'communities.manage', 'stats.view']],
        'verifier' => ['Vérificateur', 'Vérification des comptes et des sources', ['users.view', 'verifications.manage', 'sources.verify', 'candidates.manage', 'officials.manage']],
        'analyst' => ['Analyste', 'Statistiques et audit en lecture', ['stats.view', 'audit.view']],
        'debate_organizer' => ['Organisateur de débats', 'Création et gestion des débats', ['debates.manage']],
    ];

    public const CATEGORIES = [
        ['sante', 'Sante', 'Santé', 'Health', 'heart', '#e11d48'],
        ['education', 'Edikasyon', 'Éducation', 'Education', 'book', '#2563eb'],
        ['economie', 'Ekonomi', 'Économie', 'Economy', 'chart', '#059669'],
        ['emploi', 'Travay', 'Emploi', 'Employment', 'briefcase', '#d97706'],
        ['securite', 'Sekirite', 'Sécurité', 'Security', 'shield', '#475569'],
        ['agriculture', 'Agrikilti', 'Agriculture', 'Agriculture', 'leaf', '#65a30d'],
        ['environnement', 'Anviwònman', 'Environnement', 'Environment', 'globe', '#0d9488'],
        ['justice', 'Jistis', 'Justice', 'Justice', 'scale', '#7c3aed'],
        ['numerique', 'Nimerik', 'Numérique', 'Digital', 'cpu', '#0891b2'],
        ['infrastructures', 'Enfrastrikti', 'Infrastructures', 'Infrastructure', 'road', '#78716c'],
        ['jeunesse', 'Jènès', 'Jeunesse', 'Youth', 'spark', '#db2777'],
        ['diaspora', 'Dyaspora', 'Diaspora', 'Diaspora', 'plane', '#1d4ed8'],
        ['gouvernance', 'Gouvènans', 'Gouvernance', 'Governance', 'landmark', '#b45309'],
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $name => [$label, $group]) {
            Permission::updateOrCreate(['name' => $name], ['label' => $label, 'group' => $group]);
        }
        $all = Permission::pluck('id', 'name');
        foreach (self::ROLES as $name => [$label, $desc, $perms]) {
            $role = Role::updateOrCreate(['name' => $name], ['label' => $label, 'description' => $desc, 'is_system' => true]);
            $ids = $perms === ['*'] ? $all->values()->all() : $all->only($perms)->values()->all();
            if ($name === 'admin') {
                $ids = $all->except(['roles.manage'])->values()->all();
                $ids[] = $all['roles.manage']; // les admins gèrent aussi les rôles (hors super-admin, protégé dans le contrôleur)
            }
            $role->permissions()->sync($ids);
        }

        foreach (self::CATEGORIES as $i => [$slug, $ht, $fr, $en, $icon, $color]) {
            Category::updateOrCreate(['slug' => $slug], ['name_ht' => $ht, 'name_fr' => $fr, 'name_en' => $en, 'icon' => $icon, 'color' => $color, 'position' => $i, 'is_active' => true]);
        }

        foreach ([['ht', 'Créole haïtien', 'Kreyòl ayisyen', true], ['fr', 'Français', 'Français', false], ['en', 'Anglais', 'English', false]] as $i => [$code, $name, $native, $default]) {
            Language::updateOrCreate(['code' => $code], ['name' => $name, 'native_name' => $native, 'is_active' => true, 'is_default' => $default, 'position' => $i]);
        }

        foreach (SettingController::DEFINITIONS as $key => [$type, $group, $default]) {
            if (! Setting::find($key)) {
                Setting::put($key, $default, $type, $group);
            }
        }
    }
}
