<?php

declare(strict_types=1);

use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\LocaleController;
use App\Http\Controllers\Web\Admin\EnterpriseAdminDashboardController;
use App\Http\Controllers\Web\Academy\LearningAcademyController;
use App\Http\Controllers\Web\Ai\AiAssistantController;
use App\Http\Controllers\Web\Ai\CyraAiCommandCenterController;
use App\Http\Controllers\Web\Arbitrage\ArbitrageController;
use App\Http\Controllers\Web\Auction\CommodityAuctionController;
use App\Http\Controllers\Web\Buyer\BuyerDashboardController;
use App\Http\Controllers\Web\Carbon\CarbonCreditMarketplaceController;
use App\Http\Controllers\Web\Consumer\ConsumerMarketplaceController;
use App\Http\Controllers\Web\Cooperative\CooperativeManagementController;
use App\Http\Controllers\Web\Crop\CropManagementController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\DigitalTwin\DigitalTwinFarmController;
use App\Http\Controllers\Web\Distribution\SmartCityFoodDistributionController;
use App\Http\Controllers\Web\Exchange\CommodityExchangeController;
use App\Http\Controllers\Web\Equipment\EquipmentMarketplaceController;
use App\Http\Controllers\Web\Export\ExportTradeHubController;
use App\Http\Controllers\Web\Farm\FarmRegistrationController;
use App\Http\Controllers\Web\FinancialInstitution\FinancialInstitutionDashboardController;
use App\Http\Controllers\Web\FoodSecurity\FoodSecurityDashboardController;
use App\Http\Controllers\Web\Futures\CommodityFuturesController;
use App\Http\Controllers\Web\Government\GovernmentDashboardController;
use App\Http\Controllers\Web\Insurance\FarmInsuranceController;
use App\Http\Controllers\Web\Intelligence\BusinessIntelligenceController;
use App\Http\Controllers\Web\Investment\InvestmentMarketplaceController;
use App\Http\Controllers\Web\Investment\InvestorDashboardController;
use App\Http\Controllers\Web\Logistics\LogisticsNetworkController;
use App\Http\Controllers\Web\Market\MarketIntelligenceController;
use App\Http\Controllers\Web\Marketplace\MarketplaceController;
use App\Http\Controllers\Web\Messaging\NotificationsMessagingController;
use App\Http\Controllers\Web\Mobile\MobileAppPreviewController;
use App\Http\Controllers\Web\Precision\PrecisionAgricultureController;
use App\Http\Controllers\Web\Processing\FoodProcessingNetworkController;
use App\Http\Controllers\Web\Reporting\ReportingAnalyticsController;
use App\Http\Controllers\Web\Risk\RiskIntelligenceController;
use App\Http\Controllers\Web\SupplyChain\SupplyChainTrackingController;
use App\Http\Controllers\Web\Wallet\DigitalWalletController;
use App\Http\Controllers\Web\Wallet\PaystackWalletController;
use App\Http\Controllers\Web\Warehouse\WarehouseManagementController;
use App\Http\Controllers\Web\Weather\WeatherIntelligenceController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::post('/locale', [LocaleController::class, 'update'])
    ->middleware('throttle:30,1')
    ->name('locale.update');

Route::get('/consumer-marketplace/orders/{order}/verify', [ConsumerMarketplaceController::class, 'verify'])
    ->middleware('throttle:60,1')
    ->name('consumer.orders.verify');

Route::post('/webhooks/paystack', [PaystackWalletController::class, 'webhook'])
    ->middleware('throttle:120,1')
    ->name('webhooks.paystack');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Role-specific entry dashboards (admin bypasses role middleware).
    Route::get('/buyer', BuyerDashboardController::class)
        ->middleware('role:buyer')
        ->name('buyer.dashboard');

    Route::get('/investor', [InvestorDashboardController::class, 'index'])
        ->middleware('role:investor,farmer')
        ->name('investor.dashboard');
    Route::post('/investor/investments/{investment}/collect', [InvestorDashboardController::class, 'collect'])
        ->middleware(['role:investor,farmer', 'throttle:writes'])
        ->name('investor.collect');

    Route::get('/investments', [InvestmentMarketplaceController::class, 'index'])
        ->middleware('role:investor,farmer')
        ->name('investments.index');
    Route::get('/investments/{opportunity}', [InvestmentMarketplaceController::class, 'show'])
        ->middleware('role:investor,farmer')
        ->name('investments.show');
    Route::post('/investments/{opportunity}/invest', [InvestmentMarketplaceController::class, 'invest'])
        ->middleware(['role:investor,farmer', 'throttle:writes'])
        ->name('investments.invest');
    Route::post('/investments/{opportunity}/reviews', [InvestmentMarketplaceController::class, 'review'])
        ->middleware(['role:investor,farmer', 'throttle:writes'])
        ->name('investments.reviews.store');

    Route::get('/admin', EnterpriseAdminDashboardController::class)
        ->middleware('role:admin')
        ->name('admin.dashboard');

    // Platform modules — available to every verified account.
    Route::get('/government', [GovernmentDashboardController::class, 'show'])
        ->name('government.dashboard');
    Route::post('/government/subsidies', [GovernmentDashboardController::class, 'applySubsidy'])
        ->middleware('throttle:writes')
        ->name('government.subsidies.apply');
    Route::post('/government/subsidies/{subsidy}/approve', [GovernmentDashboardController::class, 'approveSubsidy'])
        ->middleware('throttle:writes')
        ->name('government.subsidies.approve');
    Route::post('/government/subsidies/{subsidy}/reject', [GovernmentDashboardController::class, 'rejectSubsidy'])
        ->middleware('throttle:writes')
        ->name('government.subsidies.reject');
    Route::post('/government/policies', [GovernmentDashboardController::class, 'storePolicy'])
        ->middleware('throttle:writes')
        ->name('government.policies.store');
    Route::post('/government/policies/{policy}/status', [GovernmentDashboardController::class, 'updatePolicyStatus'])
        ->middleware('throttle:writes')
        ->name('government.policies.status');
    Route::get('/government/export', [GovernmentDashboardController::class, 'export'])
        ->name('government.export');

    Route::get('/financial-institution', [FinancialInstitutionDashboardController::class, 'show'])
        ->name('financial.dashboard');
    Route::post('/financial-institution/applications', [FinancialInstitutionDashboardController::class, 'storeApplication'])
        ->middleware('throttle:writes')
        ->name('financial.applications.store');
    Route::post('/financial-institution/applications/{application}/approve', [FinancialInstitutionDashboardController::class, 'approveApplication'])
        ->middleware('throttle:writes')
        ->name('financial.applications.approve');
    Route::post('/financial-institution/applications/{application}/reject', [FinancialInstitutionDashboardController::class, 'rejectApplication'])
        ->middleware('throttle:writes')
        ->name('financial.applications.reject');
    Route::post('/financial-institution/applications/{application}/repay', [FinancialInstitutionDashboardController::class, 'repayApplication'])
        ->middleware('throttle:writes')
        ->name('financial.applications.repay');
    Route::get('/financial-institution/export', [FinancialInstitutionDashboardController::class, 'export'])
        ->name('financial.export');

    Route::post('/admin/farms/{farm}/approve', [EnterpriseAdminDashboardController::class, 'approveFarm'])
        ->middleware(['role:admin', 'throttle:writes'])
        ->name('admin.farms.approve');
    Route::post('/admin/farms/{farm}/reject', [EnterpriseAdminDashboardController::class, 'rejectFarm'])
        ->middleware(['role:admin', 'throttle:writes'])
        ->name('admin.farms.reject');
    Route::post('/admin/users/{user}/status', [EnterpriseAdminDashboardController::class, 'updateUserStatus'])
        ->middleware(['role:admin', 'throttle:writes'])
        ->name('admin.users.status');
    Route::post('/admin/users/{user}/role', [EnterpriseAdminDashboardController::class, 'updateUserRole'])
        ->middleware(['role:admin', 'throttle:writes'])
        ->name('admin.users.role');
    Route::post('/admin/sessions/revoke', [EnterpriseAdminDashboardController::class, 'revokeSession'])
        ->middleware(['role:admin', 'throttle:writes'])
        ->name('admin.sessions.revoke');

    Route::get('/mobile-preview', MobileAppPreviewController::class)
        ->name('mobile.preview');

    Route::get('/digital-twin', [DigitalTwinFarmController::class, 'index'])
        ->name('digital.twin');
    Route::post('/digital-twin/{farm}/scan', [DigitalTwinFarmController::class, 'scan'])
        ->middleware('throttle:writes')
        ->name('digital.twin.scan');
    Route::post('/digital-twin/{farm}/irrigate', [DigitalTwinFarmController::class, 'irrigate'])
        ->middleware('throttle:writes')
        ->name('digital.twin.irrigate');

    Route::get('/precision-agriculture', [PrecisionAgricultureController::class, 'index'])
        ->name('precision.agriculture');
    Route::post('/precision-agriculture/{farm}/scan', [PrecisionAgricultureController::class, 'scan'])
        ->middleware('throttle:writes')
        ->name('precision.scan');
    Route::post('/precision-agriculture/{farm}/irrigate', [PrecisionAgricultureController::class, 'irrigate'])
        ->middleware('throttle:writes')
        ->name('precision.irrigate');
    Route::post('/precision-agriculture/{farm}/fertilizer', [PrecisionAgricultureController::class, 'fertilizer'])
        ->middleware('throttle:writes')
        ->name('precision.fertilizer');

    Route::get('/carbon-credits', [CarbonCreditMarketplaceController::class, 'index'])
        ->name('carbon.marketplace');
    Route::post('/carbon-credits/generate', [CarbonCreditMarketplaceController::class, 'generate'])
        ->middleware('throttle:writes')
        ->name('carbon.generate');
    Route::post('/carbon-credits/list', [CarbonCreditMarketplaceController::class, 'list'])
        ->middleware('throttle:writes')
        ->name('carbon.list');
    Route::post('/carbon-credits/offset', [CarbonCreditMarketplaceController::class, 'offset'])
        ->middleware('throttle:writes')
        ->name('carbon.offset');
    Route::post('/carbon-credits/listings/{listing}/sell', [CarbonCreditMarketplaceController::class, 'sell'])
        ->middleware('throttle:writes')
        ->name('carbon.sell');

    Route::get('/export-hub', [ExportTradeHubController::class, 'index'])
        ->name('export.hub');
    Route::post('/export-hub/orders', [ExportTradeHubController::class, 'store'])
        ->middleware('throttle:writes')
        ->name('export.orders.store');
    Route::post('/export-hub/orders/{order}/advance', [ExportTradeHubController::class, 'advance'])
        ->middleware('throttle:writes')
        ->name('export.orders.advance');

    Route::get('/food-processing', [FoodProcessingNetworkController::class, 'index'])
        ->name('processing.network');
    Route::post('/food-processing/requests', [FoodProcessingNetworkController::class, 'store'])
        ->middleware('throttle:writes')
        ->name('processing.requests.store');
    Route::post('/food-processing/requests/{processingRequest}/advance', [FoodProcessingNetworkController::class, 'advance'])
        ->middleware('throttle:writes')
        ->name('processing.requests.advance');
    Route::post('/food-processing/requests/{processingRequest}/deliver', [FoodProcessingNetworkController::class, 'deliver'])
        ->middleware('throttle:writes')
        ->name('processing.requests.deliver');

    Route::get('/equipment-marketplace', [EquipmentMarketplaceController::class, 'index'])
        ->name('equipment.marketplace');
    Route::post('/equipment-marketplace/{listing}/favorite', [EquipmentMarketplaceController::class, 'favorite'])
        ->middleware('throttle:writes')
        ->name('equipment.favorite');
    Route::post('/equipment-marketplace/{listing}/cart', [EquipmentMarketplaceController::class, 'addToCart'])
        ->middleware('throttle:writes')
        ->name('equipment.cart.add');
    Route::patch('/equipment-marketplace/cart/{item}', [EquipmentMarketplaceController::class, 'updateCartItem'])
        ->middleware('throttle:writes')
        ->name('equipment.cart.update');
    Route::delete('/equipment-marketplace/cart/{item}', [EquipmentMarketplaceController::class, 'removeCartItem'])
        ->middleware('throttle:writes')
        ->name('equipment.cart.remove');
    Route::post('/equipment-marketplace/checkout', [EquipmentMarketplaceController::class, 'checkout'])
        ->middleware('throttle:writes')
        ->name('equipment.checkout');

    Route::get('/farm-insurance', [FarmInsuranceController::class, 'index'])
        ->name('insurance.platform');
    Route::post('/farm-insurance/policies', [FarmInsuranceController::class, 'purchase'])
        ->middleware('throttle:writes')
        ->name('insurance.policies.store');
    Route::post('/farm-insurance/claims', [FarmInsuranceController::class, 'fileClaim'])
        ->middleware('throttle:writes')
        ->name('insurance.claims.store');
    Route::post('/farm-insurance/claims/{claim}/advance', [FarmInsuranceController::class, 'advanceClaim'])
        ->middleware('throttle:writes')
        ->name('insurance.claims.advance');

    Route::get('/risk-intelligence', [RiskIntelligenceController::class, 'index'])
        ->name('risk.intelligence');
    Route::post('/risk-intelligence/refresh', [RiskIntelligenceController::class, 'refresh'])
        ->middleware('throttle:writes')
        ->name('risk.refresh');
    Route::post('/risk-intelligence/alerts/{alert}/acknowledge', [RiskIntelligenceController::class, 'acknowledge'])
        ->middleware('throttle:writes')
        ->name('risk.alerts.acknowledge');
    Route::post('/risk-intelligence/alerts/{alert}/dismiss', [RiskIntelligenceController::class, 'dismiss'])
        ->middleware('throttle:writes')
        ->name('risk.alerts.dismiss');
    Route::post('/risk-intelligence/mitigations', [RiskIntelligenceController::class, 'storeMitigation'])
        ->middleware('throttle:writes')
        ->name('risk.mitigations.store');
    Route::post('/risk-intelligence/mitigations/{mitigation}/complete', [RiskIntelligenceController::class, 'completeMitigation'])
        ->middleware('throttle:writes')
        ->name('risk.mitigations.complete');
    Route::get('/risk-intelligence/export', [RiskIntelligenceController::class, 'export'])
        ->name('risk.export');

    Route::get('/futures', [CommodityFuturesController::class, 'index'])
        ->name('futures.exchange');
    Route::post('/futures/orders', [CommodityFuturesController::class, 'placeOrder'])
        ->middleware('throttle:writes')
        ->name('futures.orders.store');
    Route::post('/futures/orders/{order}/cancel', [CommodityFuturesController::class, 'cancelOrder'])
        ->middleware('throttle:writes')
        ->name('futures.orders.cancel');
    Route::post('/futures/positions/{position}/close', [CommodityFuturesController::class, 'closePosition'])
        ->middleware('throttle:writes')
        ->name('futures.positions.close');

    Route::get('/auctions', [CommodityAuctionController::class, 'index'])
        ->name('auction.system');
    Route::post('/auctions/bids', [CommodityAuctionController::class, 'placeBid'])
        ->middleware('throttle:writes')
        ->name('auction.bids.store');

    Route::get('/food-security', [FoodSecurityDashboardController::class, 'index'])
        ->name('food.security');
    Route::post('/food-security/refresh', [FoodSecurityDashboardController::class, 'refresh'])
        ->middleware('throttle:writes')
        ->name('food.refresh');
    Route::post('/food-security/interventions', [FoodSecurityDashboardController::class, 'storeIntervention'])
        ->middleware('throttle:writes')
        ->name('food.interventions.store');
    Route::post('/food-security/interventions/{intervention}/complete', [FoodSecurityDashboardController::class, 'completeIntervention'])
        ->middleware('throttle:writes')
        ->name('food.interventions.complete');
    Route::get('/food-security/export', [FoodSecurityDashboardController::class, 'export'])
        ->name('food.export');

    Route::get('/cooperative', [CooperativeManagementController::class, 'index'])
        ->name('cooperative.management');
    Route::post('/cooperative/contribute', [CooperativeManagementController::class, 'contribute'])
        ->middleware('throttle:writes')
        ->name('cooperative.contribute');
    Route::post('/cooperative/loans', [CooperativeManagementController::class, 'storeLoan'])
        ->middleware('throttle:writes')
        ->name('cooperative.loans.store');
    Route::post('/cooperative/loans/{loan}/review', [CooperativeManagementController::class, 'reviewLoan'])
        ->middleware('throttle:writes')
        ->name('cooperative.loans.review');
    Route::post('/cooperative/loans/{loan}/repay', [CooperativeManagementController::class, 'repayLoan'])
        ->middleware('throttle:writes')
        ->name('cooperative.loans.repay');
    Route::post('/cooperative/votes', [CooperativeManagementController::class, 'storeVote'])
        ->middleware('throttle:writes')
        ->name('cooperative.votes.store');
    Route::post('/cooperative/votes/{vote}/cast', [CooperativeManagementController::class, 'castVote'])
        ->middleware('throttle:writes')
        ->name('cooperative.votes.cast');

    Route::get('/learning-academy', [LearningAcademyController::class, 'index'])
        ->name('academy.learning');
    Route::post('/learning-academy/enroll', [LearningAcademyController::class, 'enroll'])
        ->middleware('throttle:writes')
        ->name('academy.enroll');
    Route::post('/learning-academy/enrollments/{enrollment}/advance', [LearningAcademyController::class, 'advance'])
        ->middleware('throttle:writes')
        ->name('academy.enrollments.advance');

    Route::get('/business-intelligence', [BusinessIntelligenceController::class, 'index'])
        ->name('intelligence.command');
    Route::post('/business-intelligence/refresh', [BusinessIntelligenceController::class, 'refresh'])
        ->middleware('throttle:writes')
        ->name('intelligence.refresh');
    Route::post('/business-intelligence/insights', [BusinessIntelligenceController::class, 'storeInsight'])
        ->middleware('throttle:writes')
        ->name('intelligence.insights.store');
    Route::post('/business-intelligence/insights/{insight}/acknowledge', [BusinessIntelligenceController::class, 'acknowledge'])
        ->middleware('throttle:writes')
        ->name('intelligence.insights.acknowledge');
    Route::post('/business-intelligence/insights/{insight}/pin', [BusinessIntelligenceController::class, 'pin'])
        ->middleware('throttle:writes')
        ->name('intelligence.insights.pin');
    Route::post('/business-intelligence/insights/{insight}/dismiss', [BusinessIntelligenceController::class, 'dismiss'])
        ->middleware('throttle:writes')
        ->name('intelligence.insights.dismiss');
    Route::get('/business-intelligence/export', [BusinessIntelligenceController::class, 'export'])
        ->name('intelligence.export');

    Route::get('/consumer-marketplace', [ConsumerMarketplaceController::class, 'index'])
        ->name('consumer.marketplace');
    Route::post('/consumer-marketplace/cart/{product}', [ConsumerMarketplaceController::class, 'addToCart'])
        ->middleware('throttle:writes')
        ->name('consumer.cart.add');
    Route::patch('/consumer-marketplace/cart/{item}', [ConsumerMarketplaceController::class, 'updateCartItem'])
        ->middleware('throttle:writes')
        ->name('consumer.cart.update');
    Route::delete('/consumer-marketplace/cart/{item}', [ConsumerMarketplaceController::class, 'removeCartItem'])
        ->middleware('throttle:writes')
        ->name('consumer.cart.remove');
    Route::post('/consumer-marketplace/checkout', [ConsumerMarketplaceController::class, 'checkout'])
        ->middleware('throttle:writes')
        ->name('consumer.checkout');
    Route::post('/consumer-marketplace/orders/{order}/cancel', [ConsumerMarketplaceController::class, 'cancelOrder'])
        ->middleware('throttle:writes')
        ->name('consumer.orders.cancel');
    Route::post('/consumer-marketplace/orders/{order}/confirm', [ConsumerMarketplaceController::class, 'confirmOrder'])
        ->middleware('throttle:writes')
        ->name('consumer.orders.confirm');
    Route::get('/consumer-marketplace/orders/{order}/receipt', [ConsumerMarketplaceController::class, 'receipt'])
        ->name('consumer.orders.receipt');


    Route::get('/marketplace', [MarketplaceController::class, 'index'])
        ->name('marketplace.index');

    Route::post('/marketplace', [MarketplaceController::class, 'store'])
        ->middleware(['role:farmer,supplier', 'throttle:writes'])
        ->name('marketplace.store');

    Route::patch('/marketplace/{commodity}', [MarketplaceController::class, 'update'])
        ->middleware(['role:farmer,supplier', 'throttle:writes'])
        ->name('marketplace.update');

    Route::delete('/marketplace/{commodity}', [MarketplaceController::class, 'destroy'])
        ->middleware(['role:farmer,supplier', 'throttle:writes'])
        ->name('marketplace.destroy');

    Route::post('/marketplace/{commodity}/buy', [MarketplaceController::class, 'quickBuy'])
        ->middleware('throttle:writes')
        ->name('marketplace.buy');

    Route::patch('/marketplace/orders/{order}', [MarketplaceController::class, 'updateOrder'])
        ->middleware('throttle:writes')
        ->name('marketplace.orders.update');

    Route::post('/marketplace/orders/{order}/cancel', [MarketplaceController::class, 'cancelOrder'])
        ->middleware('throttle:writes')
        ->name('marketplace.orders.cancel');

    Route::get('/market-intelligence', [MarketIntelligenceController::class, 'index'])
        ->name('market.intelligence');
    Route::get('/market-intelligence/export', [MarketIntelligenceController::class, 'export'])
        ->name('market.export');
    Route::post('/market-intelligence/watch/{commodity}', [MarketIntelligenceController::class, 'watch'])
        ->middleware('throttle:writes')
        ->name('market.watch');
    Route::delete('/market-intelligence/watch/{commodity}', [MarketIntelligenceController::class, 'unwatch'])
        ->middleware('throttle:writes')
        ->name('market.unwatch');

    Route::post('/exchange/orders/{order}/cancel', [CommodityExchangeController::class, 'cancelOrder'])
        ->middleware('throttle:writes')
        ->name('exchange.orders.cancel');
    Route::get('/exchange/{commodity?}', [CommodityExchangeController::class, 'show'])
        ->whereNumber('commodity')
        ->name('exchange.show');
    Route::post('/exchange/{commodity}/orders', [CommodityExchangeController::class, 'storeOrder'])
        ->middleware('throttle:writes')
        ->whereNumber('commodity')
        ->name('exchange.order');

    Route::get('/arbitrage', [ArbitrageController::class, 'show'])
        ->name('arbitrage.show');
    Route::get('/arbitrage/{opportunity}/analysis', [ArbitrageController::class, 'analysis'])
        ->name('arbitrage.analysis');

    Route::get('/ai-assistant', [AiAssistantController::class, 'index'])
        ->name('ai.assistant');
    Route::post('/ai-assistant', [AiAssistantController::class, 'store'])
        ->middleware('throttle:writes')
        ->name('ai.assistant.store');
    Route::post('/ai-assistant/{conversation}/open', [AiAssistantController::class, 'open'])
        ->middleware('throttle:writes')
        ->name('ai.assistant.open');
    Route::post('/ai-assistant/{conversation}/messages', [AiAssistantController::class, 'message'])
        ->middleware('throttle:writes')
        ->name('ai.assistant.message');

    Route::get('/cyraai-command-center', CyraAiCommandCenterController::class)
        ->name('ai.command');

    Route::get('/wallet', [DigitalWalletController::class, 'index'])
        ->name('wallet.index');
    Route::post('/wallet/deposit', [DigitalWalletController::class, 'deposit'])
        ->middleware('throttle:writes')
        ->name('wallet.deposit');
    Route::get('/wallet/paystack/callback', [PaystackWalletController::class, 'callback'])
        ->middleware('throttle:60,1')
        ->name('wallet.paystack.callback');
    Route::post('/wallet/withdraw', [DigitalWalletController::class, 'withdraw'])
        ->middleware('throttle:writes')
        ->name('wallet.withdraw');

    Route::get('/logistics', [LogisticsNetworkController::class, 'index'])
        ->name('logistics.index');
    Route::post('/logistics/vehicles/{vehicle}/book', [LogisticsNetworkController::class, 'book'])
        ->middleware('throttle:writes')
        ->name('logistics.book');
    Route::post('/logistics/shipments/{shipment}/advance', [LogisticsNetworkController::class, 'advance'])
        ->middleware('throttle:writes')
        ->name('logistics.advance');
    Route::post('/logistics/shipments/{shipment}/cancel', [LogisticsNetworkController::class, 'cancel'])
        ->middleware('throttle:writes')
        ->name('logistics.cancel');

    Route::get('/smart-city-distribution', [SmartCityFoodDistributionController::class, 'index'])
        ->name('distribution.smart-city');
    Route::post('/smart-city-distribution/optimize', [SmartCityFoodDistributionController::class, 'optimize'])
        ->middleware('throttle:writes')
        ->name('distribution.optimize');
    Route::post('/smart-city-distribution/deliveries', [SmartCityFoodDistributionController::class, 'store'])
        ->middleware('throttle:writes')
        ->name('distribution.deliveries.store');
    Route::post('/smart-city-distribution/deliveries/{delivery}/advance', [SmartCityFoodDistributionController::class, 'advance'])
        ->middleware('throttle:writes')
        ->name('distribution.deliveries.advance');
    Route::post('/smart-city-distribution/deliveries/{delivery}/cancel', [SmartCityFoodDistributionController::class, 'cancel'])
        ->middleware('throttle:writes')
        ->name('distribution.deliveries.cancel');
    Route::post('/smart-city-distribution/fleet/{unit}/toggle', [SmartCityFoodDistributionController::class, 'toggleFleet'])
        ->middleware('throttle:writes')
        ->name('distribution.fleet.toggle');

    Route::get('/warehouse', [WarehouseManagementController::class, 'index'])
        ->name('warehouse.index');
    Route::post('/warehouse', [WarehouseManagementController::class, 'store'])
        ->middleware('throttle:writes')
        ->name('warehouse.store');
    Route::post('/warehouse/{warehouse}/stock', [WarehouseManagementController::class, 'receive'])
        ->middleware('throttle:writes')
        ->name('warehouse.stock.receive');
    Route::post('/warehouse/stock/{stock}/release', [WarehouseManagementController::class, 'release'])
        ->middleware('throttle:writes')
        ->name('warehouse.stock.release');

    Route::get('/supply-chain', [SupplyChainTrackingController::class, 'index'])
        ->name('supply-chain.index');
    Route::post('/supply-chain/shipments/{shipment}/advance', [SupplyChainTrackingController::class, 'advance'])
        ->middleware('throttle:writes')
        ->name('supply-chain.advance');
    Route::post('/supply-chain/shipments/{shipment}/cancel', [SupplyChainTrackingController::class, 'cancel'])
        ->middleware('throttle:writes')
        ->name('supply-chain.cancel');

    Route::get('/weather', [WeatherIntelligenceController::class, 'index'])
        ->name('weather.intelligence');
    Route::post('/weather/refresh', [WeatherIntelligenceController::class, 'refresh'])
        ->middleware('throttle:writes')
        ->name('weather.refresh');
    Route::post('/weather/alerts/{alert}/acknowledge', [WeatherIntelligenceController::class, 'acknowledge'])
        ->middleware('throttle:writes')
        ->name('weather.alerts.acknowledge');
    Route::post('/weather/alerts/{alert}/dismiss', [WeatherIntelligenceController::class, 'dismiss'])
        ->middleware('throttle:writes')
        ->name('weather.alerts.dismiss');
    Route::get('/weather/export', [WeatherIntelligenceController::class, 'export'])
        ->name('weather.export');

    Route::get('/messages', [NotificationsMessagingController::class, 'index'])
        ->name('messaging.index');
    Route::post('/messages/notifications/read-all', [NotificationsMessagingController::class, 'markAllRead'])
        ->middleware('throttle:writes')
        ->name('messaging.notifications.read-all');
    Route::post('/messages/notifications/{notification}/read', [NotificationsMessagingController::class, 'markRead'])
        ->middleware('throttle:writes')
        ->name('messaging.notifications.read');
    Route::post('/messages/send', [NotificationsMessagingController::class, 'sendMessage'])
        ->middleware('throttle:writes')
        ->name('messaging.messages.send');
    Route::post('/messages/announcements', [NotificationsMessagingController::class, 'storeAnnouncement'])
        ->middleware('throttle:writes')
        ->name('messaging.announcements.store');
    Route::post('/messages/announcements/{announcement}/acknowledge', [NotificationsMessagingController::class, 'acknowledgeAnnouncement'])
        ->middleware('throttle:writes')
        ->name('messaging.announcements.acknowledge');
    Route::post('/messages/announcements/{announcement}/dismiss', [NotificationsMessagingController::class, 'dismissAnnouncement'])
        ->middleware('throttle:writes')
        ->name('messaging.announcements.dismiss');
    Route::post('/messages/sms', [NotificationsMessagingController::class, 'sendSms'])
        ->middleware('throttle:writes')
        ->name('messaging.sms.send');
    Route::post('/messages/sms/{sms}/retry', [NotificationsMessagingController::class, 'retrySms'])
        ->middleware('throttle:writes')
        ->name('messaging.sms.retry');
    Route::post('/messages/email', [NotificationsMessagingController::class, 'sendEmail'])
        ->middleware('throttle:writes')
        ->name('messaging.email.send');
    Route::post('/messages/tasks', [NotificationsMessagingController::class, 'storeTask'])
        ->middleware('throttle:writes')
        ->name('messaging.tasks.store');
    Route::post('/messages/tasks/{task}/start', [NotificationsMessagingController::class, 'startTask'])
        ->middleware('throttle:writes')
        ->name('messaging.tasks.start');
    Route::post('/messages/tasks/{task}/complete', [NotificationsMessagingController::class, 'completeTask'])
        ->middleware('throttle:writes')
        ->name('messaging.tasks.complete');
    Route::post('/messages/tasks/{task}/cancel', [NotificationsMessagingController::class, 'cancelTask'])
        ->middleware('throttle:writes')
        ->name('messaging.tasks.cancel');
    Route::post('/messages/tasks/{task}/reopen', [NotificationsMessagingController::class, 'reopenTask'])
        ->middleware('throttle:writes')
        ->name('messaging.tasks.reopen');

    Route::get('/reports', [ReportingAnalyticsController::class, 'index'])
        ->name('reporting.analytics');
    Route::post('/reports/refresh', [ReportingAnalyticsController::class, 'refresh'])
        ->middleware('throttle:writes')
        ->name('reporting.refresh');
    Route::post('/reports/custom', [ReportingAnalyticsController::class, 'storeCustom'])
        ->middleware('throttle:writes')
        ->name('reporting.custom.store');
    Route::get('/reports/custom/{report}/download', [ReportingAnalyticsController::class, 'downloadCustom'])
        ->name('reporting.custom.download');
    Route::get('/reports/export', [ReportingAnalyticsController::class, 'export'])
        ->name('reporting.export');

    Route::get('/crops/manage', [CropManagementController::class, 'show'])
        ->name('crops.manage');
    Route::post('/crops', [CropManagementController::class, 'store'])
        ->middleware('throttle:writes')
        ->name('crops.store');
    Route::post('/crops/{crop}/activities', [CropManagementController::class, 'storeActivity'])
        ->middleware('throttle:writes')
        ->name('crops.activities.store');
    Route::post('/crops/{crop}/advance-stage', [CropManagementController::class, 'advanceStage'])
        ->middleware('throttle:writes')
        ->name('crops.advance-stage');

    Route::get('/farms/register', [FarmRegistrationController::class, 'show'])
        ->name('farms.register');
    Route::post('/farms/{farm}/register/location', [FarmRegistrationController::class, 'storeLocation'])
        ->middleware('throttle:writes')
        ->name('farms.register.location');
    Route::post('/farms/{farm}/register/details', [FarmRegistrationController::class, 'storeDetails'])
        ->middleware('throttle:writes')
        ->name('farms.register.details');
    Route::post('/farms/{farm}/register/crops', [FarmRegistrationController::class, 'storeCrops'])
        ->middleware('throttle:writes')
        ->name('farms.register.crops');
    Route::post('/farms/{farm}/register/documents', [FarmRegistrationController::class, 'storeDocuments'])
        ->middleware('throttle:writes')
        ->name('farms.register.documents');
    Route::post('/farms/{farm}/register/submit', [FarmRegistrationController::class, 'submit'])
        ->middleware('throttle:writes')
        ->name('farms.register.submit');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])
        ->middleware('throttle:writes')
        ->name('profile.update');
    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])
        ->middleware('throttle:writes')
        ->name('profile.avatar.update');
    Route::delete('/profile/avatar', [ProfileController::class, 'destroyAvatar'])
        ->middleware('throttle:writes')
        ->name('profile.avatar.destroy');
    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->middleware('throttle:writes')
        ->name('profile.destroy');
});

require __DIR__.'/auth.php';
