<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Vendor Feedback</title>
    @include('admin.includes.header_links')
</head>

<body>
    @include('admin.includes.sidebar')
    @include('admin.includes.header')

    <div class="content">
        <div class="page-head">
            <h1 class="page-title">Vendor Feedback</h1>
            <div class="action-group">
                <div class="action-group">
                    <a href="{{ route('manage_vendor') }}" class="btn-action btn-manage">Manage Vendor</a>
                </div>
            </div>
        </div>

        <div class="property-form">
            <form action="{{ route('vendor_feedbacks') }}" method="GET" class="filter-section mb-4">
                <div class="row g-3 align-items-end">
                    <div class="col-md-7">
                        <label class="form-label">Search Feedback</label>
                        <input type="text" name="search" class="form-control"
                            placeholder="Vendor & User name, email, mobile" value="{{ request('search') }}">
                    </div>

                    <div class="col-md-3">
                        <div class="d-flex gap-2">
                            <button type="submit" class="icon-btn btn-search" title="Search">
                                <i class="bi bi-search"></i>
                            </button>
                            <a href="{{ route('vendor_feedbacks') }}" class="icon-btn btn-refresh" title="Reset">
                                <i class="bi bi-arrow-clockwise"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Vendor Details</th>
                            <th>User Details</th>
                            <th>Feedback</th>
                            <!-- <th>Rating</th> -->
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($feedbacks as $feedback)
                            <tr>
                                <td>
                                    <!-- Use business_name for Vendors -->
                                    <strong>{{ $feedback->vendor?->business_name ?? 'Unknown Vendor' }}</strong><br>
                                    <small><b>Contact Person : </b>{{ $feedback->vendor?->contact_person ?? 'N/A' }}</small><br>
                                    <small class="text-muted">{{ $feedback->vendor?->business_email ?? 'N/A' }}, {{ $feedback->vendor?->business_mobile ?? 'N/A' }}</small>
                                </td>
                                <td>
                                    <!-- Use name for AppUsers -->
                                    <strong>{{ $feedback->user?->name ?? 'Deleted User' }}</strong><br>
                                    <small class="text-muted">{{ $feedback->user?->email ?? 'N/A' }}, {{ $feedback->user?->mobile ?? 'N/A' }}</small>
                                </td>
                                <td>
                                    <p>{{ $feedback->description }}</p>
                                </td>
                                <td>{{ $feedback->created_at->format('d M Y') }}</td>
                                <!-- <td>
                                    <span class="badge bg-success">Active</span>
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-danger">Delete</button>
                                </td> -->
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">No feedback found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="pagination-container d-flex justify-content-between align-items-center mt-3">
                <div class="pagination-info">
                    Showing {{ $feedbacks->firstItem() }} to {{ $feedbacks->lastItem() }} of
                    {{ $feedbacks->total() }} entries
                </div>
                <div>
                    {{ $feedbacks->appends(request()->query())->links('pagination::bootstrap-4') }}
                </div>
            </div>
        </div>
    </div>

    @include('admin.includes.footer_links')
</body>

</html>