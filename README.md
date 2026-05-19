Add this section to your `README.md` after **Package Installation** or before **Submit to Packagist**.

````md
---

## Test Install Directly from GitHub Before Packagist

Before submitting the package to Packagist, you can test the package directly from GitHub in another Laravel project.

### Step 1: Go to another Laravel project

Example:

```bash
cd F:\Projects\bridge
````

### Step 2: Add GitHub repository to the Laravel project `composer.json`

Open the Laravel project’s root `composer.json` file and add this section:

```json
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/AnimeshError-404/smart-chatbot"
    }
]
```

Example placement near the bottom of `composer.json`:

```json
{
    "require": {
        "php": "^8.2",
        "laravel/framework": "^12.0"
    },

    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/AnimeshError-404/smart-chatbot"
        }
    ]
}
```

Make sure your JSON has proper commas.

### Step 3: Install the package from GitHub

Run:

```bash
composer require ridgeben/smart-chatbot:^1.0
```

Composer should download the package from your GitHub repository.

### Step 4: Check if the Artisan command is available

For Windows PowerShell or CMD:

```bash
php artisan list | findstr smart-chatbot
```

For Linux/macOS/Git Bash:

```bash
php artisan list | grep smart-chatbot
```

You should see:

```text
smart-chatbot:install
```

### Step 5: Run the installer

```bash
php artisan smart-chatbot:install
```

The installer will ask setup questions and configure the chatbot for the Laravel website.

### Step 6: Clear cache

```bash
php artisan optimize:clear
```

### Step 7: Test the website

Start the Laravel server:

```bash
php artisan serve
```

Then visit your website and check if the chatbot widget appears.

### Direct Install Example for Bridge Holdings

```bash
php artisan smart-chatbot:install --force --inject-widget --website="Bridge Holdings Limited" --assistant="Bridge Holdings Assistant" --business="real estate company" --source-type=project --table=projects --title-column=project_title --searchable=project_title,project_slug,short_description,project_description,project_video_description,feature_text,location_description --context=project_title,project_slug,short_description,project_description,project_video_description,feature_text,location_description,location_url --active-column=status --active-value=1
```

### Important Note

This GitHub test method is only needed before publishing the package to Packagist.

After the package is submitted and accepted on Packagist, users will not need to add the `repositories` section manually. They can simply run:

```bash
composer require ridgeben/smart-chatbot
php artisan smart-chatbot:install
```

```
```
