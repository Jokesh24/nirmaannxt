<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Live Rates | NirmaanNXT</title>
    @include('admin.includes.header_links')

    <style>
        .page-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .rate-grid {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .rate-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px 24px;
            display: grid;
            grid-template-columns: minmax(260px, 2fr) 1.5fr 1.5fr auto;
            align-items: center;
            gap: 20px;
            transition: all 0.2s ease;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        }

        .rate-card:hover {
            border-color: #cbd5e1;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
        }

        .material-icon {
            width: 44px;
            height: 44px;
            border-radius: 8px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            font-size: 18px;
            flex-shrink: 0;
        }

        .input-group-custom {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-group-custom .prefix {
            position: absolute;
            left: 14px;
            color: #64748b;
            font-weight: 600;
            font-size: 15px;
        }

        .input-group-custom input {
            padding-left: 32px;
            font-weight: 600;
            font-size: 15px;
            height: 46px;
            border-radius: 8px;
        }

        /* Dynamic Trend Colors */
        .trend-up {
            color: #16a34a !important;
            border-color: #bbf7d0 !important;
            background-color: #f0fdf4 !important;
        }

        .trend-down {
            color: #dc2626 !important;
            border-color: #fecaca !important;
            background-color: #fef2f2 !important;
        }

        .input-label {
            font-size: 12px;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
            display: block;
        }

        .ticker-preview {
            background: #334155;
            border-radius: 12px;
            padding: 5px 5px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 20px;
            overflow: hidden;
            box-shadow: inset 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .ticker-badge {
            background: #ea580c;
            color: white;
            padding: 12px 16px;
            border-radius: 8px;
            font-weight: 700;
            display: flex;
            flex-direction: column;
            align-items: center;
            line-height: 1.2;
        }

        .ticker-badge i {
            font-size: 20px;
            margin-bottom: 4px;
        }

        .ticker-items {
            display: flex;
            gap: 30px;
            color: white;
        }
        
        .name-input {
            font-weight: 700 !important; 
            text-transform: uppercase;
            height: 38px !important;
        }
        
        .unit-input {
            font-size: 13px !important; 
            font-weight: 600 !important;
            height: 32px !important;
        }
    </style>
</head>

<body>

    @include('admin.includes.sidebar')
    @include('admin.includes.header')

    <div class="content">

        <div class="page-head">
            <div>
                <h1 class="page-title">Live Market Rates</h1>
                <div class="section-title">Manage the real-time ticker prices displayed on the mobile app home screen
                </div>
            </div>
            <div class="action-group d-flex gap-2">
                <button type="button" class="btn btn-outline-dark px-3 py-2 font-weight-bold" onclick="addNewRateRow()">
                    <i class="bi bi-plus-lg me-1"></i> Add New Material
                </button>
                <button type="button" class="btn btn-dark px-4 py-2 font-weight-bold" onclick="document.getElementById('ratesForm').submit();">
                    <i class="bi bi-cloud-arrow-up me-2"></i> Save & Publish Changes
                </button>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success"><i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
            </div>
        @endif

        <div class="ticker-preview">
            <div class="ticker-badge">
                <i class="bi bi-graph-up-arrow"></i>
                <span>LIVE</span>
                <span style="font-size: 11px; font-weight:500;">Rates</span>
            </div>
            <div class="ticker-items">
                <div class="text-white" style="font-size: 15px; line-height:1.6;">
                    <i class="bi bi-info-circle me-1"></i> Changes saved below instantly sync to the NirmaanNXT mobile app. Use the trash icon to completely remove a material from the ticker.
                </div>
            </div>
        </div>

        <form id="ratesForm" action="{{ route('admin.live_rates.update') }}" method="POST">
            @csrf

            <div class="rate-grid" id="ratesContainer">
                @foreach($liveRates as $index => $rate)
                    <div class="rate-card">
                        <input type="hidden" name="rates[{{ $index }}][id]" value="{{ $rate->id }}">

                        <!-- Material Name & Unit Edit -->
                        <div class="d-flex gap-3 align-items-center">
                            <div class="material-icon">
                                @if(stripos(strtolower($rate->material_name), 'cement') !== false)
                                    <i class="bi bi-box-seam"></i>
                                @elseif(stripos(strtolower($rate->material_name), 'steel') !== false)
                                    <i class="bi bi-menu-app"></i>
                                @elseif(stripos(strtolower($rate->material_name), 'sand') !== false)
                                    <i class="bi bi-layers"></i>
                                @else
                                    <i class="bi bi-tag"></i>
                                @endif
                            </div>
                            <div class="flex-grow-1">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light text-muted border-0">Unit</span>
                                    <input type="text" name="rates[{{ $index }}][unit]" class="form-control bg-light border-0 unit-input" 
                                        value="{{ $rate->unit }}" placeholder="e.g. bag, ton" required>
                                </div>
                                <input type="text" name="rates[{{ $index }}][material_name]" class="form-control name-input mb-2" 
                                    value="{{ $rate->material_name }}" placeholder="Material (e.g. CEMENT)" required>
                            </div>
                        </div>

                        <!-- Current Price -->
                        <div>
                            <label class="input-label">Current Market Price</label>
                            <div class="input-group-custom">
                                <span class="prefix">₹</span>
                                <input type="number" step="0.01" min="0" name="rates[{{ $index }}][price]"
                                    class="form-control" value="{{ $rate->price }}" required>
                            </div>
                        </div>

                        <!-- Price Change Indicator -->
                        <div>
                            <label class="input-label">24h Price Change (+ / -)</label>
                            <div class="input-group-custom">
                                <span class="prefix">₹</span>
                                <input type="number" step="0.01" name="rates[{{ $index }}][price_change]"
                                    class="form-control trend-input {{ $rate->price_change > 0 ? 'trend-up' : ($rate->price_change < 0 ? 'trend-down' : '') }}"
                                    value="{{ $rate->price_change }}" required oninput="updateTrendColor(this)">
                            </div>
                        </div>
                        
                        <!-- Remove Action -->
                        <div class="text-end">
                            <button type="button" class="btn btn-outline-danger border-0" onclick="this.closest('.rate-card').remove();" title="Remove Material">
                                <i class="bi bi-trash3-fill" style="font-size: 18px;"></i>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

        </form>

    </div>

    @include('admin.includes.footer_links')

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let rateIndex = {{ count($liveRates) }};

        function updateTrendColor(input) {
            let val = parseFloat(input.value);
            input.classList.remove('trend-up', 'trend-down');
            
            if (val > 0) {
                input.classList.add('trend-up');
            } else if (val < 0) {
                input.classList.add('trend-down');
            }
        }

        function addNewRateRow() {
            const container = document.getElementById('ratesContainer');
            const html = `
                <div class="rate-card">
                    <input type="hidden" name="rates[${rateIndex}][id]" value="">
                    
                    <div class="d-flex gap-3 align-items-center">
                        <div class="material-icon"><i class="bi bi-tag"></i></div>
                        <div class="flex-grow-1">
                        <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light text-muted border-0">Unit</span>
                                <input type="text" name="rates[${rateIndex}][unit]" class="form-control bg-light border-0 unit-input" 
                                    placeholder="e.g. bag, ton" required>
                            </div>
                            <input type="text" name="rates[${rateIndex}][material_name]" class="form-control name-input mb-2" 
                                placeholder="Material Name" required>
                            
                        </div>
                    </div>

                    <div>
                        <label class="input-label">Current Market Price</label>
                        <div class="input-group-custom">
                            <span class="prefix">₹</span>
                            <input type="number" step="0.01" min="0" name="rates[${rateIndex}][price]" class="form-control" value="0.00" required>
                        </div>
                    </div>

                    <div>
                        <label class="input-label">24h Price Change (+ / -)</label>
                        <div class="input-group-custom">
                            <span class="prefix">₹</span>
                            <input type="number" step="0.01" name="rates[${rateIndex}][price_change]" class="form-control trend-input" value="0.00" required oninput="updateTrendColor(this)">
                        </div>
                    </div>
                    
                    <div class="text-end">
                        <button type="button" class="btn btn-outline-danger border-0" onclick="this.closest('.rate-card').remove();" title="Remove Material">
                            <i class="bi bi-trash3-fill" style="font-size: 18px;"></i>
                        </button>
                    </div>
                </div>
            `;
            
            container.insertAdjacentHTML('beforeend', html);
            rateIndex++;
            window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
        }

        $(document).ready(function () {
            // Apply initial trend colors on load
            $('.trend-input').each(function() {
                updateTrendColor(this);
            });
        });
    </script>
</body>

</html>