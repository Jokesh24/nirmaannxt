<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Vendor</title>

    @include('admin.includes.header_links')
    <script src="https://cdn.ckeditor.com/4.22.1/standard/ckeditor.js"></script>
    <style>
        .cke_chrome {
            border-radius: 14px !important;
            border: 1px solid #dbe3ec !important;
            overflow: hidden;
        }

        .section-header {
            font-weight: 700;
            font-size: 1.1rem;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 20px;
            margin-top: 30px;
            color: #333;
        }

        #map {
            height: 400px;
            width: 100%;
            border-radius: 10px;
            margin-top: 10px;
        }
    </style>
</head>

<body>
    @include('admin.includes.sidebar')
    @include('admin.includes.header')

    <div class="content">
        <div class="page-head">
            <div>
                <h1 class="page-title">Add Vendor</h1>
                <div class="section-title">Complete Onboarding Information</div>
            </div>
            <div class="action-group">
                <button type="button" class="btn-action btn-back" onclick="history.back()">← Back</button>
                <a href="{{ route('manage_vendor') }}" class="btn-action btn-manage">Manage Vendors</a>
            </div>
        </div>

        @if(Session::has('error') || $errors->any())
            <div class="alert alert-danger border-0 shadow-sm mb-4">
                {{ Session::get('error') ?? 'Please correct the highlighted errors below.' }}
                @if($errors->any())
                    <ul class="mt-2 mb-0">
                        @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li> @endforeach
                    </ul>
                @endif
            </div>
        @endif

        <form class="property-form" action="{{ route('store_vendor') }}" method="post" enctype="multipart/form-data"
            autocomplete="off">
            @csrf

            <!-- 1. Business Details -->
            <div class="section-header">1. Business Details</div>
            <div class="row g-4">
                <div class="col-md-4">
                    <label class="form-label">Business/Firm Name <span class="req">*</span></label>
                    <input type="text" class="form-control" name="business_name" value="{{ old('business_name') }}"
                        pattern="[A-Za-z\s]+" title="Only letters and spaces allowed"
                        oninput="this.value = this.value.replace(/[^A-Za-z\s]/g, '')" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Business Type <span class="req">*</span></label>
                    <select class="form-select" name="business_type_id" required>
                        <option value="">Select Business Type</option>
                        @foreach($businessTypes as $type)
                            <option value="{{ $type->id }}" {{ old('business_type_id') == $type->id ? 'selected' : '' }}>
                                {{ $type->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Contact Person <span class="req">*</span></label>
                    <input type="text" class="form-control" name="contact_person" value="{{ old('contact_person') }}"
                        pattern="[A-Za-z\s]+" oninput="this.value = this.value.replace(/[^A-Za-z\s]/g, '')" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Mobile Number <span class="req">*</span></label>
                    <input type="tel" class="form-control" name="business_mobile" value="{{ old('business_mobile') }}"
                        maxlength="10" minlength="10" pattern="\d{10}"
                        oninput="this.value = this.value.replace(/[^0-9]/g, '')" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Email ID <span class="req">*</span></label>
                    <input type="email" class="form-control" name="business_email" value="{{ old('business_email') }}"
                        required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status <span class="req">*</span></label>
                    <select class="form-select" name="status" required>
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                        <option value="Blocked">Blocked</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Company Logo</label>
                    <input type="file" class="form-control" name="company_logo" accept="image/*">
                </div>
            </div>

            <!-- 2. Tax & Registration -->
            <div class="section-header">2. Tax & Registration</div>
            <div class="row g-4">
                <div class="col-md-4">
                    <label class="form-label">GSTIN Number</label>
                    <input type="text" class="form-control" name="gst_number" value="{{ old('gst_number') }}"
                        placeholder="e.g. 22AAAAA0000A1Z5">
                </div>
                <div class="col-md-4">
                    <label class="form-label">PAN Number</label>
                    <input type="text" class="form-control" name="pan_number" value="{{ old('pan_number') }}"
                        placeholder="e.g. ABCDE1234F">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Upload GST Certificate</label>
                    <input type="file" class="form-control" name="gst_certificate" accept=".pdf,image/*">
                </div>
            </div>

            <!-- 3. Location & Coverage -->
            <div class="section-header">3. Location & Coverage</div>
            <div class="row g-4">
                <div class="col-md-12">
                    <label class="form-label">Yard/Factory Address <span class="req">*</span></label>
                    <textarea class="form-control" name="address" rows="2" required>{{ old('address') }}</textarea>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Search Location on Map <span class="req">*</span></label>
                    <div id="map_search_container"></div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Delivery Radius (in KM) <span class="req">*</span></label>
                    <input type="number" id="delivery_radius_km" name="delivery_radius_km" class="form-control"
                        value="{{ old('delivery_radius_km', 10) }}" min="1" required>
                </div>

                <div class="col-md-12">
                    <small class="text-muted d-block mb-1">Drag the red marker to pinpoint exact location.</small>
                    <div id="map"></div>
                    <input type="hidden" id="latitude" name="latitude" value="{{ old('latitude') }}">
                    <input type="hidden" id="longitude" name="longitude" value="{{ old('longitude') }}">
                </div>
            </div>

            <!-- 4. Inventory & Capacity -->
            <div class="section-header">4. Inventory & Capacity</div>
            <div class="row g-4">
                <div class="col-md-12">
                    <label class="form-label">Products Supplied</label>
                    <div class="d-flex flex-wrap gap-3 mt-2">
                        @foreach($productCategories as $cat)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="products_supplied[]"
                                    value="{{ $cat->id }}" id="cat_{{ $cat->id }}" {{ (is_array(old('products_supplied')) && in_array($cat->id, old('products_supplied'))) ? 'checked' : '' }}>
                                <label class="form-check-label" for="cat_{{ $cat->id }}">{{ $cat->name }}</label>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Daily Supply Capacity (Tons/Loads)</label>
                    <input type="text" class="form-control" name="daily_capacity" value="{{ old('daily_capacity') }}"
                        placeholder="e.g. 500 Tons / 20 Loads">
                </div>
                <div class="col-md-6 d-flex align-items-center mt-5">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="weighbridge_available" id="weighbridge"
                            value="1" {{ old('weighbridge_available') ? 'checked' : '' }}>
                        <label class="form-check-label" for="weighbridge">Weighbridge Facility Available</label>
                    </div>
                </div>
            </div>

            <!-- 5. Banking/Payouts -->
            <div class="section-header">5. Banking / Payouts</div>
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <label class="form-label">Bank Account Name</label>
                    <input type="text" class="form-control" name="bank_account_name"
                        value="{{ old('bank_account_name') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Account Number</label>
                    <input type="text" class="form-control" name="bank_account_number"
                        value="{{ old('bank_account_number') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">IFSC Code</label>
                    <input type="text" class="form-control" name="bank_ifsc_code" value="{{ old('bank_ifsc_code') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Cancelled Cheque / Passbook Copy</label>
                    <input type="file" class="form-control" name="cancelled_cheque" accept=".pdf,image/*">
                </div>
            </div>

            <div class="col-12 mt-4">
                <label class="form-label">Additional Description</label>
                <textarea class="form-control" id="description" name="description"
                    rows="6">{{ old('description') }}</textarea>
            </div>

            <div class="form-actions mt-4">
                <button type="button" class="btn-cancel" onclick="history.back()">Cancel</button>
                <button type="submit" class="btn-fsave">Save Vendor</button>
            </div>
        </form>
    </div>

    @include('admin.includes.footer_links')

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>CKEDITOR.replace('description', { height: 150 });</script>


    <script>
        let map;
        let marker;
        let circle;
        let autocompleteElement;

        async function initMap() {
            const { Map } = await google.maps.importLibrary("maps");
            const { AdvancedMarkerElement } =
                await google.maps.importLibrary("marker");
            const { PlaceAutocompleteElement } =
                await google.maps.importLibrary("places");

            const latitude = parseFloat(
                document.getElementById('latitude').value
            ) || 20.5937;

            const longitude = parseFloat(
                document.getElementById('longitude').value
            ) || 78.9629;

            const defaultLocation = {
                lat: latitude,
                lng: longitude
            };

            map = new Map(document.getElementById("map"), {
                center: defaultLocation,
                zoom: 5,
                mapId: "DEMO_MAP_ID"
            });

            marker = new AdvancedMarkerElement({
                map: map,
                position: defaultLocation,
                gmpDraggable: true,
                title: "Vendor Location"
            });

            circle = new google.maps.Circle({
                map: map,
                center: defaultLocation,
                radius: (
                    parseFloat(
                        document.getElementById('delivery_radius_km').value
                    ) || 10
                ) * 1000,
                fillColor: '#FF0000',
                fillOpacity: 0.15,
                strokeColor: '#FF0000',
                strokeWeight: 1
            });

            // New Places Autocomplete
            autocompleteElement = new PlaceAutocompleteElement();

            autocompleteElement.placeholder =
                "Search for your business location...";

            document.getElementById('map_search_container')
                .appendChild(autocompleteElement);

            autocompleteElement.addEventListener('gmp-select', async (event) => {
                try {
                    // Convert selected prediction into a Place object
                    const place = event.placePrediction.toPlace();

                    // Fetch required place details
                    await place.fetchFields({
                        fields: ['location', 'formattedAddress']
                    });

                    if (!place.location) {
                        alert('Unable to retrieve the selected location.');
                        return;
                    }

                    const location = {
                        lat: place.location.lat(),
                        lng: place.location.lng()
                    };

                    // Update map
                    map.setCenter(location);
                    map.setZoom(15);

                    // Update marker and delivery circle
                    marker.position = location;
                    circle.setCenter(location);

                    // Update hidden form fields
                    updateLatLngFields(location);

                } catch (error) {
                    console.error('Place selection error:', error);
                }
            });

            // Delivery radius update
            document.getElementById('delivery_radius_km')
                .addEventListener('input', function () {
                    const radius = parseFloat(this.value) || 0;
                    circle.setRadius(radius * 1000);
                });

            // Update coordinates when marker is dragged
            marker.addEventListener('gmp-dragend', function () {

                const position = marker.position;

                const location = {
                    lat: typeof position.lat === 'function'
                        ? position.lat()
                        : position.lat,

                    lng: typeof position.lng === 'function'
                        ? position.lng()
                        : position.lng
                };

                circle.setCenter(location);

                updateLatLngFields(location);
            });

            updateLatLngFields(defaultLocation);
        }

        function updateLatLngFields(location) {
            document.getElementById('latitude').value = location.lat;
            document.getElementById('longitude').value = location.lng;
        }
    </script>

    <script
        src="https://maps.googleapis.com/maps/api/js?key=AIzaSyD9idiHNelMFTrnp9n96SsgK5NWz4MMgKA&loading=async&libraries=places,marker&callback=initMap"
        async defer>
        </script>

</body>

</html>