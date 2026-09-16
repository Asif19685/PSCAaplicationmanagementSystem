<?php

namespace App\Http\Controllers;

use App\Models\MonitoringLog;
use App\Models\Report;
use App\Models\ReportWebsite;
use App\Models\Website;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * Display a listing of generated reports.
     */
    public function index(): View
    {
        $reports = Report::with(['user'])
            ->withCount(['reportWebsites'])
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        return view('reports.index', compact('reports'));
    }

    /**
     * Show the form for creating a new report.
     */
    public function create(): View
    {
        return view('reports.create');
    }

    /**
     * Generate and store a new health report for the given date range.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'period_from' => 'required|date',
            'period_to' => 'required|date|after_or_equal:period_from',
        ]);

        $periodFrom = $validated['period_from'] . ' 00:00:00';
        $periodTo = $validated['period_to'] . ' 23:59:59';

        // Fetch logs within the selected period
        $logs = MonitoringLog::whereBetween('checked_at', [$periodFrom, $periodTo])->get();

        $totalChecks = $logs->count();
        $successfulChecks = $logs->where('status', 'up')->count();
        $failedChecks = $totalChecks - $successfulChecks;
        $uptimePercentage = $totalChecks > 0 ? round(($successfulChecks / $totalChecks) * 100, 2) : 100.00;

        DB::beginTransaction();
        try {
            $report = Report::create([
                'title' => $validated['title'],
                'period_from' => $validated['period_from'],
                'period_to' => $validated['period_to'],
                'total_checks' => $totalChecks,
                'successful_checks' => $successfulChecks,
                'failed_checks' => $failedChecks,
                'uptime_percentage' => $uptimePercentage,
                'generated_by' => Auth::id(),
            ]);

            // Group logs per website to populate report_websites
            $websites = Website::all();
            foreach ($websites as $site) {
                $siteLogs = $logs->where('website_id', $site->id);
                $siteTotal = $siteLogs->count();
                $siteUptime = $siteLogs->where('status', 'up')->count();
                $siteDown = $siteTotal - $siteUptime;
                $sitePercentage = $siteTotal > 0 ? round(($siteUptime / $siteTotal) * 100, 2) : 0.00;
                $avgResponse = $siteTotal > 0 ? round($siteLogs->avg('total_response_time_seconds'), 3) : 0.000;

                // Only include if site has logs or was active
                if ($siteTotal > 0 || $site->is_active) {
                    ReportWebsite::create([
                        'report_id' => $report->id,
                        'website_id' => $site->id,
                        'total_checks' => $siteTotal,
                        'uptime_count' => $siteUptime,
                        'downtime_count' => $siteDown,
                        'uptime_percentage' => $sitePercentage,
                        'avg_response_time_seconds' => $avgResponse,
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('reports.show', $report)
                ->with('success', "Report '{$report->title}' generated successfully with {$totalChecks} total monitoring checks analyzed!");
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->with('error', "Failed to generate report: " . $e->getMessage());
        }
    }

    /**
     * Display the specified report details.
     */
    public function show(Report $report): View
    {
        $report->load(['user', 'reportWebsites.website']);

        // Fetch logs for this period to display breakdown
        $periodFrom = $report->period_from->format('Y-m-d') . ' 00:00:00';
        $periodTo = $report->period_to->format('Y-m-d') . ' 23:59:59';

        $incidentLogs = MonitoringLog::with('website')
            ->whereBetween('checked_at', [$periodFrom, $periodTo])
            ->where('status', '!=', 'up')
            ->orderBy('checked_at', 'desc')
            ->limit(20)
            ->get();

        return view('reports.show', compact('report', 'incidentLogs'));
    }

    /**
     * Print / Export friendly view.
     */
    public function print(Report $report): View
    {
        $report->load(['user', 'reportWebsites.website']);

        return view('reports.print', compact('report'));
    }

    /**
     * Remove the specified report from storage.
     */
    public function destroy(Report $report): RedirectResponse
    {
        $title = $report->title;
        // Delete related report_websites
        $report->reportWebsites()->delete();
        $report->delete();

        return redirect()->route('reports.index')
            ->with('success', "Report '{$title}' deleted successfully!");
    }
}
