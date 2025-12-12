@extends('layouts.app')

@section('title', 'Tindakan Forensik - Digital Forensik')

@section('content')
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <h2 class="page-title">
                <i class="fas fa-clipboard-check me-2"></i>Tindakan Forensik
            </h2>
            <p class="text-muted mb-0">Manajemen tindakan dan investigasi forensik</p>
        </div>
        <div class="col-auto">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
                <i class="fas fa-plus me-2"></i>Tambah Tindakan
            </button>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="tindakanTable">
                <thead class="table-light">
                    <tr>
                        <th width="5%">No</th>
                        <th>Monitored Site</th>
                        <th>Jenis Kasus</th>
                        <th>Entry Point</th>
                        <th>Attacker IP</th>
                        <th>Webshell</th>
                        <th>Waktu</th>
                        <th>Petugas</th>
                        <th>Status</th>
                        <th width="8%">Opsi</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Data will be loaded via DataTables -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create Modal -->
<div class="modal fade" id="createModal" tabindex="-1" aria-labelledby="createModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="createModalLabel">
                    <i class="fas fa-plus-circle me-2"></i>Tambah Tindakan Forensik
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createForm">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="create_id_kasus" class="form-label">Kasus <span class="text-danger">*</span></label>
                                <select class="form-select" id="create_id_kasus" name="id_kasus" required>
                                    <option value="">-- Pilih Kasus --</option>
                                    @foreach($kasus as $k)
                                        <option value="{{ $k->id_kasus }}">
                                            {{ $k->jenis_kasus }} - {{ $k->korban->site_url ?? 'N/A' }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>

                            <div class="mb-3">
                                <label for="create_entry_point" class="form-label">Entry Point <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="create_entry_point" name="entry_point" placeholder="e.g., /uploads/backdoor.php, vulnerable plugin" required>
                                <div class="form-text">Titik masuk yang digunakan attacker</div>
                                <div class="invalid-feedback"></div>
                            </div>

                            <div class="mb-3">
                                <label for="create_attacker_ip" class="form-label">Attacker IP</label>
                                <input type="text" class="form-control" id="create_attacker_ip" name="attacker_ip" placeholder="e.g., 192.168.1.100">
                                <div class="form-text">IP address attacker (opsional)</div>
                                <div class="invalid-feedback"></div>
                            </div>

                            <div class="mb-3">
                                <label for="create_jenis_webshell" class="form-label">Jenis Webshell / Malware</label>
                                <input type="text" class="form-control" id="create_jenis_webshell" name="jenis_webshell" placeholder="e.g., c99 shell, r57 shell">
                                <div class="form-text">Jenis webshell yang ditemukan (opsional)</div>
                                <div class="invalid-feedback"></div>
                            </div>

                            <div class="mb-3">
                                <label for="create_petugas_forensik" class="form-label">Petugas Forensik <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="create_petugas_forensik" name="petugas_forensik" value="{{ auth()->user()?->name ?? '' }}" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="create_jenis_tindakan" class="form-label">Jenis Tindakan <span class="text-danger">*</span></label>
                                <select class="form-select" id="create_jenis_tindakan" name="jenis_tindakan" required>
                                    <option value="Analysis" selected>Analysis</option>
                                    <option value="Recovery">Recovery</option>
                                    <option value="Preservation">Preservation</option>
                                    <option value="Investigation">Investigation</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>

                            <div class="mb-3">
                                <label for="create_waktu_pelaksanaan" class="form-label">Waktu Pelaksanaan <span class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control" id="create_waktu_pelaksanaan" name="waktu_pelaksanaan" value="{{ now()->format('Y-m-d\\TH:i') }}" required>
                                <div class="invalid-feedback"></div>
                            </div>

                            <div class="mb-3">
                                <label for="create_metode_forensik" class="form-label">Metode Forensik</label>
                                <input type="text" class="form-control" id="create_metode_forensik" name="metode_forensik" placeholder="Manual Analysis, Automated Scan, etc." value="Manual Analysis">
                                <div class="invalid-feedback"></div>
                            </div>

                            <div class="mb-3">
                                <label for="create_hasil_tindakan" class="form-label">Hasil Analisis</label>
                                <textarea class="form-control" id="create_hasil_tindakan" name="hasil_tindakan" rows="4" placeholder="Hasil temuan dan analisis forensik"></textarea>
                                <div class="invalid-feedback"></div>
                            </div>

                            <div class="mb-3">
                                <label for="create_status_tindakan" class="form-label">Status <span class="text-danger">*</span></label>
                                <select class="form-select" id="create_status_tindakan" name="status_tindakan" required>
                                    <option value="Completed" selected>Completed</option>
                                    <option value="In Progress">In Progress</option>
                                    <option value="Planned">Planned</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>

                            <div class="mb-3">
                                <label for="create_catatan" class="form-label">Catatan Tambahan</label>
                                <textarea class="form-control" id="create_catatan" name="catatan" rows="2" placeholder="Catatan tambahan (opsional)"></textarea>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Batal
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="editModalLabel">
                    <i class="fas fa-edit me-2"></i>Edit Tindakan Forensik
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editForm">
                @csrf
                @method('PUT')
                <input type="hidden" id="edit_id_tindakan" name="id_tindakan">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_id_kasus" class="form-label">Kasus <span class="text-danger">*</span></label>
                                <select class="form-select" id="edit_id_kasus" name="id_kasus" required>
                                    <option value="">-- Pilih Kasus --</option>
                                    @foreach($kasus as $k)
                                        <option value="{{ $k->id_kasus }}">
                                            {{ $k->jenis_kasus }} - {{ $k->korban->site_url ?? 'N/A' }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>

                            <div class="mb-3">
                                <label for="edit_entry_point" class="form-label">Entry Point <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_entry_point" name="entry_point" required>
                                <div class="invalid-feedback"></div>
                            </div>

                            <div class="mb-3">
                                <label for="edit_attacker_ip" class="form-label">Attacker IP</label>
                                <input type="text" class="form-control" id="edit_attacker_ip" name="attacker_ip">
                                <div class="invalid-feedback"></div>
                            </div>

                            <div class="mb-3">
                                <label for="edit_jenis_webshell" class="form-label">Jenis Webshell / Malware</label>
                                <input type="text" class="form-control" id="edit_jenis_webshell" name="jenis_webshell">
                                <div class="invalid-feedback"></div>
                            </div>

                            <div class="mb-3">
                                <label for="edit_petugas_forensik" class="form-label">Petugas Forensik <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_petugas_forensik" name="petugas_forensik" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_jenis_tindakan" class="form-label">Jenis Tindakan <span class="text-danger">*</span></label>
                                <select class="form-select" id="edit_jenis_tindakan" name="jenis_tindakan" required>
                                    <option value="Analysis">Analysis</option>
                                    <option value="Recovery">Recovery</option>
                                    <option value="Preservation">Preservation</option>
                                    <option value="Investigation">Investigation</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>

                            <div class="mb-3">
                                <label for="edit_waktu_pelaksanaan" class="form-label">Waktu Pelaksanaan <span class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control" id="edit_waktu_pelaksanaan" name="waktu_pelaksanaan" required>
                                <div class="invalid-feedback"></div>
                            </div>

                            <div class="mb-3">
                                <label for="edit_metode_forensik" class="form-label">Metode Forensik</label>
                                <input type="text" class="form-control" id="edit_metode_forensik" name="metode_forensik">
                                <div class="invalid-feedback"></div>
                            </div>

                            <div class="mb-3">
                                <label for="edit_hasil_tindakan" class="form-label">Hasil Analisis</label>
                                <textarea class="form-control" id="edit_hasil_tindakan" name="hasil_tindakan" rows="3"></textarea>
                                <div class="invalid-feedback"></div>
                            </div>

                            <div class="mb-3">
                                <label for="edit_status_tindakan" class="form-label">Status <span class="text-danger">*</span></label>
                                <select class="form-select" id="edit_status_tindakan" name="status_tindakan" required>
                                    <option value="Completed">Completed</option>
                                    <option value="In Progress">In Progress</option>
                                    <option value="Planned">Planned</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>

                            <div class="mb-3">
                                <label for="edit_catatan" class="form-label">Catatan Tambahan</label>
                                <textarea class="form-control" id="edit_catatan" name="catatan" rows="2"></textarea>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Batal
                    </button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save me-2"></i>Update
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize DataTable
    let table = $('#tindakanTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('tindakan_forensik.getData') }}",
            type: 'GET'
        },
        columns: [
            {
                data: 'DT_RowIndex',
                name: 'DT_RowIndex',
                orderable: false,
                searchable: false
            },
            { data: 'site_url', name: 'site_url' },
            { data: 'nama_kasus', name: 'nama_kasus' },
            { data: 'entry_point_short', name: 'entry_point' },
            { data: 'attacker_ip_display', name: 'attacker_ip' },
            { data: 'webshell_badge', name: 'jenis_webshell' },
            { data: 'waktu_formatted', name: 'waktu_pelaksanaan' },
            { data: 'petugas_forensik', name: 'petugas_forensik' },
            { data: 'status_badge', name: 'status_tindakan' },
            {
                data: 'opsi',
                name: 'opsi',
                orderable: false,
                searchable: false
            }
        ],
        order: [[0, 'desc']],
        language: {
            processing: "Memuat data...",
            search: "Pencarian:",
            lengthMenu: "Tampilkan _MENU_ data",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
            infoFiltered: "(disaring dari _MAX_ total data)",
            paginate: {
                first: "Pertama",
                last: "Terakhir",
                next: "Selanjutnya",
                previous: "Sebelumnya"
            },
            zeroRecords: "Data tidak ditemukan",
            emptyTable: "Tidak ada data tersedia"
        }
    });

    // Clear validation errors
    function clearValidationErrors(formId) {
        $(formId + ' .is-invalid').removeClass('is-invalid');
        $(formId + ' .invalid-feedback').text('');
    }

    // Display validation errors
    function displayValidationErrors(errors, formId) {
        $.each(errors, function(field, messages) {
            let input = $(formId + ' [name="' + field + '"]');
            input.addClass('is-invalid');
            input.siblings('.invalid-feedback').text(messages[0]);
        });
    }

    // Create Form Submit
    $('#createForm').on('submit', function(e) {
        e.preventDefault();
        clearValidationErrors('#createForm');

        $.ajax({
            url: "{{ route('tindakan_forensik.store') }}",
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    $('#createModal').modal('hide');
                    $('#createForm')[0].reset();
                    table.ajax.reload();
                    showToast('success', response.message);
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    let errors = xhr.responseJSON.errors;
                    displayValidationErrors(errors, '#createForm');
                } else {
                    showToast('error', 'Terjadi kesalahan pada server');
                }
            }
        });
    });

    // Edit Button Click - Load Data via AJAX
    $(document).on('click', '.edit-btn', function() {
        let id = $(this).data('id');
        clearValidationErrors('#editForm');

        $.ajax({
            url: "{{ route('tindakan_forensik.index') }}/" + id + "/edit",
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    $('#edit_id_tindakan').val(response.data.id_tindakan);
                    $('#edit_id_kasus').val(response.data.id_kasus);
                    $('#edit_jenis_tindakan').val(response.data.jenis_tindakan);
                    $('#edit_waktu_pelaksanaan').val(response.data.waktu_pelaksanaan);
                    $('#edit_metode_forensik').val(response.data.metode_forensik);
                    $('#edit_entry_point').val(response.data.entry_point);
                    $('#edit_attacker_ip').val(response.data.attacker_ip);
                    $('#edit_jenis_webshell').val(response.data.jenis_webshell);
                    $('#edit_hasil_tindakan').val(response.data.hasil_tindakan);
                    $('#edit_petugas_forensik').val(response.data.petugas_forensik);
                    $('#edit_status_tindakan').val(response.data.status_tindakan);
                    $('#edit_catatan').val(response.data.catatan);
                    $('#editModal').modal('show');
                }
            },
            error: function() {
                showToast('error', 'Gagal memuat data');
            }
        });
    });

    // Edit Form Submit
    $('#editForm').on('submit', function(e) {
        e.preventDefault();
        clearValidationErrors('#editForm');
        let id = $('#edit_id_tindakan').val();

        $.ajax({
            url: "{{ route('tindakan_forensik.index') }}/" + id,
            type: 'PUT',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    $('#editModal').modal('hide');
                    table.ajax.reload();
                    showToast('success', response.message);
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    let errors = xhr.responseJSON.errors;
                    displayValidationErrors(errors, '#editForm');
                } else {
                    showToast('error', 'Terjadi kesalahan pada server');
                }
            }
        });
    });

    // Delete Button Click
    $(document).on('click', '.delete-btn', function() {
        let id = $(this).data('id');

        Swal.fire({
            title: 'Apakah Anda yakin?',
            text: "Data tindakan forensik akan dihapus permanen!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ route('tindakan_forensik.index') }}/" + id,
                    type: 'DELETE',
                    success: function(response) {
                        if (response.success) {
                            table.ajax.reload();
                            showToast('success', response.message);
                        }
                    },
                    error: function() {
                        showToast('error', 'Gagal menghapus data');
                    }
                });
            }
        });
    });

    // Reset form when modal is closed
    $('#createModal').on('hidden.bs.modal', function() {
        $('#createForm')[0].reset();
        clearValidationErrors('#createForm');
    });

    $('#editModal').on('hidden.bs.modal', function() {
        $('#editForm')[0].reset();
        clearValidationErrors('#editForm');
    });
});
</script>
@endpush
