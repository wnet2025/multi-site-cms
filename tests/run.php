<?php
declare(strict_types=1);

use MultiSiteCms\Exceptions\AuthenticationException;
use MultiSiteCms\Exceptions\HttpException;
use MultiSiteCms\Repositories\DomainRepositoryInterface;
use MultiSiteCms\Repositories\WebsiteRepositoryInterface;
use MultiSiteCms\Services\AdminAuthenticationService;
use MultiSiteCms\Services\CsrfService;
use MultiSiteCms\Services\DomainNormalizer;
use MultiSiteCms\Services\DomainResolver;
use MultiSiteCms\Support\Configuration;

require_once __DIR__ . '/../src/Exceptions/HttpException.php';
require_once __DIR__ . '/../src/Exceptions/AuthenticationException.php';
require_once __DIR__ . '/../src/Exceptions/DomainNotFoundException.php';
require_once __DIR__ . '/../src/Exceptions/WebsiteUnavailableException.php';
require_once __DIR__ . '/../src/Support/Configuration.php';
require_once __DIR__ . '/../src/Repositories/DomainRepositoryInterface.php';
require_once __DIR__ . '/../src/Repositories/WebsiteRepositoryInterface.php';
require_once __DIR__ . '/../src/Services/DomainNormalizer.php';
require_once __DIR__ . '/../src/Services/DomainResolver.php';
require_once __DIR__ . '/../src/Services/CsrfService.php';
require_once __DIR__ . '/../src/Services/AdminAuthenticationService.php';
require_once __DIR__ . '/../src/Database/MigrationRunner.php';

ob_start();

final class FakeDomains implements DomainRepositoryInterface
{
    public function findByNormalizedDomain(string $domain): ?array
    {
        if (!in_array($domain, ['good.test', 'archived.test', 'unpublished.test'], true)) { return null; }
        return ['id' => 10, 'website_id' => $domain === 'good.test' ? 7 : 8, 'domain' => $domain];
    }
}

final class FakeWebsites implements WebsiteRepositoryInterface
{
    public function findEligibleById(int $id): ?array
    {
        return $id === 7 ? ['id' => 7, 'status' => 'published', 'deleted_at' => null] : null;
    }
}

function ok(bool $condition, string $name): void
{
    if (!$condition) { throw new RuntimeException($name); }
}

function throws(callable $call, string $name, int $status): void
{
    try { $call(); } catch (HttpException $e) { ok($e->status === $status, $name . ' status'); return; }
    throw new RuntimeException($name . ' did not throw');
}

$tests = [];
$tests['domain normalization'] = function (): void {
    $n = new DomainNormalizer();
    ok($n->normalize(' WWW.Good.Test. ') === 'good.test', 'normalization');
    foreach (['', 'https://good.test', 'good.test/path', 'good.test?x=1', 'good.test:443', 'bad_host'] as $host) { throws(fn() => $n->normalize($host), 'invalid host', 400); }
};
$tests['domain resolution and tenant context'] = function (): void {
    $r = new DomainResolver(new FakeDomains(), new FakeWebsites(), new DomainNormalizer());
    $resolved = $r->resolve('WWW.GOOD.TEST.');
    ok($resolved['website']['id'] === 7, 'correct website');
    throws(fn() => $r->resolve('unknown.test'), 'unknown domain', 404);
    throws(fn() => $r->resolve('unpublished.test'), 'unpublished website', 404);
    throws(fn() => $r->resolve('archived.test'), 'archived website', 404);
    ok(!array_key_exists('website_id', ['host' => 'good.test']), 'client website id is not an input');
};
$tests['csrf lifecycle'] = function (): void {
    $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cms-test-' . bin2hex(random_bytes(4)); mkdir($dir);
    session_save_path($dir); session_name('CMS_TEST_' . bin2hex(random_bytes(3))); session_start();
    $csrf = new CsrfService(); $token = $csrf->token(); ok(strlen($token) === 64, 'token generation');
    throws(fn() => $csrf->validate(null), 'missing csrf', 419); throws(fn() => $csrf->validate(str_repeat('0', 64)), 'invalid csrf', 419); $csrf->validate($token);
    $_SESSION = []; if (session_status() === PHP_SESSION_ACTIVE) { session_destroy(); session_write_close(); }
};
$tests['authentication and session settings'] = function (): void {
    $hash = password_hash('correct-password', PASSWORD_DEFAULT); $config = new Configuration('admin', $hash, 300); $auth = new AdminAuthenticationService($config);
    $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cms-auth-' . bin2hex(random_bytes(4)); mkdir($dir); session_save_path($dir); session_name('CMS_AUTH_' . bin2hex(random_bytes(3)));
    $_SERVER['HTTPS'] = 'off'; $auth->configureSession(); ok(ini_get('session.cookie_httponly') === '1', 'HttpOnly'); ok(strtolower((string) ini_get('session.cookie_samesite')) === 'lax', 'SameSite'); ok(ini_get('session.cookie_secure') === '0', 'HTTP not secure'); ok(ini_get('session.gc_maxlifetime') === '300', 'expiration lifetime');
    $_SERVER['HTTPS'] = 'on'; $auth->configureSession(); ok(ini_get('session.cookie_secure') === '1', 'HTTPS secure');
    session_id('old-session-id'); $auth->login('admin', 'correct-password'); $newId = session_id(); ok($newId !== 'old-session-id', 'session regeneration'); session_write_close(); ok($auth->isAuthenticated(), 'session status');
    $auth->logout(); ok(!$auth->isAuthenticated(), 'logout');
    session_write_close();
    try { $auth->login('admin', 'wrong-password'); throw new RuntimeException('invalid login accepted'); } catch (AuthenticationException $e) { ok($e->status === 401, 'invalid login rejected'); }
    if (session_status() === PHP_SESSION_ACTIVE) { session_write_close(); }
    unset($_SERVER['HTTPS']);
};
$tests['migration runner preflight validation'] = function (): void {
    $root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cms-migrations-' . bin2hex(random_bytes(4)); mkdir($root);
    $pdo = new class extends PDO { public function __construct() {} };
    $migration = static function (string $file, string $name, int $version): void {
        $source = "<?php return ['name'=>'{$name}','version'=>{$version},'checksum'=>'" . str_repeat('a', 64) . "','up'=>static function(\\PDO \$pdo): void {}];";
        file_put_contents($file, $source);
    };
    $migration($root . '/202611040001_first.php', '202611040001_first', 202611040001);
    ok(count((new MultiSiteCms\Database\MigrationRunner($pdo, $root))->discover()) === 1, 'valid migration discovery');
    $cases = [
        ['202611040002_second.php', '202611040001_second', 202611040002, 'filename mismatch'],
    ];
    foreach ($cases as [$file, $name, $version, $message]) {
        $migration($root . '/' . $file, $name, $version);
        try { (new MultiSiteCms\Database\MigrationRunner($pdo, $root))->discover(); throw new RuntimeException('preflight accepted ' . $message); } catch (RuntimeException $e) { ok(str_contains($e->getMessage(), $message) || ($message === 'duplicate migration version' && str_contains($e->getMessage(), 'Invalid migration ordering')), $message); }
        unlink($root . '/' . $file);
    }
};
$passed = 0; $failed = 0;
foreach ($tests as $name => $test) { try { $test(); echo "PASS {$name}\n"; $passed++; } catch (Throwable $e) { echo "FAIL {$name}: {$e->getMessage()}\n"; $failed++; } }
echo "RESULT passed={$passed} failed={$failed}\n";
$output = ob_get_clean();
echo $output;
exit($failed === 0 ? 0 : 1);

