# PHP Error Insight Symfony Bundle

A Symfony bundle that integrates [PHP Error Insight](https://github.com/raffaelecarelle/php-error-insight) to provide AI‑assisted explanations and richer error pages for HTTP and Console errors.

## Features

- 🔍 AI‑assisted error explanations (optional, multiple backends)
- 🎨 Enhanced HTML/text/JSON rendering provided by the core library
- ⚡ Seamless Symfony integration for HTTP exceptions and Console errors
- 🔧 Highly configurable: choose backend, model, output, verbosity and paths

## Requirements

- PHP >= 8.1
- Symfony >= 6.4
- Composer

## Installation

Install the bundle (typically in dev/test):

```bash
composer require --dev raffaelecarelle/php-error-insight-symfony-bundle
```

### Configure Symfony Runtime error handler (required)

To enable the bundle's PHP error handling, you must tell Symfony Runtime to use this bundle's error handler. Add the following to your application's `composer.json` under the `extra.runtime` section, then regenerate the autoloader:

```json
{
  "extra": {
    "runtime": {
      "error_handler": "PhpErrorInsightBundle\\Handler\\ErrorHandler"
    }
  }
}
```

After editing composer.json, run:

```bash
composer dump-autoload
```

Notes:
- The configuration must live in your application root `composer.json` (not in a dependency).
- This requires the `symfony/runtime` Composer plugin to be allowed in your project (see `config.allow-plugins["symfony/runtime"]`).

## Configuration

The bundle exposes a single configuration root: `php_error_insight`.

### Basic configuration

Create or update `config/packages/php_error_insight.yaml`:

```yaml
php_error_insight:
    enabled: true
    backend: 'none'   # or: local, api, openai, anthropic, google, gemini
    language: 'en'
    output: 'auto'    # or: html, text, json
```

### Advanced configuration

```yaml
php_error_insight:
    enabled: true
    backend: 'local'
    model: 'llama3:instruct'
    api_url: 'http://localhost:11434'
    language: 'en'
    output: 'html'
    verbose: false

    # Absolute path of your project inside the running environment
    # (e.g. "/app" inside a Docker container)
    project_root: '%kernel.project_dir%'

    # Optional: absolute path of the project on the host machine to map
    # container paths to host paths when rendering links
    host_root: '/absolute/path/on/host'

    # Optional: custom HTML template supported by the core library
    template: null

    # Optional: editor URL pattern, e.g. "phpstorm://open?file=%file%&line=%line%"
    editor_url: null

    # API key for external backends (openai, anthropic, google/gemini, custom api)
    api_key: null
```

Notes:
- `backend` supports: `none`, `local`, `api`, `openai`, `anthropic`, `google`, `gemini`.
- When using local backends (e.g. Ollama), set `api_url` to your local endpoint.
- Use `project_root` and optionally `host_root` to make editor links and paths resolve correctly across containers/hosts.
- `editor_url` placeholders supported by the core renderer are typically `%file%` and `%line%`.

### Environment variables

You can configure via environment variables as well (e.g. in `.env.local`):

```bash
PHP_ERROR_INSIGHT_ENABLED=true
PHP_ERROR_INSIGHT_BACKEND=local
PHP_ERROR_INSIGHT_MODEL=llama3:instruct
PHP_ERROR_INSIGHT_API_URL=http://localhost:11434
PHP_ERROR_INSIGHT_LANG=en
PHP_ERROR_INSIGHT_OUTPUT=html
PHP_ERROR_INSIGHT_VERBOSE=false
PHP_ERROR_INSIGHT_PROJECT_ROOT=/app
PHP_ERROR_INSIGHT_HOST_ROOT=/Users/you/Projects/acme
PHP_ERROR_INSIGHT_EDITOR="phpstorm://open?file=%file%&line=%line%"
PHP_ERROR_INSIGHT_API_KEY=your-key-if-needed
```

## Backend configuration examples

### 1) Local AI (Ollama)

```yaml
php_error_insight:
    backend: 'local'
    model: 'llama3:instruct'
    api_url: 'http://localhost:11434'
```

### 2) OpenAI

```yaml
php_error_insight:
    backend: 'openai'
    model: 'gpt-4o-mini'
    api_key: 'sk-your-openai-api-key'
```

### 3) Anthropic Claude

```yaml
php_error_insight:
    backend: 'anthropic'
    model: 'claude-3-5-sonnet-20240620'
    api_key: 'your-anthropic-api-key'
```

### 4) Google (Generative AI)

```yaml
php_error_insight:
    backend: 'google'
    model: 'gemini-1.5-flash'
    api_key: 'your-google-api-key'
```

### 5) Gemini (alias backend)

```yaml
php_error_insight:
    backend: 'gemini'
    model: 'gemini-1.5-flash'
    api_key: 'your-google-api-key'
```

## Usage

### HTTP exceptions

The bundle registers a high‑priority listener for `kernel.exception`. When `enabled` is true, exceptions are rendered using PHP Error Insight’s renderer. If rendering fails or produces empty output, Symfony’s default error handling is used.

### Console errors

A console event subscriber listens to `ConsoleEvents::ERROR` and, when `enabled`, prints the rendered output to the console. If rendering fails or produces empty output, normal console error output is preserved.

### Programmatic usage

You can call the service directly:

```php
use PhpErrorInsightBundle\Service\ErrorInsightService;

final class YourController
{
    public function __construct(private ErrorInsightService $errorInsightService) {}

    public function someAction(): Response
    {
        try {
            // ... your code
        } catch (\Throwable $e) {
            if ($this->errorInsightService->isEnabled()) {
                $html = $this->errorInsightService->renderException($e);
                // do something with $html (log, email, custom response, ...)
            }

            throw $e; // keep default handling flow
        }
    }
}
```

Service IDs:
- `PhpErrorInsightBundle\Service\ErrorInsightService` (autowired)
- Alias: `php_error_insight.service`

## How it works

1. Exception interception (HTTP) via `kernel.exception` listener
2. Error interception (Console) via `ConsoleEvents::ERROR` subscriber
3. The bundle builds a Config and delegates rendering to the core library
4. Graceful fallback to Symfony’s default error handling when needed

## Environment considerations

It’s recommended to enable the bundle only in `dev` and/or `test`. You control this through:
- `config/bundles.php` (register the bundle for specific environments)
- `php_error_insight.enabled` setting per environment config

## Customization

- `template`: path to a custom HTML template supported by the core renderer
- `editor_url`: URL pattern to open files in your editor (e.g. PhpStorm)
- `project_root` / `host_root`: map paths correctly between container and host

## Troubleshooting

- Ensure the bundle is registered for the environment you’re using
- Verify `php_error_insight.enabled` is true for that environment
- For local backends, ensure your local service (e.g. Ollama) is running
- For API backends, verify `api_key` and reachability of `api_url` if applicable
- Check your logs for errors from the renderer or backend

## Development

Run tests:

```bash
composer install
composer test
```

Quality tools:

```bash
composer quality
composer fix-all
```

## License

This bundle is licensed under the GPL-3.0-or-later license. See [LICENSE](LICENSE) for details.

## Related

- [PHP Error Insight](https://github.com/raffaelecarelle/php-error-insight)
- [Symfony](https://symfony.com)