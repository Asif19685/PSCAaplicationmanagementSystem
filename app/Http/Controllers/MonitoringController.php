<?php

namespace App\Http\Controllers;

use App\Models\MonitoringBatch;
use App\Models\MonitoringLog;
use App\Models\Website;
use App\Services\SiteMonitorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MonitoringController extends Controller
{
    /**
     * Dashboard Overview with real-time metrics and sites table.
     */
    public function dashboard(): View
    {
        $websites = Website::with(['latestLog'])
            ->orderBy('is_active', 'desc')
            ->orderBy('name', 'asc')
            ->get();

        $totalSites = $websites->count();
        $activeSites = $websites->where('is_active', true)->count();

        // Calculate live metrics based on each website's latest log
        $sitesUp = 0;
        $sitesDown = 0;
        $totalResponseTime = 0;
        $measuredCount = 0;

        foreach ($websites as $site) {
            if ($site->latestLog) {
                if ($site->latestLog->status === 'up') {
                    $sitesUp++;
                } else {
                    $sitesDown++;
                }

                if ($site->latestLog->total_response_time_seconds > 0) {
                    $totalResponseTime += $site->latestLog->total_response_time_seconds;
                    $measuredCount++;
                }
            }
        }

        $avgResponseTime = $measuredCount > 0 ? round($totalResponseTime / $measuredCount, 3) : 0.000;

        // Recent 10 logs across the system
        $recentLogs = MonitoringLog::with(['website'])
            ->orderBy('checked_at', 'desc')
            ->limit(10)
            ->get();

        // Recent batches
        $recentBatches = MonitoringBatch::with(['user'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('dashboard', compact(
            'websites',
            'totalSites',
            'activeSites',
            'sitesUp',
            'sitesDown',
            'avgResponseTime',
            'recentLogs',
            'recentBatches'
        ));
    }

    /**
     * Manual On-Demand Single Site Monitoring Test (AJAX).
     */
    public function testSingle(Request $request, Website $website, SiteMonitorService $service): JsonResponse
    {
        // Allow unlimited execution time - Chrome login can take 15-30s
        @set_time_limit(0);
        @ini_set('max_execution_time', 0);

        $forceScreenshot = $request->boolean('force_screenshot', true);
        $log = $service->checkWebsite($website, null, $forceScreenshot);

        return response()->json([
            'success' => true,
            'log'     => [
                'id'             => $log->id,
                'website_id'     => $website->id,
                'status'         => $log->status,
                'http_status_code' => $log->http_status_code,
                'response_time'  => $log->total_response_time_seconds,
                'error_message'  => $log->error_message,
                'checked_at'     => $log->checked_at->format('d M Y, h:i:s A'),
                'screenshot_url' => $log->screenshot_url,
                'console_errors' => $log->console_errors,
            ],
            'message' => "Tested '{$website->name}': " . strtoupper($log->status) . " ({$log->total_response_time_seconds}s)",
        ]);
    }

    /**
     * Manual On-Demand Batch Monitoring Execution (All or Selected Websites).
     */
    public function runBatch(Request $request, SiteMonitorService $service): JsonResponse|RedirectResponse
    {
        $websiteIds = $request->input('website_ids', []);
        $result = $service->runBatch($websiteIds, Auth::id());

        $batch = $result['batch'];
        $message = "Batch {$batch->batch_id} completed: {$result['successful']} UP, {$result['failed']} Failed out of {$result['total']} websites.";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'batch_id' => $batch->batch_id,
                'total' => $result['total'],
                'successful' => $result['successful'],
                'failed' => $result['failed'],
                'message' => $message,
            ]);
        }

        return redirect()->route('monitoring.batches')
            ->with('success', $message);
    }

    /**
     * Monitoring Logs Explorer / History with search and filters.
     */
    public function logs(Request $request): View
    {
        $query = MonitoringLog::with(['website', 'batch']);

        if ($request->filled('website_id')) {
            $query->where('website_id', $request->get('website_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->filled('batch_id')) {
            $query->where('batch_id', $request->get('batch_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('checked_at', '>=', $request->get('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('checked_at', '<=', $request->get('date_to'));
        }

        $logs = $query->orderBy('checked_at', 'desc')->paginate(20)->withQueryString();
        $websites = Website::orderBy('name', 'asc')->get();

        return view('monitoring.logs', compact('logs', 'websites'));
    }

    /**
     * Fetch Single Log Details for Modal Evidence Viewer (AJAX).
     */
    public function getLogModal(MonitoringLog $log): JsonResponse
    {
        $log->load('website');

        return response()->json([
            'id' => $log->id,
            'website_name' => $log->website?->name ?? 'Unknown',
            'website_url' => $log->website?->url ?? '#',
            'status' => $log->status,
            'http_status_code' => $log->http_status_code,
            'response_time' => $log->total_response_time_seconds,
            'error_message' => $log->error_message,
            'console_errors' => $log->console_errors,
            'screenshot_url' => $log->screenshot_url,
            'checked_at' => $log->checked_at->format('d M Y, h:i:s A'),
            'batch_id' => $log->batch_id,
        ]);
    }

    /**
     * List Monitoring Batches.
     */
    public function batches(): View
    {
        $batches = MonitoringBatch::with(['user'])
            ->withCount(['logs'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('monitoring.batches', compact('batches'));
    }

    /**
     * View Details of a Specific Batch.
     */
    public function batchDetail(MonitoringBatch $batch): View
    {
        $logs = MonitoringLog::with(['website'])
            ->where('batch_id', $batch->batch_id)
            ->orderBy('checked_at', 'asc')
            ->get();

        return view('monitoring.batch-detail', compact('batch', 'logs'));
    }
}
