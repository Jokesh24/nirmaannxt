<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/* Controllers */
use App\Http\Controllers\api\AuthController;
use App\Http\Controllers\api\AddressController;
use App\Http\Controllers\api\BankController;
use App\Http\Controllers\api\CatalogController;
use App\Http\Controllers\api\ProductController;
use App\Http\Controllers\api\BannerController;
use App\Http\Controllers\api\LiveRateController;
use App\Http\Controllers\api\ContentController;
use App\Http\Controllers\api\CartController;
use App\Http\Controllers\api\WishlistController;
use App\Http\Controllers\api\EstimatorApiController;
use App\Http\Controllers\api\CouponController;
use App\Http\Controllers\api\OrderController;
use App\Http\Controllers\api\RewardPointsController;
use App\Http\Controllers\api\BulkQuoteController;
use App\Http\Controllers\api\NotificationController;




/* ==========================================================
 * PUBLIC ROUTES
 * ========================================================== */

/* Authentication */
Route::post('login', [AuthController::class, 'login']);
Route::post('verifyOtp', [AuthController::class, 'verifyOtp']);
Route::post('refreshToken', [AuthController::class, 'refreshToken']);

/* Catalog & Products */
Route::get('/getBrands', [CatalogController::class, 'getBrands']);
Route::post('/searchBrands', [CatalogController::class, 'searchBrands']);
Route::get('/getCategories', [CatalogController::class, 'getCategories']);
Route::post('/searchCategories', [CatalogController::class, 'searchCategories']);
Route::post('/getSubcategories', [CatalogController::class, 'getSubcategories']);
Route::post('/getProducts', [ProductController::class, 'getProducts']);
Route::post('/productDetails', [ProductController::class, 'getProductDetails']);
Route::post('/searchProducts', [ProductController::class, 'searchProducts']);
Route::get('/getSortOptions', [ProductController::class, 'getSortOptions']);
Route::post('/getFilterOptions', [ProductController::class, 'getFilterOptions']);

/* Application Content & Master Data */
Route::get('/getBanners', [BannerController::class, 'getBanners']);
Route::get('/getLiveRates', [LiveRateController::class, 'getLiveRates']);
Route::get('/getBanks', [BankController::class, 'getBanks']);
Route::get('/getTermsAndConditions', [ContentController::class, 'getTermsAndConditions']);
Route::get('/getAboutNirmaanNxt', [ContentController::class, 'getAboutNirmaanNxt']);


/* ==========================================================
 * PROTECTED ROUTES (Requires Authentication)
 * ========================================================== */

Route::middleware('auth:api')->group(function () {

    /* User Profile */
    Route::get('/getProfile', [AuthController::class, 'getProfile']);
    Route::post('/updateUserName', [AuthController::class, 'updateUserName']);
    Route::post('/updateProfileData', [AuthController::class, 'updateProfileData']);
    Route::get('/home', [AuthController::class, 'home']);


    /* Recent Searches */
    Route::get('/getRecentSearches', [ProductController::class, 'getRecentSearches']);
    Route::post('/logRecentSearch', [ProductController::class, 'logRecentSearch']);
    Route::post('/clearRecentSearches', [ProductController::class, 'clearRecentSearches']);

    /* Address Management */
    Route::get('/getAddresses', [AddressController::class, 'getAddressList']);
    Route::post('/addAddress', [AddressController::class, 'store']);
    Route::post('/updateAddress', [AddressController::class, 'updateAddress']);
    Route::post('/getAddressbyId', [AddressController::class, 'getAddressbyId']);
    Route::post('/setDefaultAddress', [AddressController::class, 'setDefaultAddress']);
    Route::post('/deleteAddress', [AddressController::class, 'deleteAddress']);

    /* Bank Accounts */
    Route::get('/getUserBankAccounts', [BankController::class, 'getUserBankAccounts']);
    Route::post('/storeUserBankAccount', [BankController::class, 'storeUserBankAccount']);
    Route::post('/getBankById', [BankController::class, 'getBankById']);
    Route::post('/updateUserBankAccount', [BankController::class, 'updateUserBankAccount']);
    Route::post('/deleteUserBankAccount', [BankController::class, 'deleteUserBankAccount']);
    Route::post('/setDefaultBank', [BankController::class, 'setDefaultBank']);


    /* Shopping Cart */
    Route::get('/getCart', [CartController::class, 'getCart']);
    Route::post('/addToCart', [CartController::class, 'addToCart']);
    Route::post('/updateCartQty', [CartController::class, 'updateCartQty']);
    Route::post('/removeFromCart', [CartController::class, 'removeFromCart']);
    Route::post('/clearCart', [CartController::class, 'clearCart']);
    Route::post('/updateCartAddress', [CartController::class, 'updateCartAddress']);
    Route::post('/setCoupon', [CartController::class, 'setCoupon']);
    Route::post('/getPaymentInfo', [CartController::class, 'getPaymentInfo']);

    /* Bulk Quote Requests */
    Route::post('/getBulkProducts', [BulkQuoteController::class, 'getBulkProducts']);
    Route::post('/addToBulkCart', [BulkQuoteController::class, 'addToBulkCart']);
    Route::post('/updateBulkCartQty', [BulkQuoteController::class, 'updateBulkCartQty']);
    Route::get('/getBulkCart', [BulkQuoteController::class, 'getBulkCart']);
    Route::post('/requestQuote', [BulkQuoteController::class, 'requestQuote']);
    Route::get('/myBulkQuotes', [BulkQuoteController::class, 'myBulkQuotes']);
    Route::post('/getBulkQuoteById', [BulkQuoteController::class, 'getBulkQuoteById']);
    Route::post('/searchBulkProducts', [BulkQuoteController::class, 'searchBulkProducts']);
    Route::get('/getBulkFilterOptions', [BulkQuoteController::class, 'getBulkFilterOptions']);
    Route::get('/getBulkSortOptions', [BulkQuoteController::class, 'getBulkSortOptions']);
    Route::post('/getBulkQuoteVendorQuotes', [BulkQuoteController::class, 'getBulkQuoteVendorQuotes']);
    Route::post('/getBulkQuoteVendorQuoteDetails', [BulkQuoteController::class, 'getBulkQuoteVendorQuoteDetails']);
    Route::post('/acceptBulkQuoteItem', [BulkQuoteController::class, 'acceptBulkQuoteItem']);
    Route::post('/notifyBulkQuote', [BulkQuoteController::class, 'notifyBulkQuote']);
    Route::post('/getBulkQuoteLockedItems', [BulkQuoteController::class, 'getBulkQuoteLockedItems']);
    Route::post('/confirmBulkQuoteOrder', [BulkQuoteController::class, 'confirmBulkQuoteOrder']);


    /* Coupons */
    Route::post('/getCoupons', [CouponController::class, 'getCoupons']);


    /* Wishlist */
    Route::get('/getWishlist', [WishlistController::class, 'getWishlist']);
    Route::post('/toggleWishlist', [WishlistController::class, 'toggleWishlist']);
    Route::post('/clearWishlist', [WishlistController::class, 'clearWishlist']);

    /* Orders */
    Route::get('/myOrders', [OrderController::class, 'getMyOrders']);
    Route::post('/placeOrder', [OrderController::class, 'placeOrder']);
    Route::post('/orderDetails', [OrderController::class, 'getOrderDetails']);
    Route::post('/cancelOrder', [OrderController::class, 'cancelOrder']);
    Route::get('/cancelReasons', [OrderController::class, 'getCancelReasons']);
    Route::post('/rateOrder', [OrderController::class, 'rateOrder']);
    Route::post('/rateProductVariant', [OrderController::class, 'rateProductVariant']);
    /* Order Rejection & Quantity Verification */
    Route::get('/rejectReasons', [OrderController::class, 'getRejectReasons']);
    Route::post('/rejectOrderDelivery', [OrderController::class, 'rejectOrderDelivery']);
    Route::post('/orderQty', [OrderController::class, 'getOrderQty']);
    Route::post('/verifyOrderQty', [OrderController::class, 'verifyOrderQty']);
    Route::post('/qtyVerificationStatus', [OrderController::class, 'getQtyVerificationStatus']);
    /* Pending Payment Processing */
    Route::post('/pendingPaymentDetails', [OrderController::class, 'getPendingPaymentDetails']);
    Route::post('/payPendingAmount', [OrderController::class, 'payPendingAmount']);
    Route::post('/completeOrder', [OrderController::class, 'completeOrder']);
    Route::post('/completedOrderSummary', [OrderController::class, 'getCompletedOrderSummary']);
    Route::get('/download-invoice/{id}', [OrderController::class, 'downloadInvoice'])->name('api.download_invoice');

    /* Loyalty & Reward Points */
    Route::get('/rewardPoints/dashboard', [RewardPointsController::class, 'getDashboard']);
    Route::get('/rewardPoints/transactions', [RewardPointsController::class, 'getTransactions']);
    Route::post('/rewardPoints/submitKyc', [RewardPointsController::class, 'submitKyc']);
    Route::post('/rewardPoints/withdraw', [RewardPointsController::class, 'withdrawPoints']);
    Route::get('/rewardPoints/leaderboard', [RewardPointsController::class, 'getLeaderboard']);


    /* AI Material Estimator */
    Route::get('/getEstimatorConfig', [EstimatorApiController::class, 'getEstimatorConfig']);
    Route::post('/calculateEstimate', [EstimatorApiController::class, 'calculateEstimate']);


    /* Notification Management */
    Route::get('/getNotifications', [NotificationController::class, 'getNotifications']);
    Route::post('/markNotificationAsSeen', [NotificationController::class, 'markAsSeen']);

});