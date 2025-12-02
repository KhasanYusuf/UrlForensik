<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Incident Report - Kasus {{ $kasus->id_kasus }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size:12px; }
        header { text-align:center; margin-bottom:20px }
        .section { margin-bottom:18px }
        table { width:100%; border-collapse:collapse }
        table th, table td { border:1px solid #ccc; padding:6px }
        .small { font-size:11px }
    </style>
</head>
<body>
<header>
    <h2>Incident Report</h2>
    <div class="small">Case ID: {{ $kasus->id_kasus }} | Generated: {{ now()->format('d-m-Y H:i:s') }}</div>
</header>

<div class="section">
    <h4>Header: Monitored Site</h4>
    <table>
        <tr>
            <th>Site URL</th>
            <td>{{ $kasus->korban->site_url ?? 'N/A' }}</td>
        </tr>
        <tr>
            <th>IP Address</th>
            <td>{{ $kasus->korban->ip_address ?? 'N/A' }}</td>
        </tr>
        <tr>
            <th>Incident Time</th>
            <td>{{ $kasus->tanggal_kejadian->format('d-m-Y') }}</td>
        </tr>
    </table>
</div>

<div class="section">
    <h4>Chronology</h4>
    <table>
        <thead>
            <tr><th>Time</th><th>Action</th><th>Officer</th><th>Notes</th></tr>
        </thead>
        <tbody>
            @foreach($chronology as $c)
                <tr>
                    <td>{{ optional($c->waktu_pelaksanaan)->format('d-m-Y H:i') }}</td>
                    <td>{{ $c->jenis_tindakan }}</td>
                    <td>{{ $c->petugas_forensik }}</td>
                    <td class="small">{{ Str::limit($c->catatan ?? $c->hasil_tindakan, 300) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="section">
    <h4>Evidence Collected</h4>
    <table>
        <thead><tr><th>File</th><th>Type</th><th>MD5</th><th>SHA256</th><th>Notes</th></tr></thead>
        <tbody>
            @foreach($evidences as $e)
                <tr>
                    <td>{{ $e['file'] }}</td>
                    <td>{{ $e['jenis'] }}</td>
                    <td class="small">{{ $e['md5'] ?? 'N/A' }}</td>
                    <td class="small">{{ $e['sha256'] ?? 'N/A' }}</td>
                    <td class="small">{{ Str::limit($e['keterangan'] ?? '', 200) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="section">
    <h4>Analysis Results</h4>
    @foreach($analysis as $a)
        <div style="margin-bottom:8px">
            <strong>By:</strong> {{ $a->petugas_forensik }} <strong>At:</strong> {{ optional($a->waktu_pelaksanaan)->format('d-m-Y H:i') }}
            <div class="small">{!! nl2br(e($a->hasil_tindakan)) !!}</div>
        </div>
    @endforeach
</div>

<footer>
    <div style="margin-top:30px">
        <div>Signed by:</div>
        <div style="margin-top:40px; font-weight:bold">{{ $signer }}</div>
        <div class="small">Forensics Officer</div>
    </div>
</footer>
</body>
</html>
