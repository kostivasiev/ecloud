<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array
     */
    protected $middleware = [
        // \App\Http\Middleware\TrustHosts::class,
        \App\Http\Middleware\TrustProxies::class,
        \Fruitcake\Cors\HandleCors::class,
        \App\Http\Middleware\PreventRequestsDuringMaintenance::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            // \Illuminate\Session\Middleware\AuthenticateSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],

        'api' => [
            //'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array
     */
    protected $routeMiddleware = [
//        'auth' => \App\Http\Middleware\Authenticate::class,
    'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
    'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
    'can' => \Illuminate\Auth\Middleware\Authorize::class,
    'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
    'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
    'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
    'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
    'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
    'auth' => \UKFast\Api\Auth\Authenticate::class,
    'is-admin' => \UKFast\Api\Auth\Middleware\IsAdmin::class,
    'paginator-limit' => \UKFast\Api\Paginator\Middleware\PaginatorLimit::class,
    'has-reseller-id' => \App\Http\Middleware\HasResellerId::class,
    'hostgroup-has-capacity' => \App\Http\Middleware\HostGroup\HasCapacity::class,
    'instance-is-locked' => \App\Http\Middleware\Instance\IsLocked::class,
    'instance-console-enabled' => \App\Http\Middleware\Instance\ConsoleEnabled::class,
    'instance-requires-floating-ip' => \App\Http\Middleware\Instance\RequiresFloatingIp::class,
    'instance-can-migrate' => \App\Http\Middleware\Instance\CanMigrate::class,
    'can-enable-support' => \App\Http\Middleware\Vpc\CanEnableSupport::class,
    'is-pending' => \App\Http\Middleware\DiscountPlan\IsPending::class,
    'customer-max-vpc' => \App\Http\Middleware\IsMaxVpcForCustomer::class,
    'customer-max-instance' => \App\Http\Middleware\IsMaxInstanceForCustomer::class,
    'customer-max-ssh-key-pairs' => \App\Http\Middleware\IsMaxSshKeyPairForCustomer::class,
    'can-attach-instance-volume' => \App\Http\Middleware\CanAttachInstanceVolume::class,
    'can-detach' => \App\Http\Middleware\CanDetach::class,
    'network-rule-can-edit' => \App\Http\Middleware\NetworkRule\CanEdit::class,
    'network-rule-can-delete' => \App\Http\Middleware\NetworkRule\CanDelete::class,
    'network-rule-port-can-edit' => \App\Http\Middleware\NetworkRulePort\CanEdit::class,
    'network-rule-port-can-delete' => \App\Http\Middleware\NetworkRulePort\CanDelete::class,
    'can-update-image' => \App\Http\Middleware\image\CanUpdate::class,
    'can-delete-image' => \App\Http\Middleware\image\CanDelete::class,
    'orchestrator-config-is-valid' => \App\Http\Middleware\OrchestratorConfig\IsValid::class,
    'orchestrator-config-has-reseller-id' => \App\Http\Middleware\OrchestratorConfig\HasResellerId::class,
    'orchestrator-config-is-locked' => \App\Http\Middleware\OrchestratorConfig\IsLocked::class,
    'floating-ip-can-be-assigned' => \App\Http\Middleware\FloatingIp\CanBeAssigned::class,
    'floating-ip-can-be-unassigned' => \App\Http\Middleware\FloatingIp\CanBeUnassigned::class,
    'floating-ip-can-be-deleted' => \App\Http\Middleware\FloatingIp\CanBeDeleted::class,
    'host-group-can-be-deleted' => \App\Http\Middleware\HostGroup\CanBeDeleted::class,
    'volume-can-be-deleted' => \App\Http\Middleware\Volume\CanDelete::class,
    'ip-address-can-delete' => \App\Http\Middleware\IpAddress\CanDelete::class,
    'vpn-endpoint-can-delete' => \App\Http\Middleware\VpnEndpoint\CanDelete::class,
    'vpc-can-delete' => \App\Http\Middleware\Vpc\CanDelete::class,
    'load-balancer-is-max-for-customer' => \App\Http\Middleware\Loadbalancer\IsMaxForForCustomer::class,
    'can-be-deleted' => \App\Http\Middleware\CanBeDeleted::class,
    'loadbalancer-max-vip' => \App\Http\Middleware\Vips\MaxVipLimitReached::class,
    'is-managed' => \App\Http\Middleware\IsManaged::class,
    'affinity-rule-member-are-members-syncing' => \App\Http\Middleware\AffinityRuleMember\AreMembersSyncing::class,
    ];
}
