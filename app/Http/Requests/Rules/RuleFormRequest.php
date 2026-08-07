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
            'auto_like_comment' => ['boolean'],
            'is_active' => ['boolean'],
            'actions' => ['required', 'array', 'min:1'],
            'actions.*.action_type' => ['required', Rule::enum(RuleActionType::class)],
            'actions.*.message_template' => ['required', 'string', 'max:2000'],
            'actions.*.delay_seconds' => ['integer', 'min:0', 'max:86400'],
            'actions.*.image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:5120'],
        ];
    }

    /**
     * Comment rules may only reply publicly/privately; non-comment surfaces
     * (stories, plain messages) may only DM, and have no post/media to
     * scope to.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $isComment = $this->input('trigger_surface') === WebhookSurface::PostComment->value;

            $allowed = $isComment
                ? [RuleActionType::PublicReply->value, RuleActionType::PrivateReply->value]
                : [RuleActionType::Dm->value];

            foreach ((array) ($this->all()['actions'] ?? []) as $index => $action) {
                if (! in_array($action['action_type'] ?? null, $allowed, true)) {
                    $validator->errors()->add(
                        "actions.{$index}.action_type",
                        __('rules.invalid_action_for_surface'),
                    );
                }

                if (($action['action_type'] ?? null) === RuleActionType::PublicReply->value && isset($action['image'])) {
                    $validator->errors()->add(
                        "actions.{$index}.image",
                        __('rules.image_not_supported_for_public_reply'),
                    );
                }
            }

            if (! $isComment && $this->input('target_scope') === RuleTargetScope::Specific->value) {
                $validator->errors()->add('target_scope', __('rules.target_scope_requires_comment'));
            }
        });
    }
}
