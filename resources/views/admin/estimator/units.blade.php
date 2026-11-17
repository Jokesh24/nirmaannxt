<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Measurement Units | NirmaanNXT</title>
    @include('admin.includes.header_links')
</head>

<body>
    @include('admin.includes.sidebar')
    @include('admin.includes.header')

    <div class="content">
        <div class="page-head mb-4">
            <h1 class="page-title">Manage Estimator Units</h1>
            <div class="section-title">Configure measurement units and their conversion algorithms.</div>
        </div>

        @if(session('success'))
            <div class="alert alert-success"><i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
            </div>
        @endif

        <div class="row g-4">
            <!-- Add Unit Form -->
            <div class="col-md-4">
                <div class="card shadow-sm border-0" style="border-radius: 16px;">
                    <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                        <h5 class="font-weight-bold text-dark m-0">Add New Unit</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.estimator.units.store') }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label font-weight-bold" style="font-size: 13px;">Unit
                                    Category</label>
                                <select name="type" class="form-select bg-light" required>
                                    <option value="area">Slab Area (Converts to Sq.Ft)</option>
                                    <option value="thickness">Concrete Thickness (Converts to Inches)</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label font-weight-bold" style="font-size: 13px;">Unit Name (e.g.
                                    Sq.M, mm)</label>
                                <input type="text" name="unit_name" class="form-control bg-light" required>
                            </div>
                            <div class="mb-4">
                                <label class="form-label font-weight-bold text-primary"
                                    style="font-size: 13px;">Conversion Multiplier</label>
                                <input type="number" step="0.0001" name="conversion_multiplier"
                                    class="form-control bg-light" placeholder="e.g. 10.7639" required>
                                <small class="text-muted d-block mt-1" style="font-size: 11px;">Multiplier required to
                                    convert this unit into the Base Unit (Sq.Ft or Inches).</small>
                            </div>
                            <button type="submit" class="btn btn-dark w-100 py-2 font-weight-bold">Save Unit</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Existing Units Table -->
            <div class="col-md-8">
                <div class="card shadow-sm border-0" style="border-radius: 16px;">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light text-secondary">
                                    <tr>
                                        <th class="ps-4">Type</th>
                                        <th>Unit Name</th>
                                        <th>Multiplier (Base Conv.)</th>
                                        <th class="text-end pe-4">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($units as $unit)
                                        <tr>
                                            <td class="ps-4">
                                                @if($unit->type == 'area')
                                                    <span class="badge bg-info text-dark px-2 py-1"><i
                                                            class="bi bi-bounding-box me-1"></i> Area</span>
                                                @else
                                                    <span class="badge bg-warning text-dark px-2 py-1"><i
                                                            class="bi bi-layers me-1"></i> Thickness</span>
                                                @endif
                                            </td>
                                            <td class="font-weight-bold">
                                                {{ $unit->unit_name }}
                                                @if($unit->is_base_unit)
                                                    <span class="badge bg-success ms-2" style="font-size: 10px;">CORE
                                                        BASE</span>
                                                @endif
                                            </td>
                                            <td class="text-primary font-weight-bold">{{ $unit->conversion_multiplier }}
                                            </td>
                                            <td class="text-end pe-4">
                                                @if(!$unit->is_base_unit)
                                                    <a href="{{ route('admin.estimator.units.delete', $unit->id) }}"
                                                        class="btn btn-sm btn-outline-danger border-0"
                                                        onclick="return confirm('Delete this measurement unit?')">
                                                        <i class="bi bi-trash3-fill"></i>
                                                    </a>
                                                @else
                                                    <span class="text-muted" style="font-size:12px;">Locked</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center py-4">No units found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @include('admin.includes.footer_links')
</body>

</html>