<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Requests\PublicSite\ContactMessageRequest;
use App\Models\ContactMessage;
use App\Services\AuthService;
use Illuminate\Http\RedirectResponse;

class ContactMessageController
{
    public function store(ContactMessageRequest $request, AuthService $authService): RedirectResponse
    {
        $user = $request->user();
        $customerId = $user !== null && $authService->resolveRole($user) === AuthService::ROLE_CUSTOMER
            ? $user->customer?->id
            : null;

        ContactMessage::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'type' => $request->validated('type'),
            'message' => $request->validated('message'),
            'customer_id' => $customerId,
            'status' => 'new',
        ]);

        return back()->with('status', 'Thank you. Your message has been sent.');
    }
}
