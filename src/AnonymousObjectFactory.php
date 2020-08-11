<?php

declare(strict_types=1);

namespace TTBooking\Accounting;

use Closure;
use ReflectionClass;
use RuntimeException;

final class AnonymousObjectFactory
{
    /** @var string */
    private static string $tempDir;

    /** @var string */
    private string $extends;

    /** @var string[] */
    private array $implements = [];

    /** @var string[] */
    private array $uses = [];

    /**
     * @param string $path
     *
     * @return string
     */
    public static function getTempPath(string $path = ''): string
    {
        return (self::$tempDir ?: sys_get_temp_dir()).($path ? DIRECTORY_SEPARATOR.$path : $path);
    }

    /**
     * @param string $tempDir
     *
     * @return void
     */
    public static function setTempDirectory(string $tempDir = ''): void
    {
        self::$tempDir = $tempDir;
    }

    /**
     * Anonymous object factory constructor.
     *
     * @param string ...$dependencies
     */
    public function __construct(string ...$dependencies)
    {
        array_walk($dependencies, [__CLASS__, 'sanitizeDependency']);

        $inheritanceCount = 0;
        foreach ($dependencies as $dependency) {
            $depRefl = new ReflectionClass($dependency);
            switch (true) {
                case $depRefl->isInterface():
                    $this->implements[] = $dependency;
                    break;
                case $depRefl->isTrait():
                    $this->uses[] = $dependency;
                    break;
                default:
                    if (++$inheritanceCount > 1) {
                        throw new RuntimeException('Multiple inheritance unsupported by the language.');
                    }
                    $this->extends = $dependency;
            }
        }
    }

    /**
     * @param mixed ...$constructorArgs
     *
     * @return object
     */
    public function __invoke(...$constructorArgs): object
    {
        return call_user_func_array($this->loadClassFactory(), $constructorArgs);
    }

    /**
     * @return Closure
     */
    private function loadClassFactory(): Closure
    {
        return require $this->ensureFactoryExists();
    }

    /**
     * @return string
     */
    private function ensureFactoryExists(): string
    {
        $code = $this->formatFactoryStub();

        if (file_exists($path = self::getTempPath('factory-'.sha1($code).'.php'))) {
            return $path;
        }

        file_put_contents($path, $code);

        return $path;
    }

    /**
     * @return string
     */
    private function formatFactoryStub(): string
    {
        $replacements = [
            $this->extends ? ' extends '.$this->extends : '',
            $this->implements ? ' implements '.implode(', ', $this->implements) : '',
            $this->uses ? ' use '.implode(', ', $this->uses).';' : '',
        ];

        return str_replace(['{{ extends }}', '{{ implements }}', '{{ uses }}'], $replacements, $this->getFactoryStub());
    }

    /**
     * @return string
     */
    private function getFactoryStub(): string
    {
        return '<?php return fn (...$args) => new class(...$args){{ extends }}{{ implements }} {{{ uses }}};';
    }

    /**
     * @param string $dependency
     *
     * @throws RuntimeException
     *
     * @return void
     */
    private static function sanitizeDependency(string $dependency): void
    {
        if (! preg_match('/(?:\w|\\\\)+/', $dependency)) {
            throw new RuntimeException('Invalid dependency has been provided.');
        }
    }
}
