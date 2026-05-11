<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @mixin \Illuminate\Http\Request
 */
class ChatMessageRequest extends FormRequest
{
    /**
     * Meghatározza, hogy a felhasználó jogosult-e a kérés elküldésére.
     */
    public function authorize(): bool
    {
        // A jogosultság-ellenőrzést a Middleware-ek már elvégzik a Controller szintjén.
        return true;
    }

    /**
     * Adatok előkészítése a validációra (Tisztítás).
     */
    protected function prepareForValidation()
    {
        if (request()->has('content')) {
            $this->merge([
                'content' => strip_tags(request()->input('content')),
            ]);
        }
    }

    /**
     * A kérésre vonatkozó validációs szabályok.
     */
    public function rules(): array
    {
        return [
            'content' => 'required|string|max:5000',
        ];
    }
}
