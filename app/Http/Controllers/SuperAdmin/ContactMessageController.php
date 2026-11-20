<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Models\ContactMessage;

class ContactMessageController
{
    public function index()
    {
        $messages = ContactMessage::with('customer.user')->latest()->paginate(15);

        return view('super-admin.messages.index', compact('messages'));
    }

    public function markHandled(ContactMessage $message)
    {
        $message->update(['status' => 'handled']);

        return back()->with('success', 'Message marked as handled.');
    }
}
