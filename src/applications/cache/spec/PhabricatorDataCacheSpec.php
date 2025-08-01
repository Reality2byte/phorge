<?php

final class PhabricatorDataCacheSpec extends PhabricatorCacheSpec {

  private $cacheSummary;

  public function setCacheSummary(array $cache_summary) {
    $this->cacheSummary = $cache_summary;
    return $this;
  }

  public function getCacheSummary() {
    return $this->cacheSummary;
  }

  public static function getActiveCacheSpec() {
    $spec = new PhabricatorDataCacheSpec();

    if (extension_loaded('apcu')) {
      $spec->initAPCuSpec();
    } else {
      $spec->initNoneSpec();
    }

    return $spec;
  }

  private function initAPCuSpec() {
    $this
      ->setName(pht('APCu'))
      ->setVersion(phpversion('apcu'));

    if (ini_get('apc.enabled')) {
      if (function_exists('apcu_clear_cache')) {
        $clear_callback = 'apcu_clear_cache';
      }

      $this
        ->setIsEnabled(true)
        ->setClearCacheCallback($clear_callback);
      $this->initAPCCommonSpec();
    } else {
      $this->setIsEnabled(false);
      $this->raiseEnableAPCIssue();
    }
  }

  private function initNoneSpec() {
    $message = pht(
      'Installing the "APCu" PHP extension will improve performance. '.
      'This extension is strongly recommended. Without it, this software '.
      'must rely on a very inefficient disk-based cache.');

    $this
      ->newIssue('extension.apcu')
      ->setShortName(pht('APCu'))
      ->setName(pht('PHP Extension "APCu" Not Installed'))
      ->setMessage($message)
      ->addPHPExtension('apcu');
  }

  private function initAPCCommonSpec() {
    $state = array();

    if (function_exists('apcu_sma_info')) {
      $mem = apcu_sma_info();
      $info = apcu_cache_info();
    } else {
      $mem = null;
    }

    if ($mem) {
      $this->setTotalMemory($mem['num_seg'] * $mem['seg_size']);

      $this->setUsedMemory($info['mem_size']);
      $this->setEntryCount(count($info['cache_list']));

      $cache = $info['cache_list'];
      $state = array();
      foreach ($cache as $item) {
        // Some older versions of APCu report the cachekey as "key", while
        // newer APCu report it as "info". Just check both indexes for
        // compatibility. See T13164 for details.

        $info = idx($item, 'info');
        if ($info === null) {
          $info = idx($item, 'key');
        }

        if ($info === null) {
          $key = '<unknown-key>';
        } else {
          $key = self::getKeyPattern($info);
        }

        if (empty($state[$key])) {
          $state[$key] = array(
            'max' => 0,
            'total' => 0,
            'count' => 0,
          );
        }
        $state[$key]['max'] = max($state[$key]['max'], $item['mem_size']);
        $state[$key]['total'] += $item['mem_size'];
        $state[$key]['count']++;
      }
    }

    $this->setCacheSummary($state);
  }

  private static function getKeyPattern($key) {
    // If this key isn't in the current cache namespace, don't reveal any
    // information about it.
    $namespace = PhabricatorEnv::getEnvConfig('phabricator.cache-namespace');
    if (strncmp($key, $namespace.':', strlen($namespace) + 1)) {
      return '<other-namespace>';
    }

    $key = preg_replace('/(?<![a-zA-Z])\d+(?![a-zA-Z])/', 'N', $key);
    $key = preg_replace('/PHID-[A-Z]{4}-[a-z0-9]{20}/', 'PHID', $key);

    // TODO: We should probably standardize how digests get embedded into cache
    // keys to make this rule more generic.
    $key = preg_replace('/:celerity:.*$/', ':celerity:X', $key);
    $key = preg_replace('/:pkcs8:.*$/', ':pkcs8:X', $key);

    return $key;
  }
}
