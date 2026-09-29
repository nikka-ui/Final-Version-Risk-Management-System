<?php

namespace Tests;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\UploadedFile;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    protected function tearDown(): void
    {
        $this->resetAuthState();
        parent::tearDown();
    }

    protected function resetAuthState(): void
    {
        $this->flushHeaders();
        if ($this->app->bound('auth')) {
            $this->app['auth']->forgetGuards();
        }
    }

    protected function bearerTokenFor(string $username, string $password): string
    {
        $this->resetAuthState();

        $response = $this->postJson('/v1/auth/token', compact('username', 'password'));
        $response->assertOk();

        return (string) $response->json('token');
    }

    protected function withBearerToken(string $token): static
    {
        return $this->withToken($token);
    }

    public function withToken(string $token, string $type = 'Bearer'): static
    {
        $this->resetAuthState();

        return parent::withToken($token, $type);
    }

    /**
     * Uploads are validated by sniffing file contents, so fakes need real magic bytes.
     */
    protected function fakePdf(string $name = 'evidence.pdf'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            "%PDF-1.4\n1 0 obj << /Type /Catalog >> endobj\ntrailer << /Root 1 0 R >>\n%%EOF\n",
        );
    }

    protected function fakePng(string $name = 'image.png'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            (string) base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='),
        );
    }

    public function createApplication()
    {
        // Docker API image removes `.env` (secrets come from the environment).
        // Dotenv still probes the path; an empty file avoids PHPUnit warnings.
        $envPath = dirname(__DIR__).DIRECTORY_SEPARATOR.'.env';
        if (! is_file($envPath)) {
            file_put_contents($envPath, '');
        }

        // Container env sets DB_* to live Postgres; never let RefreshDatabase wipe it.
        putenv('DB_CONNECTION=sqlite');
        putenv('DB_DATABASE=:memory:');
        $_ENV['DB_CONNECTION'] = 'sqlite';
        $_ENV['DB_DATABASE'] = ':memory:';
        $_SERVER['DB_CONNECTION'] = 'sqlite';
        $_SERVER['DB_DATABASE'] = ':memory:';

        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        return $app;
    }
}
