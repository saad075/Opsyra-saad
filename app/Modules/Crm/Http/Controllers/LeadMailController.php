<?php

namespace App\Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Crm\Models\LeadEmail;
use App\Modules\Crm\Notifications\LeadEmailNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule as ValidationRule;

class LeadMailController extends Controller
{
    public function store(Request $request)
    {
        $this->authorize('manage-leads');

        $validated = $request->validate([
            'lead_id' => ['required', 'integer', ValidationRule::exists('leads', 'id')->where('tenant_id', $request->user()->tenant_id)],
            'to_email' => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
        ]);

        Notification::route('mail', $validated['to_email'])
            ->notify(new LeadEmailNotification($validated['subject'], $validated['body']));

        $email = LeadEmail::create([
            ...$validated,
            'sent_by' => $request->user()->id,
        ]);

        return response()->json(['status' => true, 'message' => 'Email sent.', 'data' => $email->load('sender:id,name')], 201);
    }
}
