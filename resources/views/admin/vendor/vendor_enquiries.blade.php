<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Enquiries | {{ $vendor->business_name }}</title>
    @include('admin.includes.header_links')
    <style>
        .page-content {
            padding: 25px;
        }

        .enquiry-card {
            background: #fff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .table thead {
            background: #f8fafc;
            color: #475569;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
        }

        .row-unseen {
            background-color: #fffbeb !important;
            border-left: 4px solid #f59e0b;
        }

        .badge-pill {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }

        .action-icon {
            cursor: pointer;
            transition: 0.2s;
        }

        .action-icon:hover {
            color: #000;
        }
    </style>
</head>

<body>
    @include('admin.includes.sidebar')
    @include('admin.includes.header')

    <div class="content page-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 fw-bold text-dark mb-1">Enquiries: {{ $vendor->business_name }}</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('manage_vendor') }}">Vendors</a></li>
                        <li class="breadcrumb-item active">Enquiries</li>
                    </ol>
                </nav>
            </div>
            <a href="{{ route('manage_vendor') }}" class="btn btn-outline-primary"><i class="bi bi-arrow-left"></i> Back
                to List</a>
        </div>

        <div class="enquiry-card p-3 mb-4">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="btn-group" role="group">
                        <a href="{{ route('vendor_enquiries', $vendor->id) }}"
                            class="btn btn-sm {{ !request('filter') ? 'btn-dark' : 'btn-outline-secondary' }}">All</a>
                        <a href="{{ route('vendor_enquiries', ['id' => $vendor->id, 'filter' => 'unseen']) }}"
                            class="btn btn-sm {{ request('filter') == 'unseen' ? 'btn-warning' : 'btn-outline-secondary' }}">Unseen</a>
                        <a href="{{ route('vendor_enquiries', ['id' => $vendor->id, 'filter' => 'seen']) }}"
                            class="btn btn-sm {{ request('filter') == 'seen' ? 'btn-success' : 'btn-outline-secondary' }}">Seen</a>
                    </div>
                </div>
                <div class="col-md-6">
                    <form action="{{ route('vendor_enquiries', $vendor->id) }}" method="GET" class="d-flex">
                        <input type="text" name="search" class="form-control form-control-sm"
                            placeholder="Search by name, email or subject..." value="{{ request('search') }}">
                        <button type="submit" class="btn btn-sm btn-primary ms-2 px-3">Search</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="enquiry-card overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead>
                        <tr>
                            <th class="ps-4">S.No</th>
                            <th>Status</th>
                            <th>User Details</th>
                            <th>Location & Date</th>
                            <th>Message</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($enquiries as $key => $enquiry)
                            <tr class="{{ !$enquiry->is_seen ? 'row-unseen' : '' }}">
                                <td class="ps-4 fw-bold">{{ $enquiries->firstItem() + $key }}</td>
                                <td>
                                    <span
                                        class="badge {{ $enquiry->is_seen ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }} badge-pill">
                                        {{ $enquiry->is_seen ? 'SEEN' : 'NEW' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold">{{ $enquiry->name }}</div>
                                    <div class="small text-muted"><i
                                            class="bi bi-envelope me-1"></i>{{ $enquiry->email ?? 'N/A' }}</div>
                                    <div class="small text-muted"><i
                                            class="bi bi-phone me-1"></i>{{ $enquiry->mobile ?? 'N/A' }}</div>
                                </td>
                                <td>
                                    <div class="fw-medium text-dark">{{ $enquiry->location }}</div>
                                    <small class="text-muted"><i
                                            class="bi bi-calendar3 me-1"></i>{{ $enquiry->created_at->format('d M, Y') }}</small>
                                </td>
                                <td>
                                    <div class="text-truncate" style="max-width: 200px;">{{ $enquiry->description }}</div>
                                    <button class="btn btn-link btn-sm p-0 text-decoration-none"
                                        onclick="viewMessage('{{ addslashes($enquiry->location) }}', '{{ addslashes($enquiry->description) }}')">
                                        Read Full
                                    </button>
                                </td>
                                <td class="text-end pe-4">
                                    <button class="btn btn-sm {{ $enquiry->is_seen ? 'btn-outline-dark' : 'btn-dark' }}"
                                        onclick="toggleStatus({{ $enquiry->id }}, {{ $enquiry->is_seen ? 0 : 1 }})">
                                        {{ $enquiry->is_seen ? 'Mark New' : 'Mark Seen' }}
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">No records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4 d-flex justify-content-end">
            {{ $enquiries->appends(request()->input())->links('pagination::bootstrap-4') }}
        </div>
    </div>

    <div class="modal fade" id="viewMessageModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="msgSubject">Enquiry Subject</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p id="msgBody" style="white-space: pre-wrap; color: #475569;"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    @include('admin.includes.footer_links')

    <script>
        function toggleStatus(id, status) {
            var url = "{{ route('toggle_enquiry_status', ':id') }}".replace(':id', id);
            $.ajax({
                url: url,
                type: "POST",
                data: { _token: "{{ csrf_token() }}", status: status },
                success: function (response) {
                    if (response.success) location.reload();
                },
                error: function () { alert('Failed to update status.'); }
            });
        }
    </script>

    <script>
        function viewMessage(subject, body) {
            $('#msgSubject').text(subject);
            $('#msgBody').text(body);
            $('#viewMessageModal').modal('show');
        }
    </script>
</body>

</html>