<?php namespace Waavi\Translation\Cache;

use Illuminate\Contracts\Cache\Store;

class SimpleRepository implements CacheRepositoryInterface
{
    /**
     * The cache store implementation.
     *
     * @var \Illuminate\Contracts\Cache\Store
     */
    protected $store;

    /**
     * The translation cache tag
     *
     * @var string
     */
    protected $cacheTag;

    /**
     * Create a new cache repository instance.
     *
     * @param  \Illuminate\Contracts\Cache\Store  $store
     * @return void
     */
    public function __construct(Store $store, $cacheTag)
    {
        $this->store    = $store;
        $this->cacheTag = $cacheTag;
    }

    /**
     *  Checks if an entry with the given key exists in the cache.
     *
     *  @param  string  $locale
     *  @param  string  $group
     *  @param  string  $namespace
     *  @return boolean
     */
    public function has($locale, $group, $namespace)
    {
        return !is_null($this->get($locale, $group, $namespace));
    }

    /**
     *  Get an item from the cache
     *
     *  @param  string  $locale
     *  @param  string  $group
     *  @param  string  $namespace
     *  @return mixed
     */
    public function get($locale, $group, $namespace)
    {
        $key = $this->getKey($locale, $group, $namespace);
        return $this->store->get($key);
    }

    /**
     *  Put an item into the cache store
     *
     *  @param  string  $locale
     *  @param  string  $group
     *  @param  string  $namespace
     *  @param  mixed   $content
     *  @param  integer $minutes
     *  @return void
     */
    public function put($locale, $group, $namespace, $content, $minutes)
    {
        $key = $this->getKey($locale, $group, $namespace);
        // Store::put() takes the timeout in seconds.
        $this->store->put($key, $content, $minutes * 60);
    }

    /**
     *  Flush the cache for the given entries
     *
     *  @param  string  $locale
     *  @param  string  $group
     *  @param  string  $namespace
     *  @return void
     */
    public function flush($locale, $group, $namespace)
    {
        $this->store->forget($this->getKey($locale, $group, $namespace));
    }

    /**
     *  Flush all translation entries without clearing the rest of the cache store.
     *  Bumping the version invalidates every translation key at once; orphaned
     *  entries expire through their regular cache timeout.
     *
     *  @return void
     */
    public function flushAll()
    {
        $this->store->forever($this->getVersionKey(), max(time(), $this->getVersion() + 1));
    }

    /**
     *  Returns a unique cache key.
     *
     *  @param  string  $locale
     *  @param  string  $group
     *  @param  string  $namespace
     *  @return string
     */
    protected function getKey($locale, $group, $namespace)
    {
        return md5("{$this->cacheTag}-{$this->getVersion()}-{$locale}-{$group}-{$namespace}");
    }

    /**
     *  Returns the current translation cache version.
     *
     *  @return integer
     */
    protected function getVersion()
    {
        return $this->store->get($this->getVersionKey()) ?: 1;
    }

    /**
     *  Returns the cache key holding the translation cache version.
     *
     *  @return string
     */
    protected function getVersionKey()
    {
        return md5("{$this->cacheTag}-version");
    }

}
