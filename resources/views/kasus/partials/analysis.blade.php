<div id="analysis-diff" class="mb-4">
    <h5>Perbandingan (Diff)</h5>

    <div class="mb-2">
        <div class="row">
            <div class="col-md-6">
                <label class="form-label">Renderer</label>
                <div>
                    @php $libAvailable = isset($diffUsingLibrary) && $diffUsingLibrary; @endphp
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="diffRenderer" id="renderer_lib" value="lib" {{ $libAvailable ? 'checked' : '' }} {{ $libAvailable ? '' : 'disabled' }}>
                        <label class="form-check-label" for="renderer_lib">Library</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="diffRenderer" id="renderer_fallback" value="fallback" {{ $libAvailable ? '' : 'checked' }}>
                        <label class="form-check-label" for="renderer_fallback">Fallback (Line-by-line)</label>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label">Select Baseline / Snapshot</label>
                <div class="d-flex">
                    <select id="baselineSelect" class="form-select me-2">
                        <option value="">-- Select baseline --</option>
                        @foreach($baselineBukits ?? collect() as $b)
                            <option value="{{ $b->id_evidence }}">{{ basename($b->file_url) }} @ {{ $b->created_date?->format('Y-m-d H:i') }}</option>
                        @endforeach
                        @if(empty($baselineBukits) || $baselineBukits->count()===0)
                            <option disabled>-- No baseline bukti --</option>
                        @endif
                    </select>
                    <select id="snapshotSelect" class="form-select">
                        <option value="">-- Select snapshot --</option>
                        @foreach($snapshotBukits ?? collect() as $s)
                            <option value="{{ $s->id_evidence }}">{{ basename($s->file_url) }} @ {{ $s->created_date?->format('Y-m-d H:i') }}</option>
                        @endforeach
                        @if(empty($snapshotBukits) || $snapshotBukits->count()===0)
                            <option disabled>-- No snapshot bukti --</option>
                        @endif
                    </select>
                    <button id="loadDiffBtn" class="btn btn-secondary ms-2">Load Diff</button>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-2">
            <div id="libRenderer" style="display: {{ ($libAvailable ? 'block' : 'none') }}; overflow:auto;">
                @if(isset($diffHtml))
                    {!! $diffHtml !!}
                @else
                    <div class="alert alert-secondary">Tidak ada hasil diff dari library.</div>
                @endif
            </div>

            <div id="fallbackRenderer" style="display: {{ ($libAvailable ? 'none' : 'block') }}; overflow:auto;">
                @php
                    $leftLines = preg_split('/\r?\n/', $diffLeft ?? '');
                    $rightLines = preg_split('/\r?\n/', $diffRight ?? '');
                    $max = max(count($leftLines), count($rightLines));
                @endphp
                <table class="table table-sm table-bordered mb-0">
                    <thead>
                        <tr>
                            <th style="width:60px">#</th>
                            <th>Baseline</th>
                            <th>Current</th>
                        </tr>
                    </thead>
                    <tbody>
                    @for($i=0;$i<$max;$i++)
                        @php
                            $l = $leftLines[$i] ?? '';
                            $r = $rightLines[$i] ?? '';
                            $isDiff = ($l !== $r);
                        @endphp
                        <tr class="{{ $isDiff ? 'table-warning' : '' }}">
                            <td class="align-top text-muted small">{{ $i+1 }}</td>
                            <td class="p-1" style="max-width:48%;white-space:pre-wrap;word-break:break-word;">
                                <div class="line-content" data-full="{{ e($l) }}">{{ Illuminate\Support\Str::limit($l, 300) }}</div>
                                @if(strlen($l) > 300)
                                    <a href="#" class="toggle-line small">Show more</a>
                                @endif
                            </td>
                            <td class="p-1" style="max-width:48%;white-space:pre-wrap;word-break:break-word;">
                                <div class="line-content" data-full="{{ e($r) }}">{{ Illuminate\Support\Str::limit($r, 300) }}</div>
                                @if(strlen($r) > 300)
                                    <a href="#" class="toggle-line small">Show more</a>
                                @endif
                            </td>
                        </tr>
                    @endfor
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div id="analysis-form" class="mt-3">
    <h5>Tambahkan Analisis Forensik</h5>
    <form id="analysisForm" method="POST" action="{{ route('kasus.analysis.store', ['id' => $kasus->id_kasus]) }}">
        @csrf

        <div class="mb-3">
            <label for="entry_point" class="form-label">Entry Point <span class="text-danger">*</span></label>
            <input type="text" name="entry_point" id="entry_point" class="form-control" placeholder="e.g., /uploads/backdoor.php, vulnerable plugin, etc." required>
            <div class="form-text">Titik masuk yang digunakan attacker untuk melakukan defacement</div>
        </div>

        <div class="mb-3">
            <label for="attacker_ip" class="form-label">Attacker IP Address</label>
            <input type="text" name="attacker_ip" id="attacker_ip" class="form-control" placeholder="e.g., 192.168.1.100">
            <div class="form-text">IP address yang terdeteksi melakukan serangan (opsional)</div>
        </div>

        <div class="mb-3">
            <label for="jenis_webshell" class="form-label">Jenis Webshell / Malware</label>
            <input type="text" name="jenis_webshell" id="jenis_webshell" class="form-control" placeholder="e.g., c99 shell, r57 shell, custom backdoor">
            <div class="form-text">Jenis webshell atau malware yang ditemukan (opsional)</div>
        </div>

        <div class="mb-3">
            <label for="notes" class="form-label">Catatan Analisis</label>
            <textarea name="notes" id="notes" rows="6" class="form-control" placeholder="Tuliskan hasil analisis, temuan penting, rekomendasi, dll..."></textarea>
            <div class="form-text">Catatan tambahan hasil analisis forensik</div>
        </div>

        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save me-2"></i>Simpan Analisis
        </button>
    </form>
</div>

@push('scripts')
<script>
$(function(){
    // Renderer toggle
    $('input[name="diffRenderer"]').on('change', function(){
        let val = $(this).val();
        if (val === 'lib') {
            $('#libRenderer').show();
            $('#fallbackRenderer').hide();
        } else {
            $('#libRenderer').hide();
            $('#fallbackRenderer').show();
        }
    });

    // Toggle long lines show/hide
    $(document).on('click', '.toggle-line', function(e){
        e.preventDefault();
        let a = $(this);
        let container = a.prev('.line-content');
        if (!container.data('expanded')) {
            container.text(container.data('full'));
            container.data('expanded', true);
            a.text('Show less');
        } else {
            let full = container.data('full');
            let truncated = full.length > 300 ? full.substring(0,300) + '...' : full;
            container.text(truncated);
            container.data('expanded', false);
            a.text('Show more');
        }
    });
    $('#analysisForm').on('submit', function(e){
        e.preventDefault();
        let form = $(this);
        $.ajax({
            url: form.attr('action'),
            method: 'POST',
            data: form.serialize(),
            success: function(resp){
                if(resp.success){
                    showToast('success', resp.message || 'Tindakan tersimpan');
                    // Optionally reload the page or parts of it
                    setTimeout(function(){ location.reload(); }, 800);
                } else {
                    showToast('error', resp.message || 'Gagal menyimpan tindakan');
                }
            },
            error: function(xhr){
                let msg = 'Gagal menyimpan tindakan';
                if(xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                showToast('error', msg);
            }
        });
    });

    // Load diff for selected bukti pair via AJAX
    $('#loadDiffBtn').on('click', function(e){
        e.preventDefault();
        let baselineId = $('#baselineSelect').val();
        let snapshotId = $('#snapshotSelect').val();
        let kasusId = {{ $kasus->id_kasus }};

        $.ajax({
            url: "{{ route('kasus.diff.bukti', ['id' => $kasus->id_kasus]) }}",
            method: 'POST',
            data: {
                baseline_id: baselineId,
                snapshot_id: snapshotId,
                _token: '{{ csrf_token() }}'
            },
            success: function(resp){
                if(resp.success){
                    // Update renderers
                    if(resp.diffUsingLibrary){
                        $('#libRenderer').show().html(resp.diffHtml);
                        $('#fallbackRenderer').hide();
                        $('input[name="diffRenderer"][value="lib"]').prop('checked', true);
                    } else {
                        $('#libRenderer').hide();
                        // build fallback table from diffLeft/diffRight
                        buildFallback(resp.diffLeft, resp.diffRight);
                        $('#fallbackRenderer').show();
                        $('input[name="diffRenderer"][value="fallback"]').prop('checked', true);
                    }
                } else {
                    showToast('error', resp.message || 'Gagal memuat diff');
                }
            },
            error: function(xhr){
                showToast('error', 'Gagal memuat diff');
            }
        });
    });

    function buildFallback(left, right){
        let leftLines = left ? left.split(/\r?\n/) : [];
        let rightLines = right ? right.split(/\r?\n/) : [];
        let max = Math.max(leftLines.length, rightLines.length);
        let tbody = '';
        for(let i=0;i<max;i++){
            let l = leftLines[i] || '';
            let r = rightLines[i] || '';
            let isDiff = (l !== r);
            tbody += '<tr class="' + (isDiff? 'table-warning' : '') + '">';
            tbody += '<td class="align-top text-muted small">'+(i+1)+'</td>';

            tbody += '<td class="p-1" style="max-width:48%;white-space:pre-wrap;word-break:break-word;">'
                + '<div class="line-content" data-full="'+escapeHtml(l)+'">'+truncate(l,300)+'</div>'
                + (l && l.length>300 ? '<a href="#" class="toggle-line small">Show more</a>' : '')
                + '</td>';

            tbody += '<td class="p-1" style="max-width:48%;white-space:pre-wrap;word-break:break-word;">'
                + '<div class="line-content" data-full="'+escapeHtml(r)+'">'+truncate(r,300)+'</div>'
                + (r && r.length>300 ? '<a href="#" class="toggle-line small">Show more</a>' : '')
                + '</td>';

            tbody += '</tr>';
        }
        $('#fallbackRenderer table tbody').html(tbody);
    }

    function escapeHtml(str){
        if(!str) return '';
        return String(str).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function truncate(s,n){ return s && s.length>n ? s.substring(0,n)+'...' : (s||''); }
});
</script>
@endpush
