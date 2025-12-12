<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Digital Forensic Incident Report - Case #{{ $kasus->id_kasus }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 11px;
            line-height: 1.6;
            color: #333;
            margin: 20mm 15mm 20mm 15mm;
        }

        .header-section {
            border-bottom: 3px solid #2c3e50;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }

        .header-title {
            text-align: center;
            color: #2c3e50;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .header-subtitle {
            text-align: center;
            color: #555;
            font-size: 12px;
            margin-bottom: 12px;
        }

        .meta-info {
            display: table;
            width: 100%;
            font-size: 10px;
            color: #666;
            margin-top: 10px;
        }

        .meta-left {
            display: table-cell;
            width: 50%;
            text-align: left;
        }

        .meta-right {
            display: table-cell;
            width: 50%;
            text-align: right;
        }

        .section {
            margin-bottom: 20px;
            page-break-inside: avoid;
        }

        .section-title {
            background-color: #34495e;
            color: white;
            padding: 8px 12px;
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 10px;
            border-radius: 3px;
        }

        .section-subtitle {
            background-color: #7f8c8d;
            color: white;
            padding: 6px 10px;
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 8px;
            margin-top: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        table th {
            background-color: #ecf0f1;
            color: #2c3e50;
            font-weight: bold;
            text-align: left;
            padding: 8px 10px;
            border: 1px solid #bdc3c7;
            font-size: 10px;
        }

        table td {
            padding: 7px 10px;
            border: 1px solid #ddd;
            vertical-align: top;
            font-size: 10px;
        }

        table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }

        .info-row {
            display: table-row;
        }

        .info-label {
            display: table-cell;
            width: 30%;
            font-weight: bold;
            padding: 5px 10px 5px 0;
            color: #2c3e50;
        }

        .info-value {
            display: table-cell;
            width: 70%;
            padding: 5px 0;
            border-bottom: 1px solid #ecf0f1;
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .badge-high { background-color: #e74c3c; color: white; }
        .badge-medium { background-color: #f39c12; color: white; }
        .badge-low { background-color: #3498db; color: white; }
        .badge-open { background-color: #f39c12; color: white; }
        .badge-closed { background-color: #27ae60; color: white; }
        .badge-auto { background-color: #3498db; color: white; }
        .badge-manual { background-color: #95a5a6; color: white; }

        .highlight-box {
            background-color: #fff3cd;
            border-left: 4px solid #f39c12;
            padding: 12px;
            margin: 10px 0;
        }

        .alert-box {
            background-color: #f8d7da;
            border-left: 4px solid #e74c3c;
            padding: 12px;
            margin: 10px 0;
        }

        .description-text {
            text-align: justify;
            line-height: 1.7;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 3px;
        }

        .hash-text {
            font-family: monospace;
            font-size: 8px;
            word-break: break-all;
            color: #555;
        }

        .footer-section {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #2c3e50;
        }

        .signature-block {
            margin-top: 30px;
            text-align: right;
        }

        .signature-line {
            margin-top: 50px;
            border-top: 1px solid #333;
            width: 200px;
            display: inline-block;
        }

        .signature-name {
            font-weight: bold;
            margin-top: 5px;
        }

        .signature-title {
            font-size: 9px;
            color: #666;
            font-style: italic;
        }

        .page-break {
            page-break-after: always;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-muted { color: #666; }
        .font-mono { font-family: monospace; }
        .small-text { font-size: 9px; }
        .mt-10 { margin-top: 10px; }
        .mb-10 { margin-bottom: 10px; }

        .hash-text {
            font-family: monospace;
            font-size: 8px;
            color: #444;
            word-break: break-all;
            background: #f8f9fa;
            padding: 3px 6px;
            border-radius: 3px;
        }
    </style>
</head>
<body>

<!-- HEADER -->
<div class="header-section">
    <div class="header-title">Digital Forensic Incident Report</div>
    <div class="header-subtitle">Defacement Detection & Analysis System</div>
    <div class="meta-info">
        <div class="meta-left">
            <strong>Case ID:</strong> #{{ str_pad($kasus->id_kasus, 6, '0', STR_PAD_LEFT) }}<br>
            <strong>Report Type:</strong> {{ $kasus->jenis_kasus }}
        </div>
        <div class="meta-right">
            <strong>Generated:</strong> {{ now()->format('d F Y, H:i:s') }} WIB<br>
            <strong>Page:</strong> 1 of 1
        </div>
    </div>
</div>

<!-- EXECUTIVE SUMMARY -->
<div class="section">
    <div class="section-title">1. Executive Summary</div>

    <div class="info-grid">
        <div class="info-row">
            <div class="info-label">Case Status:</div>
            <div class="info-value">
                <span class="badge {{ $kasus->status_kasus == 'Open' ? 'badge-open' : 'badge-closed' }}">
                    {{ $kasus->status_kasus }}
                </span>
            </div>
        </div>
        <div class="info-row">
            <div class="info-label">Impact Level:</div>
            <div class="info-value">
                @php
                    $impactClass = 'badge-low';
                    if($kasus->impact_level == 'High') $impactClass = 'badge-high';
                    elseif($kasus->impact_level == 'Medium') $impactClass = 'badge-medium';
                @endphp
                <span class="badge {{ $impactClass }}">{{ $kasus->impact_level ?? 'Low' }}</span>
            </div>
        </div>
        <div class="info-row">
            <div class="info-label">Detection Source:</div>
            <div class="info-value">
                <span class="badge {{ $kasus->detection_source == 'System Monitoring' ? 'badge-auto' : 'badge-manual' }}">
                    {{ $kasus->detection_source ?? 'Manual' }}
                </span>
            </div>
        </div>
        <div class="info-row">
            <div class="info-label">Incident Type:</div>
            <div class="info-value">{{ $kasus->jenis_kasus }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Incident Date:</div>
            <div class="info-value">{{ $kasus->tanggal_kejadian->format('d F Y, H:i') }} WIB</div>
        </div>
    </div>

    @if($kasus->impact_level == 'High')
    <div class="alert-box">
        <strong>⚠ HIGH IMPACT ALERT:</strong> This incident has been classified as high impact and requires immediate attention and remediation.
    </div>
    @endif

    <div class="section-subtitle">Case Description</div>
    <div class="description-text">
        {{ $kasus->deskripsi_kasus }}
    </div>
</div>

<!-- AFFECTED SYSTEM INFORMATION -->
<div class="section">
    <div class="section-title">2. Affected System Information</div>

    <div class="info-grid">
        <div class="info-row">
            <div class="info-label">Target URL:</div>
            <div class="info-value"><strong>{{ $kasus->korban->site_url ?? 'N/A' }}</strong></div>
        </div>
        <div class="info-row">
            <div class="info-label">IP Address:</div>
            <div class="info-value">{{ $kasus->korban->ip_address ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Site Status:</div>
            <div class="info-value">
                @if($kasus->korban)
                    <span class="badge {{ $kasus->korban->status == 'UP' ? 'badge-closed' : ($kasus->korban->status == 'DEFACED' ? 'badge-high' : 'badge-medium') }}">
                        {{ $kasus->korban->status }}
                    </span>
                @else
                    N/A
                @endif
            </div>
        </div>
        <div class="info-row">
            <div class="info-label">Last Checked:</div>
            <div class="info-value">
                {{ $kasus->korban->last_checked_at ? $kasus->korban->last_checked_at->format('d F Y, H:i') . ' WIB' : 'Never' }}
            </div>
        </div>
        @if($kasus->korban && $kasus->korban->baseline_file_path)
        <div class="info-row">
            <div class="info-label">Baseline Established:</div>
            <div class="info-value">Yes - File: {{ basename($kasus->korban->baseline_file_path) }}</div>
        </div>
        @endif
    </div>

    @if($kasus->korban && $kasus->korban->allowed_domains)
    <div class="section-subtitle">Whitelisted Domains</div>
    <div class="description-text">
        @php
            $domains = is_array($kasus->korban->allowed_domains)
                ? $kasus->korban->allowed_domains
                : json_decode($kasus->korban->allowed_domains, true);
        @endphp
        @if(is_array($domains) && count($domains) > 0)
            {{ implode(', ', $domains) }}
        @else
            No whitelisted domains configured
        @endif
    </div>
    @endif
</div>

<div class="section">
    <div class="section-title">3. Incident Details</div>
    <table>
        <tr>
            <th width="30%">Target URL</th>
            <td>{{ $kasus->korban->site_url ?? 'N/A' }}</td>
        </tr>
        <tr>
            <th>IP Address</th>
            <td>{{ $kasus->korban->ip_address ?? 'N/A' }}</td>
        </tr>
        <tr>
            <th>Incident Date & Time</th>
            <td>{{ $kasus->tanggal_kejadian->format('d F Y, H:i:s') }} WIB</td>
        </tr>
        <tr>
            <th>Detection Method</th>
            <td>
                @if($kasus->detection_source === 'Auto')
                    <span class="badge badge-auto">Automatic Monitoring</span>
                @else
                    <span class="badge badge-manual">Manual Report</span>
                @endif
            </td>
        </tr>
    </table>
</div>

<!-- DIGITAL EVIDENCE COLLECTED -->
<div class="section">
    <div class="section-title">4. Digital Evidence Collected</div>

    @if(count($evidences) > 0)
    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="20%">Evidence File</th>
                <th width="15%">Type</th>
                <th width="10%">Collected</th>
                <th width="20%">Hash Verification</th>
                <th width="30%">Notes</th>
            </tr>
        </thead>
        <tbody>
            @foreach($evidences as $index => $e)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="font-mono small-text">{{ $e['file'] }}</td>
                    <td>
                        <span class="badge badge-low">{{ $e['jenis'] }}</span>
                    </td>
                    <td class="small-text">{{ $e['created'] ?? '-' }}</td>
                    <td>
                        @if(!empty($e['md5']))
                            <div class="small-text"><strong>MD5:</strong></div>
                            <div class="hash-text">{{ $e['md5'] }}</div>
                        @endif
                        @if(!empty($e['sha256']))
                            <div class="small-text mt-10"><strong>SHA256:</strong></div>
                            <div class="hash-text">{{ $e['sha256'] }}</div>
                        @endif
                        @if(empty($e['md5']) && empty($e['sha256']))
                            <span class="text-muted">Not calculated</span>
                        @endif
                    </td>
                    <td class="small-text">{{ Str::limit($e['keterangan'] ?? 'No notes', 150) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="highlight-box mt-10">
        <strong>Evidence Summary:</strong> Total {{ count($evidences) }} evidence file(s) collected and preserved according to digital forensic best practices.
    </div>
    @else
    <div class="description-text">
        <em>No digital evidence has been collected for this case yet.</em>
    </div>
    @endif
</div>

<!-- FORENSIC TIMELINE / CHRONOLOGY -->
<div class="section">
    <div class="section-title">5. Forensic Investigation Timeline</div>

    @if(count($chronology) > 0)
    <table>
        <thead>
            <tr>
                <th width="15%">Date & Time</th>
                <th width="20%">Action Taken</th>
                <th width="20%">Forensic Officer</th>
                <th width="45%">Results / Notes</th>
            </tr>
        </thead>
        <tbody>
            @foreach($chronology as $c)
                <tr>
                    <td class="small-text">{{ optional($c->waktu_pelaksanaan)->format('d M Y, H:i') ?? 'N/A' }}</td>
                    <td><strong>{{ $c->jenis_tindakan }}</strong></td>
                    <td>{{ $c->petugas_forensik }}</td>
                    <td class="small-text">{{ Str::limit($c->catatan ?? $c->hasil_tindakan, 350) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <div class="description-text">
        <em>No forensic actions have been recorded for this case yet.</em>
    </div>
    @endif
</div>


<!-- ANALYSIS RESULTS -->
<div class="section">
    <div class="section-title">6. Forensic Analysis Results</div>

    @if(count($analysis) > 0)
    @foreach($analysis as $index => $a)
        <div style="border-left: 3px solid #0056b3; padding-left: 12px; margin-bottom: 15px;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                <div>
                    <strong>Analysis #{{ $index + 1 }}</strong> - {{ $a->jenis_tindakan }}
                </div>
                <div class="small-text">
                    <strong>Officer:</strong> {{ $a->petugas_forensik }} |
                    <strong>Date:</strong> {{ optional($a->waktu_pelaksanaan)->format('d M Y, H:i') ?? 'N/A' }}
                </div>
            </div>

            @if(!empty($a->hasil_tindakan))
            <div class="highlight-box">
                <strong>Findings:</strong><br>
                {!! nl2br(e($a->hasil_tindakan)) !!}
            </div>
            @endif

            @if(!empty($a->catatan))
            <div class="description-text mt-10">
                <strong>Additional Notes:</strong> {{ $a->catatan }}
            </div>
            @endif
        </div>
    @endforeach
    @else
    <div class="description-text">
        <em>No forensic analysis has been performed for this case yet.</em>
    </div>
    @endif
</div>

<!-- RECOMMENDATIONS -->
<div class="section">
    <div class="section-title">7. Recommendations & Remediation Steps</div>

    <div class="description-text">
        Based on the findings of this investigation, the following recommendations are provided:
    </div>

    <div style="background: #f8f9fa; padding: 12px; margin-top: 10px; border-left: 4px solid #28a745;">
        <ul style="margin: 0; padding-left: 20px; line-height: 1.6; font-size: 10px;">
            <li><strong>Immediate Action:</strong> Isolate affected systems and review all unauthorized changes detected in the forensic analysis.</li>
            <li><strong>Security Hardening:</strong> Implement strict domain whitelisting policies and configure Content Security Policy (CSP) headers.</li>
            <li><strong>Monitoring Enhancement:</strong> Increase monitoring frequency for this site and enable real-time alerts for unauthorized domain injections.</li>
            <li><strong>Evidence Preservation:</strong> All collected digital evidence should be stored in a secure, tamper-proof environment with strict chain-of-custody documentation.</li>
            <li><strong>Incident Response:</strong> Coordinate with relevant stakeholders to implement incident response procedures and prevent future occurrences.</li>
        </ul>
    </div>
</div>

<!-- FOOTER / SIGNATURE -->
<div style="margin-top: 40px;">
    <div class="signature-block">
        <div class="signature-title">Report Prepared By:</div>
        <div class="signature-line"></div>
        <div class="signature-name">{{ $signer }}</div>
        <div class="signature-title">Digital Forensics Officer</div>
        <div class="signature-title">{{ now()->format('d F Y') }}</div>
    </div>
</div>


</body>
</html>
