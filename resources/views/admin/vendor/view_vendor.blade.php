<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>View Vendor Details</title>
    @include('admin.includes.header_links')
    <style>
        .info-card {
            background: #fff;
            border-radius: 20px;
            padding: 30px;
            border: 1px solid #edf2f7;
        }

        .label-text {
            color: #64748b;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .value-text {
            color: #1e293b;
            font-size: 16px;
            font-weight: 500;
            line-height: 1.6;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .value-text p {
            margin-bottom: 1rem;
            white-space: normal;
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 30px;
        }

        .profile-logo {
            width: 80px;
            height: 80px;
            border-radius: 15px;
            object-fit: cover;
            border: 3px solid #f8fafc;
        }

        .section-break {
            margin: 30px 0;
            border-top: 1px dashed #cbd5e1;
        }

        .badge-supply {
            background: #eff6ff;
            color: #1d4ed8;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            margin-right: 8px;
            display: inline-block;
            margin-bottom: 8px;
        }

        /* Map Styles */
        #mini-map {
            height: 120px;
            width: 100%;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        #mini-map::after {
            content: 'Click to expand map';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.6);
            color: #fff;
            font-size: 12px;
            text-align: center;
            padding: 4px;
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
        }

        #mini-map:hover::after {
            opacity: 1;
        }

        #large-map {
            height: 60vh;
            width: 100%;
            border-radius: 10px;
        }
    </style>
</head>

<body>
    @include('admin.includes.sidebar')
    @include('admin.includes.header')

    <div class="content">
        <div class="page-head">
            <h1 class="page-title">Vendor Profile</h1>
            <div class="action-group">
                <button class="btn-action btn-back" onclick="history.back()">← Back</button>
                <a href="{{ route('edit_vendor', $vendor->id) }}" class="btn-action btn-manage">Edit Vendor</a>
            </div>
        </div>

        <div class="info-card shadow-sm">
            <div class="profile-header">
                <img src="{{ $vendor->company_logo ? asset('uploads/company_logos/' . $vendor->company_logo) : 'https://via.placeholder.com/80' }}"
                    class="profile-logo shadow-sm">
                <div>
                    <h2 class="mb-1 fw-bold text-dark">{{ $vendor->business_name }}</h2>
                    <div class="text-muted mb-2"><i class="bi bi-briefcase"></i>
                        {{ $vendor->businessType->name ?? 'N/A' }}</div>
                    <span
                        class="status-badge {{ $vendor->status == 1 ? 'status-active' : ($vendor->status == 2 ? 'bg-danger text-white' : 'status-inactive') }}">
                        {{ $vendor->status == 1 ? 'Active' : ($vendor->status == 2 ? 'Blocked' : 'Inactive') }}
                    </span>
                </div>
            </div>

            <!-- Business & Contact -->
            <h5 class="fw-bold mb-4 text-primary">Business & Contact Details</h5>
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="label-text">Contact Person</div>
                    <div class="value-text">{{ $vendor->contact_person }}</div>
                </div>
                <div class="col-md-3">
                    <div class="label-text">Mobile Number</div>
                    <div class="value-text">{{ $vendor->business_mobile }}</div>
                </div>
                <div class="col-md-3">
                    <div class="label-text">Email Address</div>
                    <div class="value-text">{{ $vendor->business_email }}</div>
                </div>
            </div>

            <div class="section-break"></div>

            <!-- Tax & Registration -->
            <h5 class="fw-bold mb-4 text-primary">Tax & Registration</h5>
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="label-text">GSTIN Number</div>
                    <div class="value-text">{{ $vendor->gst_number ?? 'N/A' }}</div>
                </div>
                <div class="col-md-3">
                    <div class="label-text">PAN Number</div>
                    <div class="value-text">{{ $vendor->pan_number ?? 'N/A' }}</div>
                </div>
                <div class="col-md-4">
                    <div class="label-text">GST Certificate</div>
                    @if($vendor->gst_certificate)
                        <a href="{{ asset('uploads/vendor_docs/' . $vendor->gst_certificate) }}" target="_blank"
                            class="btn btn-sm btn-outline-primary">View Document</a>
                    @else
                        <span class="text-muted">Not Uploaded</span>
                    @endif
                </div>
            </div>

            <div class="section-break"></div>

            <!-- Location & Operations -->
            <h5 class="fw-bold mb-4 text-primary">Location & Inventory</h5>
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="label-text">Address</div>
                    <div class="value-text">{{ $vendor->address }}</div>
                </div>
                <div class="col-md-3">
                    <div class="label-text">Delivery Radius</div>
                    <div class="value-text">{{ $vendor->delivery_radius_km ?? '0' }} KM</div>
                </div>
                <div class="col-md-3">
                    <div class="label-text">Service Area Map</div>
                    @if($vendor->latitude && $vendor->longitude)
                        <!-- Mini Map Thumbnail -->
                        <div id="mini-map" data-bs-toggle="modal" data-bs-target="#mapModal" title="Click to open full map">
                        </div>
                    @else
                        <div class="value-text text-muted">Coordinates not set.</div>
                    @endif
                </div>

                <div class="col-md-12 mt-4">
                    <div class="label-text mb-2">Products Supplied</div>
                    <div>
                        @if(is_array($vendor->products_supplied) && count($vendor->products_supplied) > 0)
                            @foreach(App\Models\ProductCategory::whereIn('id', $vendor->products_supplied)->pluck('name') as $productName)
                                <span class="badge-supply">{{ $productName }}</span>
                            @endforeach
                        @else
                            <span class="text-muted">No products specified.</span>
                        @endif
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="label-text">Daily Capacity</div>
                    <div class="value-text">{{ $vendor->daily_capacity ?? 'N/A' }}</div>
                </div>
                <div class="col-md-3">
                    <div class="label-text">Weighbridge</div>
                    <div class="value-text">{{ $vendor->weighbridge_available ? 'Yes, Available' : 'No' }}</div>
                </div>
            </div>

            <div class="section-break"></div>

            <!-- Banking -->
            <h5 class="fw-bold mb-4 text-primary">Banking Details</h5>
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="label-text">Account Name</div>
                    <div class="value-text">{{ $vendor->bank_account_name ?? 'N/A' }}</div>
                </div>
                <div class="col-md-3">
                    <div class="label-text">Account No.</div>
                    <div class="value-text">{{ $vendor->bank_account_number ?? 'N/A' }}</div>
                </div>
                <div class="col-md-3">
                    <div class="label-text">IFSC Code</div>
                    <div class="value-text">{{ $vendor->bank_ifsc_code ?? 'N/A' }}</div>
                </div>
                <div class="col-md-3">
                    <div class="label-text">Cancelled Cheque</div>
                    @if($vendor->cancelled_cheque)
                        <a href="{{ asset('uploads/vendor_docs/' . $vendor->cancelled_cheque) }}" target="_blank"
                            class="btn btn-sm btn-outline-primary">View Cheque</a>
                    @else
                        <span class="text-muted">Not Uploaded</span>
                    @endif
                </div>
            </div>

            <div class="section-break"></div>

            <!-- Description -->
            <div class="row g-4">
                <div class="col-12">
                    <div class="label-text">Additional Description</div>
                    <div class="value-text p-0 border-0 mt-2">
                        @if($vendor->description) {!! $vendor->description !!}
                        @else <span class="text-muted italic">No description available.</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Map Modal -->
    <div class="modal fade" id="mapModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content" style="border-radius: 16px;">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Vendor Service Area</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="large-map"></div>
                </div>
            </div>
        </div>
    </div>

    @include('admin.includes.footer_links')

    <!-- Google Maps script -->
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyC68uSRK23hrzev5tCx1ePVfNiLeMhEP4c&callback=initMaps"
        async defer></script>
    <script>
        function initMaps() {
            const lat = {{ $vendor->latitude ?? 'null' }};
            const lng = {{ $vendor->longitude ?? 'null' }};
            const radiusKm = {{ $vendor->delivery_radius_km ?? 0 }};

            if (lat !== null && lng !== null) {
                const vendorLoc = { lat: parseFloat(lat), lng: parseFloat(lng) };
                const radiusMeters = radiusKm * 1000;

                // 1. Initialize Mini Map (Read-only, disabled interactions)
                const miniMap = new google.maps.Map(document.getElementById('mini-map'), {
                    zoom: 10,
                    center: vendorLoc,
                    disableDefaultUI: true,
                    zoomControl: false,
                    draggable: false,
                    scrollwheel: false,
                    disableDoubleClickZoom: true,
                    keyboardShortcuts: false
                });

                new google.maps.Marker({
                    position: vendorLoc,
                    map: miniMap,
                });

                new google.maps.Circle({
                    map: miniMap,
                    center: vendorLoc,
                    radius: radiusMeters,
                    fillColor: '#FF0000',
                    fillOpacity: 0.15,
                    strokeColor: '#FF0000',
                    strokeWeight: 1,
                });

                // 2. Initialize Large Map only when modal is opened (prevents grey box rendering issue)
                let largeMapInit = false;
                const mapModal = document.getElementById('mapModal');

                if (mapModal) {
                    mapModal.addEventListener('shown.bs.modal', function () {
                        if (!largeMapInit) {
                            const largeMap = new google.maps.Map(document.getElementById('large-map'), {
                                zoom: 11,
                                center: vendorLoc,
                            });

                            new google.maps.Marker({
                                position: vendorLoc,
                                map: largeMap,
                            });

                            new google.maps.Circle({
                                map: largeMap,
                                center: vendorLoc,
                                radius: radiusMeters,
                                fillColor: '#FF0000',
                                fillOpacity: 0.15,
                                strokeColor: '#FF0000',
                                strokeWeight: 1,
                            });

                            largeMapInit = true;
                        }
                    });
                }
            }
        }
    </script>
</body>

</html>