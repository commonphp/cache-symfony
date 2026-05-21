<?php

declare(strict_types=1);

namespace CommonPHP\Drivers\Cache\Symfony\Tests\Unit;

use CommonPHP\Cache\CacheItem;
use CommonPHP\Cache\CacheManager;
use CommonPHP\Cache\Contracts\CacheDriverInterface;
use CommonPHP\Drivers\Cache\Symfony\Exceptions\SymfonyCacheConfigurationException;
use CommonPHP\Drivers\Cache\Symfony\Exceptions\SymfonyCacheDriverException;
use CommonPHP\Drivers\Cache\Symfony\SymfonyAdapterFactory;
use CommonPHP\Drivers\Cache\Symfony\SymfonyCacheDriver;
use CommonPHP\Drivers\Cache\Symfony\SymfonyCacheKeyMapper;
use CommonPHP\Drivers\Cache\Symfony\SymfonyCacheOptions;
use CommonPHP\Runtime\Contracts\DriverInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

final class SymfonyCacheDriverTest extends TestCase
{
    /**
     * @var list<string>
     */
    private array $temporaryDirectories = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryDirectories as $directory) {
            $this->removeDirectory($directory);
        }

        $this->temporaryDirectories = [];
    }

    public function testItStoresFetchesAndChecksItems(): void
    {
        $driver = new SymfonyCacheDriver();
        $item = CacheItem::create('user.42', 'payload', 60);

        self::assertInstanceOf(CacheDriverInterface::class, $driver);
        self::assertInstanceOf(DriverInterface::class, $driver);
        self::assertFalse($driver->has('user.42'));

        $driver->store($item);

        self::assertTrue($driver->has('user.42'));
        self::assertEquals($item, $driver->fetch('user.42'));
    }

    public function testItDeletesAndClearsOnlyMappedDriverKeys(): void
    {
        $adapter = new ArrayAdapter();
        $driver = new SymfonyCacheDriver($adapter, new SymfonyCacheKeyMapper('tenant-a.'));
        $otherDriver = new SymfonyCacheDriver($adapter, new SymfonyCacheKeyMapper('tenant-b.'));

        $driver->store(CacheItem::create('one', '1'));
        $driver->store(CacheItem::create('two', '2'));
        $otherDriver->store(CacheItem::create('one', 'external'));

        $driver->delete('one');

        self::assertFalse($driver->has('one'));
        self::assertTrue($driver->has('two'));
        self::assertTrue($otherDriver->has('one'));

        $driver->clear();

        self::assertFalse($driver->has('two'));
        self::assertTrue($otherDriver->has('one'));
    }

    public function testStoringExpiredItemRemovesExistingItem(): void
    {
        $driver = new SymfonyCacheDriver();
        $driver->store(CacheItem::create('key', 'fresh', 60));
        $driver->store(CacheItem::create('key', 'stale', 0));

        self::assertFalse($driver->has('key'));
        self::assertNull($driver->fetch('key'));
    }

    public function testItMapsCommonPhpKeysWithSymfonyReservedCharacters(): void
    {
        $driver = new SymfonyCacheDriver();
        $key = 'tenant/{acme}/users(42)@profile';
        $item = CacheItem::create($key, 'payload', 60);

        $driver->store($item);

        $mappedKey = $driver->getKeyMapper()->map($key);

        self::assertStringStartsWith(SymfonyCacheOptions::DEFAULT_KEY_PREFIX, $mappedKey);
        self::assertStringNotContainsString('/', $mappedKey);
        self::assertStringNotContainsString('{', $mappedKey);
        self::assertStringNotContainsString('}', $mappedKey);
        self::assertStringNotContainsString('(', $mappedKey);
        self::assertStringNotContainsString(')', $mappedKey);
        self::assertStringNotContainsString('@', $mappedKey);
        self::assertEquals($item, $driver->fetch($key));
    }

    public function testItRejectsUnexpectedValuesStoredUnderMappedKeys(): void
    {
        $adapter = new ArrayAdapter();
        $driver = new SymfonyCacheDriver($adapter);
        $symfonyItem = $adapter->getItem($driver->getKeyMapper()->map('key'));
        $symfonyItem->set('not-a-commonphp-cache-item');
        $adapter->save($symfonyItem);

        $this->expectException(SymfonyCacheDriverException::class);

        $driver->fetch('key');
    }

    public function testFilesystemAdapterRoundTripsCommonPhpItems(): void
    {
        $driver = new SymfonyCacheDriver(SymfonyCacheOptions::filesystem(
            directory: $this->temporaryDirectory(),
            namespace: 'cache_tests',
        ));
        $item = CacheItem::create('filesystem/item', 'payload', 60);

        $driver->store($item);

        self::assertEquals($item, $driver->fetch('filesystem/item'));
    }

    public function testFactoryCreatesManagersDriversAndAdaptersFromOptions(): void
    {
        $factory = new SymfonyAdapterFactory();
        $manager = $factory->fromArray([
            'adapter' => 'array',
            'key_prefix' => 'factory.',
            'default_lifetime' => 30,
        ]);
        $driver = $factory->driver(SymfonyCacheOptions::array(keyPrefix: 'driver.'));
        $adapter = $factory->adapter(SymfonyCacheOptions::filesystem(
            directory: $this->temporaryDirectory(),
            namespace: 'cache_tests',
        ));

        self::assertInstanceOf(CacheManager::class, $manager);
        self::assertInstanceOf(SymfonyCacheDriver::class, $driver);
        self::assertInstanceOf(FilesystemAdapter::class, $adapter);
    }

    public function testInvalidOptionsThrowConfigurationExceptions(): void
    {
        $this->expectException(SymfonyCacheConfigurationException::class);

        SymfonyCacheOptions::fromArray(['adapter' => 'unknown']);
    }

    private function temporaryDirectory(): string
    {
        $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'commonphp-symfony-cache-' . bin2hex(random_bytes(8));

        if (!mkdir($directory, 0777, true) && !is_dir($directory)) {
            self::fail('Could not create temporary directory for Symfony cache tests.');
        }

        $this->temporaryDirectories[] = $directory;

        return $directory;
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = scandir($directory);

        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $item;

            if (is_dir($path)) {
                $this->removeDirectory($path);

                continue;
            }

            @unlink($path);
        }

        @rmdir($directory);
    }
}
