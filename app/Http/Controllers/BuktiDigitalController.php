<?php

namespace App\Http\Controllers;

use App\Models\BuktiDigital;
use App\Models\Kasus;
use App\Http\Requests\StoreBuktiDigitalRequest;
use App\Http\Requests\UpdateBuktiDigitalRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Models\ActivityLog;
use Yajra\DataTables\Facades\DataTables;

class BuktiDigitalController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $kasus = Kasus::with('korban')->orderBy('id_kasus', 'desc')->get();
        return view('bukti_digital.index', compact('kasus'));
    }

    /**
     * Get data for DataTables (Server-side processing)
     */
    public function getData(Request $request)
    {
        if ($request->ajax()) {
            $data = BuktiDigital::with('kasus.korban')
                ->select('bukti_digital.*')
                ->orderBy('id_evidence', 'desc');

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
                ->addColumn('file_link', function ($row) {
                    $fileName = basename($row->file_url);
                    $url = route('bukti_digital.download', $row->id_evidence);
                    return '<a href="' . $url . '" target="_blank" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-download me-1"></i>' . $fileName . '
                    </a>';
                })
                ->addColumn('created_date_formatted', function ($row) {
                    return $row->created_date->format('d-m-Y H:i');
                })
                ->addColumn('opsi', function ($row) {
                    $btn = '<div class="dropdown">
                        <button class="btn btn-sm btn-light" type="button" id="dropdownMenu' . $row->id_evidence . '" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="dropdownMenu' . $row->id_evidence . '">
                            <li><a class="dropdown-item edit-btn" href="javascript:void(0)" data-id="' . $row->id_evidence . '">
                                <i class="fas fa-edit me-2"></i>Edit
                            </a></li>
                            <li><a class="dropdown-item delete-btn text-danger" href="javascript:void(0)" data-id="' . $row->id_evidence . '">
                                <i class="fas fa-trash me-2"></i>Delete
                            </a></li>
                        </ul>
                    </div>';
                    return $btn;
                })
                ->rawColumns(['file_link', 'opsi'])
                ->make(true);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBuktiDigitalRequest $request)
    {
        try {
            $data = $request->validated();

            // Handle file upload
            if ($request->hasFile('file_url')) {
                $file = $request->file('file_url');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('bukti_digital', $fileName, 'public');
                $data['file_url'] = $filePath;
            }

            BuktiDigital::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Bukti digital berhasil ditambahkan!'
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
            $bukti = BuktiDigital::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'id_evidence' => $bukti->id_evidence,
                    'id_kasus' => $bukti->id_kasus,
                    'jenis_bukti' => $bukti->jenis_bukti,
                    'file_url' => $bukti->file_url,
                    'file_name' => basename($bukti->file_url),
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
    public function update(UpdateBuktiDigitalRequest $request, $id)
    {
        try {
            $bukti = BuktiDigital::findOrFail($id);
            $data = $request->validated();

            // Handle file upload if new file is provided
            if ($request->hasFile('file_url')) {
                // Delete old file
                if ($bukti->file_url && Storage::disk('public')->exists($bukti->file_url)) {
                    Storage::disk('public')->delete($bukti->file_url);
                }

                // Upload new file
                $file = $request->file('file_url');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('bukti_digital', $fileName, 'public');
                $data['file_url'] = $filePath;
            }

            $bukti->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Bukti digital berhasil diupdate!'
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
            $bukti = BuktiDigital::findOrFail($id);

            // Delete file from storage
            if ($bukti->file_url && Storage::disk('public')->exists($bukti->file_url)) {
                Storage::disk('public')->delete($bukti->file_url);
            }

            $bukti->delete();

            return response()->json([
                'success' => true,
                'message' => 'Bukti digital berhasil dihapus!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Return raw content of a bukti file (for diff/viewer).
     */
    public function raw($id)
    {
        try {
            $bukti = BuktiDigital::findOrFail($id);

            // Attempt to read file from storage
            $path = $bukti->file_url;
            if (Storage::disk('public')->exists($path)) {
                $content = Storage::disk('public')->get($path);
                return response($content, 200)->header('Content-Type', 'text/plain');
            }

            return response('File not found', 404);
        } catch (\Exception $e) {
            return response('Error: '.$e->getMessage(), 500);
        }
    }

    /**
     * Download a bukti file (records activity log for chain of custody)
     */
    public function download($id)
    {
        try {
            $bukti = BuktiDigital::findOrFail($id);
            $path = $bukti->file_url;

            if (! Storage::disk('public')->exists($path)) {
                return response('File not found', 404);
            }

            // Log activity
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'download_bukti',
                'target_type' => 'bukti_digital',
                'target_id' => $bukti->id_evidence,
                'case_id' => $bukti->id_kasus,
                'ip_address' => request()->ip(),
                'changes' => ['file_url' => $bukti->file_url],
            ]);

            return Storage::disk('public')->download($path);
        } catch (\Exception $e) {
            return response('Error: '.$e->getMessage(), 500);
        }
    }
}
