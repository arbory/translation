<?php namespace Waavi\Translation\Cache;

use Illuminate\Cache\TaggableStore;
use Illuminate\Contracts\Cache\Store;

class RepositoryFactory
{
    public static function make(Store $store, $cacheTag)
    {
        return $store instanceof TaggableStore ? new TaggedRepository($store, $cacheTag) : new SimpleRepository($store, $cacheTag);
    }
}
