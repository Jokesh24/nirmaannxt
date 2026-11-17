<?php

use GuzzleHttp\Middleware;
use Illuminate\Support\Facades\Route;

/* Admin Controllers */
use App\Http\Controllers\admin\LoginController;
use App\Http\Controllers\admin\DashboardController;
use App\Http\Controllers\admin\masterdata\BrandController;
use App\Http\Controllers\admin\masterdata\BusinessTypeController;
use App\Http\Controllers\admin\masterdata\BankController;
use App\Http\Controllers\admin\masterdata\MasterdataController;
use App\Http\Controllers\admin\masterdata\CancelReasonsController;
use App\Http\Controllers\admin\ProductController;
use App\Http\Controllers\admin\VendorController;
use App\Http\Controllers\admin\VendorStockController;
use App\Http\Controllers\admin\EngineerController;
use App\Http\Controllers\admin\DeliveryPartnerController;
use App\Http\Controllers\admin\LiveRateController;
use App\Http\Controllers\admin\EstimatorController;
use App\Http\Controllers\admin\EstimatorUnitController;
use App\Http\Controllers\admin\DiscountCouponController;
use App\Http\Controllers\admin\BannerController;
use App\Http\Controllers\admin\AppUserController;
use App\Http\Controllers\admin\ContentController;
use App\Http\Controllers\admin\OrderAdminController;
use App\Http\Controllers\admin\VendorBulkProductController;
use App\Http\Controllers\admin\AdminBulkQuoteController;
use App\Http\Controllers\admin\BulkQuoteAdminController;






Route::get('/', function () {
    return view('welcome');
});


/* ==========================================================
 * ADMIN PUBLIC ROUTES (No Auth Required)
 * ========================================================== */

Route::group(['prefix' => 'admin'], function () {
    Route::get('/', [LoginController::class, 'login'])->name('admin.login');
    Route::post('/authenticate', [LoginController::class, 'authenticate'])->name('admin.authenticate');
    Route::get('/logout', [LoginController::class, 'logout'])->name('admin.logout');
    Route::get('/create-test', [LoginController::class, 'createTestAdmin']);
    Route::post('/send-otp', [LoginController::class, 'sendOtp'])->name('admin.sendOtp');
    Route::post('/verify-otp', [LoginController::class, 'verifyOtp'])->name('admin.verifyOtp');
    Route::post('/reset-password', [LoginController::class, 'resetPassword'])->name('admin.resetPassword');
});


/* ==========================================================
 * ADMIN PROTECTED ROUTES (Auth Required)
 * ========================================================== */

Route::group(['prefix' => 'admin', 'middleware' => ['auth:admin', 'nocache']], function () {

    /* Dashboard & Profile */
    Route::get('/dashboard', [DashboardController::class, 'dashboard'])->name('admin.dashboard');
    Route::post('/profile/update', [LoginController::class, 'updateProfile'])->name('admin.profile.update');
    Route::get('/change-password', [LoginController::class, 'changePassword'])->name('admin.changePassword');
    Route::post('/change-password', [LoginController::class, 'updatePassword'])->name('admin.updatePassword');

    /* --------------------------------------------------------
     * MASTER DATA
     * -------------------------------------------------------- */

    /* Brands */
    Route::get('/add-brand', [BrandController::class, 'addBrand'])->name('add_brand');
    Route::post('/store-brand', [BrandController::class, 'storeBrand'])->name('store_brand');
    Route::get('/manage-brand', [BrandController::class, 'manageBrand'])->name('manage_brand');
    Route::post('/update-brand', [BrandController::class, 'updateBrand'])->name('update_brand');
    Route::get('/delete-brand/{id}', [BrandController::class, 'deleteBrand'])->name('delete_brand');

    /* Sizes */
    Route::get('/add-size', [MasterdataController::class, 'addSizes'])->name('add_size');
    Route::post('/store-size', [MasterdataController::class, 'storeSizes'])->name('store_size');
    Route::get('/manage-size', [MasterdataController::class, 'manageSizes'])->name('manage_size');
    Route::post('/update-size', [MasterdataController::class, 'updateSizes'])->name('update_size');
    Route::get('/delete-size/{id}', [MasterdataController::class, 'deleteSizes'])->name('delete_size');

    /* Colours */
    Route::get('/add-colour', [MasterdataController::class, 'addColours'])->name('add_colour');
    Route::post('/store-colour', [MasterdataController::class, 'storeColours'])->name('store_colour');
    Route::get('/manage-colour', [MasterdataController::class, 'manageColours'])->name('manage_colour');
    Route::post('/update-colour', [MasterdataController::class, 'updateColours'])->name('update_colour');
    Route::get('/delete-colour/{id}', [MasterdataController::class, 'deleteColours'])->name('delete_colour');

    /* Units */
    Route::get('/add-unit', [MasterdataController::class, 'addUnits'])->name('add_unit');
    Route::post('/store-unit', [MasterdataController::class, 'storeUnits'])->name('store_unit');
    Route::get('/manage-unit', [MasterdataController::class, 'manageUnits'])->name('manage_unit');
    Route::post('/update-unit', [MasterdataController::class, 'updateUnits'])->name('update_unit');
    Route::get('/delete-unit/{id}', [MasterdataController::class, 'deleteUnits'])->name('delete_unit');

    /* Business Types */
    Route::get('/add-business-type', [BusinessTypeController::class, 'addBusinessType'])->name('add_business_type');
    Route::post('/store-business-type', [BusinessTypeController::class, 'storeBusinessType'])->name('store_business_type');
    Route::get('/manage-business-type', [BusinessTypeController::class, 'manageBusinessType'])->name('manage_business_type');
    Route::post('/update-business-type', [BusinessTypeController::class, 'updateBusinessType'])->name('update_business_type');
    Route::get('/delete-business-type/{id}', [BusinessTypeController::class, 'deleteBusinessType'])->name('delete_business_type');

    /* Cancel Reasons */
    Route::get('/add-cancel-reason', [CancelReasonsController::class, 'addCancelReason'])->name('add_cancel_reason');
    Route::post('/store-cancel-reason', [CancelReasonsController::class, 'storeCancelReason'])->name('store_cancel_reason');
    Route::get('/manage-cancel-reason', [CancelReasonsController::class, 'manageCancelReason'])->name('manage_cancel_reason');
    Route::post('/update-cancel-reason', [CancelReasonsController::class, 'updateCancelReason'])->name('update_cancel_reason');
    Route::get('/delete-cancel-reason/{id}', [CancelReasonsController::class, 'deleteCancelReason'])->name('delete_cancel_reason');

    /* Banks */
    Route::get('/add-bank', [BankController::class, 'addBank'])->name('add_bank');
    Route::post('/store-bank', [BankController::class, 'storeBank'])->name('store_bank');
    Route::get('/manage-bank', [BankController::class, 'manageBank'])->name('manage_bank');
    Route::post('/update-bank', [BankController::class, 'updateBank'])->name('update_bank');
    Route::get('/delete-bank/{id}', [BankController::class, 'deleteBank'])->name('delete_bank');


    /* --------------------------------------------------------
     * CATALOG & PRODUCTS
     * -------------------------------------------------------- */

    /* Products */
    Route::get('/add-product', [ProductController::class, 'add_product'])->name('admin.products.add');
    Route::get('/get-subcategories/{category_id}', [ProductController::class, 'get_subcategories'])->name('admin.get_subcategories');
    Route::post('/product/store', [ProductController::class, 'store_product'])->name('admin.products.store');
    Route::get('/manage-product', [ProductController::class, 'manage_product'])->name('admin.products.manage');
    Route::get('/product-view/{id}', [ProductController::class, 'product_view'])->name('admin.products.view');
    Route::get('/product-delete/{id}', [ProductController::class, 'delete_product'])->name('admin.products.delete');
    Route::get('/product-edit/{id}', [ProductController::class, 'edit_product'])->name('admin.products.edit');
    Route::post('/product-update/{id}', [ProductController::class, 'update_product'])->name('admin.products.update');

    /* Product Categories */
    Route::get('/add-product-category', [ProductController::class, 'add_product_category'])->name('admin.product_categories.add');
    Route::post('/product-category/store', [ProductController::class, 'store_product_category'])->name('admin.product_category.store');
    Route::get('/manage-product-category', [ProductController::class, 'manage_product_category'])->name('admin.product_categories.manage');
    Route::get('/edit-product-category/{id}', [ProductController::class, 'edit_product_category'])->name('admin.product_category.edit');
    Route::post('/update-product-category/{id}', [ProductController::class, 'update_product_category'])->name('admin.product_category.update');
    Route::get('/delete-product-category/{id}', [ProductController::class, 'delete_product_category'])->name('admin.product_category.delete');

    /* Product Subcategories */
    Route::get('/add-product-subcategory', [ProductController::class, 'add_product_subcategory'])->name('admin.product_subcategories.add');
    Route::post('/product-subcategory/store', [ProductController::class, 'store_product_subcategory'])->name('admin.product_subcategory.store');
    Route::get('/manage-product-subcategory', [ProductController::class, 'manage_product_subcategory'])->name('admin.product_subcategories.manage');
    Route::get('/edit-product-subcategory/{id}', [ProductController::class, 'edit_product_subcategory'])->name('admin.product_subcategory.edit');
    Route::post('/update-product-subcategory/{id}', [ProductController::class, 'update_product_subcategory'])->name('admin.product_subcategory.update');
    Route::get('/delete-product-subcategory/{id}', [ProductController::class, 'delete_product_subcategory'])->name('admin.product_subcategory.delete');


    // Manage Orders Route Group
    Route::get('/manage-orders', [OrderAdminController::class, 'manage'])->name('manage_orders');
    Route::get('/view-order/{id}', [OrderAdminController::class, 'view'])->name('view_order');
    Route::post('/assign-order-engineer', [OrderAdminController::class, 'assignEngineer'])->name('assign_order_engineer');
    Route::post('/assign-order-vendor', [OrderAdminController::class, 'assignVendor'])->name('assign_order_vendor');
    Route::post('/update-order-status', [OrderAdminController::class, 'updateStatus'])->name('update_order_status');
    Route::get('/pending-qty-verifications', [OrderAdminController::class, 'pendingQuantityVerifications'])->name('pending_qty_verifications');
    Route::post('/approve-qty-verification', [OrderAdminController::class, 'approveQuantityVerification'])->name('approve_qty_verification');
    Route::post('/reject-qty-verification', [OrderAdminController::class, 'rejectQuantityVerification'])->name('reject_qty_verification');
    Route::get('/pending-customer-rejections', [OrderAdminController::class, 'pendingCustomerRejections'])->name('pending_customer_rejections');
    Route::post('/approve-customer-rejection', [OrderAdminController::class, 'approveCustomerRejection'])->name('approve_customer_rejection');
    Route::post('/reject-customer-rejection', [OrderAdminController::class, 'rejectCustomerRejection'])->name('reject_customer_rejection');

    // // Bulk Quote Routes
    // Route::get('/bulk-quotes', [AdminBulkQuoteController::class, 'manage'])->name('manage_bulk_quotes');
    // Route::get('/bulk-quotes/view/{id}', [AdminBulkQuoteController::class, 'view'])->name('view_bulk_quote');
    // Route::post('/bulk-quotes/assign-vendor', [AdminBulkQuoteController::class, 'assignVendor'])->name('assign_bulk_quote_vendor');
    // Route::post('/bulk-quotes/update-status', [AdminBulkQuoteController::class, 'updateStatus'])->name('update_bulk_quote_status');


    Route::get('/admin/bulk-quotes', [BulkQuoteAdminController::class, 'manage'])->name('manage_bulk_quotes');
    Route::get('/admin/bulk-quotes/{id}', [BulkQuoteAdminController::class, 'view'])->name('view_bulk_quote');
    Route::post('/admin/bulk-quotes/assign-vendors', [BulkQuoteAdminController::class, 'assignVendors'])->name('assign_bulk_quote_vendors');
    Route::get('/admin/bulk-quotes/{quoteId}/vendor/{vendorId}', [BulkQuoteAdminController::class, 'vendorView'])->name('bulk_quote_vendor_view');
    Route::post('/admin/bulk-quotes/vendor-response', [BulkQuoteAdminController::class, 'vendorResponse'])->name('bulk_quote_vendor_response');


    /* --------------------------------------------------------
     * ENTITY MANAGEMENT (Users, Vendors, Engineers, Delivery)
     * -------------------------------------------------------- */

    /* App Users */
    Route::get('/app-users', [AppUserController::class, 'manage'])->name('manage_app_users');
    Route::get('/app-users/view/{id}', [AppUserController::class, 'view'])->name('view_app_user');
    Route::post('/app-users/status/{id}', [AppUserController::class, 'updateStatus'])->name('update_app_user_status');

    /* Vendors */
    Route::get('/add_vendor', [VendorController::class, 'add'])->name('add_vendor');
    Route::post('/store-vendor', [VendorController::class, 'store'])->name('store_vendor');
    Route::get('/get-cities', [VendorController::class, 'getCities'])->name('get_cities');
    Route::get('/manage_vendor', [VendorController::class, 'manage'])->name('manage_vendor');
    Route::get('/view-vendor/{id}', [VendorController::class, 'view'])->name('view_vendor');
    Route::get('/edit-vendor/{id}', [VendorController::class, 'edit'])->name('edit_vendor');
    Route::post('/update-vendor/{id}', [VendorController::class, 'update'])->name('update_vendor');
    Route::get('/delete-vendor/{id}', [VendorController::class, 'delete'])->name('delete_vendor');
    Route::get('/export-vendor', [VendorController::class, 'export'])->name('export_vendor');
    Route::get('/vendor-feedbacks', [VendorController::class, 'vendorFeedback'])->name('vendor_feedbacks');
    Route::get('/vendor-enquiries/{id}', [VendorController::class, 'vendorEnquiries'])->name('vendor_enquiries');
    Route::post('/toggle-enquiry-status/{id}', [VendorController::class, 'toggleEnquiryStatus'])->name('toggle_enquiry_status');
    Route::post('/update-vendor-position', [VendorController::class, 'updatePosition'])->name('update_vendor_position');

    /* Vendor Stock */
    Route::get('vendor-stock/add', [VendorStockController::class, 'add_stock'])->name('admin.vendor_stock.add');
    Route::get('vendor-stock/manage', [VendorStockController::class, 'manage_stock'])->name('admin.vendor_stock.manage');
    Route::get('vendor-stock/get-variants', [VendorStockController::class, 'get_variants_for_stock'])->name('admin.vendor_stock.get_variants');
    Route::post('vendor-stock/store', [VendorStockController::class, 'store_stock'])->name('admin.vendor_stock.store');
    Route::get('vendor-stock/edit/{id}', [VendorStockController::class, 'edit_stock'])->name('admin.vendor_stock.edit');
    Route::post('vendor-stock/update/{id}', [VendorStockController::class, 'update_stock'])->name('admin.vendor_stock.update');
    Route::get('vendor-stock/delete/{id}', [VendorStockController::class, 'delete_stock'])->name('admin.vendor_stock.delete');

    /* Vendor Bulk Products */
    Route::get('/vendor-bulk-products/manage', [VendorBulkProductController::class, 'manage'])->name('admin.vendor_bulk.manage');
    Route::get('/vendor-bulk-products/configure/{vendor_id}', [VendorBulkProductController::class, 'edit'])->name('admin.vendor_bulk.edit');
    Route::post('/vendor-bulk-products/update/{vendor_id}', [VendorBulkProductController::class, 'update'])->name('admin.vendor_bulk.update');


    /* Engineers */
    Route::get('/add_engineer', [EngineerController::class, 'add'])->name('add_engineer');
    Route::post('/store-engineer', [EngineerController::class, 'store'])->name('store_engineer');
    Route::get('/manage_engineer', [EngineerController::class, 'manage'])->name('manage_engineer');
    Route::get('/view-engineer/{id}', [EngineerController::class, 'view'])->name('view_engineer');
    Route::get('/edit-engineer/{id}', [EngineerController::class, 'edit'])->name('edit_engineer');
    Route::post('/update-engineer/{id}', [EngineerController::class, 'update'])->name('update_engineer');
    Route::get('/delete-engineer/{id}', [EngineerController::class, 'delete'])->name('delete_engineer');
    Route::post('/update-engineer-position', [EngineerController::class, 'updatePosition'])->name('update_engineer_position');

    /* Delivery Partners */
    Route::get('/add_delivery_partner', [DeliveryPartnerController::class, 'add'])->name('add_delivery_partner');
    Route::post('/store-delivery-partner', [DeliveryPartnerController::class, 'store'])->name('store_delivery_partner');
    Route::get('/manage_delivery_partner', [DeliveryPartnerController::class, 'manage'])->name('manage_delivery_partner');
    Route::get('/view-delivery-partner/{id}', [DeliveryPartnerController::class, 'view'])->name('view_delivery_partner');
    Route::get('/edit-delivery-partner/{id}', [DeliveryPartnerController::class, 'edit'])->name('edit_delivery_partner');
    Route::post('/update-delivery-partner/{id}', [DeliveryPartnerController::class, 'update'])->name('update_delivery_partner');
    Route::get('/delete-delivery-partner/{id}', [DeliveryPartnerController::class, 'delete'])->name('delete_delivery_partner');
    Route::post('/update-delivery-partner-position', [DeliveryPartnerController::class, 'updatePosition'])->name('update_delivery_partner_position');


    /* --------------------------------------------------------
     * ORDERS & TRANSACTIONS
     * -------------------------------------------------------- */

    /* Orders */
    Route::get('/order-list', [ProductController::class, 'order_list'])->name('admin.orders.list');

    /* Discount Coupons */
    Route::get('/coupons', [DiscountCouponController::class, 'manage'])->name('manage_coupon');
    Route::get('/coupons/add', [DiscountCouponController::class, 'add'])->name('add_coupon');
    Route::post('/coupons/store', [DiscountCouponController::class, 'store'])->name('store_coupon');
    Route::get('/coupons/view/{id}', [DiscountCouponController::class, 'view'])->name('view_coupon');
    Route::get('/coupons/edit/{id}', [DiscountCouponController::class, 'edit'])->name('edit_coupon');
    Route::post('/coupons/update/{id}', [DiscountCouponController::class, 'update'])->name('update_coupon');
    Route::get('/coupons/delete/{id}', [DiscountCouponController::class, 'delete'])->name('delete_coupon');
    Route::post('/coupons/update-position', [DiscountCouponController::class, 'updatePosition'])->name('update_coupon_position');


    /* --------------------------------------------------------
     * TOOLS & FEATURES
     * -------------------------------------------------------- */

    /* Live Rates */
    Route::get('/live-rates', [LiveRateController::class, 'index'])->name('admin.live_rates.manage');
    Route::post('/live-rates/update', [LiveRateController::class, 'update_rates'])->name('admin.live_rates.update');

    /* Material Estimator */
    Route::get('/estimator', [EstimatorController::class, 'index'])->name('admin.estimator.index');
    Route::get('/estimator/create', [EstimatorController::class, 'create'])->name('admin.estimator.create');
    Route::post('/estimator/store', [EstimatorController::class, 'store'])->name('admin.estimator.store');
    Route::get('/estimator/edit/{id}', [EstimatorController::class, 'edit'])->name('admin.estimator.edit');
    Route::post('/estimator/update/{id}', [EstimatorController::class, 'update'])->name('admin.estimator.update');
    Route::get('/estimator/delete/{id}', [EstimatorController::class, 'destroy'])->name('admin.estimator.delete');

    /* Estimator AJAX endpoints */
    Route::get('/estimator/get-variants/{category_id}', [EstimatorController::class, 'get_variants_by_category']);
    Route::post('/estimator/add-unit-ajax', [EstimatorController::class, 'store_unit_ajax'])->name('admin.estimator.add_unit_ajax');

    /* Estimator Units */
    Route::get('/estimator/units', [EstimatorUnitController::class, 'index'])->name('admin.estimator.units.index');
    Route::post('/estimator/units/store', [EstimatorUnitController::class, 'store'])->name('admin.estimator.units.store');
    Route::get('/estimator/units/delete/{id}', [EstimatorUnitController::class, 'destroy'])->name('admin.estimator.units.delete');


    /* --------------------------------------------------------
     * APP CONTENT & UI
     * -------------------------------------------------------- */

    /* Banners */
    Route::get('/banners/add', [BannerController::class, 'add'])->name('admin.banners.add');
    Route::post('/banners/store', [BannerController::class, 'store'])->name('admin.banners.store');
    Route::get('/banners/edit/{id}', [BannerController::class, 'edit'])->name('admin.banners.edit');
    Route::post('/banners/update', [BannerController::class, 'update'])->name('admin.banners.update');
    Route::get('/banners/delete/{id}', [BannerController::class, 'delete'])->name('admin.banners.delete');
    Route::post('/banner-types/store', [BannerController::class, 'storeType'])->name('admin.banner-types.store');

    /* Content Management (Terms, About, etc.) */
    Route::group(['prefix' => 'content/{type}'], function () {
        Route::get('/manage', [ContentController::class, 'manage'])->name('manage_content');
        Route::get('/add', [ContentController::class, 'add'])->name('add_content');
        Route::post('/store', [ContentController::class, 'store'])->name('store_content');
        Route::get('/edit/{id}', [ContentController::class, 'edit'])->name('edit_content');
        Route::post('/update/{id}', [ContentController::class, 'update'])->name('update_content');
        Route::get('/delete/{id}', [ContentController::class, 'delete'])->name('delete_content');
    });

});