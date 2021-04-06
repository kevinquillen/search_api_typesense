<?php

namespace Drupal\search_api_typesense\Client;

/**
 * Interface SearchApiTypesenseClientInterface.
 *
 * @package Drupal\search_api_typesense\Client
 */
interface SearchApiTypesenseClientFactoryInterface {

  /**
   * Returns an instance of Typesense\Client.
   *
   * @param array $settings
   *   The settings for a Typesense server connection.
   *
   * @return Typesense\Client
   *   A Typesense client.
   *
   * @see https://typesense.org/docs/0.19.0/api/authentication.html
   */
  public function getInstance(array $settings);

}
