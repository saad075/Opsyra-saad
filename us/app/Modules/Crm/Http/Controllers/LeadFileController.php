<?php

namespace App\Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Crm\Models\LeadFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule as ValidationRule;

/**
 * Stored on the private `local` disk under a random filename Laravel
 * generates (never the uploaded filename — classic path-traversal/overwrite
 * footgun) and only ever served back through download() below, which forces
 * Content-Disposition: attachment — never rendered inline, so a malicious
 * HTML/SVG upload can't execute in the browser the way a plain public URL
 * could let it.
 */
class LeadFileController extends Controller
{
    private const MAX_KB = 10240; // 10 MB

    private const ALLOWED_MIMES = 'pdf,doc,docx,xls,xlsx,csv,txt,png,jpg,jpeg,gif,zip';

    public function store(Request $request)
    {
        $this->authorize('manage-leads');

        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'lead_id' => ['required', 'integer', ValidationRule::exists('leads', 'id')->where('tenant_id', $tenantId)],
            'file' => ['required', 'file', 'max:'.self::MAX_KB, 'mimes:'.self::ALLOWED_MIMES],
        ]);

        $uploaded = $request->file('file');
        $path = $uploaded->store("leads/{$tenantId}/{$validated['lead_id']}", 'local');

        $file = LeadFile::create([
            'lead_id' => $validated['lead_id'],
            'original_name' => $uploaded->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $uploaded->getMimeType(),
            'size' => $uploaded->getSize(),
            'uploaded_by' => $request->user()->id,
        ]);

        return response()->json(['status' => true, 'message' => 'File uploaded.', 'data' => $file->load('uploader:id,name')], 201);
    }

    public function download(Request $request)
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', ValidationRule::exists('lead_files', 'id')->where('tenant_id', $request->user()->tenant_id)],
        ]);

        $file = LeadFile::findOrFail($validated['id']);

        if (! Storage::disk('local')->exists($file->path)) {
            return response()->json(['status' => false, 'message' => 'File no longer exists.'], 404);
        }

        return Storage::disk('local')->download($file->path, $file->original_name);
    }

    public function destroy(Request $request)
    {
        $this->authorize('manage-leads');

        $validated = $request->validate([
            'id' => ['required', 'integer', ValidationRule::exists('lead_files', 'id')->where('tenant_id', $request->user()->tenant_id)],
        ]);

        LeadFile::findOrFail($validated['id'])->delete();

        return response()->json(['status' => true, 'message' => 'File deleted.']);
    }
}
