<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\SecurityAlert;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SecurityAlertController extends Controller
{
    public function index(Request $request)
    {
        $query = SecurityAlert::with(['reporter', 'assignee']);

        // Apply filters
        if ($request->has('severity')) {
            $query->where('severity', $request->severity);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $alerts = $query->latest()->paginate(10);

        return response()->json($alerts);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'severity' => 'required|in:low,medium,high,critical',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $alert = SecurityAlert::create([
            'title' => $request->title,
            'description' => $request->description,
            'severity' => $request->severity,
            'status' => 'open',
            'reported_by' => auth()->id(),
            'assigned_to' => $request->assigned_to,
        ]);

        return response()->json([
            'message' => 'Security alert created successfully',
            'alert' => $alert->load(['reporter', 'assignee'])
        ], 201);
    }

    public function show(SecurityAlert $alert)
    {
        return response()->json($alert->load(['reporter', 'assignee']));
    }

    public function update(Request $request, SecurityAlert $alert)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'severity' => 'sometimes|required|in:low,medium,high,critical',
            'status' => 'sometimes|required|in:open,in_progress,resolved,closed',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // If status is changing to resolved, set resolved_at timestamp
        if ($request->has('status') && $request->status === 'resolved' && $alert->status !== 'resolved') {
            $alert->resolved_at = now();
        }

        $alert->update($request->all());

        return response()->json([
            'message' => 'Security alert updated successfully',
            'alert' => $alert->fresh()->load(['reporter', 'assignee'])
        ]);
    }

    public function destroy(SecurityAlert $alert)
    {
        $alert->delete();

        return response()->json(['message' => 'Security alert deleted successfully']);
    }
}