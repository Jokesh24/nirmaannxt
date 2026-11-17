<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>View Engineer Details</title>
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
    </style>
</head>

<body>
    @include('admin.includes.sidebar')
    @include('admin.includes.header')

    <div class="content">
        <div class="page-head">
            <h1 class="page-title">Engineer Profile</h1>
            <div class="action-group">
                <button class="btn-action btn-back" onclick="history.back()">← Back</button>
                <a href="{{ route('edit_engineer', $engineer->id) }}" class="btn-action btn-manage">Edit Profile</a>
            </div>
        </div>

        <div class="info-card shadow-sm">
            <div class="profile-header">
                <!-- FIXED: Changed company_logo to profile_photo and updated the folder path -->
                <img src="{{ $engineer->profile_photo ? asset('uploads/engineer_profiles/' . $engineer->profile_photo) : 'https://via.placeholder.com/80' }}"
                    class="profile-logo shadow-sm" alt="Profile Photo">
                <div>
                    <h2 class="mb-1 fw-bold text-dark">{{ $engineer->full_name }}</h2>
                    <div class="text-muted mb-2"><i class="bi bi-person-badge"></i> ID: {{ $engineer->engineer_id }}
                    </div>
                    <span
                        class="status-badge {{ $engineer->status == 1 ? 'status-active' : ($engineer->status == 2 ? 'bg-danger text-white' : 'status-inactive') }}">
                        {{ $engineer->status == 1 ? 'Active' : ($engineer->status == 2 ? 'Blocked' : 'Inactive') }}
                    </span>
                </div>
            </div>

            <!-- Business & Contact -->
            <h5 class="fw-bold mb-4 text-primary">Contact & Assignment Details</h5>
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="label-text">Mobile Number</div>
                    <div class="value-text">{{ $engineer->mobile_number }}</div>
                </div>
                <div class="col-md-3">
                    <div class="label-text">Email Address</div>
                    <div class="value-text">{{ $engineer->email }}</div>
                </div>
                <div class="col-md-3">
                    <div class="label-text">Assigned Territory</div>
                    <div class="value-text">{{ $engineer->assigned_territory }}</div>
                </div>
                <div class="col-md-3">
                    <div class="label-text">Highest Qualification</div>
                    <div class="value-text">
                        {{ $engineer->highest_qualification }}
                        @if($engineer->qualification_proof)
                            <a href="{{ asset('uploads/engineer_docs/' . $engineer->qualification_proof) }}" target="_blank"
                                class="ms-2 badge bg-primary text-decoration-none">View Proof</a>
                        @endif
                    </div>
                </div>
            </div>

            <div class="section-break"></div>

            <!-- Tax & Registration -->
            <h5 class="fw-bold mb-4 text-primary">KYC Documents</h5>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="label-text">Aadhaar Number</div>
                    <div class="value-text">{{ $engineer->aadhaar_number }}</div>
                    @if($engineer->aadhaar_upload)
                        <a href="{{ asset('uploads/engineer_docs/' . $engineer->aadhaar_upload) }}" target="_blank"
                            class="btn btn-sm btn-outline-primary mt-2">View Document</a>
                    @else
                        <div class="small text-muted mt-1">Not Uploaded</div>
                    @endif
                </div>
                <div class="col-md-4">
                    <div class="label-text">PAN Number</div>
                    <div class="value-text">{{ $engineer->pan_number ?? 'N/A' }}</div>
                    @if($engineer->pan_upload)
                        <a href="{{ asset('uploads/engineer_docs/' . $engineer->pan_upload) }}" target="_blank"
                            class="btn btn-sm btn-outline-primary mt-2">View Document</a>
                    @else
                        <div class="small text-muted mt-1">Not Uploaded</div>
                    @endif
                </div>
            </div>

            <div class="section-break"></div>

            <!-- Banking -->
            <h5 class="fw-bold mb-4 text-primary">Banking Details</h5>
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="label-text">Account Name</div>
                    <div class="value-text">{{ $engineer->bank_account_name ?? 'N/A' }}</div>
                </div>
                <div class="col-md-3">
                    <div class="label-text">Account No.</div>
                    <div class="value-text">{{ $engineer->bank_account_number ?? 'N/A' }}</div>
                </div>
                <div class="col-md-3">
                    <div class="label-text">IFSC Code</div>
                    <div class="value-text">{{ $engineer->bank_ifsc_code ?? 'N/A' }}</div>
                </div>
                <div class="col-md-3">
                    <div class="label-text">Cancelled Cheque</div>
                    @if($engineer->cancelled_cheque)
                        <a href="{{ asset('uploads/engineer_docs/' . $engineer->cancelled_cheque) }}" target="_blank"
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
                        @if($engineer->description)
                            {!! $engineer->description !!}
                        @else
                            <span class="text-muted italic">No description available.</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    @include('admin.includes.footer_links')
</body>

</html>