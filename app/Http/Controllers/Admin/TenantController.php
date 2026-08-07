<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Tenancy\ActivateTenant;
use App\Actions\Tenancy\CreateTenant;
use App\Actions\Tenancy\SuspendTenant;
use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class TenantController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Tenant::class);

        $tenants = QueryBuilder::for(Tenant::class)
            ->allowedFilters(AllowedFilter::exact('status'))
            ->allowedSorts('created_at', 'name')
            ->defaultSort('-created_at')
            ->with(['owner:id,name,email', 'subscriptions' => fn ($q) => $q->latest('ends_at')->with('plan:id,name')])
            ->withCount('channelConnections')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/tenants/index', ['tenants' => $tenants]);
    }

    public function suspend(Tenant $tenant, SuspendTenant $action): RedirectResponse
    {
        $this->authorize('update', $tenant);

        $action->handle($tenant);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('admin.tenant_suspended')]);

        return back();
    }

    public function store(Request $request, CreateTenant $action): RedirectResponse
    {
        $this->authorize('create', Tenant::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'email_verified_at' => now(),
        ]);

        $tenant = $action->handle($user, $validated['name']);

        $whatsappText = "أهلاً بك {$validated['name']} في منصة بريق للبوت والرد التلقائي! 🎉\n\nبيانات دخول حسابك هي:\nالبريد الإلكتروني: {$validated['email']}\nكلمة المرور: {$validated['password']}\n\nرابط تسجيل الدخول:\nhttps://bareeqplatform.site/login";
        
        $phoneDigits = preg_replace('/\D/', '', $validated['phone'] ?? '');
        $whatsappUrl = 'https://wa.me/' . ($phoneDigits ?: '') . '?text=' . urlencode($whatsappText);

        return back()->with('flash', [
            'type' => 'success',
            'message' => __('admin.tenant_created'),
            'whatsapp_url' => $whatsappUrl,
            'whatsapp_text' => $whatsappText,
        ]);
    }
}
