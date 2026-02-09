@extends('layouts.app')

@section('title', 'Tambah PIC DMS')

@section('content')

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="card-title mb-1">Tambah PIC DMS</h4>
                        <p class="text-muted mb-0">Person In Charge Document Management System</p>
                    </div>
                    <a href="{{ route('pic.index') }}" class="btn btn-secondary">
                        <i class="mdi mdi-arrow-left"></i> Kembali
                    </a>
                </div>

                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Terjadi kesalahan:</strong>
                        <ul class="mb-0 mt-2">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <form action="{{ route('pic.store') }}" method="POST">
                    @csrf

                    <div class="mb-4">
                        <label for="ketua_nip" class="form-label">Ketua PIC DMS <span class="text-danger">*</span></label>
                        <select class="form-select @error('ketua_nip') is-invalid @enderror"
                                id="ketua_nip"
                                name="ketua_nip"
                                required>
                            <option value="">-- Pilih Ketua PIC DMS --</option>
                            @foreach($pegawai as $p)
                                <option value="{{ $p->nip }}" {{ old('ketua_nip') == $p->nip ? 'selected' : '' }}>
                                    {{ $p->nama }} ({{ $p->nip }})
                                </option>
                            @endforeach
                        </select>
                        @error('ketua_nip')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Ketua akan otomatis menjadi anggota tim</small>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Anggota Tim</label>
                                <div class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
                                    <div class="mb-2">
                                        <input type="text"
                                               class="form-control form-control-sm"
                                               id="searchAnggota"
                                               placeholder="Cari pegawai...">
                                    </div>
                                    <div id="anggotaList" style="padding-left: 5px;">
                                        @foreach($pegawai as $p)
                                            <div class="form-check anggota-item" style="padding-left: 1.5rem;">
                                                <input class="form-check-input"
                                                       type="checkbox"
                                                       name="anggota_nip[]"
                                                       value="{{ $p->nip }}"
                                                       id="anggota_{{ $p->nip }}"
                                                       {{ in_array($p->nip, old('anggota_nip', [])) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="anggota_{{ $p->nip }}">
                                                    {{ $p->nama }} <small class="text-muted">({{ $p->nip }})</small>
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                <small class="text-muted">Pilih anggota tim (ketua otomatis termasuk)</small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Instansi yang Dipegang</label>
                                <div class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
                                    <div class="mb-2">
                                        <input type="text"
                                               class="form-control form-control-sm"
                                               id="searchInstansi"
                                               placeholder="Cari instansi...">
                                    </div>
                                    <div id="instansiList" style="padding-left: 5px;">
                                        @foreach($instansi as $inst)
                                            <div class="form-check instansi-item" style="padding-left: 1.5rem;">
                                                <input class="form-check-input"
                                                       type="checkbox"
                                                       name="instansi_id[]"
                                                       value="{{ $inst->id }}"
                                                       id="instansi_{{ $inst->id }}"
                                                       {{ in_array($inst->id, old('instansi_id', [])) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="instansi_{{ $inst->id }}">
                                                    {{ $inst->nama }}
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                <small class="text-muted">Pilih instansi yang akan dimonitor oleh tim ini</small>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3" style="padding-left: 5px;">
                        <div class="form-check form-switch" style="padding-left: 2.5rem;">
                            <input class="form-check-input"
                                   type="checkbox"
                                   id="is_active"
                                   name="is_active"
                                   value="1"
                                   {{ old('is_active', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                Status Aktif
                            </label>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="mdi mdi-content-save"></i> Simpan
                        </button>
                        <a href="{{ route('pic.index') }}" class="btn btn-secondary">
                            <i class="mdi mdi-close"></i> Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Search Anggota
    document.getElementById('searchAnggota').addEventListener('keyup', function() {
        const searchTerm = this.value.toLowerCase();
        document.querySelectorAll('.anggota-item').forEach(item => {
            const text = item.textContent.toLowerCase();
            item.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    });

    // Search Instansi
    document.getElementById('searchInstansi').addEventListener('keyup', function() {
        const searchTerm = this.value.toLowerCase();
        document.querySelectorAll('.instansi-item').forEach(item => {
            const text = item.textContent.toLowerCase();
            item.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    });

    // Auto-check ketua when selected
    document.getElementById('ketua_nip').addEventListener('change', function() {
        const ketuaNip = this.value;
        if (ketuaNip) {
            const checkbox = document.getElementById('anggota_' + ketuaNip);
            if (checkbox) {
                checkbox.checked = true;
            }
        }
    });
});
</script>

@endsection
