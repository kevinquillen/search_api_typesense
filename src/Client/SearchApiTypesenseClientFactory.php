<?php

namespace Drupal\search_api_typesense\Client;

use Drupal\search_api_typesense\Api\SearchApiTypesenseException;
use Typesense\Client;

/**
 * The Search Api Typesense client factory.
 *
 * @package Drupal\search_api_typesense\Client
 */
class SearchApiTypesenseClientFactory implements SearchApiTypesenseClientFactoryInterface {

  /**
   * {@inheritdoc}
   */
  public function getInstance(array $settings) {
    try {
      return new Client($settings);
    }
    catch (\Exception $e) {
      throw new SearchApiTypesenseException($e->getMessage(), $e->getCode(), $e);
    }
  }

}
