<?php

namespace App\Http\Middleware;

use App\Support\TenantContext;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * Locales that render right-to-left.
     *
     * @var list<string>
     */
    protected array $rtlLocales = ['ar', 'fa', 'he', 'ur'];

    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $locale = app()->getLocale();
        $tenant = app(TenantContext::class)->current();
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
                'currentTenant' => $tenant?->only(['id', 'name', 'slug', 'status']),
                'tenants' => $user
                    ? $user->tenants()->get(['tenants.id', 'tenants.name', 'tenants.slug'])
                    : [],
            ],
            'locale' => $locale,
            'direction' => in_array($locale, $this->rtlLocales, true) ? 'rtl' : 'ltr',
            'translations' => $this->translations($locale),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    /**
     * Load the flat JSON translation dictionary for the given locale.
     *
     * @return array<string, string>
     */
    protected function translations(string $locale): array
    {
        $path = lang_path("{$locale}.json");

        if (! is_file($path)) {
            return [];
        }

        return json_decode((string) file_get_contents($path), true) ?: [];
    }
}
