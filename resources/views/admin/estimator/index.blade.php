<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>AI Estimator Rules | NirmaanNXT</title>
    @include('admin.includes.header_links')

    <style>
        .filter-card {
            background: #f8fafc;
            border: 1px solid #e9eef4;
            border-radius: 18px;
            padding: 20px;
            margin-bottom: 24px;
        }

        .icon-btn {
            width: 44px;
            height: 44px;
            border: none;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: 0.2s ease;
            font-size: 18px;
        }

        .btn-search {
            background: #0f172a;
            color: #fff;
        }

        .btn-search:hover {
            background: #1e293b;
            transform: translateY(-1px);
            color: #fff;
        }

        .btn-refresh {
            background: #e2e8f0;
            color: #0f172a;
        }

        .btn-refresh:hover {
            background: #cbd5e1;
            transform: translateY(-1px);
            color: #0f172a;
        }

        .pagination-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            margin-top: 24px;
        }

        .pagination-info {
            font-size: 14px;
            color: #64748b;
            font-weight: 500;
        }

        /* --- Custom Modal Styles --- */
        .modal-product-img {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            object-fit: cover;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
        }

        .modal-product-title {
            font-weight: 700;
            color: #0f172a;
            font-size: 14px;
        }

        .qty-badge {
            background: #eff6ff;
            color: #1d4ed8;
            font-weight: 700;
            padding: 6px 12px;
            border-radius: 8px;
            border: 1px solid #bfdbfe;
            display: inline-block;
        }

        .unit-badge {
            background: #f1f5f9;
            color: #475569;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 6px;
            display: inline-block;
        }

        .btn-view-mapped {
            border-radius: 8px;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.2s ease;
        }

        .btn-view-mapped:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02);
        }

        @media (max-width: 768px) {
            .pagination-container {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>

<body>
    @include('admin.includes.sidebar')
    @include('admin.includes.header')

    <div class="content">
        <div class="page-head d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="page-title">AI Material Estimator Logic</h1>
                <div class="section-title">Configure quantities and map products to power the app's estimation engine
                </div>
            </div>
            <a href="{{ route('admin.estimator.create') }}" class="btn btn-dark px-4 py-2 font-weight-bold">
                <i class="bi bi-plus-lg me-2"></i> Create New Rule
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success"><i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}</div>
        @endif

        <!-- Search & Filter Area -->
        <form action="{{ route('admin.estimator.index') }}" method="GET" class="filter-card">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label font-weight-bold">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Search by Rule Name..."
                        value="{{ request('search') }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label font-weight-bold">Calculation Strategy</label>
                    <select name="type" class="form-select">
                        <option value="">All Strategies</option>
                        <option value="area" {{ request('type') == 'area' ? 'selected' : '' }}>By Area (Sq.Ft)</option>
                        <option value="bhk" {{ request('type') == 'bhk' ? 'selected' : '' }}>By House Type (BHK)</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label font-weight-bold">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <div class="d-flex gap-2">
                        <button type="submit" class="icon-btn btn-search" title="Search"><i
                                class="bi bi-search"></i></button>
                        <a href="{{ route('admin.estimator.index') }}" class="icon-btn btn-refresh"
                            title="Reset Filters"><i class="bi bi-arrow-clockwise"></i></a>
                    </div>
                </div>
            </div>
        </form>

        <div class="card border-0 shadow-sm rounded-3 mt-3">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="bg-light text-secondary">
                            <tr>
                                <th class="ps-4">#</th>
                                <th>Rule Name</th>
                                <th>Calculation Strategy</th>
                                <th>Included Materials</th>
                                <th>Status</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($projects as $key => $project)
                                <tr>
                                    <td class="ps-4 text-muted">{{ $projects->firstItem() + $key }}</td>
                                    <td class="font-weight-bold text-dark">{{ $project->name }}</td>
                                    <td>
                                        @if($project->type == 'area')
                                            <span class="badge bg-info text-dark px-3 py-2"><i
                                                    class="bi bi-bounding-box-circles me-1"></i> By Area (Sq.Ft)</span>
                                        @else
                                            <span class="badge bg-warning text-dark px-3 py-2"><i
                                                    class="bi bi-house-door me-1"></i> By House Type (BHK)</span>
                                        @endif
                                    </td>
                                    <td>
                                        <!-- INSIDE YOUR INDEX BLADE FOR EACH LOOP -->
                                        @php
                                            $mappedData = $project->materials->map(function ($m) {
                                                $productName = ($m->variant && $m->variant->product) ? $m->variant->product->name : '';
                                                $variantName = $m->variant ? $m->variant->variant_name : 'Variant Unavailable';
                                                $image = ($m->variant && $m->variant->image) ? asset('uploads/products/' . $m->variant->image) : null;

                                                return [
                                                    'category' => $m->category->name ?? 'Category Unassigned',
                                                    'product' => $productName . ' (' . $variantName . ')',
                                                    'image' => $image,
                                                    'qty' => $m->base_quantity,
                                                    'unit' => $m->display_unit
                                                ];
                                            })->toJson();
                                        @endphp
                                        <!-- REST REMAINS THE SAME -->

                                        <button type="button" class="btn btn-outline-secondary btn-view-mapped"
                                            data-bs-toggle="modal" data-bs-target="#viewMaterialsModal"
                                            data-rule-name="{{ $project->name }}"
                                            data-strategy="{{ $project->type == 'area' ? 'By Area (Sq.Ft)' : 'By House Type (BHK)' }}"
                                            data-materials="{{ $mappedData }}">
                                            <i class="bi bi-box-seam text-primary me-1"></i>
                                            {{ $project->materials->count() }} Mapped Items
                                        </button>
                                    </td>
                                    <td>
                                        @if($project->status == 1)
                                            <span class="badge bg-success px-3 py-2">Active</span>
                                        @else
                                            <span class="badge bg-secondary px-3 py-2">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-end pe-4">
                                        <a href="{{ route('admin.estimator.edit', $project->id) }}"
                                            class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i> Edit</a>
                                        <a href="{{ route('admin.estimator.delete', $project->id) }}"
                                            class="btn btn-sm btn-outline-danger ms-1"
                                            onclick="return confirm('Are you sure you want to completely remove this estimation rule?')"><i
                                                class="bi bi-trash"></i> Delete</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="bi bi-calculator fs-2 d-block mb-2"></i>
                                        No estimation rules configured yet or matching your search criteria.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div class="pagination-container">
            <div class="pagination-info">
                Showing {{ $projects->firstItem() ?? 0 }}–{{ $projects->lastItem() ?? 0 }} of {{ $projects->total() }}
                entries
            </div>
            <nav>
                {{ $projects->appends(request()->query())->links('pagination::bootstrap-4') }}
            </nav>
        </div>

    </div>

    <!-- Mapped Items View Modal -->
    <div class="modal fade" id="viewMaterialsModal" tabindex="-1" aria-labelledby="viewMaterialsModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
                <div class="modal-header bg-light border-bottom-0 pb-3 pt-4 px-4" style="border-radius: 16px 16px 0 0;">
                    <div>
                        <h5 class="modal-title font-weight-bold text-dark mb-1" id="modalRuleName">Rule Name</h5>
                        <span class="badge bg-secondary" id="modalStrategy">Strategy</span>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-secondary"
                                style="font-size: 12px; text-transform:uppercase; letter-spacing:0.5px;">
                                <tr>
                                    <th class="ps-4 border-top-0 border-bottom-0">Product Assignment</th>
                                    <th class="border-top-0 border-bottom-0">Category</th>
                                    <th class="border-top-0 border-bottom-0">Allocated Quantity</th>
                                    <th class="pe-4 border-top-0 border-bottom-0">Unit Format</th>
                                </tr>
                            </thead>
                            <tbody id="materialsTableBody">
                                <!-- Dynamic Content injected via JS -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top-0 px-4 py-3" style="border-radius: 0 0 16px 16px;">
                    <button type="button" class="btn btn-secondary px-4 font-weight-bold" data-bs-dismiss="modal">Close
                        Window</button>
                </div>
            </div>
        </div>
    </div>

    @include('admin.includes.footer_links')

    <!-- Modal Population Logic -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const viewModal = document.getElementById('viewMaterialsModal');
            if (viewModal) {
                viewModal.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;

                    // Extract data attributes
                    const ruleName = button.getAttribute('data-rule-name');
                    const strategy = button.getAttribute('data-strategy');
                    const materials = JSON.parse(button.getAttribute('data-materials'));

                    // Set header details
                    document.getElementById('modalRuleName').textContent = ruleName;
                    document.getElementById('modalStrategy').textContent = strategy;

                    // Populate table
                    const tbody = document.getElementById('materialsTableBody');
                    tbody.innerHTML = '';

                    if (materials.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="4" class="text-center py-5 text-muted"><i class="bi bi-box-seam fs-2 d-block mb-2"></i>No materials have been mapped to this specific rule.</td></tr>';
                        return;
                    }

                    materials.forEach(mat => {
                        const imgHtml = mat.image
                            ? `<img src="${mat.image}" class="modal-product-img shadow-sm" alt="Product Image">`
                            : `<div class="modal-product-img d-flex align-items-center justify-content-center text-muted shadow-sm"><i class="bi bi-box-seam fs-5"></i></div>`;

                        const row = `
                            <tr>
                                <td class="ps-4 py-3">
                                    <div class="d-flex align-items-center gap-3">
                                        ${imgHtml}
                                        <div class="modal-product-title">${mat.product}</div>
                                    </div>
                                </td>
                                <td class="py-3"><span class="text-muted fw-semibold" style="font-size: 13px;">${mat.category}</span></td>
                                <td class="py-3"><span class="qty-badge">${mat.qty}</span></td>
                                <td class="pe-4 py-3"><span class="unit-badge">${mat.unit}</span></td>
                            </tr>
                        `;
                        tbody.insertAdjacentHTML('beforeend', row);
                    });
                });
            }
        });
    </script>
</body>

</html>