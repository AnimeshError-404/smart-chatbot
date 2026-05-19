<?php

namespace Ridgeben\SmartChatbot;

use Illuminate\Support\ServiceProvider;
use Ridgeben\SmartChatbot\Console\InstallSmartChatbotCommand;

class SmartChatbotServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/smart-chatbot.php',
            'smart-chatbot'
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        $this->loadViewsFrom(
            __DIR__ . '/../resources/views',
            'smart-chatbot'
        );

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallSmartChatbotCommand::class,
            ]);

            $this->publishes([
                __DIR__ . '/../config/smart-chatbot.php' => config_path('smart-chatbot.php'),
            ], 'smart-chatbot-config');

            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/smart-chatbot'),
            ], 'smart-chatbot-views');

            if (is_dir(__DIR__ . '/../database/migrations')) {
                $this->publishesMigrations([
                    __DIR__ . '/../database/migrations' => database_path('migrations'),
                ]);
            }
        }
    }
}