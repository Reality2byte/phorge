<?php

/**
 * Interface to a key-value cache like Memcache or APCu. This class provides a
 * uniform interface to multiple different key-value caches and integration
 * with PhutilServiceProfiler.
 *
 * @task  kvimpl    Key-Value Cache Implementation
 */
abstract class PhutilKeyValueCache extends Phobject {


/* -(  Key-Value Cache Implementation  )------------------------------------- */


  /**
   * Determine if the cache is available. For example, the APCu cache tests if
   * APCu is installed. If this method returns false, the cache is not
   * operational and can not be used.
   *
   * @return bool True if the cache can be used.
   * @task kvimpl
   */
  public function isAvailable() {
    return false;
  }


  /**
   * Get a single key from cache. See @{method:getKeys} to get multiple keys at
   * once.
   *
   * @param   string  $key Key to retrieve.
   * @param   wild    $default (optional) Value to return if the key is not
   *                  found. By default, returns null.
   * @return  wild    Cache value (on cache hit) or default value (on cache
   *                  miss).
   * @task kvimpl
   */
  final public function getKey($key, $default = null) {
    $map = $this->getKeys(array($key));
    return idx($map, $key, $default);
  }


  /**
   * Set a single key in cache. See @{method:setKeys} to set multiple keys at
   * once.
   *
   * See @{method:setKeys} for a description of TTLs.
   *
   * @param   string    $key Key to set.
   * @param   wild      $value Value to set.
   * @param   int|null  $ttl (optional) TTL.
   * @return  $this
   * @task kvimpl
   */
  final public function setKey($key, $value, $ttl = null) {
    return $this->setKeys(array($key => $value), $ttl);
  }


  /**
   * Delete a key from the cache. See @{method:deleteKeys} to delete multiple
   * keys at once.
   *
   * @param   string $key Key to delete.
   * @return  $this
   * @task kvimpl
   */
  final public function deleteKey($key) {
    return $this->deleteKeys(array($key));
  }


  /**
   * Get data from the cache.
   *
   * @param   list<string>        $keys List of cache keys to retrieve.
   * @return  array<string, wild> Dictionary of keys that were found in the
   *                              cache. Keys not present in the cache are
   *                              omitted, so you can detect a cache miss.
   * @task kvimpl
   */
  abstract public function getKeys(array $keys);


  /**
   * Put data into the key-value cache.
   *
   * With a TTL ("time to live"), the cache will automatically delete the key
   * after a specified number of seconds. By default, there is no expiration
   * policy and data will persist in cache indefinitely.
   *
   * @param array<string, wild> $keys Map of cache keys to values.
   * @param int|null            $ttl (optional) TTL for cache keys, in seconds.
   * @return $this
   * @task kvimpl
   */
  abstract public function setKeys(array $keys, $ttl = null);


  /**
   * Delete a list of keys from the cache.
   *
   * @param list<string> $keys List of keys to delete.
   * @return $this
   * @task kvimpl
   */
  abstract public function deleteKeys(array $keys);


  /**
   * Completely destroy all data in the cache.
   *
   * @return $this
   * @task kvimpl
   */
  abstract public function destroyCache();

}
