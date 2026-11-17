<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Delivery Partner</title>
    @include('admin.includes.header_links')
    <style>
        .info-card { background: #fff; border-radius: 20px; padding: 30px; border: 1px solid #edf2f7; }
        .label-text { color: #64748b; font-size: 13px; font-weight: 600; text-transform: uppercase; margin-bottom: 5px; }
        .value-text { color: #1e293b; font-size: 16px; font-weight: 500; }
        .profile-header { display: flex; align-items: center; gap: 20px; margin-bottom: 30px; }
        .profile-logo { width: 80px; height: 80px; border-radius: 15px; object-fit: cover; border: 3px solid #f8fafc; }
        .section-break { margin: 25px 0; border-top: 1px dashed #cbd5e1; }
        
        /* Image & Document Preview Hover Effect */
        .img-preview {
            cursor: pointer;
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }
        .img-preview:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
    </style>
</head>
<body>
    @include('admin.includes.sidebar')
    @include('admin.includes.header')

    <div class="content">
        <div class="page-head">
            <h1 class="page-title">Delivery Partner Profile</h1>
            <div class="action-group">
                <button class="btn-action btn-back" onclick="history.back()">← Back</button>
                <a href="{{ route('edit_delivery_partner', $partner->id) }}" class="btn-action btn-manage">Edit Profile</a>
            </div>
        </div>

        <div class="info-card shadow-sm">
            <div class="profile-header">
                <img src="{{ $partner->profile_photo ? asset('uploads/delivery_partners/' . $partner->profile_photo) : 'https://via.placeholder.com/80' }}" class="profile-logo shadow-sm">
                <div>
                    <h2 class="mb-1 fw-bold text-dark">{{ $partner->driver_name }}</h2>
                    <div class="text-muted mb-2">ID: {{ $partner->driver_id }}</div>
                    <span class="status-badge {{ $partner->status == 1 ? 'status-active' : ($partner->status == 2 ? 'bg-danger text-white' : 'status-inactive') }}">
                        {{ $partner->status == 1 ? 'Active' : ($partner->status == 2 ? 'Blocked' : 'Inactive') }}
                    </span>
                </div>
            </div>

            <!-- Personal -->
            <h5 class="fw-bold mb-3 text-primary">Personal Details</h5>
            <div class="row g-4">
                <div class="col-md-3"><div class="label-text">Mobile Number</div><div class="value-text">{{ $partner->mobile_number }}</div></div>
                <div class="col-md-3">
                    <div class="label-text">Alternate Mobile</div>
                    <div class="value-text">
                        {{ $partner->alternate_mobile ?? 'N/A' }} 
                        @if($partner->alternate_relation) <span class="small text-muted">({{ $partner->alternate_relation }})</span> @endif
                    </div>
                </div>
                <div class="col-md-6"><div class="label-text">Current Address</div><div class="value-text">{{ $partner->current_address }}</div></div>
                <div class="col-md-12">
                    <div class="label-text">Languages Known</div>
                    <div class="value-text">
                        @if(is_array($partner->languages_known) && count($partner->languages_known) > 0)
                            {{ implode(', ', $partner->languages_known) }}
                        @else
                            <span class="text-muted">Not specified</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="section-break"></div>

            <!-- KYC -->
            <h5 class="fw-bold mb-3 text-primary">KYC Documents</h5>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="label-text">DL Number</div>
                    <div class="value-text">{{ $partner->dl_number }}</div>
                    @if($partner->dl_photo) 
                        <button type="button" class="btn btn-sm btn-outline-primary mt-2 doc-preview" data-src="{{ asset('uploads/delivery_partners/docs/' . $partner->dl_photo) }}">
                            <i class="bi bi-eye"></i> View DL
                        </button> 
                    @endif
                </div>
                <div class="col-md-4">
                    <div class="label-text">Aadhaar Number</div>
                    <div class="value-text">{{ $partner->aadhaar_number }}</div>
                    @if($partner->aadhaar_photo) 
                        <button type="button" class="btn btn-sm btn-outline-primary mt-2 doc-preview" data-src="{{ asset('uploads/delivery_partners/docs/' . $partner->aadhaar_photo) }}">
                            <i class="bi bi-eye"></i> View Aadhaar
                        </button> 
                    @endif
                </div>
            </div>

            <div class="section-break"></div>

            <!-- Vehicle -->
            <h5 class="fw-bold mb-3 text-primary">Vehicle Details</h5>
            <div class="row g-4">
                <div class="col-md-3"><div class="label-text">Registration Number</div><div class="value-text">{{ $partner->vehicle_reg_number }}</div></div>
                <div class="col-md-3"><div class="label-text">Vehicle Type</div><div class="value-text">{{ $partner->vehicle_type }}</div></div>
                <div class="col-md-3"><div class="label-text">Capacity</div><div class="value-text">{{ $partner->vehicle_capacity }}</div></div>
            </div>
            
            <div class="row g-4 mt-1">
                <div class="col-md-12">
                    <div class="label-text">Vehicle Documents</div>
                    <div class="d-flex gap-2 flex-wrap mt-1">
                        @if($partner->rc_book) 
                            <button type="button" class="btn btn-sm btn-outline-secondary doc-preview" data-src="{{ asset('uploads/delivery_partners/vehicle/' . $partner->rc_book) }}">
                                <i class="bi bi-file-earmark-text"></i> RC Book
                            </button> 
                        @endif
                        @if($partner->vehicle_insurance) 
                            <button type="button" class="btn btn-sm btn-outline-secondary doc-preview" data-src="{{ asset('uploads/delivery_partners/vehicle/' . $partner->vehicle_insurance) }}">
                                <i class="bi bi-shield-check"></i> Insurance
                            </button> 
                        @endif
                        @if($partner->vehicle_fitness) 
                            <button type="button" class="btn btn-sm btn-outline-secondary doc-preview" data-src="{{ asset('uploads/delivery_partners/vehicle/' . $partner->vehicle_fitness) }}">
                                <i class="bi bi-check2-circle"></i> Fitness Cert
                            </button> 
                        @endif
                    </div>
                </div>
                <div class="col-md-12 mt-4">
                    <div class="label-text">Vehicle Images <span class="text-muted text-lowercase" style="font-weight: 400;">(Click to view)</span></div>
                    <div class="d-flex gap-3 mt-2">
                        @if($partner->vehicle_image_front)
                            <div>
                                <div class="small text-muted text-center mb-1">Front</div>
                                <img src="{{ asset('uploads/delivery_partners/vehicle/' . $partner->vehicle_image_front) }}" class="img-preview" data-bs-toggle="modal" data-bs-target="#imageViewerModal" style="width: 100px; height: 80px; object-fit: cover; border-radius: 8px;" alt="Vehicle Front">
                            </div>
                        @endif
                        @if($partner->vehicle_image_back)
                            <div>
                                <div class="small text-muted text-center mb-1">Back</div>
                                <img src="{{ asset('uploads/delivery_partners/vehicle/' . $partner->vehicle_image_back) }}" class="img-preview" data-bs-toggle="modal" data-bs-target="#imageViewerModal" style="width: 100px; height: 80px; object-fit: cover; border-radius: 8px;" alt="Vehicle Back">
                            </div>
                        @endif
                        @if($partner->vehicle_image_side)
                            <div>
                                <div class="small text-muted text-center mb-1">Side</div>
                                <img src="{{ asset('uploads/delivery_partners/vehicle/' . $partner->vehicle_image_side) }}" class="img-preview" data-bs-toggle="modal" data-bs-target="#imageViewerModal" style="width: 100px; height: 80px; object-fit: cover; border-radius: 8px;" alt="Vehicle Side">
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="section-break"></div>

            <!-- Payout -->
            <h5 class="fw-bold mb-3 text-primary">Payout Details</h5>
            <div class="row g-4">
                <div class="col-md-3"><div class="label-text">Bank Name</div><div class="value-text">{{ $partner->bank_name ?? 'N/A' }}</div></div>
                <div class="col-md-3"><div class="label-text">Account No.</div><div class="value-text">{{ $partner->bank_account_number ?? 'N/A' }}</div></div>
                <div class="col-md-3"><div class="label-text">IFSC Code</div><div class="value-text">{{ $partner->bank_ifsc_code ?? 'N/A' }}</div></div>
                <div class="col-md-3"><div class="label-text">UPI ID</div><div class="value-text">{{ $partner->upi_id ?? 'N/A' }}</div></div>
            </div>

            <!-- Audit Info -->
            <div class="mt-5 p-3 bg-light rounded text-muted small">
                <strong>Record Audit:</strong> Created by {{ $partner->created_by_role }} on {{ $partner->created_at ? $partner->created_at->format('d M Y, h:i A') : 'N/A' }}. 
                @if($partner->updated_by) Last updated by {{ $partner->updated_by_role }} on {{ $partner->updated_at->format('d M Y, h:i A') }}. @endif
            </div>

        </div>
    </div>

    <!-- Image Viewer Modal (For purely image files like Vehicle Front/Back/Side) -->
    <div class="modal fade" id="imageViewerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content" style="background: transparent; border: none;">
                <div class="modal-header border-0 pb-0" style="position: absolute; right: 0; z-index: 10;">
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" style="background-color: rgba(0,0,0,0.5); border-radius: 50%; padding: 10px;"></button>
                </div>
                <div class="modal-body text-center p-0">
                    <img id="modalEnlargedImage" src="" class="img-fluid" style="border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); max-height: 85vh;" alt="Enlarged Image">
                </div>
            </div>
        </div>
    </div>

    <!-- Universal Document Viewer Modal (Supports PDFs and Images for KYC/Docs) -->
    <div class="modal fade" id="documentViewerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content" style="border-radius: 16px; overflow: hidden;">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold">Document Viewer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0" style="height: 75vh; background: #f8fafc;">
                    <iframe id="modalDocumentFrame" src="" style="width: 100%; height: 100%; border: none;"></iframe>
                </div>
            </div>
        </div>
    </div>

    @include('admin.includes.footer_links')
    
    <!-- jQuery Script to handle Modals -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Handle Vehicle Images (Img tag mapping)
            $('.img-preview').on('click', function() {
                var imageSrc = $(this).attr('src');
                $('#modalEnlargedImage').attr('src', imageSrc);
            });

            // Handle KYC & Docs (Iframe mapping for PDF/Image support)
            $('.doc-preview').on('click', function() {
                var docSrc = $(this).attr('data-src');
                $('#modalDocumentFrame').attr('src', docSrc);
                $('#documentViewerModal').modal('show');
            });

            // Clear iframe src on close to stop background rendering
            $('#documentViewerModal').on('hidden.bs.modal', function () {
                $('#modalDocumentFrame').attr('src', '');
            });
        });
    </script>
</body>
</html>