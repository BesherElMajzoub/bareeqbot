<?php

namespace App\Http\Requests\Connections;

use App\Models\ChannelConnection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates the POST /connections payload submitted after the user
 * selects which Pages/IG accounts to connect from the asset picker.
 *
 * The session key 'meta_oauth_result' (set by the callback) is used only
 * for server-side verification — the request payload carries the selected
 * assets explicitly to keep the controller logic clear.
 */
class StoreConnectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', ChannelConnection::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'selected_assets' => ['required', 'array', 'min:1'],
            'selected_assets.*.id' => ['required', 'string'],
            'selected_assets.*.name' => ['required', 'string'],
            'selected_assets.*.access_token' => ['required', 'string'],
            'selected_assets.*.platform' => ['required', Rule::in(['facebook', 'instagram'])],
            'selected_assets.*.instagram_business_account_id' => ['nullable', 'string'],
            'selected_assets.*.instagram_username' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'selected_assets.required' => __('connections.select_at_least_one'),
            'selected_assets.min' => __('connections.select_at_least_one'),
        ];
    }
}
