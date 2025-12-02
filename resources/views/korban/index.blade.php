@extends('layouts.app')

@section('title', 'Monitored Sites - Digital Forensik')

@section('content')
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <h2 class="page-title">
                <i class="fas fa-globe me-2"></i>Monitored Sites
            </h2>
            <p class="text-muted mb-0">Manajemen situs yang dimonitor untuk deteksi defacement</p>
        </div>
        <div class="col-auto">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
                <i class="fas fa-plus me-2"></i>Tambah Site
            </button>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="korbanTable">
                <thead class="table-light">
                    <tr>
                        <th width="5%">No</th>
                        <th>Site URL</th>
                        <th>IP Address</th>
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
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="createModalLabel">
                    <i class="fas fa-plus-circle me-2"></i>Tambah Monitored Site
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="create_site_url" class="form-label">Site URL <span class="text-danger">*</span></label>
                        <input type="url" class="form-control" id="create_site_url" name="site_url" placeholder="https://example.com" required>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="mb-3">
                        <label for="create_ip_address" class="form-label">IP Address</label>
                        <input type="text" class="form-control" id="create_ip_address" name="ip_address" placeholder="Optional">
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="mb-3">
                        <label for="create_status" class="form-label">Status</label>
                        <select class="form-select" id="create_status" name="status">
                            <option value="UP">UP</option>
                            <option value="DOWN">DOWN</option>
                            <option value="DEFACED">DEFACED</option>
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
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="editModalLabel">
                    <i class="fas fa-edit me-2"></i>Edit Data Korban
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editForm">
                @csrf
                @method('PUT')
                <input type="hidden" id="edit_id_site" name="id_site">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_site_url" class="form-label">Site URL <span class="text-danger">*</span></label>
                        <input type="url" class="form-control" id="edit_site_url" name="site_url" required>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_ip_address" class="form-label">IP Address</label>
                        <input type="text" class="form-control" id="edit_ip_address" name="ip_address">
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_status" class="form-label">Status</label>
                        <select class="form-select" id="edit_status" name="status">
                            <option value="UP">UP</option>
                            <option value="DOWN">DOWN</option>
                            <option value="DEFACED">DEFACED</option>
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
    let table = $('#korbanTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('monitored_sites.getData') }}",
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
            { data: 'ip_address', name: 'ip_address' },
            { data: 'status', name: 'status' },
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
            url: "{{ route('monitored_sites.store') }}",
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
            url: "{{ route('monitored_sites.index') }}/" + id + "/edit",
            type: 'GET',
                success: function(response) {
                if (response.success) {
                    $('#edit_id_site').val(response.data.id_site);
                    $('#edit_site_url').val(response.data.site_url);
                    $('#edit_ip_address').val(response.data.ip_address);
                    $('#edit_status').val(response.data.status ?? 'UP');
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
        let id = $('#edit_id_site').val();

        $.ajax({
            url: "{{ route('monitored_sites.index') }}/" + id,
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
                    url: "{{ route('monitored_sites.index') }}/" + id,
                    type: 'DELETE',
                    success: function(response) {
                        if (response.success) {
                            table.ajax.reload();
                            showToast('success', response.message);
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 400) {
                            showToast('error', xhr.responseJSON.message);
                        } else {
                            showToast('error', 'Gagal menghapus data');
                        }
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
