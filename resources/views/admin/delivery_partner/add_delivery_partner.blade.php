<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Onboard Delivery Partner</title>
    @include('admin.includes.header_links')

    <!-- Select2 CSS for Multi-Select Chips -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

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

        /* Custom styling to match Select2 with Bootstrap */
        .select2-container .select2-selection--multiple {
            min-height: 38px;
            border: 1px solid #ced4da;
            border-radius: 0.375rem;
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: #e2e8f0;
            border: 1px solid #cbd5e1;
            color: #1e293b;
            border-radius: 4px;
            padding: 2px 8px;
            margin-top: 6px;
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            color: #ef4444;
            margin-right: 5px;
            border-right: 1px solid #cbd5e1;
            padding-right: 5px;
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice__display {
            cursor: default;
            padding-left: 14px;
            padding-right: 5px;
        }
    </style>
</head>

<body>
    @include('admin.includes.sidebar')
    @include('admin.includes.header')

    <div class="content">
        <div class="page-head">
            <div>
                <h1 class="page-title">Onboard Delivery Partner</h1>
                <div class="section-title">Register Driver or Fleet</div>
            </div>
            <div class="action-group">
                <button type="button" class="btn-action btn-back" onclick="history.back()">← Back</button>
                <a href="{{ route('manage_delivery_partner') }}" class="btn-action btn-manage">Manage Partners</a>
            </div>
        </div>

        @if(Session::has('error'))
            <div class="alert alert-danger border-0 shadow-sm mb-4">{{ Session::get('error') }}</div>
        @endif

        <form class="property-form" action="{{ route('store_delivery_partner') }}" method="post"
            enctype="multipart/form-data" autocomplete="off">
            @csrf

            <!-- 1. Personal Info -->
            <div class="section-header">1. Personal Info</div>
            <div class="row g-4">
                <div class="col-md-4">
                    <label class="form-label">Driver/Owner Name <span class="req">*</span></label>
                    <input type="text" class="form-control @error('driver_name') is-invalid @enderror"
                        name="driver_name" value="{{ old('driver_name') }}"
                        oninput="this.value = this.value.replace(/[^A-Za-z\s]/g, '')">
                    @error('driver_name') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Mobile Number <span class="req">*</span></label>
                    <input type="text" class="form-control @error('mobile_number') is-invalid @enderror"
                        name="mobile_number" value="{{ old('mobile_number') }}" maxlength="10"
                        oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                    @error('mobile_number') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Alternate Mobile</label>
                    <input type="text" id="alternate_mobile"
                        class="form-control @error('alternate_mobile') is-invalid @enderror" name="alternate_mobile"
                        value="{{ old('alternate_mobile') }}" maxlength="10"
                        oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                    @error('alternate_mobile') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>
                <div class="col-md-4" id="relation_wrapper"
                    style="display: {{ old('alternate_mobile') ? 'block' : 'none' }};">
                    <label class="form-label">Relation with Alternate Mobile <span class="req">*</span></label>
                    <input type="text" class="form-control @error('alternate_relation') is-invalid @enderror"
                        name="alternate_relation" value="{{ old('alternate_relation') }}">
                    @error('alternate_relation') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Profile Photo</label>
                    <input type="file" class="form-control @error('profile_photo') is-invalid @enderror"
                        name="profile_photo" accept="image/*">
                    @error('profile_photo') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Languages Known</label>
                    <!-- Updated to use Select2 -->
                    <select id="languages_known" class="form-select @error('languages_known') is-invalid @enderror"
                        name="languages_known[]" multiple="multiple" style="width: 100%;">
                        @php $oldLangs = old('languages_known', []); @endphp
                        <option value="English" {{ in_array('English', $oldLangs) ? 'selected' : '' }}>English</option>
                        <option value="Hindi" {{ in_array('Hindi', $oldLangs) ? 'selected' : '' }}>Hindi</option>
                        <option value="Telugu" {{ in_array('Telugu', $oldLangs) ? 'selected' : '' }}>Telugu</option>
                        <option value="Tamil" {{ in_array('Tamil', $oldLangs) ? 'selected' : '' }}>Tamil</option>
                        <option value="Marathi" {{ in_array('Marathi', $oldLangs) ? 'selected' : '' }}>Marathi</option>
                        <option value="Odia" {{ in_array('Odia', $oldLangs) ? 'selected' : '' }}>Odia</option>
                    </select>
                    @error('languages_known') <span class="text-danger small d-block mt-1">{{ $message }}</span>
                    @enderror
                </div>
                <div class="col-12">
                    <label class="form-label">Current Address <span class="req">*</span></label>
                    <textarea class="form-control @error('current_address') is-invalid @enderror" name="current_address"
                        rows="2">{{ old('current_address') }}</textarea>
                    @error('current_address') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status <span class="req">*</span></label>
                    <select class="form-select" name="status">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                        <option value="Blocked">Blocked</option>
                    </select>
                </div>
            </div>

            <!-- 2. KYC -->
            <div class="section-header">2. KYC & Driving Documents</div>
            <div class="row g-4">
                <div class="col-md-6">
                    <label class="form-label">Driving License (DL) Number <span class="req">*</span></label>
                    <input type="text" class="form-control @error('dl_number') is-invalid @enderror" name="dl_number"
                        value="{{ old('dl_number') }}">
                    @error('dl_number') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Upload DL Photo</label>
                    <input type="file" class="form-control @error('dl_photo') is-invalid @enderror" name="dl_photo"
                        accept=".pdf,image/*">
                    @error('dl_photo') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Aadhaar Card Number <span class="req">*</span></label>
                    <input type="text" class="form-control @error('aadhaar_number') is-invalid @enderror"
                        name="aadhaar_number" value="{{ old('aadhaar_number') }}" maxlength="12"
                        oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                    @error('aadhaar_number') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Upload Aadhaar Photo</label>
                    <input type="file" class="form-control @error('aadhaar_photo') is-invalid @enderror"
                        name="aadhaar_photo" accept=".pdf,image/*">
                    @error('aadhaar_photo') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- 3. Vehicle Details -->
            <div class="section-header">3. Vehicle Details</div>
            <div class="row g-4">
                <div class="col-md-4">
                    <label class="form-label">Vehicle Reg. Number <span class="req">*</span></label>
                    <input type="text" class="form-control @error('vehicle_reg_number') is-invalid @enderror"
                        name="vehicle_reg_number" value="{{ old('vehicle_reg_number') }}">
                    @error('vehicle_reg_number') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Vehicle Type <span class="req">*</span></label>
                    <!-- Updated to Text Entry Field -->
                    <input type="text" class="form-control @error('vehicle_type') is-invalid @enderror"
                        name="vehicle_type" value="{{ old('vehicle_type') }}"
                        placeholder="e.g. Tata Ace, 10-Tyre Tipper">
                    @error('vehicle_type') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Vehicle Capacity (Tonnage / Vol) <span class="req">*</span></label>
                    <input type="text" class="form-control @error('vehicle_capacity') is-invalid @enderror"
                        name="vehicle_capacity" value="{{ old('vehicle_capacity') }}" placeholder="e.g. 2 Tons">
                    @error('vehicle_capacity') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">RC Book Copy</label>
                    <input type="file" class="form-control @error('rc_book') is-invalid @enderror" name="rc_book">
                    @error('rc_book') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Insurance Copy</label>
                    <input type="file" class="form-control @error('vehicle_insurance') is-invalid @enderror"
                        name="vehicle_insurance">
                    @error('vehicle_insurance') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Fitness Certificate</label>
                    <input type="file" class="form-control @error('vehicle_fitness') is-invalid @enderror"
                        name="vehicle_fitness">
                    @error('vehicle_fitness') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>

                <!-- Vehicle Images -->
                <div class="col-md-4">
                    <label class="form-label">Vehicle Image (Front)</label>
                    <input type="file" class="form-control @error('vehicle_image_front') is-invalid @enderror"
                        name="vehicle_image_front" accept="image/*">
                    @error('vehicle_image_front') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Vehicle Image (Back)</label>
                    <input type="file" class="form-control @error('vehicle_image_back') is-invalid @enderror"
                        name="vehicle_image_back" accept="image/*">
                    @error('vehicle_image_back') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Vehicle Image (Side)</label>
                    <input type="file" class="form-control @error('vehicle_image_side') is-invalid @enderror"
                        name="vehicle_image_side" accept="image/*">
                    @error('vehicle_image_side') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- 4. Payout -->
            <div class="section-header">4. Payout Details</div>
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <label class="form-label">Bank Name</label>
                    <input type="text" class="form-control @error('bank_name') is-invalid @enderror" name="bank_name"
                        value="{{ old('bank_name') }}">
                    @error('bank_name') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Account Number</label>
                    <input type="text" class="form-control @error('bank_account_number') is-invalid @enderror"
                        name="bank_account_number" value="{{ old('bank_account_number') }}">
                    @error('bank_account_number') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">IFSC Code</label>
                    <input type="text" class="form-control @error('bank_ifsc_code') is-invalid @enderror"
                        name="bank_ifsc_code" value="{{ old('bank_ifsc_code') }}">
                    @error('bank_ifsc_code') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">UPI ID (For instant payout)</label>
                    <input type="text" class="form-control @error('upi_id') is-invalid @enderror" name="upi_id"
                        value="{{ old('upi_id') }}" placeholder="e.g. number@upi">
                    @error('upi_id') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="form-actions mt-4">
                <button type="button" class="btn-cancel" onclick="history.back()">Cancel</button>
                <button type="submit" class="btn-save">Save Partner</button>
            </div>
        </form>
    </div>

    @include('admin.includes.footer_links')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function () {
            // Initialize Select2 for Languages Known
            $('#languages_known').select2({
                placeholder: "Select or type languages",
                tags: true, // Allows typing custom languages
                tokenSeparators: [',']
            });

            // Alternate Mobile Relation Logic
            $('#alternate_mobile').on('input', function () {
                if ($(this).val().trim().length > 0) {
                    $('#relation_wrapper').fadeIn();
                } else {
                    $('#relation_wrapper').fadeOut();
                    $('input[name="alternate_relation"]').val('');
                }
            });
        });
    </script>
</body>

</html>