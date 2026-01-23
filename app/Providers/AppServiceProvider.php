<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use Illuminate\Database\Eloquent\Relations\Relation;
use App\Models\Items\Item;
use App\Models\Party\Party;
use App\Models\Party\PartyTransaction;
use App\Models\Twilio;
use App\Models\AppSettings;
use App\Models\SmtpSettings;
use App\Models\Purchase\PurchaseOrder;
use App\Models\Purchase\Purchase;
use App\Models\Purchase\PurchaseReturn;

use App\Models\Sale\SaleOrder;
use App\Models\Sale\Sale;
use App\Models\Sale\SaleReturn;

use App\Models\PaymentTransaction;
use App\Models\Items\ItemTransaction;
use App\Models\Expenses\Expense;
use App\Models\CashAdjustment;
use App\Models\StockTransfer;
use App\Observers\TwilioObserver;
use App\Services\GeneralDataService;
use App\Services\SmsService;
use Illuminate\Support\Facades\Route;
use App\Services\CacheService;
use App\Enums\App;
use App\Models\Party\PartyPayment;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if(env('INSTALLATION_STATUS')){
            $this->app->singleton(GeneralDataService::class, function ($app) {
                return new GeneralDataService();
            });

            $this->app->singleton(SmsService::class, function ($app) {
                return new SmsService();
            });

            $this->app->singleton('site', function () {
                $appSettings = CacheService::get('appSetting');//AppSettings::find(App::APP_SETTINGS_RECORD_ID->value);
                return [
                    'name'              => $appSettings ? $appSettings->application_name : null,
                    'colored_logo'      => $appSettings ? $appSettings->colored_logo : null,
                ];
            });
            $this->app->singleton('smtp_settings', function () {
                $smtpSettings = SmtpSettings::find(App::APP_SETTINGS_RECORD_ID->value);
                return [
                    'host'              => $smtpSettings ? $smtpSettings->host : null,
                    'port'              => $smtpSettings ? $smtpSettings->port : null,
                    'username'          => $smtpSettings ? $smtpSettings->username : null,
                    'password'          => $smtpSettings ? $smtpSettings->password : null,
                    'encryption'        => $smtpSettings ? $smtpSettings->encryption : null,
                ];
            });
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrap();

        if(env('INSTALLATION_STATUS')){
            //Twilio::observe(TwilioObserver::class);

            /**
             * Start:
             * SMTP Settings
             * */
             if (!app()->runningInConsole() && Schema::hasTable('smtp_settings')) {
            $smtpSettings = $this->app->make('smtp_settings');

            // Extract SMTP settings from the model
            $driver = "smtp";

             // Update mail configuration with retrieved settings
            config(['mail.driver' => $driver]);

            config([
                'mail.host' => $smtpSettings['host'],
                'mail.port' => $smtpSettings['port'],
                'mail.username' => $smtpSettings['username'],
                'mail.password' => $smtpSettings['password'],
                'mail.encryption' => $smtpSettings['encryption'],
            ]);
            /**
             * End:
             * SMTP Settings
             * */
        }
    }

        Relation::morphMap([
            'Item Opening'              =>  Item::class, //Used in ItemTransaction class,

            'Purchase Order'            =>  PurchaseOrder::class,//Used in ItemTransaction class,

            'Purchase'                  =>  Purchase::class,//Used in ItemTransaction class,

            'Purchase Return'           =>  PurchaseReturn::class,//Used in ItemTransaction class,

            'Sale Order'                =>  SaleOrder::class,//Used in ItemTransaction class,

            'Sale'                      =>  Sale::class,//Used in ItemTransaction class,

            'Sale Return'               =>  SaleReturn::class,//Used in ItemTransaction class,

            'Party Opening'             =>  Party::class,//Used in PartyTransaction class

            'Payment Transaction'       =>  PaymentTransaction::class,//Used in PaymentTransaction class

            'Expense'                   =>  Expense::class,//Used in PaymentTransaction class

            'Party Transaction'         =>  PartyTransaction::class,//Used in AccountTransaction class

            'Item Transaction'          =>  ItemTransaction::class,//Used in AccountTransaction class

            'Cash Adjustment'           =>  CashAdjustment::class,//Used in CashController class

            'Stock Transfer'            =>  StockTransfer::class,//Used in CashController class

            'Party Payment'            =>  PartyPayment::class,//Used in PartyTransactionController class

        ]);
    }
}
