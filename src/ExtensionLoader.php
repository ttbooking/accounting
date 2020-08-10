<?php

declare(strict_types=1);

namespace TTBooking\Accounting;

use Illuminate\Support\Str;

class ExtensionLoader
{
    /**
     * The array of events.
     *
     * @var array
     */
    protected array $events;

    /**
     * Indicates if a loader has been registered.
     *
     * @var bool
     */
    protected bool $registered = false;

    /**
     * The namespace for all real-time events.
     *
     * @var string
     */
    private static string $eventNamespace = __NAMESPACE__.'\\Contracts\\Events\\';

    /**
     * The singleton instance of the loader.
     *
     * @var static
     */
    protected static self $instance;

    /**
     * Create a new ExtensionLoader instance.
     *
     * @param array $events
     *
     * @return void
     */
    private function __construct(array $events)
    {
        $this->events = $events;
    }

    /**
     * Get or create the singleton event loader instance.
     *
     * @param array $events
     *
     * @return static
     */
    public static function getInstance(array $events = [])
    {
        if (is_null(static::$instance)) {
            return static::$instance = new static($events);
        }

        $events = array_merge(static::$instance->getEvents(), $events);

        static::$instance->setEvents($events);

        return static::$instance;
    }

    /**
     * Load a class alias if it is registered.
     *
     * @param string $alias
     *
     * @return bool|null
     */
    public function load(string $alias): ?bool
    {
        if (static::$eventNamespace && strpos($alias, static::$eventNamespace) === 0) {
            $this->loadEvent($alias);

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
    protected function loadEvent(string $alias): void
    {
        require $this->ensureEventExists($alias);
    }

    /**
     * Ensure that the given alias has an existing real-time event class.
     *
     * @param string $alias
     *
     * @return string
     */
    protected function ensureEventExists(string $alias): string
    {
        if (file_exists($path = storage_path('framework/cache/event-'.sha1($alias).'.php'))) {
            return $path;
        }

        file_put_contents($path, $this->formatEventStub(
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
    protected function formatEventStub(string $alias, string $stub): string
    {
        $event = Str::studly(str_replace('.', '\\', $alias));
        $extends = (array) $this->getEvents()[$alias] ?? [];

        $replacements = [
            str_replace('/', '\\', dirname(str_replace('\\', '/', $event))),
            class_basename($event),
            $extends ? ' extends '.implode(', ', $extends) : '',
        ];

        return str_replace(
            ['DummyNamespace', 'DummyInterface', 'DummyExtends'], $replacements, $stub
        );
    }

    /**
     * Add an event to the loader.
     *
     * @param string $event
     * @param array $extends
     *
     * @return void
     */
    public function event($event, $extends): void
    {
        $this->events[$event] = $extends;
    }

    /**
     * Register the loader on the auto-loader stack.
     *
     * @return void
     */
    public function register(): void
    {
        if (! $this->registered) {
            $this->appendToLoaderStack();

            $this->registered = true;
        }
    }

    /**
     * Append the load method to the auto-loader stack.
     *
     * @return void
     */
    protected function appendToLoaderStack(): void
    {
        spl_autoload_register([$this, 'load']);
    }

    /**
     * Get the registered events.
     *
     * @return array
     */
    public function getEvents(): array
    {
        return $this->events;
    }

    /**
     * Set the registered events.
     *
     * @param array $events
     *
     * @return void
     */
    public function setEvents(array $events): void
    {
        $this->events = $events;
    }

    /**
     * Indicates if the loader has been registered.
     *
     * @return bool
     */
    public function isRegistered(): bool
    {
        return $this->registered;
    }

    /**
     * Set the "registered" state of the loader.
     *
     * @param bool $value
     *
     * @return void
     */
    public function setRegistered(bool $value): void
    {
        $this->registered = $value;
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
        static::$eventNamespace = rtrim($namespace, '\\').'\\';
    }

    /**
     * Set the value of the singleton event loader.
     *
     * @param static $loader
     *
     * @return void
     */
    public static function setInstance(self $loader): void
    {
        static::$instance = $loader;
    }

    /**
     * Clone method.
     *
     * @return void
     */
    private function __clone()
    {
        //
    }
}
