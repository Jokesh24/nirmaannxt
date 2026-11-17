<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>View App User Details</title>
    @include('admin.includes.header_links')
    <style>
        /* Base Styling */
        .info-card {
            background: #fff;
            border-radius: 16px;
            padding: 30px;
            border: 1px solid #edf2f7;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
        }

        .label-text {
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }

        .value-text {
            color: #1e293b;
            font-size: 15px;
            font-weight: 500;
            line-height: 1.5;
        }

        /* Profile Header */
        .profile-header {
            display: flex;
            align-items: center;
            gap: 24px;
            margin-bottom: 30px;
        }

        .profile-logo {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #f1f5f9;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            transition: opacity 0.3s ease;
        }

        .profile-logo:hover {
            opacity: 0.85;
        }

        /* Nav Tabs Customization */
        .custom-tabs {
            border-bottom: 2px solid #e2e8f0;
            margin-bottom: 25px;
        }

        .custom-tabs .nav-link {
            font-weight: 600;
            color: #64748b;
            border: none;
            border-bottom: 3px solid transparent;
            padding: 12px 24px;
            background: transparent;
            transition: all 0.2s ease;
        }

        .custom-tabs .nav-link:hover {
            color: #4338ca;
        }

        .custom-tabs .nav-link.active {
            color: #4338ca;
            border-bottom-color: #4338ca;
            background: transparent;
        }

        /* Nested Cards (Addresses & Banks) */
        .nested-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            height: 100%;
            transition: all 0.2s ease;
        }

        .nested-card:hover {
            border-color: #cbd5e1;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }

        .card-title-pill {
            background: #e0e7ff;
            color: #4338ca;
            padding: 5px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
            display: inline-block;
            margin-bottom: 15px;
        }

        /* Clickable Photo styling */
        .address-photo {
            width: 100%;
            height: 160px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: opacity 0.3s ease;
            border: 1px solid #e2e8f0;
        }

        .address-photo:hover {
            opacity: 0.85;
        }

        /* Mini Map Styling */
        .mini-map-wrapper {
            position: relative;
            width: 100%;
            height: 120px;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            cursor: pointer;
        }

        .mini-map-canvas {
            width: 100%;
            height: 100%;
        }

        .mini-map-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.1);
            z-index: 2;
            display: flex;
            align-items: center;
            justify-content: center;
            color: transparent;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .mini-map-wrapper:hover .mini-map-overlay {
            background: rgba(0, 0, 0, 0.5);
            color: #fff;
        }

        /* Modal Map Styling */
        #large-map {
            height: 60vh;
            width: 100%;
            border-radius: 8px;
        }
    </style>
</head>

<body>
    @include('admin.includes.sidebar')
    @include('admin.includes.header')

    <div class="content">
        <div class="page-head d-flex justify-content-between align-items-center mb-4">
            <h1 class="page-title m-0">App User Profile</h1>
            <button class="btn-action btn-back" onclick="history.back()">← Back</button>
        </div>

        @if(Session::has('success'))
            <div class="alert alert-success mb-4">{{ Session::get('success') }}</div>
        @endif

        <div class="info-card">

            <!-- Global Profile Header -->
            <div class="profile-header">
                <img src="{{ $user->profile_photo ? asset($user->profile_photo) : asset('public/assets/images/default-avatar.png') }}"
                    class="profile-logo" title="Click to view full image"
                    onclick="openImageModal('{{ $user->profile_photo ? asset($user->profile_photo) : asset('public/assets/images/default-avatar.png') }}', '{{ $user->name ?? 'Guest User' }} Profile Photo')">
                <div>
                    <h2 class="mb-1 fw-bold text-dark">{{ $user->name ?? 'Guest User' }}</h2>
                    <div class="text-muted mb-2">
                        <i class="bi bi-hash"></i> {{ $user->customer_code }} &nbsp;|&nbsp;
                        <i class="bi bi-telephone"></i> +91 {{ $user->mobile }}
                    </div>
                    <span
                        class="status-badge {{ $user->status == 1 ? 'status-active' : ($user->status == 2 ? 'bg-danger text-white' : 'status-inactive') }}">
                        {{ $user->status == 1 ? 'Active' : ($user->status == 2 ? 'Blocked' : 'Inactive') }}
                    </span>
                    <span class="status-badge bg-light text-dark border ms-2">
                        Gender: {{ $user->gender == 1 ? 'Male' : ($user->gender == 2 ? 'Female' : 'Not Specified') }}
                    </span>
                </div>

                <!-- Quick Status Toggle -->
                <div class="ms-auto bg-light p-3 rounded border">
                    <label class="label-text mb-2 d-block">Manage Access</label>
                    <form action="{{ route('update_app_user_status', $user->id) }}" method="POST">
                        @csrf
                        <div class="input-group">
                            <select name="status" class="form-select form-select-sm">
                                <option value="1" {{ $user->status == 1 ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ $user->status == 0 ? 'selected' : '' }}>Inactive</option>
                                <option value="2" {{ $user->status == 2 ? 'selected' : '' }}>Blocked</option>
                            </select>
                            <button type="submit" class="btn btn-sm btn-primary">Apply</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tab Navigation -->
            <ul class="nav nav-tabs custom-tabs" id="profileTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview"
                        type="button" role="tab">
                        <i class="bi bi-person me-2"></i>Overview
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="addresses-tab" data-bs-toggle="tab" data-bs-target="#addresses"
                        type="button" role="tab">
                        <i class="bi bi-geo-alt me-2"></i>Addresses ({{ $addresses->count() }})
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="banks-tab" data-bs-toggle="tab" data-bs-target="#banks" type="button"
                        role="tab">
                        <i class="bi bi-bank me-2"></i>Bank Accounts ({{ $bankAccounts->count() }})
                    </button>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content" id="profileTabsContent">

                <!-- 1. OVERVIEW TAB -->
                <div class="tab-pane fade show active" id="overview" role="tabpanel">
                    <h5 class="fw-bold mb-4 text-primary">Account Information</h5>
                    <div class="row g-4">
                        <div class="col-md-4">
                            <div class="label-text">Email Address</div>
                            <div class="value-text">{{ $user->email ?? 'Not Provided' }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="label-text">Registered On</div>
                            <div class="value-text">
                                {{ $user->created_at ? $user->created_at->format('d M, Y h:i A') : 'N/A' }}
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="label-text">Total Saved Addresses</div>
                            <div class="value-text">{{ $addresses->count() }}</div>
                        </div>
                    </div>
                </div>

                <!-- 2. ADDRESSES TAB -->
                <div class="tab-pane fade" id="addresses" role="tabpanel">
                    <div class="row g-4">
                        @forelse($addresses as $address)
                            <div class="col-lg-4 col-md-6">
                                <div class="nested-card position-relative">
                                    @if($address->is_selected)
                                        <span class="position-absolute top-0 end-0 badge bg-success m-3 z-3">Default</span>
                                    @endif

                                    <div class="card-title-pill"><i
                                            class="bi bi-bookmark me-1"></i>{{ $address->address_title }}</div>

                                    @if($address->site_photo)
                                        <img src="{{ asset($address->site_photo) }}" class="address-photo" alt="Site Photo"
                                            title="Click to view full image"
                                            onclick="openImageModal('{{ asset($address->site_photo) }}', '{{ $address->address_title }}')">
                                    @endif

                                    <div class="label-text mt-2">Ordering For</div>
                                    <div class="value-text mb-3">
                                        {{ $address->ordering_for == 1 ? 'Individual' : 'Business' }}
                                        @if($address->ordering_for == 2)
                                            <br><small class="text-muted">{{ $address->company_name }} (GSTIN:
                                                {{ $address->gstin ?? 'N/A' }})</small>
                                        @endif
                                    </div>

                                    <div class="label-text">Delivery Address</div>
                                    <div class="value-text mb-3">{{ $address->delivery_address }}</div>

                                    <div class="label-text">Billing Address</div>
                                    <div class="value-text mb-3">
                                        @if($address->is_billing_same)
                                            <span class="text-muted fst-italic">Same as Delivery Address</span>
                                        @else
                                            {{ $address->billing_address }}
                                        @endif
                                    </div>

                                    <div class="label-text">Location Map</div>
                                    <div class="mini-map-wrapper"
                                        onclick="openMapModal({{ $address->latitude }}, {{ $address->longitude }}, '{{ $address->address_title }}')">
                                        <div class="mini-map-overlay"><i class="bi bi-arrows-fullscreen me-2"></i>Expand Map
                                        </div>
                                        <div class="mini-map-canvas" data-lat="{{ $address->latitude }}"
                                            data-lng="{{ $address->longitude }}"></div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12">
                                <div class="text-center p-5 text-muted border rounded" style="background: #f8fafc;">
                                    <i class="bi bi-map fs-1 d-block mb-3"></i>
                                    <h5>No Addresses Found</h5>
                                    <p>This user hasn't saved any addresses yet.</p>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- 3. BANKS TAB -->
                <div class="tab-pane fade" id="banks" role="tabpanel">
                    <div class="row g-4">
                        @forelse($bankAccounts as $account)
                            <div class="col-lg-4 col-md-6">
                                <div class="nested-card">
                                    <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
                                        @if($account->bank && $account->bank->logo)
                                            <img src="{{ asset('uploads/banks/' . $account->bank->logo) }}" alt="Bank Logo"
                                                style="width: 45px; height: 45px; object-fit: contain; border-radius: 8px; border: 1px solid #e2e8f0; padding: 2px; background: #fff;">
                                        @else
                                            <div class="bg-primary text-white rounded d-flex align-items-center justify-content-center"
                                                style="width: 45px; height: 45px;">
                                                <i class="bi bi-bank2"></i>
                                            </div>
                                        @endif
                                        <div>
                                            <div class="fw-bold text-dark fs-6">{{ $account->bank->name ?? 'Unknown Bank' }}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="label-text">Account Holder Name</div>
                                    <div class="value-text mb-3">{{ $account->account_holder_name }}</div>

                                    <div class="label-text">Account Number</div>
                                    <div class="value-text mb-3 fw-bold font-monospace">{{ $account->account_number }}</div>

                                    <div class="label-text">IFSC Code</div>
                                    <div class="value-text mb-3 fw-bold font-monospace">{{ $account->ifsc_code }}</div>

                                    @if($account->upi_id)
                                        <div class="label-text">UPI ID</div>
                                        <div class="value-text text-primary">{{ $account->upi_id }}</div>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="col-12">
                                <div class="text-center p-5 text-muted border rounded" style="background: #f8fafc;">
                                    <i class="bi bi-wallet2 fs-1 d-block mb-3"></i>
                                    <h5>No Bank Accounts Found</h5>
                                    <p>This user hasn't saved any bank details yet.</p>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Image Preview Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 bg-transparent">
                <div class="modal-header border-0 pb-1 justify-content-end">
                    <button type="button" class="btn-close btn-close-black bg-white p-2" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body text-center p-0">
                    <img id="previewImage" src="" alt="Site Photo Preview" class="img-fluid rounded shadow-lg"
                        style="max-height: 85vh; border: 3px solid #fff;">
                    <div id="imageModalTitle" class="text-white mt-2 fw-bold fs-5 shadow-sm"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Map Preview Modal -->
    <div class="modal fade" id="mapModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content" style="border-radius: 12px; overflow: hidden;">
                <div class="modal-header bg-light border-bottom">
                    <h5 class="modal-title fw-bold text-dark" id="mapModalTitle">Location Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div id="large-map"></div>
                </div>
            </div>
        </div>
    </div>

    @include('admin.includes.footer_links')

    <!-- Google Maps Script -->
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyC68uSRK23hrzev5tCx1ePVfNiLeMhEP4c&callback=initMaps"
        async defer></script>

    <script>
        // Global variables for the large modal map
        let largeMap = null;
        let largeMarker = null;

        /**
         * Initialize all static mini-maps across the addresses tab
         */
        function initMaps() {
            const miniMapElements = document.querySelectorAll('.mini-map-canvas');

            miniMapElements.forEach(el => {
                const lat = parseFloat(el.getAttribute('data-lat'));
                const lng = parseFloat(el.getAttribute('data-lng'));

                if (!isNaN(lat) && !isNaN(lng)) {
                    const loc = { lat: lat, lng: lng };

                    const miniMap = new google.maps.Map(el, {
                        center: loc,
                        zoom: 14,
                        disableDefaultUI: true, // Hide controls for thumbnail
                        draggable: false,
                        scrollwheel: false,
                        disableDoubleClickZoom: true,
                        keyboardShortcuts: false
                    });

                    new google.maps.Marker({
                        position: loc,
                        map: miniMap
                    });
                }
            });
        }

        /**
         * Open the large map modal dynamically
         */
        function openMapModal(lat, lng, title) {
            document.getElementById('mapModalTitle').innerText = title + " - Map View";
            const mapLoc = { lat: parseFloat(lat), lng: parseFloat(lng) };

            const mapModalElement = document.getElementById('mapModal');
            const mapModal = new bootstrap.Modal(mapModalElement);
            mapModal.show();

            // Render Google map ONLY after modal is fully open (fixes gray box rendering bug)
            mapModalElement.addEventListener('shown.bs.modal', function () {
                if (!largeMap) {
                    largeMap = new google.maps.Map(document.getElementById('large-map'), {
                        zoom: 15,
                        center: mapLoc,
                        mapTypeControl: true,
                        streetViewControl: false
                    });

                    largeMarker = new google.maps.Marker({
                        position: mapLoc,
                        map: largeMap,
                        animation: google.maps.Animation.DROP
                    });
                } else {
                    // Just update existing map instance
                    largeMap.setCenter(mapLoc);
                    largeMarker.setPosition(mapLoc);
                }
            }, { once: true }); // ensure event listener fires only once per click
        }

        /**
         * Open the image preview modal dynamically
         */
        function openImageModal(imgSrc, title) {
            document.getElementById('previewImage').src = imgSrc;
            document.getElementById('imageModalTitle').innerText = title;
            const imageModal = new bootstrap.Modal(document.getElementById('imageModal'));
            imageModal.show();
        }
    </script>
</body>

</html>