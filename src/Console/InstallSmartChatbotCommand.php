<?php

namespace Ridgeben\SmartChatbot\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class InstallSmartChatbotCommand extends Command
{
    protected $signature = 'smart-chatbot:install
        {--force : Overwrite existing smart-chatbot config}
        {--inject-widget : Automatically inject chatbot widget into Blade layout}
        {--layout= : Blade layout path, example: resources/views/app.blade.php}
        {--website= : Website name}
        {--assistant= : Assistant name}
        {--business= : Business type}
        {--ollama-url=http://127.0.0.1:11434 : Ollama base URL}
        {--ollama-model=llama3.2:3b : Ollama model name}
        {--source-type= : Source type, example: project, product, service}
        {--table= : Main database table name}
        {--title-column= : Main title/name column}
        {--searchable= : Comma-separated searchable columns}
        {--context= : Comma-separated context columns}
        {--active-column= : Active/status column}
        {--active-value= : Active/status value}';

    protected $description = 'Install and configure Ridgeben Smart Chatbot automatically.';

    public function handle(): int
    {
        $this->info('Installing Ridgeben Smart Chatbot...');

        $websiteName = $this->option('website') ?: $this->ask('Website name?', config('app.name', 'Website'));
        $assistantName = $this->option('assistant') ?: $this->ask('Assistant name?', $websiteName . ' Assistant');
        $businessType = $this->option('business') ?: $this->ask('Business type?', 'business');

        $ollamaUrl = $this->option('ollama-url') ?: 'http://127.0.0.1:11434';
        $ollamaModel = $this->option('ollama-model') ?: 'llama3.2:3b';

        $this->updateEnv([
            'SMART_CHATBOT_ENABLED' => 'true',
            'SMART_CHATBOT_NAME' => $assistantName,
            'SMART_CHATBOT_WEBSITE_NAME' => $websiteName,
            'SMART_CHATBOT_BUSINESS_TYPE' => $businessType,
            'OLLAMA_URL' => $ollamaUrl,
            'OLLAMA_MODEL' => $ollamaModel,
            'OLLAMA_TIMEOUT' => '180',
        ]);

        $source = $this->buildSourceConfig();

        $this->writeConfigFile($websiteName, $assistantName, $businessType, $source);

        if ($this->option('inject-widget') || $this->confirm('Do you want to automatically add the chatbot widget to your Blade layout?', true)) {
            $this->injectWidgetIntoLayout();
        }

        $this->callSilent('optimize:clear');

        $this->newLine();
        $this->info('Smart Chatbot installed successfully!');
        $this->line('Next steps:');
        $this->line('1. Start Ollama: ollama serve');
        $this->line('2. Install model: ollama pull ' . $ollamaModel);
        $this->line('3. Visit your website and test the chatbot.');
        $this->newLine();

        return self::SUCCESS;
    }

    private function buildSourceConfig(): array
    {
        $configureDatabase = true;

        if (!$this->option('table')) {
            $configureDatabase = $this->confirm('Do you want to connect chatbot with a database table now?', true);
        }

        if (!$configureDatabase) {
            return [];
        }

        $sourceType = $this->option('source-type') ?: $this->ask('Source type? Example: project, product, service', 'general');
        $table = $this->option('table') ?: $this->ask('Main database table name? Example: projects, products, services');
        $titleColumn = $this->option('title-column') ?: $this->ask('Title/name column? Example: project_title, name, title');

        $searchableInput = $this->option('searchable') ?: $this->ask(
            'Searchable columns? Use comma-separated format',
            $titleColumn
        );

        $contextInput = $this->option('context') ?: $this->ask(
            'Context columns to send to AI? Use comma-separated format',
            $searchableInput
        );

        $activeColumn = $this->option('active-column') ?: $this->ask(
            'Active/status column? Leave empty if not needed',
            ''
        );

        $activeValue = null;

        if (!empty($activeColumn)) {
            $activeValue = $this->option('active-value');

            if ($activeValue === null) {
                $activeValue = $this->ask('Active/status value? Example: 1', '1');
            }
        }

        return [
            [
                'source_type' => $sourceType,
                'table' => $table,
                'primary_key' => 'id',
                'title_column' => $titleColumn,
                'searchable_columns' => $this->commaList($searchableInput),
                'context_columns' => $this->makeContextColumns($this->commaList($contextInput)),
                'active_column' => $activeColumn ?: null,
                'active_value' => $activeColumn ? $this->castValue($activeValue) : null,
            ],
        ];
    }

    private function writeConfigFile(string $websiteName, string $assistantName, string $businessType, array $sources): void
    {
        $configPath = config_path('smart-chatbot.php');

        if (file_exists($configPath) && !$this->option('force')) {
            if (!$this->confirm('config/smart-chatbot.php already exists. Overwrite it?', false)) {
                $this->warn('Config file was not overwritten.');
                return;
            }
        }

        $sourcesExport = var_export($sources, true);
        $websiteExport = var_export($websiteName, true);
        $assistantExport = var_export($assistantName, true);
        $businessExport = var_export($businessType, true);

        $generalKnowledge = "{$websiteName} is a {$businessType}. The assistant should answer using website information, help users understand available information, and avoid inventing exact facts if they are not available.";
        $generalKnowledgeExport = var_export($generalKnowledge, true);

        $content = <<<PHP
<?php

return [

    'enabled' => env('SMART_CHATBOT_ENABLED', true),

    'bot_name' => env('SMART_CHATBOT_NAME', {$assistantExport}),

    'ollama' => [
        'url' => env('OLLAMA_URL', 'http://127.0.0.1:11434'),
        'model' => env('OLLAMA_MODEL', 'llama3.2:3b'),
        'timeout' => env('OLLAMA_TIMEOUT', 180),
    ],

    'route' => [
        'prefix' => env('SMART_CHATBOT_ROUTE_PREFIX', 'smart-chatbot'),
        'middleware' => ['web', 'throttle:30,1'],
    ],

    'memory' => [
        'enabled' => true,
        'history_limit' => 6,
    ],

    'context' => [
        'max_characters' => 12000,
        'max_results' => 5,
    ],

    'website' => [
        'name' => env('SMART_CHATBOT_WEBSITE_NAME', {$websiteExport}),
        'assistant_name' => env('SMART_CHATBOT_NAME', {$assistantExport}),
        'business_type' => env('SMART_CHATBOT_BUSINESS_TYPE', {$businessExport}),
    ],

    'general_knowledge' => {$generalKnowledgeExport},

    'allow_ai_without_database_context' => true,

    'sources' => {$sourcesExport},

    'fallback_message' => 'Sorry, I could not find that information right now. Please contact our team for more details.',

];

PHP;

        file_put_contents($configPath, $content);
    }

    private function injectWidgetIntoLayout(): void
    {
        $layoutPath = $this->option('layout');

        if ($layoutPath) {
            $fullPath = base_path($layoutPath);
        } else {
            $fullPath = $this->detectLayoutPath();
        }

        if (!$fullPath || !file_exists($fullPath)) {
            $this->warn('Could not find Blade layout automatically.');
            $this->line("Please manually add this before </body>:");
            $this->line("@include('smart-chatbot::widget')");
            return;
        }

        $content = file_get_contents($fullPath);

        if (str_contains($content, "smart-chatbot::widget")) {
            $this->info('Chatbot widget already exists in layout.');
            return;
        }

        $include = "\n    @include('smart-chatbot::widget')\n";

        if (str_contains(strtolower($content), '</body>')) {
            $content = str_ireplace('</body>', $include . '</body>', $content);
        } else {
            $content .= $include;
        }

        file_put_contents($fullPath, $content);

        $this->info('Chatbot widget injected into: ' . str_replace(base_path() . DIRECTORY_SEPARATOR, '', $fullPath));
    }

    private function detectLayoutPath(): ?string
    {
        $possiblePaths = [
            resource_path('views/app.blade.php'),
            resource_path('views/layouts/app.blade.php'),
            resource_path('views/layouts/master.blade.php'),
            resource_path('views/layouts/frontend.blade.php'),
            resource_path('views/frontend/layouts/app.blade.php'),
            resource_path('views/frontend/layout.blade.php'),
        ];

        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

    private function updateEnv(array $values): void
    {
        $envPath = base_path('.env');

        if (!file_exists($envPath)) {
            if (file_exists(base_path('.env.example'))) {
                copy(base_path('.env.example'), $envPath);
            } else {
                file_put_contents($envPath, '');
            }
        }

        $env = file_get_contents($envPath);

        foreach ($values as $key => $value) {
            $formattedValue = $this->formatEnvValue((string) $value);

            if (preg_match('/^' . preg_quote($key, '/') . '=.*/m', $env)) {
                $env = preg_replace(
                    '/^' . preg_quote($key, '/') . '=.*/m',
                    $key . '=' . $formattedValue,
                    $env
                );
            } else {
                $env .= PHP_EOL . $key . '=' . $formattedValue;
            }
        }

        file_put_contents($envPath, $env);
    }

    private function formatEnvValue(string $value): string
    {
        if ($value === 'true' || $value === 'false' || is_numeric($value)) {
            return $value;
        }

        $value = str_replace('"', '\"', $value);

        return '"' . $value . '"';
    }

    private function commaList(string $value): array
    {
        return collect(explode(',', $value))
            ->map(fn($item) => trim($item))
            ->filter()
            ->unique()
            ->values()
            ->toArray();
    }

    private function makeContextColumns(array $columns): array
    {
        $mapped = [];

        foreach ($columns as $column) {
            $mapped[$column] = Str::title(str_replace('_', ' ', $column));
        }

        return $mapped;
    }

    private function castValue($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value === 'true') {
            return true;
        }

        if ($value === 'false') {
            return false;
        }

        if (is_numeric($value)) {
            return $value + 0;
        }

        return $value;
    }
}