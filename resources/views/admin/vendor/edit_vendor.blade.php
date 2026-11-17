<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Edit Vendor</title>
    @include('admin.includes.header_links')
    <script src="https://cdn.ckeditor.com/4.22.1/standard/ckeditor.js"></script>
    <style>
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

        #map_search_container gmp-place-autocomplete {
            width: 100%;
            display: block;
        }
    </style>
</head>

<body>
    @include('admin.includes.sidebar')
    @include('admin.includes.header')

    <div class="content">
        <div class="page-head">
            <div>
                <h1 class="page-title">Edit Vendor</h1>
                <div class="section-title">Update Information for {{ $vendor->business_name }}</div>
            </div>
            <div class="action-group">
                <button type="button" class="btn-action btn-back" onclick="history.back()">← Back</button>
                <a href="{{ route('manage_vendor') }}" class="btn-action btn-manage">Manage Vendors</a>
            </div>
        </div>

        @if(session('error') || $errors->any())
            <div class="alert alert-danger border-0 shadow-sm mb-4">
                {{ session('error') ?? 'Please correct the highlighted errors below.' }}
                @if($errors->any())
                    <ul class="mt-2 mb-0">
                        @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li> @endforeach
                    </ul>
                @endif
            </div>
        @endif

        <form class="property-form" action="{{ route('update_vendor', $vendor->id) }}" method="post"
            enctype="multipart/form-data">
            @csrf

            <!-- 1. Business Details -->
            <div class="section-header">1. Business Details</div>
            <div class="row g-4">
                <div class="col-md-4">
                    <label class="form-label">Business Name <span class="req">*</span></label>
                    <input type="text" class="form-control" name="business_name"
                        value="{{ old('business_name', $vendor->business_name) }}" pattern="[A-Za-z\s]+"
                        oninput="this.value = this.value.replace(/[^A-Za-z\s]/g, '')" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Business Type <span class="req">*</span></label>
                    <select class="form-select" name="business_type_id" required>
                        @foreach($businessTypes as $type)
                            <option value="{{ $type->id }}" {{ (old('business_type_id', $vendor->business_type_id) == $type->id) ? 'selected' : '' }}>{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Contact Person <span class="req">*</span></label>
                    <input type="text" class="form-control" name="contact_person"
                        value="{{ old('contact_person', $vendor->contact_person) }}" pattern="[A-Za-z\s]+"
                        oninput="this.value = this.value.replace(/[^A-Za-z\s]/g, '')" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Mobile Number <span class="req">*</span></label>
                    <input type="tel" class="form-control" name="business_mobile"
                        value="{{ old('business_mobile', $vendor->business_mobile) }}" maxlength="10" minlength="10"
                        pattern="\d{10}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Email ID <span class="req">*</span></label>
                    <input type="email" class="form-control" name="business_email"
                        value="{{ old('business_email', $vendor->business_email) }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status <span class="req">*</span></label>
                    <select class="form-select" name="status" required>
                        <option value="1" {{ old('status', $vendor->status) == 1 ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ old('status', $vendor->status) == 0 ? 'selected' : '' }}>Inactive</option>
                        <option value="2" {{ old('status', $vendor->status) == 2 ? 'selected' : '' }}>Blocked</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Company Logo (Leave blank to keep)</label>
                    <input type="file" class="form-control" name="company_logo" accept="image/*">
                </div>
            </div>

            <!-- 2. Tax & Registration -->
            <div class="section-header">2. Tax & Registration</div>
            <div class="row g-4">
                <div class="col-md-4">
                    <label class="form-label">GSTIN Number</label>
                    <input type="text" class="form-control" name="gst_number"
                        value="{{ old('gst_number', $vendor->gst_number) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">PAN Number</label>
                    <input type="text" class="form-control" name="pan_number"
                        value="{{ old('pan_number', $vendor->pan_number) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">GST Certificate (Leave blank to keep)</label>
                    <input type="file" class="form-control" name="gst_certificate" accept=".pdf,image/*">
                    @if($vendor->gst_certificate)
                        <small class="text-success mt-1 d-block"><i class="bi bi-check-circle"></i> Document
                            uploaded</small>
                    @endif
                </div>
            </div>

            <!-- 3. Location & Coverage -->
            <div class="section-header">3. Location & Coverage</div>
            <div class="row g-4">
                <div class="col-12">
                    <label class="form-label">Yard/Factory Address <span class="req">*</span></label>
                    <textarea class="form-control" name="address" rows="2"
                        required>{{ old('address', $vendor->address) }}</textarea>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Search Location</label>
                    <div id="map_search_container"></div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Delivery Radius (in KM) <span class="req">*</span></label>
                    <input type="number" id="delivery_radius_km" name="delivery_radius_km" class="form-control"
                        value="{{ old('delivery_radius_km', $vendor->delivery_radius_km ?? 10) }}" min="1" required>
                </div>

                <div class="col-md-12">
                    <small class="text-muted d-block mb-1">Drag the marker to adjust exact coordinates.</small>
                    <div id="map"></div>
                    <input type="hidden" id="latitude" name="latitude" value="{{ old('latitude', $vendor->latitude) }}">
                    <input type="hidden" id="longitude" name="longitude"
                        value="{{ old('longitude', $vendor->longitude) }}">
                </div>
            </div>

            <!-- 4. Inventory & Capacity -->
            <div class="section-header">4. Inventory & Capacity</div>
            <div class="row g-4">
                <div class="col-md-12">
                    <label class="form-label">Products Supplied</label>
                    <div class="d-flex flex-wrap gap-3 mt-2">
                        @php 
                            $supplied = is_array(old('products_supplied')) ? old('products_supplied') : (is_array($vendor->products_supplied) ? $vendor->products_supplied : []); 
                        @endphp
                        @foreach($productCategories as $cat)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="products_supplied[]"
                                    value="{{ $cat->id }}" id="cat_{{ $cat->id }}" {{ in_array($cat->id, $supplied) ? 'checked' : '' }}>
                                <label class="form-check-label" for="cat_{{ $cat->id }}">{{ $cat->name }}</label>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Daily Supply Capacity</label>
                    <input type="text" class="form-control" name="daily_capacity"
                        value="{{ old('daily_capacity', $vendor->daily_capacity) }}">
                </div>
                <div class="col-md-6 d-flex align-items-center mt-5">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="weighbridge_available" id="weighbridge"
                            value="1" {{ old('weighbridge_available', $vendor->weighbridge_available) ? 'checked' : '' }}>
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
                        value="{{ old('bank_account_name', $vendor->bank_account_name) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Account Number</label>
                    <input type="text" class="form-control" name="bank_account_number"
                        value="{{ old('bank_account_number', $vendor->bank_account_number) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">IFSC Code</label>
                    <input type="text" class="form-control" name="bank_ifsc_code"
                        value="{{ old('bank_ifsc_code', $vendor->bank_ifsc_code) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Cancelled Cheque (Leave blank to keep)</label>
                    <input type="file" class="form-control" name="cancelled_cheque" accept=".pdf,image/*">
                    @if($vendor->cancelled_cheque)
                        <small class="text-success mt-1 d-block"><i class="bi bi-check-circle"></i> Document
                            uploaded</small>
                    @endif
                </div>
            </div>

            <div class="col-12 mt-4">
                <label class="form-label">Additional Description</label>
                <textarea class="form-control" id="description"
                    name="description">{{ old('description', $vendor->description) }}</textarea>
            </div>

            <div class="form-actions mt-4">
                <button type="button" class="btn-cancel" onclick="history.back()">Cancel</button>
                <button type="submit" class="btn-save">Update Vendor</button>
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
            const { AdvancedMarkerElement } = await google.maps.importLibrary("marker");
            const { PlaceAutocompleteElement } = await google.maps.importLibrary("places");

            // Get existing vendor coordinates
            let initLat = parseFloat(document.getElementById('latitude').value);
            let initLng = parseFloat(document.getElementById('longitude').value);

            const vendorLocation = (!isNaN(initLat) && !isNaN(initLng))
                ? { lat: initLat, lng: initLng }
                : { lat: 20.5937, lng: 78.9629 };

            // Initialize map (FIXED mapId here)
            map = new Map(document.getElementById("map"), {
                center: vendorLocation,
                zoom: (!isNaN(initLat) && !isNaN(initLng)) ? 12 : 5,
                mapId: "DEMO_MAP_ID"
            });

            // Initialize draggable marker
            marker = new AdvancedMarkerElement({
                map: map,
                position: vendorLocation,
                gmpDraggable: true
            });

            // Initialize delivery radius circle
            circle = new google.maps.Circle({
                map: map,
                center: vendorLocation,
                radius: (parseFloat(document.getElementById('delivery_radius_km').value) || 10) * 1000,
                fillColor: '#FF0000',
                fillOpacity: 0.15,
                strokeColor: '#FF0000',
                strokeWeight: 1
            });

            // Initialize location search
            autocompleteElement = new PlaceAutocompleteElement();

            document.getElementById('map_search_container').appendChild(autocompleteElement);

            // Handle selected location
            autocompleteElement.addEventListener('gmp-select', async (event) => {
                try {
                    const place = event.placePrediction.toPlace();

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

                    map.setCenter(location);
                    map.setZoom(15);

                    marker.position = location;
                    circle.setCenter(location);

                    updateLatLngFields(location);

                } catch (error) {
                    console.error('Place selection error:', error);
                }
            });

            // Update marker position when dragged
            marker.addEventListener('gmp-dragend', function () {
                const position = marker.position;

                const location = {
                    lat: typeof position.lat === 'function' ? position.lat() : position.lat,
                    lng: typeof position.lng === 'function' ? position.lng() : position.lng
                };

                circle.setCenter(location);
                updateLatLngFields(location);
            });

            // Update delivery radius
            document.getElementById('delivery_radius_km').addEventListener('input', function () {
                const radius = parseFloat(this.value) || 0;
                circle.setRadius(radius * 1000);
            });

            // Populate coordinates if they were initially empty
            if (isNaN(initLat) || isNaN(initLng)) {
                updateLatLngFields(vendorLocation);
            }
        }

        // Update hidden latitude and longitude fields
        function updateLatLngFields(location) {
            const lat = typeof location.lat === 'function'
                ? location.lat()
                : location.lat;

            const lng = typeof location.lng === 'function'
                ? location.lng()
                : location.lng;

            document.getElementById('latitude').value = lat;
            document.getElementById('longitude').value = lng;
        }
    </script>

    <!-- FIXED API KEY HERE -->
    <script
        src="https://maps.googleapis.com/maps/api/js?key=AIzaSyD9idiHNelMFTrnp9n96SsgK5NWz4MMgKA&loading=async&libraries=places,marker&callback=initMap"
        async defer>
        </script>
</body>

</html>