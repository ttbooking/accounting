<?php

declare(strict_types=1);

namespace TTBooking\Accounting;

final class ExtensionLoader
{
    /**
     * Indicates if a loader has been registered.
     *
     * @var bool
     */
    private static bool $registered = false;

    /**
     * The namespace for all real-time events.
     *
     * @var string
     */
    private static string $eventNamespace = 'TTBooking\\AccountingExtensions\\Events\\';

    /**
     * Load a class alias if it is registered.
     *
     * @param string $alias
     *
     * @return bool|null
     */
    public static function load(string $alias): ?bool
    {
        if (self::$eventNamespace && strpos($alias, self::$eventNamespace) === 0) {
            self::loadEvent($alias);

            return true;
        }
    }

    /**
     * Load a real-time event for the given alias.
     *
     * @param string $alias
     *
     * @return void
     */
    private static function loadEvent(string $alias): void
    {
        require self::ensureEventExists($alias);
    }

    /**
     * Ensure that the given alias has an existing real-time event class.
     *
     * @param string $alias
     *
     * @return string
     */
    private static function ensureEventExists(string $alias): string
    {
        if (file_exists($path = storage_path('framework/cache/event-'.sha1($alias).'.php'))) {
            return $path;
        }

        file_put_contents($path, self::formatEventStub(
            $alias, file_get_contents(__DIR__.'/stubs/event.stub')
        ));

        return $path;
    }

    /**
     * Format the event stub with the proper namespace and class.
     *
     * @param string $alias
     * @param string $stub
     *
     * @return string
     */
    private static function formatEventStub(string $alias, string $stub): string
    {
        $replacements = [
            str_replace('/', '\\', dirname(str_replace('\\', '/', $alias))),
            class_basename($alias),
            substr($alias, strlen(self::$eventNamespace)),
        ];

        return str_replace(
            ['DummyNamespace', 'DummyClass', 'DummyTarget'], $replacements, $stub
        );
    }

    /**
     * Register the loader on the auto-loader stack.
     *
     * @return void
     */
    public static function register(): void
    {
        if (! self::$registered) {
            self::appendToLoaderStack();

            self::$registered = true;
        }
    }

    /**
     * Indicates if the loader has been registered.
     *
     * @return bool
     */
    public static function isRegistered(): bool
    {
        return self::$registered;
    }

    /**
     * Set the "registered" state of the loader.
     *
     * @param bool $value
     *
     * @return void
     */
    public static function setRegistered(bool $value): void
    {
        self::$registered = $value;
    }

    /**
     * Set the real-time event namespace.
     *
     * @param string $namespace
     *
     * @return void
     */
    public static function setEventNamespace(string $namespace): void
    {
        self::$eventNamespace = rtrim($namespace, '\\').'\\';
    }

    /**
     * Append the load method to the auto-loader stack.
     *
     * @return void
     */
    private static function appendToLoaderStack(): void
    {
        spl_autoload_register([__CLASS__, 'load']);
    }
}
