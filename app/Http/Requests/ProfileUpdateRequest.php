<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'mail_integrate' => ['nullable', 'boolean'],
            'mail_integration_login' => [
                Rule::requiredIf(fn () => $this->canIntegrateMail() && $this->boolean('mail_integrate')),
                'nullable',
                'email',
                'max:255',
            ],
            'mail_integration_password' => [
                Rule::requiredIf(fn () => $this->canIntegrateMail() && $this->boolean('mail_integrate') && !$this->user()->mail_integration_password),
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);

        if (!$this->canIntegrateMail() || !$this->boolean('mail_integrate')) {
            $data['mail_integrate'] = false;
            $data['mail_integration_login'] = null;
            $data['mail_integration_password'] = null;

            return $data;
        }

        $data['mail_integrate'] = true;

        if (!$this->filled('mail_integration_password')) {
            unset($data['mail_integration_password']);
        }

        return $data;
    }

    private function canIntegrateMail(): bool
    {
        $user = $this->user();

        return $user
            && method_exists($user, 'hasAnyRole')
            && $user->hasAnyRole(['admin', 'administrateur']);
    }
}
