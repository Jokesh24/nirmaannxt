<!-- Send to Vendors Modal (multi-select) -->
<div class="modal fade" id="vendorAssignModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form id="vendorAssignForm" action="{{ route('assign_bulk_quote_vendors') }}" method="POST"
            class="modal-content border-0 shadow rounded-3">
            @csrf
            <div class="modal-header border-bottom-0 pb-0">
                <h6 class="modal-title fw-bold text-dark">Send Quote Request to Vendors</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pb-2">
                <input type="hidden" name="bulk_quote_request_id" id="modal_bqr_id">

                <p class="small text-muted mb-3">
                    Select one or more vendors below. Distance shown is calculated from the
                    delivery location to each vendor's registered address.
                </p>

                <div id="vendor_checkbox_list" style="max-height: 360px; overflow-y: auto;">
                    @foreach($vendors as $vendor)
                        <label class="vendor-pick-row w-100" data-vendor-row="{{ $vendor->id }}">
                            <span class="d-flex align-items-center">
                                <input type="checkbox" class="form-check-input me-2" name="vendor_ids[]"
                                    value="{{ $vendor->id }}" data-lat="{{ $vendor->latitude ?? '' }}"
                                    data-lng="{{ $vendor->longitude ?? '' }}">
                                <span>
                                    <span class="d-block fw-semibold" style="color:#374151; font-size:0.9rem;">
                                        {{ $vendor->business_name }}
                                    </span>
                                    <span class="d-block small text-muted">{{ $vendor->contact_person }}</span>
                                </span>
                            </span>
                            <span class="vendor-dist" data-vendor-dist="{{ $vendor->id }}">Distance unknown</span>
                        </label>
                    @endforeach

                    @if($vendors->isEmpty())
                        <p class="text-muted small mb-0">No active vendors available.</p>
                    @else
                        <p class="text-muted small mb-0 d-none" id="vendor_no_match">
                            No vendors offer bulk for the items in this quote.
                        </p>
                    @endif
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-dark fw-medium px-4">
                    <i class="bi bi-send me-1"></i> Send to Selected Vendors
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Haversine formula — identical to the one already used on the Manage Orders page,
    // reused here so vendor distance is calculated the same way everywhere in the admin panel.
    function bqCalculateDistance(lat1, lon1, lat2, lon2) {
        if (!lat1 || !lon1 || !lat2 || !lon2) return null;

        const R = 6371; // Earth's radius in km
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lon2 - lon1) * Math.PI / 180;

        const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
            Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
            Math.sin(dLon / 2) * Math.sin(dLon / 2);

        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

        return (R * c).toFixed(1);
    }

    /**
     * Opens the multi-vendor "Send to Vendors" modal for a given bulk quote request.
     *
     * @param {number} quoteId
     * @param {number|null} quoteLat  Delivery location latitude (from bulk_quote_requests.latitude)
     * @param {number|null} quoteLng  Delivery location longitude (from bulk_quote_requests.longitude)
     * @param {number[]} alreadySentVendorIds  Vendor IDs already assigned to this quote (pre-checked + highlighted)
     * @param {number[]} eligibleVendorIds  Vendor IDs that offer bulk for at least one item in this quote
     */
    function openVendorAssignModal(quoteId, quoteLat, quoteLng, alreadySentVendorIds, eligibleVendorIds) {
        $('#modal_bqr_id').val(quoteId);

        quoteLat = parseFloat(quoteLat);
        quoteLng = parseFloat(quoteLng);
        alreadySentVendorIds = Array.isArray(alreadySentVendorIds) ? alreadySentVendorIds.map(Number) : [];
        eligibleVendorIds = Array.isArray(eligibleVendorIds) ? eligibleVendorIds.map(Number) : [];

        let visibleRows = 0;

        $('#vendor_checkbox_list input[type="checkbox"]').each(function () {
            let checkbox = $(this);
            let vendorId = parseInt(checkbox.val(), 10);
            let row = checkbox.closest('[data-vendor-row]');
            let distEl = $('[data-vendor-dist="' + vendorId + '"]');

            // Only vendors that actually offer bulk for at least one of this
            // quote's product items (via vendor_bulk_products) are shown.
            if (!eligibleVendorIds.includes(vendorId)) {
                checkbox.prop('checked', false);
                row.addClass('d-none');
                return;
            }

            row.removeClass('d-none');
            visibleRows++;

            let targetLat = parseFloat(checkbox.data('lat'));
            let targetLng = parseFloat(checkbox.data('lng'));

            if (quoteLat && quoteLng && targetLat && targetLng) {
                let distance = bqCalculateDistance(quoteLat, quoteLng, targetLat, targetLng);
                distEl.text(distance + ' km away');
            } else {
                distEl.text('Distance unknown');
            }

            if (alreadySentVendorIds.includes(vendorId)) {
                checkbox.prop('checked', true);
                row.addClass('already-sent');
            } else {
                checkbox.prop('checked', false);
                row.removeClass('already-sent');
            }
        });

        $('#vendor_no_match').toggleClass('d-none', visibleRows > 0);

        $('#vendorAssignModal').modal('show');
    }
</script>