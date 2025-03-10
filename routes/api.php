<?php

use App\Http\Controllers\Api\Admin\ArtProvinceController;
use App\Http\Controllers\Api\Admin\ArtProvinceDetailController;
use App\Http\Controllers\Api\Admin\CategoryController;
use App\Http\Controllers\Api\Admin\ShopController as AdminShopController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\SocialiteController;
use App\Http\Controllers\Api\CategoryController as ApiCategoryController;
use App\Http\Controllers\Api\City\CityController;
use App\Http\Controllers\Api\City\ProvinceController;
use App\Http\Controllers\Api\User\BankAccountController;
use App\Http\Controllers\Api\User\Bookmark\BookmarkProductController;
use App\Http\Controllers\Api\User\Bookmark\BookmarkServiceController;
use App\Http\Controllers\Api\User\Product\ProductController as ProductProductController;
use App\Http\Controllers\Api\User\Service\ServiceController as ServiceServiceController;
use App\Http\Controllers\Api\User\Shop\ImageProductController;
use App\Http\Controllers\Api\User\Shop\ImageServiceController;
use App\Http\Controllers\Api\User\Shop\ProductController;
use App\Http\Controllers\Api\User\Shop\ServiceController;
use App\Http\Controllers\Api\User\ShopController;
use App\Http\Controllers\Api\User\Cart\CartController;
use App\Http\Controllers\Api\User\Cart\CartItemController;
use App\Http\Controllers\Api\User\Order\OrderController;
use App\Http\Controllers\Api\User\Order\OrderServiceController;
use App\Http\Controllers\Api\User\Shop\DetailShopControler;
use App\Http\Controllers\Api\User\Shop\ShopController as ShopShopController;
use App\Http\Controllers\Api\User\User\Address\AddressController;
use App\Http\Controllers\Api\User\User\Rating\Product\RatingController;
use App\Http\Controllers\Api\User\User\Rating\Service\RatingServiceController;
use App\Http\Controllers\Api\User\User\UserController as UserUserController;
use App\Http\Controllers\ImageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'auth', 'as' => 'auth.'], function () {
    Route::get('google', [SocialiteController::class, 'googleLogin']);
    Route::get('google-callback', [SocialiteController::class, 'handleGoogleCallback']);
    Route::post('verify-google', [SocialiteController::class, 'verifyGoogleToken']);
    Route::post('register', [AuthController::class, 'register']);
    Route::get('profile', [AuthController::class, 'profile']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('verify-otp', [AuthController::class, 'verifyOTP']);
    Route::post('resend-otp', [AuthController::class, 'resendOTP']);
    Route::post('forgot-password', [AuthController::class, 'sendResetLink']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
    Route::middleware('auth:api')->post('logout', [AuthController::class, 'logout']);
    Route::middleware('auth:api')->post('refresh', [AuthController::class, 'refreshToken']);
});

Route::prefix('admin')->middleware(['auth:api', 'admin'])->group(function () {

    // User Management
    Route::get('users', [UserController::class, 'getAllUsers']);
    Route::get('users/{id}', [UserController::class, 'getUser']);
    Route::post('users', [UserController::class, 'createUser']);
    Route::put('users/{id}', [UserController::class, 'updateUser']);
    Route::delete('users/{id}', [UserController::class, 'deleteUser']);

    // Category Management
    Route::get('category', [CategoryController::class, 'index']);
    Route::get('category/{id}', [CategoryController::class, 'show']);
    Route::post('category', [CategoryController::class, 'store']);
    Route::put('category/{id}', [CategoryController::class, 'update']);
    Route::delete('category/{id}', [CategoryController::class, 'destroy']);

    // Shop Management
    Route::get('shop', [AdminShopController::class, 'index']);
    Route::put('shop/verification/{id}', [AdminShopController::class, 'verificationShop']);
});

Route::prefix('user')->middleware(['auth:api', 'user'])->group(function () {
    // User Profile
    Route::get('/profile', [UserUserController::class, 'show']);

    // Edit Profile
    Route::put('/edit-profile', [UserUserController::class, 'edit']);
    Route::put('/edit-profile/password', [UserUserController::class, 'updatePassword']);

    // Cart
    Route::get('/cart', [CartController::class, 'index']);
    Route::delete('/cart/{id}', [CartController::class, 'destroy']);
    Route::get('/cart/{cart_id}/items', [CartItemController::class, 'index']);
    Route::post('/cart/items', [CartItemController::class, 'store']);
    Route::delete('/cart/items/{id}', [CartItemController::class, 'destroy']);
    Route::put('/cart/items/{id}', [CartItemController::class, 'updateQty']);
    Route::put('/cart/items/increment/{id}', [CartItemController::class, 'incrementQty']);
    Route::put('/cart/items/decrement/{id}', [CartItemController::class, 'decrementQty']);

    // View Order
    Route::get('/shop/order-product', [ProductController::class, 'getOrdersByShop']);
    Route::get('/shop/order-service', [ServiceController::class, 'getOrdersByShop']);

    // View Pending Deliverry Order
    Route::get('/shop/order-product/pending-delivery', [ProductController::class, 'getPendingDeliveries']);
    Route::get('/shop/order-service/pending-delivery', [ServiceController::class, 'getPendingOrderServiceByShop']);

    // View Product with low stock
    Route::get('/shop/products/low-stock', [ProductController::class, 'getLowStockProducts']);

    //  View revenue
    Route::get('/shop/revenue-service', [ServiceController::class, 'getRevenueFromService']);
    Route::get('/shop/revenue-product', [ServiceController::class, 'getRevenueFromProduct']);
    Route::get('/shop/revenue', [ServiceController::class, 'getRevenue']);

    // View Produnct sold count
    Route::get('/shop/products/sold-count', [ProductController::class, 'getSoldProducts']);
    Route::get('/shop/products/total-count', [ProductController::class, 'getTotalSoldItems']);
    Route::get('/shop/products/summary-sales', [ProductController::class, 'getSalesDataByYear']);

    // View Shop data shop by login
    Route::get('/shop/view-login', [ShopShopController::class, 'getShopByLogin']);

    // View Service sold count
    Route::get('/shop/service/sold-count', [ServiceController::class, 'countSoldServices']);

    // Cashout
    Route::put('/shop/cashout', [ShopShopController::class, 'cashOutBalance']);
    Route::get('/shop/log-balance', [ShopShopController::class, 'getLogBalanceByUser']);

    // Accept Order
    Route::put('/shop/accept-order/{id}', [ProductController::class, 'setStatus']);
    Route::put('/shop/reject-order/{id}', [ProductController::class, 'setStatusReject']);

    // Accept Order Service
    Route::put('/shop/accept-order-service/{orderServiceId}', [ServiceController::class, 'setStatusConfirmed']);
    Route::put('/shop/reject-order-service/{orderServiceId}', [ServiceController::class, 'setStatusRejected']);

    // Bookmark Service
    Route::get('/bookmark-service', [BookmarkServiceController::class, 'index']);
    Route::post('/bookmark-service', [BookmarkServiceController::class, 'store']);
    Route::delete('/bookmark-service/{id}', [BookmarkServiceController::class, 'destroy']);

    // Bookmark Product
    Route::get('/bookmark-product', [BookmarkProductController::class, 'index']);
    Route::post('/bookmark-product', [BookmarkProductController::class, 'store']);
    Route::delete('/bookmark-product/{id}', [BookmarkProductController::class, 'destroy']);

    // Shop
    Route::post('shop', [ShopController::class, 'create']);
    Route::put('shop/{id}', [ShopController::class, 'update']);
    Route::get('shop/check-status', [ShopController::class, 'checkStatusShop']);

    // Shop Bank Account
    Route::get('shop/bank-account', [BankAccountController::class, 'index']);
    Route::get('shop/bank-account/{id}', [BankAccountController::class, 'show']);
    Route::post('shop/bank-account', [BankAccountController::class, 'create']);
    Route::put('shop/bank-account/{id}', [BankAccountController::class, 'update']);
    Route::delete('shop/bank-account/{id}', [BankAccountController::class, 'destroy']);

    Route::get('address', [AddressController::class, 'index']);
    Route::post('address', [AddressController::class, 'store']);
    Route::get('address/{id}', [AddressController::class, 'show']);
    Route::put('address/{id}', [AddressController::class, 'update']);
    Route::delete('address/{id}', [AddressController::class, 'destroy']);

    // Product Shop
    Route::get('/shop/products', [ProductController::class, 'index']);
    Route::get('/shop/products/{id}', [ProductController::class, 'show']);
    Route::post('/shop/products', [ProductController::class, 'create']);
    Route::put('/shop/products/{id}', [ProductController::class, 'update']);
    Route::delete('/shop/products/{id}', [ProductController::class, 'destroy']);

    Route::get('/shop/order/{id}', [ProductController::class, 'setStatus']);

    // Product Image
    Route::get('/shop/products/{productId}/image', [ImageProductController::class, 'index']);
    Route::post('/shop/products/{productId}/image', [ImageProductController::class, 'create']);
    Route::put('/shop/products/{productId}/image/{imageId}', [ImageProductController::class, 'update']);
    Route::delete('/shop/products/{productId}/image/{imageId}', [ImageProductController::class, 'destroy']);

    // Service Shop
    Route::get('/shop/service', [ServiceController::class, 'index']);
    Route::get('/shop/service/{id}', [ServiceController::class, 'show']);
    Route::post('/shop/service', [ServiceController::class, 'create']);
    Route::put('/shop/service/{id}', [ServiceController::class, 'update']);
    Route::delete('/shop/service/{id}', [ServiceController::class, 'destroy']);

    //Service Image
    Route::get('/shop/service/{serviceId}/image', [ImageServiceController::class, 'index']);
    Route::post('/shop/service/{serviceId}/image', [ImageServiceController::class, 'create']);
    Route::put('/shop/service/{serviceId}/image/{imageId}', [ImageServiceController::class, 'update']);
    Route::delete('/shop/service/{serviceId}/image/{imageId}', [ImageServiceController::class, 'destroy']);

    // Order
    Route::post('order', [OrderController::class, 'create']);
    Route::get('/transaction-history', [OrderController::class, 'transactionHistory']);
    Route::get('/transaction-history/{orderId}', [OrderController::class, 'transactionDetail']);
    Route::put('/order/payment-status/{orderId}', [OrderController::class, 'updatePaymentStatus']);
    Route::get('/order/status-order', [OrderController::class, 'getDataOrderProductByStatus']);

    Route::get('/order/status-order-count', [OrderController::class, 'getOrderProductCountByStatus']);
    Route::get('/order-service/status-order-count', [OrderServiceController::class, 'getOrderServiceCountByStatus']);


    // Order Service
    Route::post('order-service', [OrderServiceController::class, 'create']);
    Route::get('/transaction-history-service', [OrderServiceController::class, 'transactionHistory']);
    Route::get('/transaction-history-service/{orderId}', [OrderServiceController::class, 'transactionDetail']);
    Route::put('/order-service/payment-status/{orderId}', [OrderServiceController::class, 'updatePaymentStatus']);
    Route::get('/order-service/status-order', [OrderServiceController::class, 'getDataOrderServiceByStatus']);

    // Rating Product
    Route::post('/product/rating/{id}', [RatingController::class, 'store']);

    // Rating Service
    Route::post('/service/rating/{id}', [RatingServiceController::class, 'store']);
});

Route::get('/shops/{id}', [ShopShopController::class, 'show']);

Route::get('/products', [ProductProductController::class, 'index']);
Route::get('/products/{id}', [ProductProductController::class, 'show']);
Route::get('/random-product', [ProductProductController::class, 'randomProducts']);
Route::get('/top-shop', [ProductProductController::class, 'topShops']);
Route::get('/service', [ServiceServiceController::class, 'index']);
Route::get('/service/{id}', [ServiceServiceController::class, 'show']);
Route::get('/random-services', [ServiceServiceController::class, 'randomServices']);
Route::get('cities', [CityController::class, 'index']);
Route::get('cities-by-province/{id}', [CityController::class, 'getCitiesByProvince']);
Route::get('provinces', [ProvinceController::class, 'index']);
Route::post('check-ongkir', [OrderController::class, 'checkOngkir']);
Route::get('category/search', [CategoryController::class, 'search']);
Route::get('user/search', [UserController::class, 'search']);
Route::get('category', [ApiCategoryController::class, 'index']);
Route::post('/notification', [OrderController::class, 'notificationCallback'])->name('notification');

Route::get('/detail-shop/{shopId}', [DetailShopControler::class, 'getShopDetails']);
Route::get('/detail-shop/rating/{shopId}', [DetailShopControler::class, 'getRatingProductAndService']);
Route::get('/detail-shop/product/{shopId}', [DetailShopControler::class, 'getProductsByShop']);
Route::get('/detail-shop/service/{shopId}', [DetailShopControler::class, 'getServicesByShop']);


Route::get('fetch-image', [ImageController::class, 'fetchImage']);


// Art Province
Route::get('art-provinces', [ArtProvinceController::class, 'index']);
Route::get('art-provinces/{slug}', [ArtProvinceController::class, 'show']);
Route::post('art-provinces', [ArtProvinceController::class, 'store']);
Route::put('art-provinces/{id}', [ArtProvinceController::class, 'update']);
Route::delete('art-provinces/{id}', [ArtProvinceController::class, 'delete']);

// Art Province Detail
Route::get('art-province-details', [ArtProvinceDetailController::class, 'index']);
Route::get('art-province-details/{id}', [ArtProvinceDetailController::class, 'show']);
Route::get('art-province-details/province/{id}', [ArtProvinceDetailController::class, 'getByArtProvince']);
Route::post('art-province-details', [ArtProvinceDetailController::class, 'store']);
Route::delete('art-province-details/{id}', [ArtProvinceDetailController::class, 'delete']);
