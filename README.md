Replace your `README.md` with this complete installation section:

````md
# Ridgeben Smart Chatbot

Reusable Laravel database-aware AI chatbot using Ollama.

Ridgeben Smart Chatbot is a Laravel package that connects your website database with a local AI model through Ollama. The chatbot searches your configured database tables first, builds useful context, and then sends that context to Ollama so users receive natural, helpful, and database-based answers.

---

## Requirements

Before installing, make sure your project has:

- PHP 8.2 or higher
- Laravel 11, 12, or 13
- Composer
- MySQL or supported Laravel database
- Ollama installed on your machine/server

---

## Install Ollama

Download and install Ollama from:

```bash
https://ollama.com
````

Then pull the recommended model:

```bash
ollama pull llama3.2:3b
```

Start Ollama:

```bash
ollama serve
```

Check if the model is available:

```bash
ollama list
```

---

## Package Installation

Install the package using Composer:

```bash
composer require ridgeben/smart-chatbot
```

After installation, run the setup command:

```bash
php artisan smart-chatbot:install
```

The installer will ask you some setup questions, such as:

```text
Website name
Assistant name
Business type
Database table name
Title/name column
Searchable columns
Context columns
Active/status column
Active/status value
Blade layout injection
```

---

## One-Line Installation

For Linux/macOS/Git Bash:

```bash
composer require ridgeben/smart-chatbot && php artisan smart-chatbot:install
```

For Windows PowerShell:

```powershell
composer require ridgeben/smart-chatbot; php artisan smart-chatbot:install
```

---

## Example: Real Estate Website Installation

Example command for a real estate project table:

```bash
php artisan smart-chatbot:install --force --inject-widget --website="Bridge Holdings Limited" --assistant="Bridge Holdings Assistant" --business="real estate company" --source-type=project --table=projects --title-column=project_title --searchable=project_title,project_slug,short_description,project_description,project_video_description,feature_text,location_description --context=project_title,project_slug,short_description,project_description,project_video_description,feature_text,location_description,location_url --active-column=status --active-value=1
```

---

## Example: eCommerce Website Installation

```bash
php artisan smart-chatbot:install --force --inject-widget --website="My Shop" --assistant="Shop Assistant" --business="eCommerce website" --source-type=product --table=items --title-column=name --searchable=name,slug,description,short_details,specification --context=name,slug,price,discount_price,stock,description,short_details,specification --active-column=status --active-value=1
```

---

## Example: Service Website Installation

```bash
php artisan smart-chatbot:install --force --inject-widget --website="My Company" --assistant="Company Assistant" --business="service company" --source-type=service --table=services --title-column=title --searchable=title,slug,short_description,description --context=title,short_description,description --active-column=status --active-value=1
```

---

## What the Installer Does

The installer command automatically:

```text
1. Creates or updates config/smart-chatbot.php
2. Adds chatbot-related values to .env
3. Configures Ollama URL and model
4. Configures website name, assistant name, and business type
5. Configures database source mapping
6. Adds chatbot widget to your Blade layout if possible
7. Clears Laravel cache
```

---

## Manual Widget Installation

If the installer cannot detect your Blade layout automatically, add this manually before `</body>` in your main Blade layout:

```blade
@include('smart-chatbot::widget')
```

Common layout files:

```text
resources/views/app.blade.php
resources/views/layouts/app.blade.php
resources/views/layouts/master.blade.php
resources/views/layouts/frontend.blade.php
```

For Inertia projects, add it inside `resources/views/app.blade.php` before `</body>`:

```blade
<body>
    @inertia

    @include('smart-chatbot::widget')
</body>
```

---

## Environment Variables

The installer will add these automatically, but you can also add them manually:

```env
SMART_CHATBOT_ENABLED=true
SMART_CHATBOT_NAME="Website Assistant"
SMART_CHATBOT_WEBSITE_NAME="Your Website Name"
SMART_CHATBOT_BUSINESS_TYPE="business"

OLLAMA_URL=http://127.0.0.1:11434
OLLAMA_MODEL=llama3.2:3b
OLLAMA_TIMEOUT=180
```

---

## Configuration File

After installation, the main config file will be created here:

```text
config/smart-chatbot.php
```

The most important section is:

```php
'sources' => [
    [
        'source_type' => 'project',
        'table' => 'projects',
        'primary_key' => 'id',
        'title_column' => 'project_title',

        'searchable_columns' => [
            'project_title',
            'project_slug',
            'short_description',
            'project_description',
        ],

        'context_columns' => [
            'project_title' => 'Project Name',
            'short_description' => 'Short Description',
            'project_description' => 'Project Description',
        ],

        'active_column' => 'status',
        'active_value' => 1,
    ],
],
```

This tells the chatbot which database table and columns it should search.

---

## How It Works

```text
User sends question
↓
Laravel chatbot route receives request
↓
Package searches configured database tables
↓
Package builds useful context
↓
Context is sent to Ollama
↓
Ollama paraphrases the database information
↓
Answer returns to chatbot widget
```

The chatbot is designed to answer from your website database. It should not invent exact project names, product prices, stock, status, locations, or other factual details if they are not available in the configured database context.

---

## Useful Commands

Clear Laravel cache:

```bash
php artisan optimize:clear
```

Check if the chatbot command is available:

```bash
php artisan list | grep smart-chatbot
```

For Windows PowerShell or CMD:

```powershell
php artisan list | findstr smart-chatbot
```

Check routes:

```bash
php artisan route:list
```

The chatbot route should appear as:

```text
POST smart-chatbot/ask
```

---

## Testing

After installation, run:

```bash
php artisan serve
```

Visit your website and test questions such as:

```text
hello
who are you?
tell me about [project/product/service name]
what facilities do you have?
what is the price?
is it available?
clear memory
```

---

## Troubleshooting

### Command Not Found

If this command does not work:

```bash
php artisan smart-chatbot:install
```

Run:

```bash
composer dump-autoload
php artisan package:discover
php artisan optimize:clear
```

Then check:

```bash
php artisan list | findstr smart-chatbot
```

---

### Chatbot Widget Not Showing

Make sure this line exists before `</body>`:

```blade
@include('smart-chatbot::widget')
```

Then run:

```bash
php artisan optimize:clear
```

---

### Ollama Not Responding

Make sure Ollama is running:

```bash
ollama serve
```

Check model:

```bash
ollama list
```

Install model if needed:

```bash
ollama pull llama3.2:3b
```

Check `.env`:

```env
OLLAMA_URL=http://127.0.0.1:11434
OLLAMA_MODEL=llama3.2:3b
```

---

### Chatbot Cannot Find Database Information

Check your config:

```text
config/smart-chatbot.php
```

Make sure:

```text
table name is correct
title column exists
searchable columns exist
context columns exist
active_column and active_value are correct
```

You can test columns using Laravel Tinker:

```bash
php artisan tinker
```

Then:

```php
Schema::getColumnListing('your_table_name');
```

---

## Security Notes

Do not configure private or sensitive tables as chatbot sources.

Avoid exposing:

```text
users
admins
passwords
sessions
orders with private customer data
payment information
API keys
tokens
private emails
private phone numbers
```

Only use public website information that visitors are allowed to know.

---

## Production Notes

For production:

```text
1. Keep Ollama private.
2. Do not expose Ollama directly to the internet.
3. Let Laravel call Ollama through localhost or a private server.
4. Use route throttling.
5. Limit question length.
6. Only configure public database tables.
7. Monitor storage/logs/laravel.log for errors.
```

---

## Update Package

To update the package:

```bash
composer update ridgeben/smart-chatbot
php artisan optimize:clear
```

---

## License

MIT

```
```
