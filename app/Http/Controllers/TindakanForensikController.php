<?php

namespace App\Http\Controllers;

use App\Models\TindakanForensik;
use App\Models\Kasus;
use App\Http\Requests\StoreTindakanForensikRequest;
use App\Http\Requests\UpdateTindakanForensikRequest;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Auth;

class TindakanForensikController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $kasus = Kasus::with('korban')->orderBy('id_kasus', 'desc')->get();
        return view('tindakan_forensik.index', compact('kasus'));
    }

    /**
     * Get data for DataTables (Server-side processing)
     */
    public function getData(Request $request)
    {
        if ($request->ajax()) {
            $data = TindakanForensik::with('kasus.korban')
                ->select('tindakan_forensik.*')
                ->orderBy('id_tindakan', 'desc');

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('nama_kasus', function ($row) {
                    return $row->kasus->jenis_kasus ?? '-';
                })
                ->addColumn('site_url', function ($row) {
                    return $row->kasus->korban->site_url ?? '-';
                })
                ->addColumn('nama_korban', function ($row) {
                    return $row->kasus->korban->nama_korban ?? ($row->kasus->korban->site_url ?? '-');
                })
                ->addColumn('waktu_formatted', function ($row) {
                    return $row->waktu_pelaksanaan->format('d-m-Y H:i');
                })
                ->addColumn('status_badge', function ($row) {
                    $badges = [
                        'Planned' => '<span class="badge bg-info">Planned</span>',
                        'In Progress' => '<span class="badge bg-warning text-dark">In Progress</span>',
                        'Completed' => '<span class="badge bg-success">Completed</span>',
                    ];
                    return $badges[$row->status_tindakan] ?? '-';
                })
                ->addColumn('opsi', function ($row) {
                    $btn = '<div class="dropdown">
                        <button class="btn btn-sm btn-light" type="button" id="dropdownMenu' . $row->id_tindakan . '" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="dropdownMenu' . $row->id_tindakan . '">
                            <li><a class="dropdown-item edit-btn" href="javascript:void(0)" data-id="' . $row->id_tindakan . '">
                                <i class="fas fa-edit me-2"></i>Edit
                            </a></li>
                            <li><a class="dropdown-item delete-btn text-danger" href="javascript:void(0)" data-id="' . $row->id_tindakan . '">
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
    public function store(StoreTindakanForensikRequest $request)
    {
        try {
            TindakanForensik::create($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Tindakan forensik berhasil ditambahkan!'
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
            $tindakan = TindakanForensik::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'id_tindakan' => $tindakan->id_tindakan,
                    'id_kasus' => $tindakan->id_kasus,
                    'jenis_tindakan' => $tindakan->jenis_tindakan,
                    'waktu_pelaksanaan' => $tindakan->waktu_pelaksanaan->format('Y-m-d\TH:i'),
                    'lokasi_tindakan' => $tindakan->lokasi_tindakan,
                    'metode_forensik' => $tindakan->metode_forensik,
                    'hasil_tindakan' => $tindakan->hasil_tindakan,
                    'petugas_forensik' => $tindakan->petugas_forensik,
                    'status_tindakan' => $tindakan->status_tindakan,
                    'catatan' => $tindakan->catatan,
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
    public function update(UpdateTindakanForensikRequest $request, $id)
    {
        try {
            $tindakan = TindakanForensik::findOrFail($id);
            $tindakan->update($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Tindakan forensik berhasil diupdate!'
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
            $tindakan = TindakanForensik::findOrFail($id);
            $tindakan->delete();

            return response()->json([
                'success' => true,
                'message' => 'Tindakan forensik berhasil dihapus!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store an analysis record for a Kasus (manual investigator input).
     */
    public function storeAnalysis(HttpRequest $request, $id)
    {
        // Simple validation for analysis inputs
        $data = $request->validate([
            'entry_point' => 'required|string|max:1000',
            'attacker_ip' => 'nullable|ip',
            'jenis_webshell' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        try {
            $kasus = Kasus::findOrFail($id);

            $investigator = Auth::user()?->name ?? 'Investigator';

            $hasil = [
                'entry_point' => $data['entry_point'],
                'attacker_ip' => $data['attacker_ip'] ?? null,
                'jenis_webshell' => $data['jenis_webshell'] ?? null,
            ];

            $tindakan = TindakanForensik::create([
                'id_kasus' => $kasus->id_kasus,
                'jenis_tindakan' => 'Analysis',
                'waktu_pelaksanaan' => now(),
                'lokasi_tindakan' => $kasus->korban->site_url ?? null,
                'metode_forensik' => 'Manual Analysis',
                'hasil_tindakan' => json_encode($hasil),
                'petugas_forensik' => $investigator,
                'status_tindakan' => 'Completed',
                'catatan' => $data['notes'] ?? null,
            ]);

            return response()->json(['success' => true, 'message' => 'Analisis berhasil disimpan.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal menyimpan analisis: '.$e->getMessage()], 500);
        }
    }
}
