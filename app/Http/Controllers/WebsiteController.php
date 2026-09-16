<?php

namespace App\Http\Controllers;

use App\Models\Website;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class WebsiteController extends Controller
{
    /**
     * Display a listing of websites.
     */
    public function index(Request $request): View
    {
        $query = Website::with(['latestLog'])->withCount(['logs']);

        // Search by name or URL
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('url', 'like', "%{$search}%");
            });
        }

        // Filter by active status
        if ($request->filled('status')) {
            $status = $request->get('status');
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Filter by requires_login
        if ($request->filled('type')) {
            if ($request->get('type') === 'login') {
                $query->where('requires_login', true);
            } elseif ($request->get('type') === 'http') {
                $query->where('requires_login', false);
            }
        }

        $websites = $query->orderBy('name', 'asc')->paginate(15)->withQueryString();

        return view('websites.index', compact('websites'));
    }

    /**
     * Show the form for creating a new website.
     */
    public function create(): View
    {
        return view('websites.create');
    }

    /**
     * Store a newly created website in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url|max:1000',
            'requires_login' => 'nullable|boolean',
            'login_url' => 'nullable|url|max:1000',
            'username' => 'nullable|string|max:255',
            'password' => 'nullable|string|max:255',
            'username_field' => 'nullable|string|max:150',
            'password_field' => 'nullable|string|max:150',
            'submit_button' => 'nullable|string|max:150',
            'expected_text' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['requires_login'] = $request->boolean('requires_login');
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['created_by'] = Auth::id();

        $website = Website::create($validated);

        return redirect()->route('websites.index')
            ->with('success', "Website '{$website->name}' registered successfully!");
    }

    /**
     * Show the form for editing the specified website.
     */
    public function edit(Website $website): View
    {
        return view('websites.edit', compact('website'));
    }

    /**
     * Update the specified website in storage.
     */
    public function update(Request $request, Website $website): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url|max:1000',
            'requires_login' => 'nullable|boolean',
            'login_url' => 'nullable|url|max:1000',
            'username' => 'nullable|string|max:255',
            'password' => 'nullable|string|max:255',
            'username_field' => 'nullable|string|max:150',
            'password_field' => 'nullable|string|max:150',
            'submit_button' => 'nullable|string|max:150',
            'expected_text' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['requires_login'] = $request->boolean('requires_login');
        $validated['is_active'] = $request->boolean('is_active', true);

        // Keep existing password if not updated
        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $website->update($validated);

        return redirect()->route('websites.index')
            ->with('success', "Website '{$website->name}' updated successfully!");
    }

    /**
     * Remove the specified website from storage.
     */
    public function destroy(Website $website): RedirectResponse
    {
        $name = $website->name;
        $website->delete();

        return redirect()->route('websites.index')
            ->with('success', "Website '{$name}' deleted successfully!");
    }

    /**
     * Toggle active status.
     */
    public function toggleStatus(Website $website): RedirectResponse
    {
        $website->update(['is_active' => !$website->is_active]);
        $statusText = $website->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "Website '{$website->name}' has been {$statusText}.");
    }
}
