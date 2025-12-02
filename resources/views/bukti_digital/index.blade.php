@extends('layouts.app')

@section('title', 'Bukti Digital - Digital Forensik')

@section('content')
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <h2 class="page-title">
                <i class="fas fa-file-alt me-2"></i>Bukti Digital
            </h2>
            <p class="text-muted mb-0">Manajemen bukti digital kasus forensik</p>
        </div>
        <div class="col-auto">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
                <i class="fas fa-plus me-2"></i>Tambah Bukti
            </button>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="buktiTable">
                <thead class="table-light">
                    <tr>
                        <th width="5%">No</th>
                        <th>Site URL</th>
                        <th>Jenis Kasus</th>
                        <th>Jenis Bukti</th>
                        <th>File</th>
                        <th>Tanggal Upload</th>
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
                    <i class="fas fa-plus-circle me-2"></i>Tambah Bukti Digital
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
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
                        <label for="create_jenis_bukti" class="form-label">Jenis Bukti <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="create_jenis_bukti" name="jenis_bukti" placeholder="Contoh: Screenshot, Video, Dokumen" required>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="mb-3">
                        <label for="create_file_url" class="form-label">Upload File <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" id="create_file_url" name="file_url" required>
                        <small class="text-muted">Format: jpg, jpeg, png, pdf, doc, docx, zip, rar. Max: 10MB</small>
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
                    <i class="fas fa-edit me-2"></i>Edit Bukti Digital
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editForm" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <input type="hidden" id="edit_id_evidence" name="id_evidence">
                <div class="modal-body">
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
                        <label for="edit_jenis_bukti" class="form-label">Jenis Bukti <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_jenis_bukti" name="jenis_bukti" required>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_file_url" class="form-label">Upload File Baru (Opsional)</label>
                        <input type="file" class="form-control" id="edit_file_url" name="file_url">
                        <small class="text-muted">Kosongkan jika tidak ingin mengganti file. Format: jpg, jpeg, png, pdf, doc, docx, zip, rar. Max: 10MB</small>
                        <div class="invalid-feedback"></div>
                        <div id="current_file_info" class="mt-2"></div>
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
    let table = $('#buktiTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('bukti_digital.getData') }}",
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
            { data: 'jenis_bukti', name: 'jenis_bukti' },
            { data: 'file_link', name: 'file_url' },
            { data: 'created_date_formatted', name: 'created_date' },
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

        let formData = new FormData(this);

        $.ajax({
            url: "{{ route('bukti_digital.store') }}",
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
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
            url: "{{ route('bukti_digital.index') }}/" + id + "/edit",
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    $('#edit_id_evidence').val(response.data.id_evidence);
                    $('#edit_id_kasus').val(response.data.id_kasus);
                    $('#edit_jenis_bukti').val(response.data.jenis_bukti);
                    $('#current_file_info').html('<small class="text-info"><i class="fas fa-file me-1"></i>File saat ini: ' + response.data.file_name + '</small>');
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
        let id = $('#edit_id_evidence').val();
        let formData = new FormData(this);

        $.ajax({
            url: "{{ route('bukti_digital.index') }}/" + id,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
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
            text: "File bukti digital akan dihapus permanen!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ route('bukti_digital.index') }}/" + id,
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
        $('#current_file_info').html('');
    });
});
</script>
@endpush
