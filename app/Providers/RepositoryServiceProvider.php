<?php

namespace App\Providers;


use App\Repositories\MessageRepository;
use App\Repositories\Contract\ContractInstallmentRepository;
use App\Repositories\Contract\ContractRepository;
use App\Repositories\FreezeLogRepository;
use App\Repositories\ActionLoggerRepository;
use App\Repositories\Rbcx\RbcxCardInfoRepository;
use App\Repositories\Rbcx\RbcxOrderRepository;
use App\Repositories\Sms\SmsRepository;
use App\Repositories\UserRepository;
use App\Repositories\MenuRepository;
use App\Repositories\RoleRepository;
use App\Repositories\ActionRepository;
use App\Repositories\PermissionRepository;
use App\Repositories\WaterPurifier\BankCardsRepository;
use App\Repositories\WaterPurifier\DeviceRepository;
use App\Repositories\WaterPurifier\InstallmentRepository;
use App\Repositories\WaterPurifier\MaintainRepository;
use App\Repositories\WaterPurifier\ChannelRepository;
use App\Repositories\WaterPurifierRepository;
use App\Repositories\WaterSaleRepository;
use App\Repositories\WxActivityStatRepository;
use App\Repositories\WxPlatform\WxEventRepository;
use App\Repositories\WxPlatform\WxMenuRepository;
use App\Repositories\WxPlatform\WxMessageRepository;
use App\Repositories\WxPlatform\WxNoticeRepository;
use App\Repositories\WxPlatform\WxTemplateMessageRepository;
use App\Repositories\WxPlatform\WxTemplateRepository;
use App\Repositories\WxPlatform\WxUserRepository;
use Illuminate\Support\ServiceProvider;
use App\Repositories\CarInsurance\OrderRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        // 合并自定义配置文件
        $configuration = realpath(__DIR__ . '/../../config/repository.php');
        $this->mergeConfigFrom($configuration, 'repository');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerMenuRepository();
        $this->registerUserRepository();
        $this->registerRoleRepository();
        $this->registerActionRepository();
        $this->registerPermissionRepository();
        $this->registerMaintainRepository();
    }

    /**
     * Register the Menu Repository
     *
     * @return mixed
     */
    public function registerMenuRepository()
    {
        $this->app->singleton('menurepository', function ($app) {
            $model = config('repository.models.menu');
            $menu = new $model();
            $validator = $app['validator'];

            return new MenuRepository($menu, $validator);
        });
    }

    public function registerUserRepository()
    {
        $this->app->singleton('userrepository', function ($app) {
            $model = config('repository.models.user');
            $user = new $model();
            $validator = $app['validator'];

            return new UserRepository($user, $validator);
        });
    }

    public function registerRoleRepository()
    {
        $this->app->singleton('rolerepository', function ($app) {
            $model = config('repository.models.role');
            $role = new $model();
            $validator = $app['validator'];

            return new RoleRepository($role, $validator);
        });
    }

    public function registerActionRepository()
    {
        $this->app->singleton('actionrepository', function ($app) {
            $model = config('repository.models.action');
            $action = new $model();
            $validator = $app['validator'];

            return new ActionRepository($action, $validator);
        });
    }

    public function registerPermissionRepository()
    {
        $this->app->singleton('permissionrepository', function ($app) {
            $model = config('repository.models.permission');
            $permission = new $model();
            $validator = $app['validator'];

            return new PermissionRepository($permission, $validator);
        });
    }
    public function registerMaintainRepository()
    {
        $this->app->singleton(ActionLoggerRepository::$accessor, function ($app) {
            $modelName = config('repository.models.actionLogger');
            $model = new $modelName();
            $validator = $app['validator'];
            return new ActionLoggerRepository($model, $validator);
        });
        $this->app->singleton(MessageRepository::$accessor, function ($app) {
            $modelName = config('repository.models.message');
            $model = new $modelName();
            $validator = $app['validator'];
            return new MessageRepository($model, $validator);
        });
    }

}
