<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Models\AiConversation;
use Illuminate\Support\Facades\Storage;

class AiConversationController
{
    public function index()
    {
        $conversations = AiConversation::with('customer.user')->latest()->paginate(15);

        return view('super-admin.ai-conversations.index', compact('conversations'));
    }

    public function show(AiConversation $conversation)
    {
        $conversation->load(['customer.user', 'recommendations.flight.airline']);

        return view('super-admin.ai-conversations.show', compact('conversation'));
    }
}
