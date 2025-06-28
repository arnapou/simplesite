<?php

declare(strict_types=1);

/*
 * This file is part of the Arnapou Simple Site package.
 *
 * (c) Arnaud Buathier <me@arnapou.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Arnapou\SimpleSite\Tests\Admin;

use Arnapou\Psr\Psr7HttpMessage\Response;
use Arnapou\SimpleSite\Admin\AdminConfig;
use Arnapou\SimpleSite\Core\UrlEncoder;
use Arnapou\SimpleSite\SimpleSite;
use Arnapou\SimpleSite\Tests\ConfigTestTrait;
use Nyholm\Psr7\ServerRequest;
use Nyholm\Psr7\Stream;
use Nyholm\Psr7\UploadedFile;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DomCrawler\Crawler;

class AdminScenarioTest extends TestCase
{
    use ConfigTestTrait;

    private const string ADMIN_CONFIG = __DIR__ . '/../../demo/data/table.' . AdminConfig::TABLE . '.yaml';
    private const string ADMIN_CONFIG_BKUP = __DIR__ . '/../../demo/data/table.' . AdminConfig::TABLE . '.yaml.bkup';
    private static Response $response;
    private static string $redirect;
    private static string $contents;

    #[RunInSeparateProcess]
    public function testWorkingScenario(): void
    {
        $this->yamlAdminConfigBackup();
        $encode = static fn (string $str) => new UrlEncoder()->encode($str) . '/';
        try {
            // Access the admin without trailing slash.
            $this->handleGet('/admin');
            self::assertRedirect('/admin/', 302);

            // Access the admin with trailing slash.
            $this->handleRedirect();
            self::assertRedirect('/admin/login');

            // The login ask us to set a new password.
            $this->handleRedirect();
            self::assertHtmlContains('form fieldset legend', 'Set the admin password');

            // We set the password: error.
            $this->handlePost(['password' => 'foo'], csrfToken: 'wrong-csrf-token');
            self::assertHtmlNotice('Invalid CSRF token.');

            // We set the password: error.
            $this->handlePost(['password' => 'foo']);
            self::assertHtmlNotice('The minimum length of the password is 8.');

            // We set the password: OK.
            $this->handlePost(['password' => 'foobarbaz123']);
            self::assertRedirect('/admin/login');

            // The login ask us to set a new password.
            $this->handleRedirect();
            self::assertHtmlContains('form fieldset legend', 'Login');

            // Login: error.
            $this->handlePost(['password' => 'foo']);
            self::assertHtmlNotice('Wrong password provided.');

            // Login: OK.
            $this->handlePost(['password' => 'foobarbaz123']);
            self::assertRedirect('/admin/');

            // Listing: root.
            $this->handleRedirect();
            self::assertListingContains([
                '@data/',
                '@pages/',
                '@public/',
                '@templates/',
            ]);

            // Listing: error.
            $this->handleGet('/admin/' . $encode('@wrong-public'));
            self::assertHtmlError('Invalid scope "@wrong-public".');

            // Listing.
            $this->handleGet('/admin/' . $encode('@public/'));
            self::assertListingContains([
                'assets/',
                'index.php',
                'test.php',
            ]);

            // Create folder: form.
            $this->handleGet('/admin/' . $encode('@public/') . 'createFolder');
            self::assertHtmlContains('form label[for="name"]', 'Create folder:');

            // Create folder: submit with error.
            $this->handlePost(['name' => '../../../error']);
            self::assertHtmlError('Unauthorized access outside root paths.');

            // Create folder: form.
            $this->handleGet('/admin/' . $encode('@public/') . 'createFolder');
            self::assertHtmlContains('form label[for="name"]', 'Create folder:');

            // Create folder: submit with error.
            $this->handlePost(['name' => '']);
            self::assertHtmlNotice('The name cannot be empty.');

            // Create folder: submit.
            $this->handlePost(['name' => 'FOO']);
            self::assertRedirect('/admin/' . $encode('@public/FOO'));

            // Listing.
            $this->handleGet('/admin/' . $encode('@public/FOO'));
            self::assertListingContains([]);

            // Create file: form.
            $this->handleGet('/admin/' . $encode('@public/FOO') . 'createFile');
            self::assertHtmlContains('form label[for="name"]', 'Create file:');

            // Create editable file: submit.
            $this->handlePost(['name' => 'bar.txt']);
            self::assertRedirect('/admin/' . $encode('@public/FOO/bar.txt'));

            // Edit file: form.
            $this->handleRedirect();
            self::assertHtmlNotice('The file "bar.txt" was created.');

            // Edit file: save.
            $this->handlePost(['source' => 'this is a test']);
            self::assertRedirect('/admin/' . $encode('@public/FOO'));

            // Listing.
            $this->handleRedirect();
            self::assertListingContains(['bar.txt']);

            // Rename file: error.
            $this->handleGet('/admin/' . $encode('@public/FOO/bar.xyz') . 'rename');
            self::assertForbidden();

            // Rename file: form.
            $this->handleGet('/admin/' . $encode('@public/FOO/bar.txt') . 'rename');
            self::assertHtmlContains('form label[for="name"]', 'Rename bar.txt to:');

            // Rename file: submit.
            $this->handlePost(['name' => 'test.txt']);
            self::assertRedirect('/admin/' . $encode('@public/FOO'));

            // Listing.
            $this->handleRedirect();
            self::assertListingContains(['test.txt']);

            // Download file: error.
            $this->handleGet('/admin/' . $encode('@public/FOO/test.xyz') . 'download');
            self::assertForbidden();

            // Download file.
            $this->handleGet('/admin/' . $encode('@public/FOO/test.txt') . 'download');
            self::assertHtmlContains('', 'this is a test');

            // Download folder.
            $this->handleGet('/admin/' . $encode('@public/FOO') . 'download');
            self::assertHeader('Content-Disposition', 'attachment; filename="FOO.zip"; filename*=UTF-8\'\'FOO.zip');

            // Delete file: form.
            $this->handleGet('/admin/' . $encode('@public/FOO/test.txt') . 'delete');
            self::assertHtmlContains('p.notice', 'Are you sure to delete the test.txt file?');

            // Delete file: submit.
            $this->handlePost([]);
            self::assertRedirect('/admin/' . $encode('@public/FOO'));

            // Upload zip: form.
            $this->handleGet('/admin/' . $encode('@public/FOO') . 'upload');
            self::assertHtmlNotice('The file "test.txt" was deleted.');
            self::assertHtmlContains('form label[for="file"]', 'File(s) to upload:');

            // Upload zip: submit.
            $this->handlePost(['isZip' => '1'], uploadContent: (string) file_get_contents(__DIR__ . '/../data/test.zip'), uploadFilename: 'test.zip');
            self::assertRedirect('/admin/' . $encode('@public/FOO'));

            // Upload again the same file.
            $this->handleGet('/admin/' . $encode('@public/FOO') . 'upload');
            self::assertHtmlNotice('1 files were uploaded.');

            $this->handlePost(['isZip' => '1'], uploadContent: (string) file_get_contents(__DIR__ . '/../data/test.zip'), uploadFilename: 'test.zip');
            self::assertHtmlContains('td.text-warning:nth-child(1)', 'test.txt');
            self::assertHtmlContains('td.text-warning:nth-child(2)', '[test.zip] Overridden.');

            // Delete folder: form.
            $this->handleGet('/admin/' . $encode('@public/FOO') . 'delete');
            self::assertHtmlContains('p.notice', 'Are you sure to delete the FOO folder?');

            // Delete folder: submit.
            $this->handlePost([]);
            self::assertRedirect('/admin/' . $encode('@public'));

            // Listing.
            $this->handleRedirect();
            self::assertListingContains([
                'assets/',
                'index.php',
                'test.php',
            ]);

            // Redirects.
            $this->handleGet('/admin/redirects');
            self::assertHtmlContains('main', 'You must provide a valid YAML list of pairs from and link');

            // Redirects: submit error.
            $this->handlePost(['source' => '123']);
            self::assertHtmlNotice('The YAML must be a list.');

            // Redirects: submit error.
            $this->handlePost(['source' => '- { foo: foo } zzzz']);
            self::assertHtmlNotice('yaml_parse(): parsing error encountered during parsing');

            // Redirects: submit error.
            $this->handlePost(['source' => '- { foo: foo }']);
            self::assertHtmlNotice('Missing "from".');

            // Redirects: submit.
            $this->handlePost(['source' => '- { from: "/foo/", link: "/bar" }' . "\n" . '- { from: "/qux", link: "/quux" }']);
            self::assertRedirect('/admin/');

            // Redirects: tests
            $this->handleGet('/foo');
            self::assertNotFound();

            $this->handleGet('/foo/');
            self::assertRedirect('/bar', 301);

            $this->handleGet('/qux');
            self::assertRedirect('/quux', 301);

            $this->handleGet('/qux/');
            self::assertRedirect('/quux', 301);

            // Logout.
            $this->handleGet('/admin/logout');
            self::assertRedirect('/admin/');
        } finally {
            $this->yamlAdminConfigRestore();
        }
    }

    private static function assertNotFound(): void
    {
        self::assertSame(404, self::$response->getStatusCode());
    }

    private static function assertRedirect(string $uri, int $status = 302): void
    {
        self::assertSame($status, self::$response->getStatusCode());
        self::assertSame($uri, self::$redirect = self::$response->getHeaderLine('Location'));
    }

    private static function assertHtmlNotice(string $text): void
    {
        $crawler = new Crawler(self::$contents);
        self::assertSame(200, self::$response->getStatusCode());
        self::assertStringContainsString($text, $crawler->filter('form p mark')->text());
    }

    private static function assertHtmlError(string $text): void
    {
        $crawler = new Crawler(self::$contents);
        self::assertSame(400, self::$response->getStatusCode());
        self::assertStringContainsString($text, $crawler->filter('p.notice code')->text());
    }

    private static function assertForbidden(string $text = 'Forbidden.'): void
    {
        $crawler = new Crawler(self::$contents);
        self::assertSame(403, self::$response->getStatusCode());
        self::assertStringContainsString($text, $crawler->filter('p.notice code')->text());
    }

    private static function assertHtmlContains(string $selector, string $text): void
    {
        $crawler = new Crawler(self::$contents);
        self::assertSame(200, self::$response->getStatusCode());
        self::assertStringContainsString($text, $crawler->filter($selector)->text());
    }

    private static function assertHeader(string $header, string $value): void
    {
        self::assertSame(200, self::$response->getStatusCode());
        self::assertSame($value, self::$response->getHeaderLine($header));
    }

    /**
     * @param array<mixed> $values
     */
    private static function assertListingContains(array $values): void
    {
        $crawler = new Crawler(self::$contents);
        self::assertSame(200, self::$response->getStatusCode());
        self::assertSame(
            $values,
            array_map(
                fn (\DOMNode $DOMNode) => trim($DOMNode->textContent),
                iterator_to_array($crawler->filter('table tr td:first-child > .node')),
            ),
        );
    }

    private function handleRedirect(): void
    {
        $this->handle('GET', self::$redirect);
    }

    private function handleGet(string $uri): void
    {
        $this->handle('GET', $uri);
    }

    /**
     * @param array<mixed> $data
     */
    private function handlePost(array $data, ?string $csrfToken = null, string $uploadContent = '', string $uploadFilename = ''): void
    {
        $crawler = new Crawler(self::$contents);
        $action = $crawler->filter('form')->attr('action');
        $csrfToken ??= $crawler->filter('form input[name="csrf_token"]')->attr('value');

        self::assertNotNull($action);
        $this->handle('POST', $action, $data + ['csrf_token' => $csrfToken], $uploadContent, $uploadFilename);
    }

    /**
     * @param array<mixed> $data
     */
    private function handle(string $method, string $uri, array $data = [], string $uploadContent = '', string $uploadFilename = ''): void
    {
        self::resetContainer();
        $config = self::createConfigDemo();
        $request = new ServerRequest($method, '/' . ltrim($uri, '/'));

        if ('POST' === $method) {
            $request = $request
                ->withParsedBody($data)
                ->withBody(Stream::create(http_build_query($data)))
                ->withHeader('Content-Type', 'application/x-www-form-urlencoded');
        }

        if ('' !== $uploadFilename) {
            $tempFile = uniqid('/tmp/upload', true) . '.zip';
            file_put_contents($tempFile, $uploadContent);
            $request = $request->withUploadedFiles([
                'files' => [
                    new UploadedFile(
                        $tempFile,
                        \strlen($uploadContent),
                        UPLOAD_ERR_OK,
                        $uploadFilename,
                    ),
                ],
            ]);
        }

        ob_start();
        self::$response = SimpleSite::handle($config, $request);
        self::$contents = self::$response->getBody()->getContents();
        ob_end_clean();
    }

    private function yamlAdminConfigBackup(): void
    {
        if (is_file(self::ADMIN_CONFIG)) {
            copy(self::ADMIN_CONFIG, self::ADMIN_CONFIG_BKUP);
            @unlink(self::ADMIN_CONFIG);
        }
    }

    private function yamlAdminConfigRestore(): void
    {
        if (is_file(self::ADMIN_CONFIG_BKUP)) {
            copy(self::ADMIN_CONFIG_BKUP, self::ADMIN_CONFIG);
            @unlink(self::ADMIN_CONFIG_BKUP);
        }
    }
}
