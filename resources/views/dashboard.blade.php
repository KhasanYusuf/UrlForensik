@extends('layouts.app')

@section('title', 'Dashboard - Digital Forensik')

@section('content')
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <h2 class="page-title">
                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
            </h2>
            <p class="text-muted mb-0">Selamat datang di Sistem Digital Forensik</p>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">Monitored Sites</h6>
                        <h2 class="mb-0">{{ $total_monitored }}</h2>
                        <div class="small text-muted mt-1">
                            <span class="me-2"><i class="fas fa-circle text-success"></i> UP: {{ $monitored_up }}</span>
                            <span><i class="fas fa-circle text-danger"></i> DEFACED: {{ $monitored_defaced }}</span>
                        </div>
                    </div>
                    <div class="bg-primary bg-opacity-10 rounded-circle p-3">
                        <i class="fas fa-server fa-2x text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">Total Kasus</h6>
                        <h2 class="mb-0">{{ $total_kasus }}</h2>
                    </div>
                    <div class="bg-success bg-opacity-10 rounded-circle p-3">
                        <i class="fas fa-folder-open fa-2x text-success"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">Bukti Digital</h6>
                        <h2 class="mb-0">{{ $total_bukti }}</h2>
                    </div>
                    <div class="bg-warning bg-opacity-10 rounded-circle p-3">
                        <i class="fas fa-file-alt fa-2x text-warning"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">Total Users</h6>
                        <h2 class="mb-0">{{ $total_users }}</h2>
                    </div>
                    <div class="bg-info bg-opacity-10 rounded-circle p-3">
                        <i class="fas fa-user-shield fa-2x text-info"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Status Kasus -->
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="fas fa-chart-pie me-2"></i>Status Kasus
                </h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6">
                        <div class="p-3">
                            <div class="mb-2">
                                <i class="fas fa-folder-open fa-3x text-warning"></i>
                            </div>
                            <h3 class="mb-1">{{ $kasus_open }}</h3>
                            <p class="text-muted mb-0">Kasus Open</p>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3">
                            <div class="mb-2">
                                <i class="fas fa-check-circle fa-3x text-success"></i>
                            </div>
                            <h3 class="mb-1">{{ $kasus_closed }}</h3>
                            <p class="text-muted mb-0">Kasus Closed</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i>Informasi Sistem
                </h5>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li class="mb-3">
                        <i class="fas fa-user text-primary me-2"></i>
                        <strong>User Login:</strong> {{ Auth::user()->name }}
                    </li>
                    <li class="mb-3">
                        <i class="fas fa-shield-alt text-success me-2"></i>
                        <strong>Role:</strong>
                        <span class="badge {{ Auth::user()->role == 'admin' ? 'bg-danger' : 'bg-info' }}">
                            {{ ucfirst(Auth::user()->role) }}
                        </span>
                    </li>
                    <li class="mb-3">
                        <i class="fas fa-calendar text-warning me-2"></i>
                        <strong>Tanggal:</strong> {{ date('d F Y') }}
                    </li>
                    <li>
                        <i class="fas fa-clock text-info me-2"></i>
                        <strong>Waktu:</strong> <span id="current-time"></span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Recent Incidents -->
<div class="row g-4 mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-bell me-2"></i>Recent Incidents</h5>
            </div>
            <div class="card-body">
                <ul class="list-group">
                    @foreach($recent_incidents as $inc)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <a href="{{ route('kasus.show', $inc->id_kasus) }}"><strong>{{ $inc->jenis_kasus }}</strong></a>
                                <div class="small text-muted">{{ $inc->korban->site_url ?? 'N/A' }} • {{ optional($inc->tanggal_kejadian)->format('d-m-Y') }}</div>
                            </div>
                            <div>
                                <span class="badge {{ $inc->status_kasus == 'Open' ? 'bg-warning text-dark' : 'bg-success' }}">{{ $inc->status_kasus }}</span>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Status Tindakan Forensik -->
<div class="row g-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="fas fa-clipboard-check me-2"></i>Status Tindakan Forensik
                </h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-4">
                        <div class="p-3">
                            <div class="mb-2">
                                <i class="fas fa-clock fa-3x text-info"></i>
                            </div>
                            <h3 class="mb-1">{{ $tindakan_planned }}</h3>
                            <p class="text-muted mb-0">Planned (Direncanakan)</p>
                            <small class="text-muted">Kasus yang akan ditindak</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 border-start border-end">
                            <div class="mb-2">
                                <i class="fas fa-spinner fa-3x text-warning"></i>
                            </div>
                            <h3 class="mb-1">{{ $tindakan_progress }}</h3>
                            <p class="text-muted mb-0">In Progress (Sedang Ditindak)</p>
                            <small class="text-muted">Kasus yang sedang dalam proses</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3">
                            <div class="mb-2">
                                <i class="fas fa-check-double fa-3x text-success"></i>
                            </div>
                            <h3 class="mb-1">{{ $tindakan_completed }}</h3>
                            <p class="text-muted mb-0">Completed (Selesai)</p>
                            <small class="text-muted">Tindakan yang telah selesai</small>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="row mt-3">
                    <div class="col-md-12">
                        <div class="alert alert-info mb-0" role="alert">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Total Kasus Sedang Ditindak:</strong>
                            <span class="badge bg-info ms-2">{{ $tindakan_planned + $tindakan_progress }}</span>
                            kasus (Planned + In Progress)
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Update current time
    function updateTime() {
        const now = new Date();
        const time = now.toLocaleTimeString('id-ID');
        document.getElementById('current-time').textContent = time;
    }

    updateTime();
    setInterval(updateTime, 1000);
</script>
@endpush
