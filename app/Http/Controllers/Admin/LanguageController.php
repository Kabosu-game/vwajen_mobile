<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Models\TranslationOverride;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

/** Gestion des langues et des traductions de l'interface. */
class LanguageController extends Controller
{
    public function index()
    {
        $languages = Language::orderBy('position')->get();
        $sourceCount = count($this->sourceKeys());
        $coverage = $languages->mapWithKeys(fn ($l) => [$l->code => $this->coverage($l->code, $sourceCount)]);

        return view('admin.languages.index', compact('languages', 'coverage'));
    }

    private function sourceKeys(): array
    {
        $fr = lang_path('fr.json');
        $en = lang_path('en.json');

        return array_keys(json_decode(is_file($en) ? file_get_contents($en) : (is_file($fr) ? file_get_contents($fr) : '{}'), true) ?: []);
    }

    private function coverage(string $code, int $total): int
    {
        if ($code === 'fr') {
            return 100; // langue source
        }
        $file = lang_path($code.'.json');
        $count = is_file($file) ? count(array_filter(json_decode(file_get_contents($file), true) ?: [])) : 0;
        $count += TranslationOverride::where('locale', $code)->count();

        return $total ? min(100, (int) round($count * 100 / $total)) : 0;
    }

    public function store(Request $request)
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:5', 'unique:languages,code'], 'name' => ['required', 'string', 'max:50'],
            'native_name' => ['required', 'string', 'max:50']]);
        $l = Language::create($data + ['is_active' => false, 'position' => Language::max('position') + 1]);
        AuditLogger::log('language.create', null, $data);

        return back()->with('status', __('Langue ajoutée (inactive). Traduisez l\'interface avant de l\'activer.'));
    }

    public function update(Request $request, Language $language)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:50'], 'native_name' => ['required', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'], 'is_default' => ['nullable', 'boolean'], 'position' => ['nullable', 'integer']]);
        $data['is_active'] = $request->boolean('is_active');
        $data['is_default'] = $request->boolean('is_default');
        if ($data['is_default']) {
            Language::where('id', '!=', $language->id)->update(['is_default' => false]);
            $data['is_active'] = true;
        }
        $language->update($data);
        Cache::forget('languages.active');
        Cache::forget('languages.default');
        AuditLogger::log('language.update', null, ['code' => $language->code] + $data);

        return back()->with('status', __('Langue mise à jour.'));
    }

    public function translations(Request $request, Language $language)
    {
        $file = lang_path($language->code.'.json');
        $base = is_file($file) ? (json_decode(file_get_contents($file), true) ?: []) : [];
        $overrides = TranslationOverride::where('locale', $language->code)->pluck('value', 'key');
        $keys = collect($this->sourceKeys());
        if ($s = $request->query('q')) {
            $keys = $keys->filter(fn ($k) => mb_stripos($k, $s) !== false || mb_stripos($base[$k] ?? '', $s) !== false);
        }
        if ($request->boolean('missing')) {
            $keys = $keys->filter(fn ($k) => empty($base[$k]) && ! isset($overrides[$k]));
        }
        $page = max(1, (int) $request->query('page', 1));
        $paginator = new LengthAwarePaginator($keys->forPage($page, 50)->values(), $keys->count(), 50, $page, ['path' => $request->url()]);

        return view('admin.languages.translations', ['language' => $language, 'keys' => $paginator->withQueryString(), 'base' => $base, 'overrides' => $overrides]);
    }

    public function saveTranslation(Request $request, Language $language)
    {
        $data = $request->validate(['key' => ['required', 'string', 'max:500'], 'value' => ['nullable', 'string', 'max:5000']]);
        if (blank($data['value'])) {
            TranslationOverride::where(['locale' => $language->code, 'key' => $data['key']])->delete();
        } else {
            TranslationOverride::updateOrCreate(['locale' => $language->code, 'key' => $data['key']], ['value' => $data['value']]);
        }
        Cache::forget('translations.overrides.'.$language->code);

        return $this->reply($request, ['ok' => true], __('Traduction enregistrée.'));
    }
}
