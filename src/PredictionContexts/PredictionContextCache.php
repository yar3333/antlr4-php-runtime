<?php

namespace Antlr4\PredictionContexts;

class PredictionContextCache
{
    /**
     * @var \SplObjectStorage
     */
    private $cache;

    function __construct()
    {
        $this->cache = new \SplObjectStorage();
    }

    // Add a context to the cache and return it. If the context already exists,
    // return that one instead and do not add a new context to the cache.
    // Protect shared cache from unsafe thread access.
    function add(PredictionContext $ctx) : PredictionContext
    {
        if ($ctx === PredictionContext::EMPTY()) return PredictionContext::EMPTY();
        $this->cache->attach($ctx);
        return $ctx;
    }

    function get(PredictionContext $ctx) : ?PredictionContext
    {
        return $this->cache->offsetExists($ctx)  ? $this->cache->offsetGet($ctx) : null;
    }

    function size() : int
    {
        return $this->cache->count();
    }
}