@extends('layouts.app')

@section('title', 'Detail Kasus - Digital Forensik')

@section('content')
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <h2 class="page-title">
                <i class="fas fa-folder-open me-2"></i>Detail Kasus
            </h2>
            <p class="text-muted mb-0">Detail dan analisis kasus</p>
        </div>
        <div class="col-auto">
            <a href="{{ route('kasus.index') }}" class="btn btn-secondary">Kembali</a>
            <a href="{{ route('kasus.report', $kasus->id_kasus) }}" class="btn btn-danger ms-2" target="_blank">
                <i class="fas fa-file-pdf me-1"></i>Generate Incident Report
            </a>
            <button class="btn btn-success ms-2" id="resolveBtn">Resolve Incident</button>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h5>{{ $kasus->jenis_kasus }}</h5>
        <p class="text-muted">Monitored Site: {{ $kasus->korban->site_url ?? 'N/A' }} | Tanggal: {{ $kasus->tanggal_kejadian->format('d-m-Y') }}</p>

        <ul class="nav nav-tabs" id="kasusTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab">Overview</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="forensic-tab" data-bs-toggle="tab" data-bs-target="#forensic" type="button" role="tab">Analisis Forensik</button>
            </li>
        </ul>
        <div class="tab-content mt-3">
            <div class="tab-pane fade show active" id="overview" role="tabpanel">
                <h6>Deskripsi</h6>
                <p>{{ $kasus->deskripsi_kasus }}</p>

                <h6>Bukti Digital</h6>
                <ul>
                    @foreach($kasus->buktiDigital as $b)
                        <li>{{ $b->jenis_bukti }} - <a href="{{ Storage::url($b->file_url) }}" target="_blank">{{ basename($b->file_url) }}</a> ({{ $b->created_date->format('d-m-Y H:i') }})</li>
                    @endforeach
                </ul>

                <h6>Tindakan Forensik</h6>
                <ul>
                    @foreach($kasus->tindakanForensik as $t)
                        <li>[{{ $t->waktu_pelaksanaan->format('d-m-Y H:i') }}] {{ $t->jenis_tindakan }} - {{ $t->petugas_forensik }} - {{ $t->status_tindakan }}</li>
                    @endforeach
                </ul>
            </div>

            <div class="tab-pane fade" id="forensic" role="tabpanel">
                <div class="row">
                    {{-- <div class="col-md-4">
                        <h6>Pilih Bukti untuk Perbandingan</h6>
                        <div class="mb-2">
                            <label class="form-label">Baseline (Original)</label>
                            <select id="baselineSelect" class="form-select">
                                <option value="">-- Pilih Bukti --</option>
                                @foreach($sourceBukits as $bukti)
                                    <option value="{{ $bukti->id_evidence }}">[{{ $bukti->created_date->format('d-m-Y H:i') }}] {{ basename($bukti->file_url) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Defaced</label>
                            <select id="defacedSelect" class="form-select">
                                <option value="">-- Pilih Bukti --</option>
                                @foreach($sourceBukits as $bukti)
                                    <option value="{{ $bukti->id_evidence }}">[{{ $bukti->created_date->format('d-m-Y H:i') }}] {{ basename($bukti->file_url) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="d-grid">
                            <button id="compareBtn" class="btn btn-primary">Compare</button>
                        </div>

                        <hr/>

                        <h6>Analisis Manual</h6>
                        <form id="analysisForm">
                            @csrf
                            <div class="mb-2">
                                <label class="form-label">Entry Point</label>
                                <input type="text" name="entry_point" class="form-control" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Attacker IP</label>
                                <input type="text" name="attacker_ip" class="form-control">
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Jenis Webshell</label>
                                <input type="text" name="jenis_webshell" class="form-control">
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Catatan</label>
                                <textarea name="notes" class="form-control"></textarea>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-success">Simpan Analisis</button>
                            </div>
                        </form>
                    </div>

                    <div class="col-md-8">
                        <h6>Diff Viewer</h6>
                        <div class="row">
                            <div class="col-6">
                                <h6>Baseline</h6>
                                <pre id="baselineContent" style="height:600px;overflow:auto;background:#f8f9fa;padding:10px;border:1px solid #ddd;white-space:pre-wrap;word-wrap:break-word;"></pre>
                            </div>
                            <div class="col-6">
                                <h6>Defaced</h6>
                                <pre id="defacedContent" style="height:600px;overflow:auto;background:#fff7f7;padding:10px;border:1px solid #ddd;white-space:pre-wrap;word-wrap:break-word;"></pre>
                            </div>
                        </div>
                    </div> --}}
                    <!-- Include additional analysis partial (diff viewer + tindakan form) -->
                    @include('kasus.partials.analysis')
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Chain of Custody -->
<div class="card mt-3">
    <div class="card-body">
        <h5>Chain of Custody</h5>
        <p class="text-muted">Riwayat aktivitas terkait penanganan kasus ini.</p>
        <ul class="list-group">
            @foreach($activityLogs ?? [] as $log)
                <li class="list-group-item">
                    <strong>{{ $log->action }}</strong>
                    <div class="small text-muted">By: {{ optional($log->user)->name ?? $log->user_id }} • {{ $log->created_at->format('d-m-Y H:i:s') }} • IP: {{ $log->ip_address }}</div>
                    @if($log->changes)
                        <pre class="mt-2" style="white-space:pre-wrap">{{ json_encode($log->changes, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</pre>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
</div>
@endsection

@push('modals')
<!-- Resolve Incident Modal -->
<div class="modal fade" id="resolveModal" tabindex="-1" aria-labelledby="resolveModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="resolveModalLabel">Resolve Incident</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="resolveForm">
                @csrf
                <div class="modal-body">
                    <p>Konfirmasi bahwa website sudah direstore. Pilih opsi jika ingin memperbarui baseline hash (konten sah telah berubah).</p>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" value="1" id="updateBaseline" name="update_baseline">
                        <label class="form-check-label" for="updateBaseline">Update Baseline Hash</label>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Catatan Recovery (opsional)</label>
                        <textarea name="recovery_note" id="recovery_note" class="form-control" rows="3" placeholder="Catatan singkat tentang recovery..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Resolve</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endpush

@push('scripts')
<script>
$(function(){
    function fetchBuktiRaw(id) {
        return $.get("{{ url('bukti-digital') }}/" + id + "/raw");
    }

    $('#compareBtn').on('click', function(){
        let baseId = $('#baselineSelect').val();
        let defId = $('#defacedSelect').val();
        if (!baseId || !defId) { alert('Pilih kedua bukti untuk dibandingkan'); return; }

        $.when(fetchBuktiRaw(baseId), fetchBuktiRaw(defId)).done(function(baseRes, defRes){
            let baseText = baseRes[0];
            let defText = defRes[0];

            // simple line-by-line highlight of differences
            let baseLines = baseText.split('\n');
            let defLines = defText.split('\n');
            let max = Math.max(baseLines.length, defLines.length);
            let baseHtml = '';
            let defHtml = '';
            for (let i=0;i<max;i++){
                let b = baseLines[i] === undefined ? '' : $('<div/>').text(baseLines[i]).html();
                let d = defLines[i] === undefined ? '' : $('<div/>').text(defLines[i]).html();
                if (b !== d) {
                    baseHtml += '<div style="background:#fff3cd;padding:2px;border-bottom:1px solid #eee">'+b+'</div>';
                    defHtml += '<div style="background:#f8d7da;padding:2px;border-bottom:1px solid #eee">'+d+'</div>';
                } else {
                    baseHtml += '<div style="padding:2px;border-bottom:1px solid #eee">'+b+'</div>';
                    defHtml += '<div style="padding:2px;border-bottom:1px solid #eee">'+d+'</div>';
                }
            }
            $('#baselineContent').html(baseHtml);
            $('#defacedContent').html(defHtml);
        }).fail(function(){
            alert('Gagal mengambil konten bukti.');
        });
    });

    $('#analysisForm').on('submit', function(e){
        e.preventDefault();
        let form = $(this);
        $.ajax({
            url: "{{ route('kasus.analysis.store', $kasus->id_kasus) }}",
            type: 'POST',
            data: form.serialize(),
            success: function(res){
                if (res.success) {
                    showToast('success', res.message || 'Analisis tersimpan');
                    form[0].reset();
                } else {
                    showToast('error', res.message || 'Gagal menyimpan');
                }
            },
            error: function(xhr){
                showToast('error', 'Gagal menyimpan analisis');
            }
        });
    });
});
</script>
@endpush

@push('scripts')
<script>
$(function(){
    // Open resolve modal (use Bootstrap Modal API for compatibility)
    $('#resolveBtn').on('click', function(){
        var modalEl = document.getElementById('resolveModal');
        if (modalEl) {
            try {
                var bsModal = new bootstrap.Modal(modalEl);
                bsModal.show();
            } catch (e) {
                // Fallback to jQuery plugin if available
                if (typeof $('#resolveModal').modal === 'function') {
                    $('#resolveModal').modal('show');
                }
            }
        }
    });

    // Submit resolve form via AJAX (PUT to kasus.update) with confirmation
    $('#resolveForm').on('submit', function(e){
        e.preventDefault();
        let form = $(this);
        Swal.fire({
            title: 'Konfirmasi Resolve',
            text: 'Anda akan menandai kasus ini sebagai CLOSED dan mengembalikan status site menjadi UP. Lanjutkan?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Resolve',
            cancelButtonText: 'Batal',
            reverseButtons: true
        }).then((result) => {
            if (!result.isConfirmed) return;

            let data = form.serializeArray();
            // add resolve flag
            data.push({name: 'resolve_incident', value: 1});
            // ensure checkbox is sent as boolean
            let updateBaseline = $('#updateBaseline').is(':checked') ? 1 : 0;
            data.push({name: 'update_baseline', value: updateBaseline});

                $.ajax({
                    url: "{{ route('kasus.resolve', $kasus->id_kasus) }}",
                    type: 'POST',
                    data: $.param(data),
                success: function(resp){
                    if (resp.success) {
                        showToast('success', resp.message || 'Kasus resolved');
                        setTimeout(function(){ location.reload(); }, 800);
                    } else {
                        showToast('error', resp.message || 'Gagal resolve kasus');
                    }
                },
                error: function(xhr){
                    let msg = 'Gagal melakukan resolve';
                    if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                    showToast('error', msg);
                }
            });
        });
    });
});
</script>
@endpush
