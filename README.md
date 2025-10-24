# PHP Error Insight Symfony Bundle

A Symfony Bundle that integrates [PHP Error Insight](https://github.com/raffaelecarelle/php-error-insight) to provide AI-powered error handling and debugging for Symfony applications.

## Features

- 🔍 **AI-Powered Error Analysis** - Get intelligent explanations and suggestions for errors
- 🎨 **Beautiful Error Pages** - Modern, responsive error page templates
- ⚡ **Seamless Integration** - Replaces Symfony's default error pages in development
- 🛠️ **Console Commands** - Test error handling via CLI
- 🔧 **Highly Configurable** - Support for multiple AI backends and output formats

## Requirements

- PHP >= 8.1
- Symfony >= 6.4
- Composer

## Installation

Install the bundle via Composer:

```bash
composer require --dev raffaelecarelle/php-error-insight-symfony-bundle
```

The bundle is fully compatible with Symfony Flex and will automatically:
- Register itself in `config/bundles.php` for dev and test environments
- Create a default configuration file in `config/packages/php_error_insight.yaml`
- Add environment variable placeholders to your `.env` file

## Configuration

### Basic Configuration

Create or update `config/packages/php_error_insight.yaml`:

```yaml
php_error_insight:
    enabled: true
    backend: 'none'  # or 'local', 'api', 'openai', 'anthropic', 'google'
    language: 'en'
    output: 'auto'   # or 'html', 'text', 'json'
```

### Advanced Configuration

```yaml
php_error_insight:
    enabled: true
    backend: 'local'
    model: 'llama3:instruct'
    api_url: 'http://localhost:11434'
    language: 'en'
    output: 'html'
    verbose: false
    override_symfony_errors: true
    project_root: '%kernel.project_dir%'  # Absolute path to your project root in the running environment (e.g., /app in Docker)
    # template: '/path/to/custom/template.html.twig'  # Optional custom template
    # api_key: 'your-api-key'  # Required for external APIs
```

### Environment Variables

You can also configure the bundle using environment variables:

```bash
# .env or .env.local
PHP_ERROR_INSIGHT_ENABLED=true
PHP_ERROR_INSIGHT_BACKEND=local
PHP_ERROR_INSIGHT_MODEL=llama3:instruct
PHP_ERROR_INSIGHT_API_URL=http://localhost:11434
PHP_ERROR_INSIGHT_LANG=en
PHP_ERROR_INSIGHT_OUTPUT=html
PHP_ERROR_INSIGHT_VERBOSE=false
PHP_ERROR_INSIGHT_PROJECT_ROOT=/app
```

Note on project_root:
- Set project_root to the absolute path of your project within the running environment (for example, /app inside a Docker container, or the repository path on your machine).
- This helps the bundle generate correct file paths for editor links and error rendering. If you also work with containers, you may pair it with host_root to map container paths to your host filesystem.

## Backend Configuration Examples

### 1. Local AI (Ollama)

```yaml
php_error_insight:
    backend: 'local'
    model: 'llama3:instruct'
    api_url: 'http://localhost:11434'
```

### 2. OpenAI

```yaml
php_error_insight:
    backend: 'openai'
    model: 'gpt-4o-mini'
    api_key: 'sk-your-openai-api-key'
```

### 3. Anthropic Claude

```yaml
php_error_insight:
    backend: 'anthropic'
    model: 'claude-3-5-sonnet-20240620'
    api_key: 'your-anthropic-api-key'
```

### 4. Google Gemini

```yaml
php_error_insight:
    backend: 'google'
    model: 'gemini-1.5-flash'
    api_key: 'your-google-api-key'
```

## Usage

### Automatic Error Handling

Once configured, the bundle automatically intercepts exceptions in development mode and displays them using PHP Error Insight instead of Symfony's default error pages.

### Testing Error Handling

Use the built-in console command to test error handling:

```bash
# Test different types of errors
php bin/console php-error-insight:test --type=exception
php bin/console php-error-insight:test --type=error
php bin/console php-error-insight:test --type=warning
php bin/console php-error-insight:test --type=notice

# Test with custom message
php bin/console php-error-insight:test --type=exception --message="Custom test error"
```

### Programmatic Usage

You can also use the service directly in your code:

```php
use PhpErrorInsightBundle\Service\ErrorInsightService;

class YourController
{
    public function __construct(
        private ErrorInsightService $errorInsightService
    ) {}
    
    public function someAction(): Response
    {
        try {
            // Your code that might throw an exception
            throw new \RuntimeException('Something went wrong');
        } catch (\Throwable $e) {
            if ($this->errorInsightService->isEnabled()) {
                $html = $this->errorInsightService->renderException($e);
                // Use or log the rendered HTML as needed
            }
            
            throw $e; // Re-throw to trigger normal error handling
        }
    }
}
```

## How It Works

1. **Exception Interception**: The bundle registers an event listener that intercepts Symfony's `kernel.exception` event
2. **AI Analysis**: When enabled, exceptions are analyzed by the configured AI backend
3. **Enhanced Display**: Error pages are rendered with AI-powered explanations and suggestions
4. **Fallback Handling**: If AI analysis fails, the bundle gracefully falls back to Symfony's default behavior

## Development Mode Only

For security reasons, the bundle only processes exceptions in development environments (`APP_ENV=dev`).

## Customization

### Custom Templates

You can provide your own Twig template for error rendering:

```yaml
php_error_insight:
    template: 'errors/custom_error.html.twig'
```

Your custom template should expect the following variables:
- `exception_class`: The exception class name
- `message`: The error message
- `file`: The file where the error occurred
- `line`: The line number where the error occurred
- `trace`: The stack trace
- `ai_explanation`: AI-generated explanation (if available)
- `ai_suggestions`: AI-generated suggestions (if available)

### Service Customization

You can extend or decorate the bundle's services:

```yaml
services:
    App\Service\CustomErrorInsightService:
        decorates: PhpErrorInsightBundle\Service\ErrorInsightService
        arguments: ['@App\Service\CustomErrorInsightService.inner']
```

## Troubleshooting

### Bundle Not Working

1. Ensure you're in development mode (`APP_ENV=dev`)
3. Verify the bundle is registered in `config/bundles.php`

### AI Backend Issues

1. For local backends (Ollama), ensure the service is running and accessible
2. For API backends, verify your API key is correct and you have sufficient credits
3. Check the logs for any connection or authentication errors

### Performance Considerations

- AI analysis adds latency to error pages
- Consider using local AI models for faster response times

## Development

### Running Tests

```bash
composer install
composer test
```

### Code Quality

```bash
composer quality  # Run all quality checks
composer fix-all  # Apply automatic fixes
```

## Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## License

This bundle is licensed under the GPL-3.0-or-later license. See [LICENSE](LICENSE) for details.

## Related Projects

- [PHP Error Insight](https://github.com/raffaelecarelle/php-error-insight) - The core library
- [Symfony](https://symfony.com) - The PHP framework this bundle integrates with