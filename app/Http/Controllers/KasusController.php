<?php

namespace App\Http\Controllers;

use App\Models\Kasus;
use App\Models\MonitoredSite;
use App\Http\Requests\StoreKasusRequest;
use App\Http\Requests\UpdateKasusRequest;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Models\BuktiDigital;
use App\Models\TindakanForensik;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Http;
use Jfcherng\Diff\DiffHelper;

class KasusController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $korbans = MonitoredSite::orderBy('site_url')->get();
        return view('kasus.index', compact('korbans'));
    }

    /**
     * Get data for DataTables (Server-side processing)
     */
    public function getData(Request $request)
    {
        if ($request->ajax()) {
            $data = Kasus::with('korban')
                ->select('kasus.*')
                ->orderBy('id_kasus', 'desc');

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('site_url', function ($row) {
                    return $row->korban->site_url ?? '-';
                })
                ->addColumn('tanggal_kejadian_formatted', function ($row) {
                    return $row->tanggal_kejadian->format('d-m-Y');
                })
                ->addColumn('status_badge', function ($row) {
                    $badge = $row->status_kasus == 'Open'
                        ? '<span class="badge bg-warning text-dark">Open</span>'
                        : '<span class="badge bg-success">Closed</span>';
                    return $badge;
                })
                ->addColumn('opsi', function ($row) {
                    $btn = '<div class="dropdown">
                        <button class="btn btn-sm btn-light" type="button" id="dropdownMenu' . $row->id_kasus . '" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="dropdownMenu' . $row->id_kasus . '">
                            <li><a class="dropdown-item" href="' . route('kasus.show', $row->id_kasus) . '">
                                <i class="fas fa-eye me-2"></i>Lihat
                            </a></li>
                            <li><a class="dropdown-item edit-btn" href="javascript:void(0)" data-id="' . $row->id_kasus . '">
                                <i class="fas fa-edit me-2"></i>Edit
                            </a></li>
                            <li><a class="dropdown-item delete-btn text-danger" href="javascript:void(0)" data-id="' . $row->id_kasus . '">
                                <i class="fas fa-trash me-2"></i>Delete
                            </a></li>
                        </ul>
                    </div>';
                    return $btn;
                })
                ->rawColumns(['status_badge', 'opsi'])
                ->make(true);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreKasusRequest $request)
    {
        try {
            Kasus::create($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Data kasus berhasil ditambahkan!'
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
            $kasus = Kasus::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'id_kasus' => $kasus->id_kasus,
                    'id_site' => $kasus->id_site,
                    'jenis_kasus' => $kasus->jenis_kasus,
                    'tanggal_kejadian' => $kasus->tanggal_kejadian->format('Y-m-d'),
                    'deskripsi_kasus' => $kasus->deskripsi_kasus,
                    'status_kasus' => $kasus->status_kasus,
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
    public function update(UpdateKasusRequest $request, $id)
    {
        try {
            $kasus = Kasus::findOrFail($id);
            $oldStatus = $kasus->status_kasus;
            $validated = $request->validated();
            $kasus->update($validated);

            // regular update flow only

            // If status changed, record activity
            if (isset($validated['status_kasus']) && $validated['status_kasus'] !== $oldStatus) {
                ActivityLog::create([
                    'user_id' => auth()->id(),
                    'action' => 'update_status',
                    'target_type' => 'kasus',
                    'target_id' => $kasus->id_kasus,
                    'case_id' => $kasus->id_kasus,
                    'ip_address' => request()->ip(),
                    'changes' => ['from' => $oldStatus, 'to' => $validated['status_kasus']],
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Data kasus berhasil diupdate!'
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
            $kasus = Kasus::findOrFail($id);
            $kasus->delete();

            return response()->json([
                'success' => true,
                'message' => 'Data kasus berhasil dihapus!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified Kasus details including forensic analysis tab.
     */
    public function show($id)
    {
        $kasus = Kasus::with(['korban', 'buktiDigital', 'tindakanForensik'])->findOrFail($id);

        // Evidence lists for the diff viewer
        $sourceBukits = $kasus->buktiDigital->where('jenis_bukti', 'source_html')->values();
        $baselineBukits = $kasus->buktiDigital->where('jenis_bukti', 'baseline_html')->values();
        $snapshotBukits = $sourceBukits; // alias for clarity in the view

        // Load activity logs for this case (chain of custody)
        $activityLogs = \App\Models\ActivityLog::where('case_id', $kasus->id_kasus)
            ->orderBy('created_at', 'desc')
            ->get();

        // Prepare diff output using jfcherng/php-diff when available
        $diffHtml = '<div class="alert alert-secondary">No diff available.</div>';
        $diffLeft = '';
        $diffRight = '';
        $diffUsingLibrary = false;

        try {
            // Preferred: compare baseline HTML (from evidence or monitored_sites) vs the defaced snapshot saved as source_html
            $baselineContent = '';
            $defacedContent = '';

            // Choose baseline evidence: prefer bukti with jenis 'baseline_html'
            $baselineBukti = null;
            if ($baselineBukits->count() > 0) {
                // pick the most recent baseline bukti if multiple
                $baselineBukti = $baselineBukits->sortByDesc('created_date')->first();
            }
            if ($baselineBukti && !empty($baselineBukti->file_url) && Storage::disk('public')->exists($baselineBukti->file_url)) {
                $baselineContent = Storage::disk('public')->get($baselineBukti->file_url);
            } else {
                // Fallback: use monitored site's baseline_file_path if present
                $siteBaselinePath = $kasus->korban->baseline_file_path ?? null;
                if (!empty($siteBaselinePath) && Storage::disk('public')->exists($siteBaselinePath)) {
                    $baselineContent = Storage::disk('public')->get($siteBaselinePath);
                }
            }

            // Find defaced snapshot evidence (jenis 'source_html') - prefer the most recent
            $defaceBukti = null;
            if ($snapshotBukits->count() > 0) {
                $defaceBukti = $snapshotBukits->sortByDesc('created_date')->first();
            }
            if ($defaceBukti && !empty($defaceBukti->file_url) && Storage::disk('public')->exists($defaceBukti->file_url)) {
                $defacedContent = Storage::disk('public')->get($defaceBukti->file_url);
            } else {
                // If no defaced snapshot is saved, as a last resort fetch live site content
                $siteUrl = $kasus->korban->site_url ?? null;
                if ($siteUrl) {
                    try {
                        $resp = Http::timeout(10)->get($siteUrl);
                        if ($resp->ok()) {
                            $defacedContent = $resp->body();
                        }
                    } catch (\Exception $e) {
                        $defacedContent = '';
                    }
                }
            }

            // If we have neither baseline nor defaced content, show informative message
            if (empty($baselineContent) && empty($defacedContent)) {
                $diffHtml = '<div class="alert alert-info">Tidak ada data baseline atau snapshot untuk membuat diff.</div>';
            } else {
                // Attempt to render SideBySide diff using library when available
                if (class_exists(DiffHelper::class)) {
                    $detailOptions = ['detailLevel' => 'word'];
                    $renderOptions = [];
                    try {
                        $diffHtml = DiffHelper::calculate($baselineContent, $defacedContent, 'SideBySide', $detailOptions, $renderOptions);
                        $diffUsingLibrary = true;
                    } catch (\Throwable $e) {
                        $diffUsingLibrary = false;
                    }
                } else {
                    $diffUsingLibrary = false;
                }

                // Expose raw content for fallback renderer
                $diffLeft = $baselineContent;
                $diffRight = $defacedContent;
            }
        } catch (\Throwable $e) {
            $diffHtml = '<div class="alert alert-danger">Gagal membuat diff: ' . e($e->getMessage()) . '</div>';
        }

        return view('kasus.show', compact('kasus', 'sourceBukits', 'baselineBukits', 'snapshotBukits', 'activityLogs', 'diffHtml', 'diffLeft', 'diffRight', 'diffUsingLibrary'));
    }

    /**
     * Resolve an incident (Recovery) — separate endpoint to avoid Update request validation.
     */
    public function resolve(Request $request, $id)
    {
        try {
            $kasus = Kasus::with('korban')->findOrFail($id);

            $site = $kasus->korban;
            if ($site) {
                $site->status = 'UP';

                if ($request->boolean('update_baseline')) {
                    try {
                        if (!empty($site->site_url)) {
                            $resp = Http::timeout(15)->get($site->site_url);
                            if ($resp->ok()) {
                                $content = $resp->body();
                                $site->baseline_hash = hash('sha256', $content);
                            }
                        }
                    } catch (\Exception $e) {
                        // ignore network errors for baseline update
                    }
                }

                $site->save();
            }

            // create TindakanForensik manually
            try {
                $t = new TindakanForensik();
                $t->id_kasus = $kasus->id_kasus;
                $t->jenis_tindakan = 'Recovery';
                $t->hasil_tindakan = $request->input('recovery_note', 'Recovery completed');
                $t->petugas_forensik = auth()->user()?->name ?? null;
                $t->status_tindakan = 'Completed';
                $t->waktu_pelaksanaan = now();
                $t->save();
            } catch (\Throwable $e) {
                // ignore
            }

            // Close the case
            $kasus->status_kasus = 'Closed';
            $kasus->save();

            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'resolve_incident',
                'target_type' => 'kasus',
                'target_id' => $kasus->id_kasus,
                'case_id' => $kasus->id_kasus,
                'ip_address' => request()->ip(),
                'changes' => ['status' => 'Closed', 'site_status' => $site->status ?? null],
            ]);

            return response()->json(['success' => true, 'message' => 'Kasus berhasil di-resolve']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal melakukan resolve: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Generate incident report PDF for a Kasus
     */
    public function generateReport($id)
    {
        $kasus = Kasus::with(['korban', 'buktiDigital', 'tindakanForensik'])->findOrFail($id);

        // Chronology from tindakan_forensik ordered by waktu_pelaksanaan
        $chronology = $kasus->tindakanForensik->sortBy('waktu_pelaksanaan')->values();

        // Evidence list with hashes
        $evidences = $kasus->buktiDigital->map(function ($b) {
            $path = $b->file_url;
            $md5 = null;
            $sha256 = null;
            if (Storage::disk('public')->exists($path)) {
                $content = Storage::disk('public')->get($path);
                $md5 = md5($content);
                $sha256 = hash('sha256', $content);
            }
            return [
                'id' => $b->id_evidence,
                'jenis' => $b->jenis_bukti,
                'file' => basename($b->file_url),
                'md5' => $md5,
                'sha256' => $sha256,
                'keterangan' => $b->keterangan,
            ];
        });

        // Analysis results from tindakan with jenis 'Analysis'
        $analysis = $kasus->tindakanForensik->where('jenis_tindakan', 'Analysis')->values();

        // For signature, pick last forensics officer if available
        $signer = $chronology->last()?->petugas_forensik ?? auth()->user()?->name ?? 'N/A';

        $pdf = Pdf::loadView('kasus.report', compact('kasus', 'chronology', 'evidences', 'analysis', 'signer'));

        $fileName = 'incident_report_kasus_' . $kasus->id_kasus . '_' . now()->format('Ymd_His') . '.pdf';
        return $pdf->download($fileName);
    }

    /**
     * AJAX: compute diff for arbitrary bukti pair (baseline_id, snapshot_id)
     */
    public function diffBukti(Request $request, $id)
    {
        try {
            $kasus = Kasus::with('korban', 'buktiDigital')->findOrFail($id);

            $baselineId = $request->input('baseline_id');
            $snapshotId = $request->input('snapshot_id');

            $baselineContent = '';
            $defacedContent = '';

            if ($baselineId) {
                $b = $kasus->buktiDigital->where('id_evidence', $baselineId)->first();
                if ($b && Storage::disk('public')->exists($b->file_url)) {
                    $baselineContent = Storage::disk('public')->get($b->file_url);
                }
            }

            if ($snapshotId) {
                $s = $kasus->buktiDigital->where('id_evidence', $snapshotId)->first();
                if ($s && Storage::disk('public')->exists($s->file_url)) {
                    $defacedContent = Storage::disk('public')->get($s->file_url);
                }
            }

            // Fallbacks
            if (empty($baselineContent)) {
                $siteBaselinePath = $kasus->korban->baseline_file_path ?? null;
                if (!empty($siteBaselinePath) && Storage::disk('public')->exists($siteBaselinePath)) {
                    $baselineContent = Storage::disk('public')->get($siteBaselinePath);
                }
            }
            if (empty($defacedContent)) {
                $siteUrl = $kasus->korban->site_url ?? null;
                if ($siteUrl) {
                    try {
                        $resp = Http::timeout(10)->get($siteUrl);
                        if ($resp->ok()) $defacedContent = $resp->body();
                    } catch (\Exception $e) {
                        // ignore
                    }
                }
            }

            if (empty($baselineContent) && empty($defacedContent)) {
                return response()->json(['success' => false, 'message' => 'Tidak ada konten untuk dibandingkan.'], 400);
            }

            $diffHtml = '<div class="alert alert-secondary">No diff available.</div>';
            $diffUsingLibrary = false;
            if (class_exists(\Jfcherng\Diff\DiffHelper::class)) {
                try {
                    $diffHtml = DiffHelper::calculate($baselineContent, $defacedContent, 'SideBySide', ['detailLevel' => 'word'], []);
                    $diffUsingLibrary = true;
                } catch (\Throwable $e) {
                    $diffUsingLibrary = false;
                }
            }

            return response()->json([
                'success' => true,
                'diffHtml' => $diffHtml,
                'diffLeft' => $baselineContent,
                'diffRight' => $defacedContent,
                'diffUsingLibrary' => $diffUsingLibrary,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
