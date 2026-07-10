<?php

namespace App\Http\Requests\Billing;

use App\Enums\SubscriptionRequestStatus;
use App\Support\TenantContext;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubscriptionRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('manage-billing');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'plan_price_id' => ['required', 'integer', Rule::exists('plan_prices', 'id')->where('is_active', true)],
            'payer_note' => ['nullable', 'string', 'max:1000'],
            'payment_proof' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ];
    }

    /**
     * A tenant may only have one active subscription at a time, and only one
     * pending request in flight — they must cancel first before switching plans.
     */
    public function withValidator(ValidatorContract $validator): void
    {
        $validator->after(function (ValidatorContract $validator) {
            $tenant = app(TenantContext::class)->current();

            if ($tenant === null) {
                return;
            }

            if ($tenant->hasActiveSubscription()) {
                $validator->errors()->add('plan_price_id', __('billing.already_subscribed'));

                return;
            }

            if ($tenant->subscriptionRequests()->where('status', SubscriptionRequestStatus::Pending)->exists()) {
                $validator->errors()->add('plan_price_id', __('billing.pending_request_exists'));
            }
        });
    }
}
