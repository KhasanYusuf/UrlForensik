@extends('layouts.app')

@section('title', 'Data Kasus - Digital Forensik')

@section('content')
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <h2 class="page-title">
                <i class="fas fa-folder-open me-2"></i>Data Kasus
            </h2>
            <p class="text-muted mb-0">Manajemen data kasus digital forensik</p>
        </div>
        <div class="col-auto">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
                <i class="fas fa-plus me-2"></i>Tambah Kasus
            </button>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="kasusTable">
                <thead class="table-light">
                    <tr>
                        <th width="5%">No</th>
                        <th>Site URL</th>
                        <th>Jenis Kasus</th>
                        <th>Tanggal Kejadian</th>
                        <th>Status</th>
                        <th width="10%">Opsi</th>
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
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="createModalLabel">
                    <i class="fas fa-plus-circle me-2"></i>Tambah Data Kasus
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="create_id_site" class="form-label">Monitored Site <span class="text-danger">*</span></label>
                        <select class="form-select" id="create_id_site" name="id_site" required>
                            <option value="">-- Pilih Site --</option>
                            @foreach($korbans as $korban)
                                <option value="{{ $korban->id_site }}">{{ $korban->site_url }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="mb-3">
                        <label for="create_jenis_kasus" class="form-label">Jenis Kasus <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="create_jenis_kasus" name="jenis_kasus" required>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="mb-3">
                        <label for="create_tanggal_kejadian" class="form-label">Tanggal Kejadian <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="create_tanggal_kejadian" name="tanggal_kejadian" required>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="mb-3">
                        <label for="create_deskripsi_kasus" class="form-label">Deskripsi Kasus <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="create_deskripsi_kasus" name="deskripsi_kasus" rows="4" required></textarea>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="mb-3">
                        <label for="create_status_kasus" class="form-label">Status Kasus <span class="text-danger">*</span></label>
                        <select class="form-select" id="create_status_kasus" name="status_kasus" required>
                            <option value="">-- Pilih Status --</option>
                            <option value="Open">Open</option>
                            <option value="Closed">Closed</option>
                        </select>
                        <div class="invalid-feedback"></div>
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
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="editModalLabel">
                    <i class="fas fa-edit me-2"></i>Edit Data Kasus
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editForm">
                @csrf
                @method('PUT')
                <input type="hidden" id="edit_id_kasus" name="id_kasus">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_id_site" class="form-label">Monitored Site <span class="text-danger">*</span></label>
                        <select class="form-select" id="edit_id_site" name="id_site" required>
                            <option value="">-- Pilih Site --</option>
                            @foreach($korbans as $korban)
                                <option value="{{ $korban->id_site }}">{{ $korban->site_url }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_jenis_kasus" class="form-label">Jenis Kasus <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_jenis_kasus" name="jenis_kasus" required>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_tanggal_kejadian" class="form-label">Tanggal Kejadian <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="edit_tanggal_kejadian" name="tanggal_kejadian" required>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_deskripsi_kasus" class="form-label">Deskripsi Kasus <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="edit_deskripsi_kasus" name="deskripsi_kasus" rows="4" required></textarea>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_status_kasus" class="form-label">Status Kasus <span class="text-danger">*</span></label>
                        <select class="form-select" id="edit_status_kasus" name="status_kasus" required>
                            <option value="">-- Pilih Status --</option>
                            <option value="Open">Open</option>
                            <option value="Closed">Closed</option>
                        </select>
                        <div class="invalid-feedback"></div>
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
    let table = $('#kasusTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('kasus.getData') }}",
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
            { data: 'jenis_kasus', name: 'jenis_kasus' },
            { data: 'tanggal_kejadian_formatted', name: 'tanggal_kejadian' },
            { data: 'status_badge', name: 'status_kasus' },
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
            url: "{{ route('kasus.store') }}",
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
            url: "{{ route('kasus.index') }}/" + id + "/edit",
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    $('#edit_id_kasus').val(response.data.id_kasus);
                    $('#edit_id_site').val(response.data.id_site);
                    $('#edit_jenis_kasus').val(response.data.jenis_kasus);
                    $('#edit_tanggal_kejadian').val(response.data.tanggal_kejadian);
                    $('#edit_deskripsi_kasus').val(response.data.deskripsi_kasus);
                    $('#edit_status_kasus').val(response.data.status_kasus);
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
        let id = $('#edit_id_kasus').val();

        $.ajax({
            url: "{{ route('kasus.index') }}/" + id,
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
            text: "Data yang dihapus tidak dapat dikembalikan!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ route('kasus.index') }}/" + id,
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
