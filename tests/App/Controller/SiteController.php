<?php

declare(strict_types=1);

namespace PhpErrorInsightBundle\Tests\App\Controller;

use Symfony\Component\HttpFoundation\Response;

final class SiteController
{
    public function login(): Response
    {
        return new Response('<html><body>Login Page</body></html>', 200, ['Content-Type' => 'text/html']);
    }

    public function admin(): Response
    {
        // If security is correctly configured, unauthenticated users should be redirected before reaching here
        return new Response('<html><body>Admin Area</body></html>', 200, ['Content-Type' => 'text/html']);
    }

    public function boom(): Response
    {
        throw new \RuntimeException('Boom');
    }
}
