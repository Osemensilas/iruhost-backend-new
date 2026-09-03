<?php

use App\Controllers\API\SessionController;
use App\Controllers\API\AuthController;
use App\Controllers\API\CartController;
use App\Controllers\API\AddToCartController;
use App\Controllers\API\BlogsController;
use App\Controllers\API\DomainController;
use App\Controllers\API\UserProductController;
use App\Controllers\API\WebsiteController;
use App\Controllers\API\SupportController;
use App\Controllers\API\ChatsController;
use App\Controllers\API\FlutterwaveController;
use App\Controllers\API\CommentsController;

/*Session Route*/
$router->get("/api/session", [SessionController::class, "UserSession"]);
$router->get('/api/session-data', [SessionController::class, 'UserData']);
$router->get('/api/acct-bal', [SessionController::class, 'AcctBal']);

/*Authentication Routes*/
$router->post('/api/register', [AuthController::class, 'Register']);
$router->get('/api/logout', [AuthController::class, 'userLogout']);
$router->post('/api/login', [AuthController::class, 'Login']);
$router->post('/api/forget-password', [AuthController::class, 'ForgetPassword']);
$router->post('/api/verify-reset-code', [AuthController::class, 'PassResetCode']);
$router->post('/api/update-password', [AuthController::class, 'UpdatePassword']);
$router->post('/api/update-email', [AuthController::class, 'UpdateEmail']);
$router->get('/api/session-address', [AuthController::class, 'UserAddress']);
$router->post('/api/update-address', [AuthController::class, 'UpdateAddress']);

/*Cart Routes*/
/*Fetching from Cart*/
$router->get('/api/cart-items', [CartController::class, 'CartItems']);
$router->get('/api/cart-total-price', [CartController::class, 'CartTotal']);
$router->get('/api/cart-domain', [CartController::class, 'CartDomain']);
$router->post('/api/empty-user-cart', [CartController::class, 'ClearAllItems']);
$router->post('/api/remove-item', [CartController::class, 'RemoveItem']);
$router->get('/api/cart-session', [CartController::class, 'CartSession']);
/*Adding to Cart*/
$router->post('/api/add-to-cart', [AddToCartController::class, 'AddDomain']);
$router->post('/api/transfer-to-cart', [AddToCartController::class, 'TranferDomain']);
$router->post('/api/add-to-cart-hosting', [AddToCartController::class, 'AddHosting']);
$router->post('/api/add-to-cart-ssl', [AddToCartController::class, 'AddSSL']);
$router->post('/api/add-to-cart-email', [AddToCartController::class, 'AddEmail']);
$router->post('/api/add-website-to-cart', [AddToCartController::class, 'AddWebsite']);
$router->post('/api/add-custom-website-to-cart', [AddToCartController::class, 'AddCustomWebsite']);

/*Domain Routes*/
$router->post('/api/domain-search', [DomainController::class, 'DomainSearch']);
$router->post('/api/domain-check', [DomainController::class, 'ExistingCheck']);
$router->post('/api/single-search', [DomainController::class, 'SingleSearch']);
$router->get('/api/get-domain-prices', [DomainController::class, 'getDomainPrices']);

/*website Routes*/
$router->post('/api/get-websites', [WebsiteController::class, 'WebList']);
$router->post('/api/consult', [WebsiteController::class, 'Consult']);

/*User Products Routes*/
$router->get('/api/get-dashboard', [UserProductController::class, 'GetDashboardProducts']);
$router->get('/api/get-expiring', [UserProductController::class, 'ExpiringProduct']);
$router->get('/api/user-domain', [UserProductController::class, 'DomainList']);
$router->get('/api/user-hosting', [UserProductController::class, 'HostingList']);
$router->get('/api/user-email', [UserProductController::class, 'EmailList']);
$router->get('/api/user-ssl', [UserProductController::class, 'SslList']);
$router->get('/api/user-app', [UserProductController::class, 'AppList']);

/*User Tickets*/
$router->post('/api/send-ticket', [SupportController::class, 'OpenTicket']);
$router->get('/api/user-tickets', [SupportController::class, 'GetTickets']);
$router->get('/api/unresolved-tickets', [SupportController::class, 'GetUnresolvedTickets']);
$router->get('/api/get-support-chats', [ChatsController::class, 'GetSupportMessages']);

/*Chat Box Url*/
$router->post('/api/chat-registration', [ChatsController::class, 'CreateChatUser']);
$router->post('/api/add-chat', [ChatsController::class, 'AddChat']);
$router->get('/api/get-chats', [ChatsController::class, 'GetChats']);

/*Flutterwave Call*/
$router->post('/api/payment-success', [FlutterwaveController::class, 'PaymentSuccessful']);

/*Blogs Routes*/
$router->get('/api/get-blogs', [BlogsController::class, 'GetBlogs']);
$router->get('/api/get-recent-blogs', [BlogsController::class, 'RecentBlogs']);
$router->get('/api/get-todays-blogs', [BlogsController::class, 'TodayBlogs']);
$router->post('/api/get-single-blogs', [BlogsController::class, 'SingleBlog']);
$router->post('/api/get-related-blogs', [BlogsController::class, 'RelatedBlog']);
$router->post('/api/get-other-blogs', [BlogsController::class, 'OtherBlog']);
$router->get('/api/get-blog/{slug}', [BlogsController::class, 'GetBlogBySlug']);

/*Comments Routes*/
$router->post('/api/add-new-comment', [CommentsController::class, 'AddNewComment']);
$router->get('/api/get-comments', [CommentsController::class, 'GetComments']);