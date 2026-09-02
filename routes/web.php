<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PrototypeController;
use App\Http\Controllers\Templates\TemplateIndexController;
use App\Http\Controllers\laravel_example\UserManagement;
use App\Http\Controllers\dashboard\Analytics;
use App\Http\Controllers\dashboard\Crm;
use App\Http\Controllers\language\LanguageController;
use App\Http\Controllers\layouts\CollapsedMenu;
use App\Http\Controllers\layouts\ContentNavbar;
use App\Http\Controllers\layouts\ContentNavSidebar;
use App\Http\Controllers\layouts\Horizontal;
use App\Http\Controllers\layouts\Vertical;
use App\Http\Controllers\layouts\WithoutMenu;
use App\Http\Controllers\layouts\WithoutNavbar;
use App\Http\Controllers\layouts\Fluid;
use App\Http\Controllers\layouts\Container;
use App\Http\Controllers\layouts\Blank;
use App\Http\Controllers\front_pages\Landing;
use App\Http\Controllers\front_pages\Pricing;
use App\Http\Controllers\front_pages\Payment;
use App\Http\Controllers\front_pages\Checkout;
use App\Http\Controllers\front_pages\HelpCenter;
use App\Http\Controllers\front_pages\HelpCenterArticle;
use App\Http\Controllers\apps\Email;
use App\Http\Controllers\apps\Chat;
use App\Http\Controllers\apps\Calendar;
use App\Http\Controllers\apps\Kanban;
use App\Http\Controllers\apps\EcommerceDashboard;
use App\Http\Controllers\apps\EcommerceProductList;
use App\Http\Controllers\apps\EcommerceProductAdd;
use App\Http\Controllers\apps\EcommerceProductCategory;
use App\Http\Controllers\apps\EcommerceOrderList;
use App\Http\Controllers\apps\EcommerceOrderDetails;
use App\Http\Controllers\apps\EcommerceCustomerAll;
use App\Http\Controllers\apps\EcommerceCustomerDetailsOverview;
use App\Http\Controllers\apps\EcommerceCustomerDetailsSecurity;
use App\Http\Controllers\apps\EcommerceCustomerDetailsBilling;
use App\Http\Controllers\apps\EcommerceCustomerDetailsNotifications;
use App\Http\Controllers\apps\EcommerceManageReviews;
use App\Http\Controllers\apps\EcommerceReferrals;
use App\Http\Controllers\apps\EcommerceSettingsDetails;
use App\Http\Controllers\apps\EcommerceSettingsPayments;
use App\Http\Controllers\apps\EcommerceSettingsCheckout;
use App\Http\Controllers\apps\EcommerceSettingsShipping;
use App\Http\Controllers\apps\EcommerceSettingsLocations;
use App\Http\Controllers\apps\EcommerceSettingsNotifications;
use App\Http\Controllers\apps\AcademyDashboard;
use App\Http\Controllers\apps\AcademyCourse;
use App\Http\Controllers\apps\AcademyCourseDetails;
use App\Http\Controllers\apps\LogisticsDashboard;
use App\Http\Controllers\apps\LogisticsFleet;
use App\Http\Controllers\apps\InvoiceList;
use App\Http\Controllers\apps\InvoicePreview;
use App\Http\Controllers\apps\InvoicePrint;
use App\Http\Controllers\apps\InvoiceEdit;
use App\Http\Controllers\apps\InvoiceAdd;
use App\Http\Controllers\apps\UserList;
use App\Http\Controllers\apps\UserViewAccount;
use App\Http\Controllers\apps\UserViewSecurity;
use App\Http\Controllers\apps\UserViewBilling;
use App\Http\Controllers\apps\UserViewNotifications;
use App\Http\Controllers\apps\UserViewConnections;
use App\Http\Controllers\apps\AccessRoles;
use App\Http\Controllers\apps\AccessPermission;
use App\Http\Controllers\pages\UserProfile;
use App\Http\Controllers\pages\UserTeams;
use App\Http\Controllers\pages\UserProjects;
use App\Http\Controllers\pages\UserConnections;
use App\Http\Controllers\pages\AccountSettingsAccount;
use App\Http\Controllers\pages\AccountSettingsSecurity;
use App\Http\Controllers\pages\AccountSettingsBilling;
use App\Http\Controllers\pages\AccountSettingsNotifications;
use App\Http\Controllers\pages\AccountSettingsConnections;
use App\Http\Controllers\pages\Faq;
use App\Http\Controllers\pages\Pricing as PagesPricing;
use App\Http\Controllers\pages\MiscError;
use App\Http\Controllers\pages\MiscUnderMaintenance;
use App\Http\Controllers\pages\MiscComingSoon;
use App\Http\Controllers\pages\MiscNotAuthorized;
use App\Http\Controllers\authentications\LoginBasic;
use App\Http\Controllers\authentications\LoginCover;
use App\Http\Controllers\authentications\RegisterBasic;
use App\Http\Controllers\authentications\RegisterCover;
use App\Http\Controllers\authentications\RegisterMultiSteps;
use App\Http\Controllers\authentications\VerifyEmailBasic;
use App\Http\Controllers\authentications\VerifyEmailCover;
use App\Http\Controllers\authentications\ResetPasswordBasic;
use App\Http\Controllers\authentications\ResetPasswordCover;
use App\Http\Controllers\authentications\ForgotPasswordBasic;
use App\Http\Controllers\authentications\ForgotPasswordCover;
use App\Http\Controllers\authentications\TwoStepsBasic;
use App\Http\Controllers\authentications\TwoStepsCover;
use App\Http\Controllers\wizard_example\Checkout as WizardCheckout;
use App\Http\Controllers\wizard_example\PropertyListing;
use App\Http\Controllers\wizard_example\CreateDeal;
use App\Http\Controllers\modal\ModalExample;
use App\Http\Controllers\cards\CardBasic;
use App\Http\Controllers\cards\CardAdvance;
use App\Http\Controllers\cards\CardStatistics;
use App\Http\Controllers\cards\CardAnalytics;
use App\Http\Controllers\cards\CardActions;
use App\Http\Controllers\user_interface\Accordion;
use App\Http\Controllers\user_interface\Alerts;
use App\Http\Controllers\user_interface\Badges;
use App\Http\Controllers\user_interface\Buttons;
use App\Http\Controllers\user_interface\Carousel;
use App\Http\Controllers\user_interface\Collapse;
use App\Http\Controllers\user_interface\Dropdowns;
use App\Http\Controllers\user_interface\Footer;
use App\Http\Controllers\user_interface\ListGroups;
use App\Http\Controllers\user_interface\Modals;
use App\Http\Controllers\user_interface\Navbar;
use App\Http\Controllers\user_interface\Offcanvas;
use App\Http\Controllers\user_interface\PaginationBreadcrumbs;
use App\Http\Controllers\user_interface\Progress;
use App\Http\Controllers\user_interface\Spinners;
use App\Http\Controllers\user_interface\TabsPills;
use App\Http\Controllers\user_interface\Toasts;
use App\Http\Controllers\user_interface\TooltipsPopovers;
use App\Http\Controllers\user_interface\Typography;
use App\Http\Controllers\extended_ui\Avatar;
use App\Http\Controllers\extended_ui\BlockUI;
use App\Http\Controllers\extended_ui\DragAndDrop;
use App\Http\Controllers\extended_ui\MediaPlayer;
use App\Http\Controllers\extended_ui\PerfectScrollbar;
use App\Http\Controllers\extended_ui\StarRatings;
use App\Http\Controllers\extended_ui\SweetAlert;
use App\Http\Controllers\extended_ui\TextDivider;
use App\Http\Controllers\extended_ui\TimelineBasic;
use App\Http\Controllers\extended_ui\TimelineFullscreen;
use App\Http\Controllers\extended_ui\Tour;
use App\Http\Controllers\extended_ui\Treeview;
use App\Http\Controllers\extended_ui\Misc;
use App\Http\Controllers\icons\Tabler;
use App\Http\Controllers\icons\FontAwesome;
use App\Http\Controllers\form_elements\BasicInput;
use App\Http\Controllers\form_elements\InputGroups;
use App\Http\Controllers\form_elements\CustomOptions;
use App\Http\Controllers\form_elements\Editors;
use App\Http\Controllers\form_elements\FileUpload;
use App\Http\Controllers\form_elements\Picker;
use App\Http\Controllers\form_elements\Selects;
use App\Http\Controllers\form_elements\Sliders;
use App\Http\Controllers\form_elements\Switches;
use App\Http\Controllers\form_elements\Extras;
use App\Http\Controllers\form_layouts\VerticalForm;
use App\Http\Controllers\form_layouts\HorizontalForm;
use App\Http\Controllers\form_layouts\StickyActions;
use App\Http\Controllers\form_wizard\Numbered as FormWizardNumbered;
use App\Http\Controllers\form_wizard\Icons as FormWizardIcons;
use App\Http\Controllers\form_validation\Validation;
use App\Http\Controllers\tables\Basic as TablesBasic;
use App\Http\Controllers\tables\DatatableBasic;
use App\Http\Controllers\tables\DatatableAdvanced;
use App\Http\Controllers\tables\DatatableExtensions;
use App\Http\Controllers\charts\ApexCharts;
use App\Http\Controllers\charts\ChartJs;
use App\Http\Controllers\maps\Leaflet;

// Main Page Route
Route::get('/', [PrototypeController::class, 'index'])->name('prototype.index');
Route::get('/dashboard', [PrototypeController::class, 'dashboard'])->name('prototype.dashboard');
Route::get('/scenarios', [PrototypeController::class, 'scenarios'])->name('prototype.scenarios');
Route::post('/scenarios/{scenario}', [PrototypeController::class, 'selectScenario'])->name('prototype.scenarios.select');
Route::post('/prototype/state', [PrototypeController::class, 'updateState'])->name('prototype.state.update');
Route::post('/prototype/state/sync', [PrototypeController::class, 'syncState'])->name('prototype.state.sync');
Route::post('/prototype/presets/{preset}', [PrototypeController::class, 'applyPreset'])->name('prototype.presets.apply');
Route::post('/prototype/reset', [PrototypeController::class, 'resetPrototype'])->name('prototype.reset');
Route::post('/prototype/interstitial/dismiss', [PrototypeController::class, 'dismissInterstitial'])->name('prototype.interstitial.dismiss');
Route::get('/loans/{loan}', fn (string $loan) => app(PrototypeController::class)->detail(request(), 'loan', $loan))->name('prototype.loan');
Route::get('/products/savings', fn () => app(PrototypeController::class)->detail(request(), 'savings'))->name('prototype.product.savings');
Route::get('/products/credit-card', fn () => app(PrototypeController::class)->detail(request(), 'credit-card'))->name('prototype.product.credit-card');
Route::get('/loans/{loan}/autopay', fn (string $loan) => app(PrototypeController::class)->detail(request(), 'autopay', $loan))->name('prototype.loan.autopay');
Route::post('/loans/{loan}/autopay', [PrototypeController::class, 'enrollAutopay'])->name('prototype.loan.autopay.enroll');
Route::post('/loans/{loan}/autopay/cancel', [PrototypeController::class, 'cancelAutopay'])->name('prototype.loan.autopay.cancel');
Route::get('/applications/{application}', fn (string $application) => app(PrototypeController::class)->detail(request(), 'application', $application))->name('prototype.application');
Route::post('/applications/start', [PrototypeController::class, 'startApplication'])->name('prototype.application.start');
Route::get('/applications/{application}/advance', fn (string $application) => redirect()->route('prototype.application', $application));
Route::post('/applications/{application}/advance', [PrototypeController::class, 'advanceApplication'])->name('prototype.application.advance');
Route::get('/applications/{application}/previous', fn (string $application) => redirect()->route('prototype.application', $application));
Route::post('/applications/{application}/previous', [PrototypeController::class, 'previousApplication'])->name('prototype.application.previous');
Route::post('/lite/select-offer', [PrototypeController::class, 'selectLendingTreeOffer'])->name('prototype.lite.select-offer');
Route::post('/lite/continue-offer', [PrototypeController::class, 'continueLiteOffer'])->name('prototype.lite.continue-offer');
Route::post('/lite/send-code', [PrototypeController::class, 'sendLiteOtp'])->name('prototype.lite.send-code');
Route::post('/lite/verify-code', [PrototypeController::class, 'verifyLiteOtp'])->name('prototype.lite.verify-code');
Route::get('/applications/62001/income', fn () => app(PrototypeController::class)->liteScreen(request(), 'income'))->name('prototype.lite.income');
Route::post('/applications/62001/income', [PrototypeController::class, 'submitLiteIncome'])->name('prototype.lite.income.submit');
Route::get('/applications/62001/vehicle-photos', fn () => app(PrototypeController::class)->liteScreen(request(), 'vehicle'))->name('prototype.lite.vehicle');
Route::post('/applications/62001/vehicle-photos', [PrototypeController::class, 'submitLiteVehiclePhotos'])->name('prototype.lite.vehicle.submit');
Route::get('/applications/62001/closing', fn () => app(PrototypeController::class)->liteScreen(request(), 'closing'))->name('prototype.lite.closing');
Route::post('/applications/62001/closing', [PrototypeController::class, 'scheduleLiteClosing'])->name('prototype.lite.closing.submit');
Route::get('/account/set-password', fn () => app(PrototypeController::class)->liteScreen(request(), 'password'))->name('prototype.lite.password');
Route::post('/account/set-password', [PrototypeController::class, 'setupLitePassword'])->name('prototype.lite.password.submit');
Route::get('/offers/{offer?}', fn (?string $offer = null) => app(PrototypeController::class)->detail(request(), 'offer', $offer))->name('prototype.offers');
Route::get('/protection-benefits', fn () => app(PrototypeController::class)->detail(request(), 'protection'))->name('prototype.protection');
Route::get('/financial-wellness', fn () => app(PrototypeController::class)->detail(request(), 'wellness'))->name('prototype.wellness');
Route::get('/assets', fn () => app(PrototypeController::class)->detail(request(), 'assets'))->name('prototype.assets');
Route::post('/assets', [PrototypeController::class, 'addVehicle'])->name('prototype.assets.add');
Route::get('/profile', fn () => app(PrototypeController::class)->detail(request(), 'profile'))->name('prototype.profile');
Route::get('/documents', fn () => app(PrototypeController::class)->detail(request(), 'documents'))->name('prototype.documents');
Route::get('/ai-chat', fn () => app(PrototypeController::class)->detail(request(), 'chat'))->name('prototype.chat');
Route::get('/notifications', fn () => app(PrototypeController::class)->detail(request(), 'notifications'))->name('prototype.notifications');
Route::post('/notifications/read-all', [PrototypeController::class, 'markAllNotificationsRead'])->name('prototype.notifications.readAll');
Route::post('/notifications/{notification}/read', [PrototypeController::class, 'markNotificationRead'])->name('prototype.notifications.read');
Route::get('/support', fn () => app(PrototypeController::class)->detail(request(), 'support'))->name('prototype.support');
Route::get('/payments/new', fn () => app(PrototypeController::class)->detail(request(), 'payment'))->name('prototype.payment');
Route::post('/payments', [PrototypeController::class, 'schedulePayment'])->name('prototype.payment.schedule');
Route::post('/payments/cancel', [PrototypeController::class, 'cancelPayment'])->name('prototype.payment.cancel');
Route::get('/settings', [PrototypeController::class, 'settings'])->name('prototype.settings');

Route::get('/templates', TemplateIndexController::class)->name('templates.index');
Route::get('/templates/dashboard/analytics', [Analytics::class, 'index'])->name('templates.dashboard-analytics');
Route::get('/templates/dashboard/crm', [Crm::class, 'index'])->name('templates.dashboard-crm');
// locale
Route::get('/lang/{locale}', [LanguageController::class, 'swap']);

// layout
Route::get('/templates/layouts/collapsed-menu', [CollapsedMenu::class, 'index'])->name('templates.layouts-collapsed-menu');
Route::get('/templates/layouts/content-navbar', [ContentNavbar::class, 'index'])->name('templates.layouts-content-navbar');
Route::get('/templates/layouts/content-nav-sidebar', [ContentNavSidebar::class, 'index'])->name('templates.layouts-content-nav-sidebar');
Route::get('/templates/layouts/horizontal', [Horizontal::class, 'index'])->name('templates.layouts-horizontal');
Route::get('/templates/layouts/vertical', [Vertical::class, 'index'])->name('templates.layouts-vertical');
Route::get('/templates/layouts/without-menu', [WithoutMenu::class, 'index'])->name('templates.layouts-without-menu');
Route::get('/templates/layouts/without-navbar', [WithoutNavbar::class, 'index'])->name('templates.layouts-without-navbar');
Route::get('/templates/layouts/fluid', [Fluid::class, 'index'])->name('templates.layouts-fluid');
Route::get('/templates/layouts/container', [Container::class, 'index'])->name('templates.layouts-container');
Route::get('/templates/layouts/blank', [Blank::class, 'index'])->name('templates.layouts-blank');

// Front Pages
Route::get('/templates/front-pages/landing', [Landing::class, 'index'])->name('templates.front-pages-landing');
Route::get('/templates/front-pages/pricing', [Pricing::class, 'index'])->name('templates.front-pages-pricing');
Route::get('/templates/front-pages/payment', [Payment::class, 'index'])->name('templates.front-pages-payment');
Route::get('/templates/front-pages/checkout', [Checkout::class, 'index'])->name('templates.front-pages-checkout');
Route::get('/templates/front-pages/help-center', [HelpCenter::class, 'index'])->name('templates.front-pages-help-center');
Route::get('/templates/front-pages/help-center-article', [HelpCenterArticle::class, 'index'])->name('templates.front-pages-help-center-article');

// apps
Route::get('/templates/app/email', [Email::class, 'index'])->name('templates.app-email');
Route::get('/templates/app/chat', [Chat::class, 'index'])->name('templates.app-chat');
Route::get('/templates/app/calendar', [Calendar::class, 'index'])->name('templates.app-calendar');
Route::get('/templates/app/kanban', [Kanban::class, 'index'])->name('templates.app-kanban');
Route::get('/templates/app/ecommerce/dashboard', [EcommerceDashboard::class, 'index'])->name('templates.app-ecommerce-dashboard');
Route::get('/templates/app/ecommerce/product/list', [EcommerceProductList::class, 'index'])->name('templates.app-ecommerce-product-list');
Route::get('/templates/app/ecommerce/product/add', [EcommerceProductAdd::class, 'index'])->name('templates.app-ecommerce-product-add');
Route::get('/templates/app/ecommerce/product/category', [EcommerceProductCategory::class, 'index'])->name('templates.app-ecommerce-product-category');
Route::get('/templates/app/ecommerce/order/list', [EcommerceOrderList::class, 'index'])->name('templates.app-ecommerce-order-list');
Route::get('/templates/app/ecommerce/order/details', [EcommerceOrderDetails::class, 'index'])->name('templates.app-ecommerce-order-details');
Route::get('/templates/app/ecommerce/customer/all', [EcommerceCustomerAll::class, 'index'])->name('templates.app-ecommerce-customer-all');
Route::get('/templates/app/ecommerce/customer/details/overview', [EcommerceCustomerDetailsOverview::class, 'index'])->name('templates.app-ecommerce-customer-details-overview');
Route::get('/templates/app/ecommerce/customer/details/security', [EcommerceCustomerDetailsSecurity::class, 'index'])->name('templates.app-ecommerce-customer-details-security');
Route::get('/templates/app/ecommerce/customer/details/billing', [EcommerceCustomerDetailsBilling::class, 'index'])->name('templates.app-ecommerce-customer-details-billing');
Route::get('/templates/app/ecommerce/customer/details/notifications', [EcommerceCustomerDetailsNotifications::class, 'index'])->name('templates.app-ecommerce-customer-details-notifications');
Route::get('/templates/app/ecommerce/manage/reviews', [EcommerceManageReviews::class, 'index'])->name('templates.app-ecommerce-manage-reviews');
Route::get('/templates/app/ecommerce/referrals', [EcommerceReferrals::class, 'index'])->name('templates.app-ecommerce-referrals');
Route::get('/templates/app/ecommerce/settings/details', [EcommerceSettingsDetails::class, 'index'])->name('templates.app-ecommerce-settings-details');
Route::get('/templates/app/ecommerce/settings/payments', [EcommerceSettingsPayments::class, 'index'])->name('templates.app-ecommerce-settings-payments');
Route::get('/templates/app/ecommerce/settings/checkout', [EcommerceSettingsCheckout::class, 'index'])->name('templates.app-ecommerce-settings-checkout');
Route::get('/templates/app/ecommerce/settings/shipping', [EcommerceSettingsShipping::class, 'index'])->name('templates.app-ecommerce-settings-shipping');
Route::get('/templates/app/ecommerce/settings/locations', [EcommerceSettingsLocations::class, 'index'])->name('templates.app-ecommerce-settings-locations');
Route::get('/templates/app/ecommerce/settings/notifications', [EcommerceSettingsNotifications::class, 'index'])->name('templates.app-ecommerce-settings-notifications');
Route::get('/templates/app/academy/dashboard', [AcademyDashboard::class, 'index'])->name('templates.app-academy-dashboard');
Route::get('/templates/app/academy/course', [AcademyCourse::class, 'index'])->name('templates.app-academy-course');
Route::get('/templates/app/academy/course-details', [AcademyCourseDetails::class, 'index'])->name('templates.app-academy-course-details');
Route::get('/templates/app/logistics/dashboard', [LogisticsDashboard::class, 'index'])->name('templates.app-logistics-dashboard');
Route::get('/templates/app/logistics/fleet', [LogisticsFleet::class, 'index'])->name('templates.app-logistics-fleet');
Route::get('/templates/app/invoice/list', [InvoiceList::class, 'index'])->name('templates.app-invoice-list');
Route::get('/templates/app/invoice/preview', [InvoicePreview::class, 'index'])->name('templates.app-invoice-preview');
Route::get('/templates/app/invoice/print', [InvoicePrint::class, 'index'])->name('templates.app-invoice-print');
Route::get('/templates/app/invoice/edit', [InvoiceEdit::class, 'index'])->name('templates.app-invoice-edit');
Route::get('/templates/app/invoice/add', [InvoiceAdd::class, 'index'])->name('templates.app-invoice-add');
Route::get('/templates/app/user/list', [UserList::class, 'index'])->name('templates.app-user-list');
Route::get('/templates/app/user/view/account', [UserViewAccount::class, 'index'])->name('templates.app-user-view-account');
Route::get('/templates/app/user/view/security', [UserViewSecurity::class, 'index'])->name('templates.app-user-view-security');
Route::get('/templates/app/user/view/billing', [UserViewBilling::class, 'index'])->name('templates.app-user-view-billing');
Route::get('/templates/app/user/view/notifications', [UserViewNotifications::class, 'index'])->name('templates.app-user-view-notifications');
Route::get('/templates/app/user/view/connections', [UserViewConnections::class, 'index'])->name('templates.app-user-view-connections');
Route::get('/templates/app/access-roles', [AccessRoles::class, 'index'])->name('templates.app-access-roles');
Route::get('/templates/app/access-permission', [AccessPermission::class, 'index'])->name('templates.app-access-permission');

// pages
Route::get('/templates/pages/profile-user', [UserProfile::class, 'index'])->name('templates.pages-profile-user');
Route::get('/templates/pages/profile-teams', [UserTeams::class, 'index'])->name('templates.pages-profile-teams');
Route::get('/templates/pages/profile-projects', [UserProjects::class, 'index'])->name('templates.pages-profile-projects');
Route::get('/templates/pages/profile-connections', [UserConnections::class, 'index'])->name('templates.pages-profile-connections');
Route::get('/templates/pages/account-settings-account', [AccountSettingsAccount::class, 'index'])->name('templates.pages-account-settings-account');
Route::get('/templates/pages/account-settings-security', [AccountSettingsSecurity::class, 'index'])->name('templates.pages-account-settings-security');
Route::get('/templates/pages/account-settings-billing', [AccountSettingsBilling::class, 'index'])->name('templates.pages-account-settings-billing');
Route::get('/templates/pages/account-settings-notifications', [AccountSettingsNotifications::class, 'index'])->name('templates.pages-account-settings-notifications');
Route::get('/templates/pages/account-settings-connections', [AccountSettingsConnections::class, 'index'])->name('templates.pages-account-settings-connections');
Route::get('/templates/pages/faq', [Faq::class, 'index'])->name('templates.pages-faq');
Route::get('/templates/pages/pricing', [PagesPricing::class, 'index'])->name('templates.pages-pricing');
Route::get('/templates/pages/misc-error', [MiscError::class, 'index'])->name('templates.pages-misc-error');
Route::get('/templates/pages/misc-under-maintenance', [MiscUnderMaintenance::class, 'index'])->name('templates.pages-misc-under-maintenance');
Route::get('/templates/pages/misc-comingsoon', [MiscComingSoon::class, 'index'])->name('templates.pages-misc-comingsoon');
Route::get('/templates/pages/misc-not-authorized', [MiscNotAuthorized::class, 'index'])->name('templates.pages-misc-not-authorized');

// authentication
Route::get('/templates/auth/login-basic', [LoginBasic::class, 'index'])->name('templates.auth-login-basic');
Route::get('/templates/auth/login-cover', [LoginCover::class, 'index'])->name('templates.auth-login-cover');
Route::get('/templates/auth/register-basic', [RegisterBasic::class, 'index'])->name('templates.auth-register-basic');
Route::get('/templates/auth/register-cover', [RegisterCover::class, 'index'])->name('templates.auth-register-cover');
Route::get('/templates/auth/register-multisteps', [RegisterMultiSteps::class, 'index'])->name('templates.auth-register-multisteps');
Route::get('/templates/auth/verify-email-basic', [VerifyEmailBasic::class, 'index'])->name('templates.auth-verify-email-basic');
Route::get('/templates/auth/verify-email-cover', [VerifyEmailCover::class, 'index'])->name('templates.auth-verify-email-cover');
Route::get('/templates/auth/reset-password-basic', [ResetPasswordBasic::class, 'index'])->name('templates.auth-reset-password-basic');
Route::get('/templates/auth/reset-password-cover', [ResetPasswordCover::class, 'index'])->name('templates.auth-reset-password-cover');
Route::get('/templates/auth/forgot-password-basic', [ForgotPasswordBasic::class, 'index'])->name('templates.auth-reset-password-basic');
Route::get('/templates/auth/forgot-password-cover', [ForgotPasswordCover::class, 'index'])->name('templates.auth-forgot-password-cover');
Route::get('/templates/auth/two-steps-basic', [TwoStepsBasic::class, 'index'])->name('templates.auth-two-steps-basic');
Route::get('/templates/auth/two-steps-cover', [TwoStepsCover::class, 'index'])->name('templates.auth-two-steps-cover');

// wizard example
Route::get('/templates/wizard/ex-checkout', [WizardCheckout::class, 'index'])->name('templates.wizard-ex-checkout');
Route::get('/templates/wizard/ex-property-listing', [PropertyListing::class, 'index'])->name('templates.wizard-ex-property-listing');
Route::get('/templates/wizard/ex-create-deal', [CreateDeal::class, 'index'])->name('templates.wizard-ex-create-deal');

// modal
Route::get('/templates/modal-examples', [ModalExample::class, 'index'])->name('templates.modal-examples');

// cards
Route::get('/templates/cards/basic', [CardBasic::class, 'index'])->name('templates.cards-basic');
Route::get('/templates/cards/advance', [CardAdvance::class, 'index'])->name('templates.cards-advance');
Route::get('/templates/cards/statistics', [CardStatistics::class, 'index'])->name('templates.cards-statistics');
Route::get('/templates/cards/analytics', [CardAnalytics::class, 'index'])->name('templates.cards-analytics');
Route::get('/templates/cards/actions', [CardActions::class, 'index'])->name('templates.cards-actions');

// User Interface
Route::get('/templates/ui/accordion', [Accordion::class, 'index'])->name('templates.ui-accordion');
Route::get('/templates/ui/alerts', [Alerts::class, 'index'])->name('templates.ui-alerts');
Route::get('/templates/ui/badges', [Badges::class, 'index'])->name('templates.ui-badges');
Route::get('/templates/ui/buttons', [Buttons::class, 'index'])->name('templates.ui-buttons');
Route::get('/templates/ui/carousel', [Carousel::class, 'index'])->name('templates.ui-carousel');
Route::get('/templates/ui/collapse', [Collapse::class, 'index'])->name('templates.ui-collapse');
Route::get('/templates/ui/dropdowns', [Dropdowns::class, 'index'])->name('templates.ui-dropdowns');
Route::get('/templates/ui/footer', [Footer::class, 'index'])->name('templates.ui-footer');
Route::get('/templates/ui/list-groups', [ListGroups::class, 'index'])->name('templates.ui-list-groups');
Route::get('/templates/ui/modals', [Modals::class, 'index'])->name('templates.ui-modals');
Route::get('/templates/ui/navbar', [Navbar::class, 'index'])->name('templates.ui-navbar');
Route::get('/templates/ui/offcanvas', [Offcanvas::class, 'index'])->name('templates.ui-offcanvas');
Route::get('/templates/ui/pagination-breadcrumbs', [PaginationBreadcrumbs::class, 'index'])->name('templates.ui-pagination-breadcrumbs');
Route::get('/templates/ui/progress', [Progress::class, 'index'])->name('templates.ui-progress');
Route::get('/templates/ui/spinners', [Spinners::class, 'index'])->name('templates.ui-spinners');
Route::get('/templates/ui/tabs-pills', [TabsPills::class, 'index'])->name('templates.ui-tabs-pills');
Route::get('/templates/ui/toasts', [Toasts::class, 'index'])->name('templates.ui-toasts');
Route::get('/templates/ui/tooltips-popovers', [TooltipsPopovers::class, 'index'])->name('templates.ui-tooltips-popovers');
Route::get('/templates/ui/typography', [Typography::class, 'index'])->name('templates.ui-typography');

// extended ui
Route::get('/templates/extended/ui-avatar', [Avatar::class, 'index'])->name('templates.extended-ui-avatar');
Route::get('/templates/extended/ui-blockui', [BlockUI::class, 'index'])->name('templates.extended-ui-blockui');
Route::get('/templates/extended/ui-drag-and-drop', [DragAndDrop::class, 'index'])->name('templates.extended-ui-drag-and-drop');
Route::get('/templates/extended/ui-media-player', [MediaPlayer::class, 'index'])->name('templates.extended-ui-media-player');
Route::get('/templates/extended/ui-perfect-scrollbar', [PerfectScrollbar::class, 'index'])->name('templates.extended-ui-perfect-scrollbar');
Route::get('/templates/extended/ui-star-ratings', [StarRatings::class, 'index'])->name('templates.extended-ui-star-ratings');
Route::get('/templates/extended/ui-sweetalert2', [SweetAlert::class, 'index'])->name('templates.extended-ui-sweetalert2');
Route::get('/templates/extended/ui-text-divider', [TextDivider::class, 'index'])->name('templates.extended-ui-text-divider');
Route::get('/templates/extended/ui-timeline-basic', [TimelineBasic::class, 'index'])->name('templates.extended-ui-timeline-basic');
Route::get('/templates/extended/ui-timeline-fullscreen', [TimelineFullscreen::class, 'index'])->name('templates.extended-ui-timeline-fullscreen');
Route::get('/templates/extended/ui-tour', [Tour::class, 'index'])->name('templates.extended-ui-tour');
Route::get('/templates/extended/ui-treeview', [Treeview::class, 'index'])->name('templates.extended-ui-treeview');
Route::get('/templates/extended/ui-misc', [Misc::class, 'index'])->name('templates.extended-ui-misc');

// icons
Route::get('/templates/icons/tabler', [Tabler::class, 'index'])->name('templates.icons-tabler');
Route::get('/templates/icons/font-awesome', [FontAwesome::class, 'index'])->name('templates.icons-font-awesome');

// form elements
Route::get('/templates/forms/basic-inputs', [BasicInput::class, 'index'])->name('templates.forms-basic-inputs');
Route::get('/templates/forms/input-groups', [InputGroups::class, 'index'])->name('templates.forms-input-groups');
Route::get('/templates/forms/custom-options', [CustomOptions::class, 'index'])->name('templates.forms-custom-options');
Route::get('/templates/forms/editors', [Editors::class, 'index'])->name('templates.forms-editors');
Route::get('/templates/forms/file-upload', [FileUpload::class, 'index'])->name('templates.forms-file-upload');
Route::get('/templates/forms/pickers', [Picker::class, 'index'])->name('templates.forms-pickers');
Route::get('/templates/forms/selects', [Selects::class, 'index'])->name('templates.forms-selects');
Route::get('/templates/forms/sliders', [Sliders::class, 'index'])->name('templates.forms-sliders');
Route::get('/templates/forms/switches', [Switches::class, 'index'])->name('templates.forms-switches');
Route::get('/templates/forms/extras', [Extras::class, 'index'])->name('templates.forms-extras');

// form layouts
Route::get('/templates/form/layouts-vertical', [VerticalForm::class, 'index'])->name('templates.form-layouts-vertical');
Route::get('/templates/form/layouts-horizontal', [HorizontalForm::class, 'index'])->name('templates.form-layouts-horizontal');
Route::get('/templates/form/layouts-sticky', [StickyActions::class, 'index'])->name('templates.form-layouts-sticky');

// form wizards
Route::get('/templates/form/wizard-numbered', [FormWizardNumbered::class, 'index'])->name('templates.form-wizard-numbered');
Route::get('/templates/form/wizard-icons', [FormWizardIcons::class, 'index'])->name('templates.form-wizard-icons');
Route::get('/templates/form/validation', [Validation::class, 'index'])->name('templates.form-validation');

// tables
Route::get('/templates/tables/basic', [TablesBasic::class, 'index'])->name('templates.tables-basic');
Route::get('/templates/tables/datatables-basic', [DatatableBasic::class, 'index'])->name('templates.tables-datatables-basic');
Route::get('/templates/tables/datatables-advanced', [DatatableAdvanced::class, 'index'])->name('templates.tables-datatables-advanced');
Route::get('/templates/tables/datatables-extensions', [DatatableExtensions::class, 'index'])->name('templates.tables-datatables-extensions');

// charts
Route::get('/templates/charts/apex', [ApexCharts::class, 'index'])->name('templates.charts-apex');
Route::get('/templates/charts/chartjs', [ChartJs::class, 'index'])->name('templates.charts-chartjs');

// maps
Route::get('/templates/maps/leaflet', [Leaflet::class, 'index'])->name('templates.maps-leaflet');

// laravel example
Route::get('/templates/laravel/user-management', [UserManagement::class, 'UserManagement'])->name('templates.laravel-example-user-management');
Route::resource('/templates/user-list', UserManagement::class)->names('templates.user-list');
