<?php

namespace App\Http\Requests\Rules;

use App\Enums\RuleActionType;
use App\Enums\RuleMatchType;
use App\Enums\RuleTargetScope;
use App\Enums\WebhookSurface;
use App\Support\TenantContext;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class RuleFormRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = app(TenantContext::class)->id();

        return [
            'channel_connection_id' => [
                'required', 'integer',
                Rule::exists('channel_connections', 'id')->where('tenant_id', $tenantId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'trigger_surface' => ['required', Rule::enum(WebhookSurface::class)],
            'target_scope' => ['required', Rule::enum(RuleTargetScope::class)],
            'target_ref' => ['nullable', 'string', 'max:255', 'required_if:target_scope,specific'],
            'match_type' => ['required', Rule::enum(RuleMatchType::class)],
            'keyword' => ['nullable', 'string', 'max:255', 'required_unless:match_type,any'],
            'case_sensitive' => ['boolean'],
            'priority' => ['integer', 'min:0', 'max:1000'],
            'is_active' => ['boolean'],
            'actions' => ['required', 'array', 'min:1'],
            'actions.*.action_type' => ['required', Rule::enum(RuleActionType::class)],
            'actions.*.message_template' => ['required', 'string', 'max:2000'],
            'actions.*.delay_seconds' => ['integer', 'min:0', 'max:86400'],
        ];
    }

    /**
     * Comment rules may only reply publicly/privately; story rules may only DM.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $isComment = $this->input('trigger_surface') === WebhookSurface::PostComment->value;

            $allowed = $isComment
                ? [RuleActionType::PublicReply->value, RuleActionType::PrivateReply->value]
                : [RuleActionType::Dm->value];

            foreach ((array) $this->input('actions', []) as $index => $action) {
                if (! in_array($action['action_type'] ?? null, $allowed, true)) {
                    $validator->errors()->add(
                        "actions.{$index}.action_type",
                        __('rules.invalid_action_for_surface'),
                    );
                }
            }
        });
    }
}
