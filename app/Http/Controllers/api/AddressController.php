<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Address;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use App\Models\Order;
use Carbon\Carbon;

class AddressController extends Controller
{

    /**
     * Store Address details from Billing & Map Screens
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized or invalid token.'
            ], 401);
        }

        if ($user->status == 0 || $user->status == 2) {
            return response()->json([
                'status' => false,
                'message' => 'Action forbidden. Your account is inactive or blocked.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'orderingFor' => 'required|in:1,2', // 1 = Individual, 2 = Business
            'siteName' => 'required|string|max:255',
            'sitePhoto' => 'required|image|mimes:jpeg,png,jpg|max:5120',
            // Conditional validations based on Ordering For selection
            'receiverName' => 'required_if:orderingFor,1|nullable|string|max:255',
            'companyName' => 'required_if:orderingFor,2|nullable|string|max:255',
            'mobileNumber' => 'required|string|max:20',

            'deliveryAddress' => 'required|string',
            'deliveryLandmark' => 'nullable|string|max:255',
            'deliveryPincode' => 'required|string|max:20',
            'deliveryGst' => 'nullable|string|max:50',

            'isBillingSame' => 'required|boolean',

            // Billing fields required only if they did NOT check the "Same as delivery" box
            'billingAddress' => 'required_if:isBillingSame,0|nullable|string',
            'billingLandmark' => 'nullable|string|max:255',
            'billingPincode' => 'required_if:isBillingSame,0|nullable|string|max:20',
            'billingGst' => 'nullable|string|max:50',

            'addressTitle' => 'nullable|string|max:100', // Label from the map popup
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Deselect previous addresses
        Address::where('user_id', $user->id)->update(['is_selected' => 0]);

        $address = new Address();
        $address->user_id = $user->id;
        $address->ordering_for = $request->orderingFor;
        $address->site_name = $request->siteName;
        $address->receiver_name = $request->receiverName;
        $address->company_name = $request->companyName;
        $address->mobile_number = $request->mobileNumber;

        $address->delivery_address = $request->deliveryAddress;
        $address->delivery_landmark = $request->deliveryLandmark;
        $address->delivery_pincode = $request->deliveryPincode;
        $address->delivery_gst = $request->deliveryGst;

        $address->is_billing_same = $request->isBillingSame;
        $address->is_selected = 1;

        // Auto-fill billing block if checkbox is checked
        if ($request->isBillingSame) {
            $address->billing_address = $request->deliveryAddress;
            $address->billing_landmark = $request->deliveryLandmark;
            $address->billing_pincode = $request->deliveryPincode;
            $address->billing_gst = $request->deliveryGst;
        } else {
            $address->billing_address = $request->billingAddress;
            $address->billing_landmark = $request->billingLandmark;
            $address->billing_pincode = $request->billingPincode;
            $address->billing_gst = $request->billingGst;
        }

        $address->address_title = $request->addressTitle;
        $address->latitude = $request->latitude;
        $address->longitude = $request->longitude;
        $address->status = 1;

        // Handle File Upload 
        if ($request->hasFile('sitePhoto')) {
            $file = $request->file('sitePhoto');
            $destinationPath = public_path('uploads/site_photos');

            if (!File::exists($destinationPath)) {
                File::makeDirectory($destinationPath, 0755, true, true);
            }

            $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move($destinationPath, $fileName);

            $address->site_photo = 'uploads/site_photos/' . $fileName;
        }

        $address->save();

        return response()->json([
            'status' => true,
            'message' => 'Address saved successfully.',
            'data' => [
                'address' => $this->formatAddressResponse($address)
            ]
        ], 200);
    }

    /**
     * Get Address List
     * Retrieves all active addresses for the authenticated user.
     */
    public function getAddressList(Request $request)
    {
        $user = auth()->user();

        if (!$user || $user->status == 0 || $user->status == 2) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized or invalid account status.'
            ], 403);
        }

        // Fetch active addresses, newest first
        $addresses = Address::where('user_id', $user->id)
            ->where('status', 1)
            ->orderBy('id', 'desc')
            ->get();

        $formattedAddresses = $addresses->map(function ($address) {
            return $this->formatAddressResponse($address);
        });

        return response()->json([
            'status' => true,
            'message' => 'Addresses retrieved successfully.',
            'data' => [
                'addresses' => $formattedAddresses
            ]
        ], 200);
    }

    /**
     * Edit / Update Address
     * Updates an existing address. Expects 'addressId' in the request body.
     * Requires multipart/form-data if uploading a new photo.
     */
    public function updateAddress(Request $request)
    {
        $user = auth()->user();

        if (!$user || $user->status == 0 || $user->status == 2) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized or invalid account status.'
            ], 403);
        }

        // Validate the incoming request, strictly enforcing the presence of addressId
        $validator = Validator::make($request->all(), [
            'addressId' => 'required|integer|exists:addresses,id',
            'orderingFor' => 'sometimes|required|in:1,2',
            'siteName' => 'sometimes|required|string|max:255',
            'sitePhoto' => 'sometimes|nullable|image|mimes:jpeg,png,jpg|max:5120',
            'receiverName' => 'sometimes|required_if:orderingFor,1|nullable|string|max:255',
            'companyName' => 'sometimes|required_if:orderingFor,2|nullable|string|max:255',
            'mobileNumber' => 'sometimes|required|string|max:20',

            'deliveryAddress' => 'sometimes|required|string',
            'deliveryLandmark' => 'sometimes|nullable|string|max:255',
            'deliveryPincode' => 'sometimes|required|string|max:20',
            'deliveryGst' => 'sometimes|nullable|string|max:50',

            'isBillingSame' => 'sometimes|required|boolean',
            'billingAddress' => 'sometimes|required_if:isBillingSame,0|nullable|string',
            'billingLandmark' => 'sometimes|nullable|string|max:255',
            'billingPincode' => 'sometimes|required_if:isBillingSame,0|nullable|string|max:20',
            'billingGst' => 'sometimes|nullable|string|max:50',

            'addressTitle' => 'sometimes|nullable|string|max:100',
            'latitude' => 'sometimes|required|numeric',
            'longitude' => 'sometimes|required|numeric'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Strict ownership check utilizing the validated addressId
        $address = Address::where('id', $request->addressId)->where('user_id', $user->id)->first();

        if (!$address) {
            return response()->json([
                'status' => false,
                'message' => 'Address not found or unauthorized.'
            ], 404);
        }

        // Update standard fields if present in the payload
        if ($request->has('orderingFor'))
            $address->ordering_for = $request->orderingFor;
        if ($request->has('siteName'))
            $address->site_name = $request->siteName;
        if ($request->has('receiverName'))
            $address->receiver_name = $request->receiverName;
        if ($request->has('companyName'))
            $address->company_name = $request->companyName;
        if ($request->has('mobileNumber'))
            $address->mobile_number = $request->mobileNumber;

        if ($request->has('deliveryAddress'))
            $address->delivery_address = $request->deliveryAddress;
        if ($request->has('deliveryLandmark'))
            $address->delivery_landmark = $request->deliveryLandmark;
        if ($request->has('deliveryPincode'))
            $address->delivery_pincode = $request->deliveryPincode;
        if ($request->has('deliveryGst'))
            $address->delivery_gst = $request->deliveryGst;

        if ($request->has('isBillingSame'))
            $address->is_billing_same = $request->isBillingSame;
        if ($request->has('addressTitle'))
            $address->address_title = $request->addressTitle;
        if ($request->has('latitude'))
            $address->latitude = $request->latitude;
        if ($request->has('longitude'))
            $address->longitude = $request->longitude;

        // Logic for billing address synchronization during the update cycle
        if ($request->has('isBillingSame')) {
            if ($request->isBillingSame == 1) {
                // Synchronize billing address with the delivery address
                $address->billing_address = $address->delivery_address;
                $address->billing_landmark = $address->delivery_landmark;
                $address->billing_pincode = $address->delivery_pincode;
                $address->billing_gst = $address->delivery_gst;
            } else {
                // Isolate the billing fields if explicitly provided and the synchronization flag is disabled
                if ($request->has('billingAddress'))
                    $address->billing_address = $request->billingAddress;
                if ($request->has('billingLandmark'))
                    $address->billing_landmark = $request->billingLandmark;
                if ($request->has('billingPincode'))
                    $address->billing_pincode = $request->billingPincode;
                if ($request->has('billingGst'))
                    $address->billing_gst = $request->billingGst;
            }
        }

        // Handle File Upload replacing the existing photographic asset
        if ($request->hasFile('sitePhoto')) {
            $file = $request->file('sitePhoto');
            $destinationPath = public_path('uploads/site_photos');

            if (!File::exists($destinationPath)) {
                File::makeDirectory($destinationPath, 0755, true, true);
            }

            $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

            // Delete the deprecated image file to optimize server storage capacity
            $oldPhoto = $address->getRawOriginal('site_photo');
            if ($oldPhoto && File::exists(public_path($oldPhoto))) {
                File::delete(public_path($oldPhoto));
            }

            $file->move($destinationPath, $fileName);
            $address->site_photo = 'uploads/site_photos/' . $fileName;
        }

        $address->save();

        return response()->json([
            'status' => true,
            'message' => 'Address updated successfully.',
            'data' => [
                'address' => $this->formatAddressResponse($address)
            ]
        ], 200);
    }

    /**
     * Delete Address
     * Hard deletes the address based on the addressId provided in the request payload.
     */
    public function deleteAddress(Request $request)
    {
        $user = auth()->user();

        if (!$user || $user->status == 0 || $user->status == 2) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized or invalid account status.'
            ], 403);
        }

        // Validate the incoming payload for the presence of addressId
        $validator = Validator::make($request->all(), [
            'addressId' => 'required|integer|exists:addresses,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Strict ownership check utilizing the validated addressId
        $address = Address::where('id', $request->addressId)->where('user_id', $user->id)->first();

        if (!$address) {
            return response()->json([
                'status' => false,
                'message' => 'Address not found or unauthorized.'
            ], 404);
        }

        // Purge the associated image file from the local filesystem
        $oldPhoto = $address->getRawOriginal('site_photo');
        if ($oldPhoto && File::exists(public_path($oldPhoto))) {
            File::delete(public_path($oldPhoto));
        }

        // Execute a hard deletion of the database record
        $address->delete();

        return response()->json([
            'status' => true,
            'message' => 'Address deleted successfully.'
        ], 200);
    }

    /**
     * Set Address as Selected
     * Makes the provided address ID the active/selected address and deselects all others.
     */
    public function setDefaultAddress(Request $request)
    {
        $user = auth()->user();

        if (!$user || $user->status == 0 || $user->status == 2) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized or invalid account status.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'addressId' => 'required|integer|exists:addresses,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Verify ownership before modifying records
        $targetAddress = Address::where('id', $request->addressId)->where('user_id', $user->id)->first();

        if (!$targetAddress) {
            return response()->json([
                'status' => false,
                'message' => 'Address not found or unauthorized.'
            ], 404);
        }

        // Bulk update: Set all addresses for this user to 0
        Address::where('user_id', $user->id)->update(['is_selected' => 0]);

        // Target update: Set the requested address to 1
        $targetAddress->is_selected = 1;
        $targetAddress->save();

        return response()->json([
            'status' => true,
            'message' => 'Address selected successfully.',
            'data' => [
                'addressId' => $targetAddress->id,
                'isSelected' => true
            ]
        ], 200);
    }

    public function getAddressbyId(Request $request)
    {
        $user = auth()->user();

        // Check authentication and account status
        if (!$user || $user->status == 0 || $user->status == 2) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized or invalid account status.'
            ], 403);
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'addressId' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Fetch address belonging to authenticated user
        $address = Address::where('id', $request->addressId)
            ->where('user_id', $user->id)
            ->where('status', 1)
            ->first();

        if (!$address) {
            return response()->json([
                'status' => false,
                'message' => 'Address not found.'
            ], 404);
        }

        // Order status labels
        $statusMap = [
            1 => 'In-Review',
            2 => 'Confirmed',
            3 => 'Delivered',
            4 => 'Out for Delivery',
            5 => 'Cancelled',
            6 => 'Rejected',
        ];

        // Fetch previous orders placed for this address
        $orders = Order::with([
            'items.product',
            'items.variant'
        ])
            ->where('user_id', $user->id)
            ->where('address_id', $address->id)
            ->orderBy('id', 'desc')
            ->get();

        // Format previous orders for the mobile UI
        $previousOrders = $orders->map(function ($order) use ($statusMap) {

            $formattedItems = $order->items->map(function ($item) {

                $product = $item->product;

                // Use the unit and quantity saved with the order
                $unit = $item->unit ?? 'Units';
                $orderedQty = $item->qty;

                return [
                    'itemId' => $item->id,
                    'productId' => $item->product_id,
                    'variantId' => $item->variant_id,

                    'productName' => $product
                        ? $product->name
                        : 'Unknown Product',

                    'price' => (float) $item->total_price,
                    'qty' => (float) $orderedQty,
                    'qtyText' => $orderedQty . ' ' . $unit,

                    'image' => ($item->variant && $item->variant->image)
                        ? asset('uploads/products/' . $item->variant->image)
                        : null,
                ];
            });

            // Format delivery date.
            // Uses updated_at for completed orders because a dedicated
            // delivery timestamp is not shown in the provided controller.
            $deliveryText = null;

            if ((int) $order->status === 3) {
                $deliveryText = 'Delivered on ' .
                    Carbon::parse($order->updated_at)
                        ->format('d M, Y \a\t h:i A');
            } elseif (isset($statusMap[$order->status])) {
                $deliveryText = $statusMap[$order->status];
            }

            return [
                'orderId' => $order->id,

                'orderNumber' => $order->order_number,

                'orderDate' => Carbon::parse($order->created_at)
                    ->format('d M, Y h:i A'),

                'statusCode' => (int) $order->status,

                'orderStatus' => $statusMap[$order->status] ?? 'Unknown',

                'deliveryText' => $deliveryText,

                'grandTotal' => (float) $order->grand_total,

                'items' => $formattedItems,
            ];
        });

        // Return address and previous orders together
        return response()->json([
            'status' => true,
            'message' => 'Address details retrieved successfully.',
            'data' => [
                'address' => $this->formatAddressResponse($address),
                'previousOrders' => $previousOrders,
            ]
        ], 200);
    }

    /**
     * Centralized Response Formatter
     * Keeps your mapping strict and avoids code duplication across store, update, and get methods.
     */
    private function formatAddressResponse($address)
    {
        return [
            'id' => $address->id,
            'orderingFor' => (int) $address->ordering_for,
            'orderingForLabel' => $address->ordering_for_label ?? ($address->ordering_for == 1 ? 'Individual' : 'Business'),
            'siteName' => $address->site_name,
            'sitePhoto' => $address->site_photo, // URL is appended automatically via Model accessor
            'receiverName' => $address->receiver_name,
            'companyName' => $address->company_name,
            'mobileNumber' => $address->mobile_number,
            'deliveryAddress' => $address->delivery_address,
            'deliveryLandmark' => $address->delivery_landmark,
            'deliveryPincode' => $address->delivery_pincode,
            'deliveryGst' => $address->delivery_gst,
            'isBillingSame' => (bool) $address->is_billing_same,
            'billingAddress' => $address->billing_address,
            'billingLandmark' => $address->billing_landmark,
            'billingPincode' => $address->billing_pincode,
            'billingGst' => $address->billing_gst,
            'isSelected' => (bool) $address->is_selected,
            'addressTitle' => $address->address_title,
            'latitude' => $address->latitude,
            'longitude' => $address->longitude
        ];
    }


}