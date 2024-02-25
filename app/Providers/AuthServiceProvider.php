<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;

use App\Models\Conversation;
use App\Models\Message;
use App\Policies\Api\v1\ConversationPolicy;
use App\Policies\Api\v1\MessagePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Message::class => MessagePolicy::class,
        Conversation::class => ConversationPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
