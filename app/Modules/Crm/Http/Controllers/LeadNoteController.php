<?php

namespace App\Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Crm\Models\LeadNote;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule as ValidationRule;

/**
 * lead_id is a direct FK (not the polymorphic notable_type/notable_id the
 * earlier, reverted CRM attempt used) — this phase only ever attaches
 * notes to leads. Listing happens inline via LeadController::show(), not a
 * separate endpoint, same as the earlier attempt's Company/Opportunity
 * detail pages.
 */
class LeadNoteController extends Controller
{
    public function store(Request $request)
    {
        $this->authorize('manage-leads');

        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'lead_id' => ['required', 'integer', ValidationRule::exists('leads', 'id')->where('tenant_id', $tenantId)],
            'body' => ['required', 'string'],
        ]);

        $note = LeadNote::create([
            'lead_id' => $validated['lead_id'],
            'body' => $validated['body'],
            'created_by' => $request->user()->id,
        ]);

        return response()->json(['status' => true, 'message' => 'Note added.', 'data' => $note->load('author:id,name')], 201);
    }

    public function update(Request $request)
    {
        $this->authorize('manage-leads');

        $validated = $request->validate([
            'id' => ['required', 'integer', ValidationRule::exists('lead_notes', 'id')->where('tenant_id', $request->user()->tenant_id)],
            'body' => ['required', 'string'],
        ]);

        $note = LeadNote::findOrFail($validated['id']);
        $note->update(['body' => $validated['body']]);

        return response()->json(['status' => true, 'message' => 'Note updated.', 'data' => $note]);
    }

    public function destroy(Request $request)
    {
        $this->authorize('manage-leads');

        $validated = $request->validate([
            'id' => ['required', 'integer', ValidationRule::exists('lead_notes', 'id')->where('tenant_id', $request->user()->tenant_id)],
        ]);

        LeadNote::findOrFail($validated['id'])->delete();

        return response()->json(['status' => true, 'message' => 'Note deleted.']);
    }
}
