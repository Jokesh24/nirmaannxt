<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Add Engineer</title>
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
            height: 350px;
            width: 100%;
            border-radius: 8px;
            border: 1px solid #ced4da;
        }
    </style>
</head>

<body>
    @include('admin.includes.sidebar')
    @include('admin.includes.header')

    <div class="content">
        <div class="page-head">
            <div>
                <h1 class="page-title">Add Engineer</h1>
                <div class="section-title">Complete Engineer Setup Profile</div>
            </div>
            <div class="action-group">
                <button type="button" class="btn-action btn-back" onclick="history.back()">← Back</button>
                <a href="{{ route('manage_engineer') }}" class="btn-action btn-manage">Manage Engineers</a>
            </div>
        </div>

        @if(Session::has('error') || $errors->any())
            <div class="alert alert-danger border-0 shadow-sm mb-4">
                {{ Session::get('error') ?? 'Please correct the highlighted errors below.' }}
                @if($errors->any())
                    <ul class="mt-2 mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endif

        <form class="property-form" action="{{ route('store_engineer') }}" method="post" enctype="multipart/form-data"
            autocomplete="off">
            @csrf

            <!-- 1. Profile Setup -->
            <div class="section-header">1. Profile Details</div>
            <div class="row g-4">
                <div class="col-md-4">
                    <label class="form-label">Full Name <span class="req">*</span></label>
                    <input type="text" class="form-control" name="full_name" value="{{ old('full_name') }}"
                        pattern="[A-Za-z\s]+" oninput="this.value = this.value.replace(/[^A-Za-z\s]/g, '')" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Mobile Number <span class="req">*</span></label>
                    <input type="tel" class="form-control" name="mobile_number" value="{{ old('mobile_number') }}"
                        maxlength="10" pattern="\d{10}" oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                        required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Email ID <span class="req">*</span></label>
                    <input type="email" class="form-control" name="email" value="{{ old('email') }}" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Profile Photo</label>
                    <input type="file" class="form-control" name="profile_photo" accept="image/*">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Highest Qualification <span class="req">*</span></label>
                    <input type="text" class="form-control" name="highest_qualification"
                        value="{{ old('highest_qualification') }}" placeholder="e.g. B.Tech Civil, Diploma" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Qualification Proof (Upload)</label>
                    <input type="file" class="form-control" name="qualification_proof" accept=".pdf,image/*">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Assigned Territory/Zone <span class="req">*</span></label>
                    <input type="text" class="form-control" name="assigned_territory"
                        value="{{ old('assigned_territory') }}" placeholder="e.g. Madhapur, Kondapur" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Status <span class="req">*</span></label>
                    <select class="form-select" name="status" required>
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                        <option value="Blocked">Blocked</option>
                    </select>
                </div>
            </div>

            <!-- Google Map Location Integration -->
            <div class="section-header">2. Location Settings</div>
            <div class="row g-4">
                <div class="col-md-12">
                    <label class="form-label text-muted small fw-bold">Search Map Location</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" id="map_search" class="form-control"
                            placeholder="Search for area or pin location">
                    </div>
                </div>
                <div class="col-md-12">
                    <div id="map"></div>
                    <small class="text-muted d-block mt-2"><i class="bi bi-info-circle me-1"></i> Drag the red marker to
                        pinpoint the exact location.</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Latitude</label>
                    <input type="text" id="latitude" name="latitude" class="form-control bg-light"
                        value="{{ old('latitude') }}" readonly>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Longitude</label>
                    <input type="text" id="longitude" name="longitude" class="form-control bg-light"
                        value="{{ old('longitude') }}" readonly>
                </div>
            </div>

            <!-- 3. Legal Documents -->
            <div class="section-header">3. Legal Documents</div>
            <div class="row g-4">
                <div class="col-md-6">
                    <label class="form-label">Aadhaar Number <span class="req">*</span></label>
                    <input type="text" class="form-control" name="aadhaar_number" value="{{ old('aadhaar_number') }}"
                        maxlength="12" pattern="\d{12}" oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                        required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Upload Aadhaar Document <span class="req">*</span></label>
                    <input type="file" class="form-control" name="aadhaar_upload" accept=".pdf,image/*">
                </div>

                <div class="col-md-6">
                    <label class="form-label">PAN Number (Optional)</label>
                    <input type="text" class="form-control" name="pan_number" value="{{ old('pan_number') }}"
                        maxlength="10">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Upload PAN Document (Optional)</label>
                    <input type="file" class="form-control" name="pan_upload" accept=".pdf,image/*">
                </div>
            </div>

            <!-- 4. Banking -->
            <div class="section-header">4. Banking Details</div>
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
                    <label class="form-label">Cancelled Cheque / Passbook</label>
                    <input type="file" class="form-control" name="cancelled_cheque" accept=".pdf,image/*">
                </div>
            </div>

            <div class="col-12 mt-4">
                <label class="form-label">Additional Description / Notes</label>
                <textarea class="form-control" id="description" name="description">{{ old('description') }}</textarea>
            </div>

            <div class="form-actions mt-4">
                <button type="button" class="btn-cancel" onclick="history.back()">Cancel</button>
                <button type="submit" class="btn-save">Save Engineer</button>
            </div>
        </form>
    </div>

    @include('admin.includes.footer_links')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script
        src="https://maps.googleapis.com/maps/api/js?key=AIzaSyC68uSRK23hrzev5tCx1ePVfNiLeMhEP4c&libraries=places&callback=initMap"
        async defer></script>
    <script>
        CKEDITOR.replace('description', { height: 150 });

        let map, marker, autocomplete;

        function initMap() {
            // Default center: Hyderabad or previous old data if validation fails
            let defaultLat = {{ old('latitude') ?: 17.385044 }};
            let defaultLng = {{ old('longitude') ?: 78.486671 }};

            const centerPosition = { lat: parseFloat(defaultLat), lng: parseFloat(defaultLng) };

            map = new google.maps.Map(document.getElementById("map"), {
                center: centerPosition,
                zoom: 12,
            });

            marker = new google.maps.Marker({
                position: centerPosition,
                map: map,
                draggable: true,
                animation: google.maps.Animation.DROP
            });

            const input = document.getElementById("map_search");
            autocomplete = new google.maps.places.Autocomplete(input);
            autocomplete.bindTo("bounds", map);

            // Listen for autocomplete selection
            autocomplete.addListener("place_changed", () => {
                const place = autocomplete.getPlace();
                if (!place.geometry || !place.geometry.location) {
                    alert("No location data found for this place.");
                    return;
                }

                if (place.geometry.viewport) {
                    map.fitBounds(place.geometry.viewport);
                } else {
                    map.setCenter(place.geometry.location);
                    map.setZoom(17);
                }

                marker.setPosition(place.geometry.location);
                updateLatLonFields(place.geometry.location.lat(), place.geometry.location.lng());
            });

            // Listen for marker drag
            marker.addListener("dragend", () => {
                const position = marker.getPosition();
                updateLatLonFields(position.lat(), position.lng());
            });

            // Set initial fields if this is a fresh load and fields are empty
            if (!document.getElementById('latitude').value) {
                updateLatLonFields(centerPosition.lat, centerPosition.lng);
            }
        }

        function updateLatLonFields(lat, lng) {
            document.getElementById('latitude').value = lat.toFixed(8);
            document.getElementById('longitude').value = lng.toFixed(8);
        }
    </script>
</body>

</html>