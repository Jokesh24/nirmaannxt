<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>{{ isset($project) ? 'Edit Rule' : 'Create Estimator Rule' }} | NirmaanNXT</title>
    @include('admin.includes.header_links')
    <style>
        .dynamic-row {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px 24px;
            display: grid;
            grid-template-columns: 2fr 2fr 1.5fr 1fr auto;
            align-items: center;
            gap: 20px;
            margin-bottom: 16px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        }

        .dynamic-row:hover {
            border-color: #cbd5e1;
        }

        .input-label {
            font-size: 11px;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            margin-bottom: 6px;
            display: block;
            letter-spacing: 0.5px;
        }

        .instruction-box {
            background: #f8fafc;
            border-left: 4px solid #0f172a;
            padding: 16px 20px;
            border-radius: 0 8px 8px 0;
            margin-bottom: 30px;
        }

        .text-primary {
            color: #F28D3C !important;
        }

        .unit-config-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
        }
    </style>
</head>

<body>
    @include('admin.includes.sidebar')
    @include('admin.includes.header')

    <div class="content">
        <div class="page-head d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="page-title">{{ isset($project) ? 'Edit Estimator Rule' : 'Create Estimator Rule' }}</h1>
                <div class="section-title">Define required quantities mapping directly to catalog products</div>
            </div>
            <a href="{{ route('admin.estimator.index') }}" class="btn btn-outline-dark px-4"><i
                    class="bi bi-arrow-left me-2"></i>Back to List</a>
        </div>

        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li> @endforeach
                </ul>
            </div>
        @endif

        <form
            action="{{ isset($project) ? route('admin.estimator.update', $project->id) : route('admin.estimator.store') }}"
            method="POST">
            @csrf

            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <label class="input-label">Configuration Strategy Type</label>
                    <select name="type" id="configType" class="form-select font-weight-bold" onchange="updateUI()">
                        <option value="area" {{ (isset($project) && $project->type == 'area') ? 'selected' : '' }}>By Area
                            (Sq.Ft, Sq.M & Thickness)</option>
                        <option value="bhk" {{ (isset($project) && $project->type == 'bhk') ? 'selected' : '' }}>By House
                            Type (BHK Flat Rate)</option>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="input-label">Rule Display Name</label>
                    <input type="text" name="name" class="form-control font-weight-bold"
                        value="{{ old('name', $project->name ?? '') }}" placeholder="e.g. Standard Slab Estimator"
                        required>
                </div>
                <div class="col-md-3">
                    <label class="input-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="1" {{ (isset($project) && $project->status == 1) ? 'selected' : '' }}>Active
                        </option>
                        <option value="0" {{ (isset($project) && $project->status == 0) ? 'selected' : '' }}>Inactive
                        </option>
                    </select>
                </div>
            </div>

            <!-- DYNAMIC UNITS CONFIGURATION BLOCK -->
            <div id="unitConfigurationBlock" style="display: none;">
                <div class="instruction-box mb-4">
                    <h6 class="font-weight-bold text-dark mb-2"><i class="bi bi-gear-fill me-2"></i>Unit Calculation
                        Factors</h6>
                    <p class="mb-0 text-secondary" style="font-size:14px; line-height:1.6;">
                        Provide the multiplier factor for every available unit. When the user enters a measurement on
                        the app, the engine will multiply <b>(Area Value) × (Thickness Value) × (Material Base Qty)</b>.
                    </p>
                </div>

                <div class="row g-4 mb-5">
                    <!-- Slab Area Units -->
                    <div class="col-md-6">
                        <div class="unit-config-card shadow-sm">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="m-0 font-weight-bold text-dark"><i
                                        class="bi bi-bounding-box text-primary me-2"></i>Slab Area Units</h6>
                                <button type="button" class="btn btn-sm btn-outline-primary"
                                    onclick="openUnitModal('area')">+ Add Unit</button>
                            </div>
                            <div class="row g-3" id="areaUnitsContainer">
                                @foreach($areaUnits as $unit)
                                    <div class="col-6">
                                        <label
                                            style="font-size: 12px; font-weight: 600; color: #475569;">{{ $unit->unit_name }}
                                            (Factor)</label>
                                        <input type="number" step="0.0001" name="area_units[{{ $unit->id }}]"
                                            class="form-control bg-light unit-input"
                                            value="{{ old('area_units.' . $unit->id, isset($projectUnitValues) ? ($projectUnitValues[$unit->id] ?? '') : '') }}"
                                            placeholder="e.g. 1.00">
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <!-- Thickness Units -->
                    <div class="col-md-6">
                        <div class="unit-config-card shadow-sm">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="m-0 font-weight-bold text-dark"><i
                                        class="bi bi-layers text-primary me-2"></i>Thickness Units</h6>
                                <button type="button" class="btn btn-sm btn-outline-primary"
                                    onclick="openUnitModal('thickness')">+ Add Unit</button>
                            </div>
                            <div class="row g-3" id="thicknessUnitsContainer">
                                @foreach($thicknessUnits as $unit)
                                    <div class="col-6">
                                        <label
                                            style="font-size: 12px; font-weight: 600; color: #475569;">{{ $unit->unit_name }}
                                            (Factor)</label>
                                        <input type="number" step="0.0001" name="thickness_units[{{ $unit->id }}]"
                                            class="form-control bg-light unit-input"
                                            value="{{ old('thickness_units.' . $unit->id, isset($projectUnitValues) ? ($projectUnitValues[$unit->id] ?? '') : '') }}"
                                            placeholder="e.g. 1.00">
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- END DYNAMIC UNITS -->

            <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-3">
                <h5 class="font-weight-bold text-dark m-0">Materials & Products Matrix</h5>
                <button type="button" class="btn btn-outline-primary btn-sm px-3 font-weight-bold"
                    onclick="addMaterialRow()">
                    <i class="bi bi-plus-lg me-1"></i> Add Material
                </button>
            </div>

            <div id="materialsContainer"></div>

            <div class="text-end mt-5 pt-3 border-top">
                <button type="submit" class="btn btn-dark px-5 py-2 font-weight-bold">
                    <i class="bi bi-check-circle me-2"></i> {{ isset($project) ? 'Update Rule' : 'Save Rule Engine' }}
                </button>
            </div>
        </form>
    </div>

    <!-- SEAMLESS ADD UNIT MODAL -->
    <div class="modal fade" id="addUnitModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content" style="border-radius: 12px;">
                <div class="modal-header border-0 pb-0">
                    <h6 class="modal-title font-weight-bold" id="modalUnitTitle">Add New Unit</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="modalUnitType">
                    <div class="form-group mb-3">
                        <label class="input-label">Unit Name (e.g., Sq.M, mm)</label>
                        <input type="text" id="modalUnitName" class="form-control" required>
                    </div>
                    <button type="button" class="btn btn-primary w-100 font-weight-bold" onclick="submitNewUnit()">Add
                        Unit To Form</button>
                </div>
            </div>
        </div>
    </div>

    @include('admin.includes.footer_links')

    <script>
        const categories = @json($categories);
        let rowIndex = 0;

        function updateUI() {
            const type = document.getElementById('configType').value;
            const unitBlock = document.getElementById('unitConfigurationBlock');
            const unitInputs = document.querySelectorAll('.unit-input');

            if (type === 'area') {
                unitBlock.style.display = 'block';
                unitInputs.forEach(input => {
                    input.setAttribute('required', 'required');
                    input.removeAttribute('disabled'); // Ensure they are submitted
                });
                document.querySelectorAll('.qty-label-dynamic').forEach(el => el.innerText = "Base Material Qty");
            } else {
                unitBlock.style.display = 'none';
                unitInputs.forEach(input => {
                    input.removeAttribute('required');
                    input.setAttribute('disabled', 'disabled'); // Prevent submitting empty values to fix validation error
                });
                document.querySelectorAll('.qty-label-dynamic').forEach(el => el.innerText = "Total Required Qty");
            }
        }

        // ---- AJAX SEAMLESS UNIT ADDITION ----
        function openUnitModal(type) {
            document.getElementById('modalUnitType').value = type;
            document.getElementById('modalUnitTitle').innerText = type === 'area' ? 'Add Slab Area Unit' : 'Add Thickness Unit';
            document.getElementById('modalUnitName').value = '';
            new bootstrap.Modal(document.getElementById('addUnitModal')).show();
        }

        function submitNewUnit() {
            const type = document.getElementById('modalUnitType').value;
            const name = document.getElementById('modalUnitName').value;

            if (!name) return alert('Unit name is required.');

            fetch('{{ route("admin.estimator.add_unit_ajax") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ type: type, unit_name: name })
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const containerId = type === 'area' ? 'areaUnitsContainer' : 'thicknessUnitsContainer';
                        const inputName = type === 'area' ? 'area_units' : 'thickness_units';

                        const newHtml = `
                        <div class="col-6">
                            <label style="font-size: 12px; font-weight: 600; color: #475569;">${data.unit.unit_name} (Factor)</label>
                            <input type="number" step="0.0001" name="${inputName}[${data.unit.id}]" class="form-control bg-light unit-input" required placeholder="e.g. 1.00">
                        </div>
                    `;
                        document.getElementById(containerId).insertAdjacentHTML('beforeend', newHtml);
                        bootstrap.Modal.getInstance(document.getElementById('addUnitModal')).hide();
                    }
                });
        }
        // -------------------------------------

        function fetchVariants(categoryId, selectElementId, preselectedId = null) {
            const selectEl = document.getElementById(selectElementId);
            selectEl.innerHTML = '<option value="">Loading Variants...</option>';

            if (!categoryId) {
                selectEl.innerHTML = '<option value="">Select a Category first</option>';
                return;
            }

            fetch(`{{ url('admin/estimator/get-variants') }}/${categoryId}`)
                .then(res => res.json())
                .then(data => {
                    let html = '<option value="">Select Specific Variant</option>';
                    data.forEach(variant => {
                        html += `<option value="${variant.id}" ${preselectedId == variant.id ? 'selected' : ''}>${variant.name}</option>`;
                    });
                    selectEl.innerHTML = html;
                });
        }

        function addMaterialRow(data = null) {
            const type = document.getElementById('configType').value;
            const qtyLabel = type === 'area' ? 'Base Material Qty' : 'Total Required Qty';

            let catOptions = '<option value="">Select Category</option>';
            categories.forEach(c => {
                catOptions += `<option value="${c.id}" ${(data && data.category_id == c.id) ? 'selected' : ''}>${c.name}</option>`;
            });

            const html = `
                <div class="dynamic-row" id="row_${rowIndex}">
                    <div>
                        <label class="input-label">Category</label>
                        <select name="materials[${rowIndex}][category_id]" class="form-select bg-light" required onchange="fetchVariants(this.value, 'var_${rowIndex}')">
                            ${catOptions}
                        </select>
                    </div>
                    <div>
                        <label class="input-label">Mapped Variant</label>
                        <select name="materials[${rowIndex}][variant_id]" id="var_${rowIndex}" class="form-select bg-light" required>
                            <option value="">Select Category First</option>
                        </select>
                    </div>
                    <div>
                        <label class="input-label text-primary qty-label-dynamic">${qtyLabel}</label>
                        <input type="number" step="0.0001" min="0" name="materials[${rowIndex}][base_quantity]" class="form-control" value="${data ? data.base_quantity : ''}" placeholder="e.g. 0.0113 or 450" required>
                    </div>
                    <div>
                        <label class="input-label">Display Unit</label>
                        <input type="text" name="materials[${rowIndex}][display_unit]" class="form-control" value="${data ? data.display_unit : ''}" placeholder="e.g. Bags, cum" required>
                    </div>
                    <div class="text-end pt-4">
                        <button type="button" class="btn btn-outline-danger border-0" onclick="document.getElementById('row_${rowIndex}').remove()"><i class="bi bi-trash3-fill" style="font-size:18px;"></i></button>
                    </div>
                </div>
            `;
            document.getElementById('materialsContainer').insertAdjacentHTML('beforeend', html);

            // Preload on Edit
            if (data && data.category_variants) {
                let varHtml = '<option value="">Select Specific Variant</option>';
                data.category_variants.forEach(v => {
                    varHtml += `<option value="${v.id}" ${data.variant_id == v.id ? 'selected' : ''}>${v.name}</option>`;
                });
                document.getElementById(`var_${rowIndex}`).innerHTML = varHtml;
            }

            rowIndex++;
        }

        document.addEventListener('DOMContentLoaded', () => {
            updateUI();
            @if(isset($project) && $project->materials->count() > 0)
                const existingMaterials = @json($project->materials);
                existingMaterials.forEach(mat => addMaterialRow(mat));
            @else
                addMaterialRow();
            @endif
        });
    </script>
</body>

</html>