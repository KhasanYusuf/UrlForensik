<?php

namespace App\Http\Controllers;

use App\Models\MonitoredSite;
use App\Http\Requests\StoreKorbanRequest;
use App\Http\Requests\UpdateKorbanRequest;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Services\DefacementDetectionService;

class MonitoredSiteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('monitored_sites.index');
    }

    /**
     * Get data for DataTables (Server-side processing)
     */
    public function getData(Request $request)
    {
        if ($request->ajax()) {
            $data = MonitoredSite::select('monitored_sites.*')
                ->orderBy('id_site', 'desc');

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('opsi', function ($row) {
                    $btn = '<div class="dropdown">
                        <button class="btn btn-sm btn-light" type="button" id="dropdownMenu' . $row->id_site . '" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="dropdownMenu' . $row->id_site . '">
                            <li><a class="dropdown-item edit-btn" href="javascript:void(0)" data-id="' . $row->id_site . '">
                                <i class="fas fa-edit me-2"></i>Edit
                            </a></li>
                            <li><a class="dropdown-item run-check" href="javascript:void(0)" data-id="' . $row->id_site . '">
                                <i class="fas fa-bolt me-2"></i>Run Check
                            </a></li>
                            <li><a class="dropdown-item delete-btn text-danger" href="javascript:void(0)" data-id="' . $row->id_site . '">
                                <i class="fas fa-trash me-2"></i>Delete
                            </a></li>
                        </ul>
                    </div>';
                    return $btn;
                })
                ->rawColumns(['opsi'])
                ->make(true);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreKorbanRequest $request)
    {
        try {
            $data = $request->validated();

            if (! isset($data['nama_korban'])) {
                $data['nama_korban'] = $data['site_url'] ?? null;
            }

            MonitoredSite::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Data monitored site berhasil ditambahkan!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the specified resource for editing (AJAX)
     */
    public function edit($id)
    {
        try {
            $korban = MonitoredSite::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'id_site' => $korban->id_site,
                    'site_url' => $korban->site_url,
                    'ip_address' => $korban->ip_address,
                    'status' => $korban->status,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan!'
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateKorbanRequest $request, $id)
    {
        try {
            $korban = MonitoredSite::findOrFail($id);
            $original = $korban->getAttributes();
            $validated = $request->validated();
            $korban->update($validated);

            // Record activity log with changes
            $changes = [];
            foreach ($validated as $key => $value) {
                $orig = $original[$key] ?? null;
                if ($orig !== $value) {
                    $changes[$key] = ['from' => $orig, 'to' => $value];
                }
            }
            if (! empty($changes)) {
                ActivityLog::create([
                    'user_id' => Auth::id(),
                    'action' => 'update_monitored_site',
                    'target_type' => 'monitored_site',
                    'target_id' => $korban->id_site,
                    'case_id' => null,
                    'ip_address' => request()->ip(),
                    'changes' => $changes,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Data monitored site berhasil diupdate!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $korban = MonitoredSite::findOrFail($id);

            // Check if monitored site has related kasus
            if ($korban->kasus()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak dapat menghapus monitored site karena masih memiliki data kasus terkait!'
                ], 400);
            }

            $korban->delete();

            return response()->json([
                'success' => true,
                'message' => 'Data monitored site berhasil dihapus!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Refresh baseline hash for a monitored site by fetching the site contents
     * and calculating SHA-256 on the response body. Also sets status to 'UP'.
     */
    public function refreshBaseline($id)
    {
        try {
            $site = MonitoredSite::findOrFail($id);
            $client = new Client(['timeout' => 15, 'verify' => false]);
            try {
                $response = $client->get($site->site_url);
            } catch (\Exception $e) {
                Log::error('Failed to fetch site for baseline refresh: ' . $e->getMessage());
                return redirect()->route('monitored_sites.index')->with('error', 'Gagal menghubungi site: ' . $e->getMessage());
            }

            $body = (string) $response->getBody();
            $hash = hash('sha256', $body);

            // Save baseline HTML to storage
            $path = "baselines/{$site->id_site}_baseline.html";
            try {
                // Use public disk so it can be inspected if needed; change to 'local' if preferred
                Storage::disk('public')->put($path, $body);
            } catch (\Exception $e) {
                Log::error('Failed to save baseline file: ' . $e->getMessage());
                return redirect()->route('monitored_sites.index')->with('error', 'Gagal menyimpan file baseline: ' . $e->getMessage());
            }

            // Parse baseline for scripts/iframes and allowed domains
            $scriptPattern = '#<script\b[^>]*>(.*?)</script>#is';
            $iframePattern = '#<iframe\b[^>]*>(.*?)</iframe>#is';

            // Count script and iframe tags
            preg_match_all($scriptPattern, $body, $scriptMatches);
            preg_match_all($iframePattern, $body, $iframeMatches);
            $scriptCount = count($scriptMatches[0] ?? []);
            $iframeCount = count($iframeMatches[0] ?? []);

            // Extract domains from src attributes in script and iframe tags
            $srcPattern = '/<(?:script|iframe)\b[^>]*\ssrc=["\']([^"\']+)["\'][^>]*>/i';
            preg_match_all($srcPattern, $body, $srcMatches);
            $domains = [];
            foreach ($srcMatches[1] ?? [] as $src) {
                $host = parse_url($src, PHP_URL_HOST);
                if ($host) {
                    // normalize to lower-case host
                    $domains[] = strtolower($host);
                }
            }
            $uniqueDomains = array_values(array_unique($domains));

            $site->baseline_file_path = $path;
            $site->baseline_hash = $hash;
            $site->last_checked_at = now();
            $site->status = 'UP';
            $site->baseline_script_count = $scriptCount + $iframeCount;
            $site->allowed_domains = $uniqueDomains;
            $site->save();

            // Log activity
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'refresh_baseline',
                'target_type' => 'monitored_site',
                'target_id' => $site->id_site,
                'case_id' => null,
                'ip_address' => request()->ip(),
                'changes' => ['baseline_hash' => $hash, 'baseline_file_path' => $path],
            ]);

            return redirect()->route('monitored_sites.index')->with('success', 'Baseline updated and file saved');
        } catch (\Exception $e) {
            Log::error('Unexpected error refreshing baseline: ' . $e->getMessage());
            return redirect()->route('monitored_sites.index')->with('error', 'Gagal memperbarui baseline: ' . $e->getMessage());
        }
    }

    /**
     * Run a single site integrity check using the DefacementDetectionService.
     */
    public function checkSite($id)
    {
        try {
            $site = MonitoredSite::findOrFail($id);

            // Use service (resolve from container)
            $service = app(DefacementDetectionService::class);
            $service->checkSite($site);

            return response()->json(['success' => true, 'message' => 'Integrity check completed.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Check failed: ' . $e->getMessage()], 500);
        }
    }
}
