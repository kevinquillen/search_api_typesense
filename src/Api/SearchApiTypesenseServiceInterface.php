<?php

namespace Drupal\search_api_typesense\Api;

/**
 * Interface SearchApiTypesenseServiceInterface.
 *
 * This is a minimal wrapper around parts of typesense/typesense-php that we
 * use to make things a bit simpler in SearchApiTypesenseBackend.
 *
 * In the Typesense package itself, the pattern is usually noun->verb(). So in
 * this interface, where we deal directly with they Typesense API, we define
 * the service' methods according the the pattern verbNoun(). With any luck
 * this will help make it clearer what the method call is doing with the
 * underlying Typesense methods.
 */
interface SearchApiTypesenseServiceInterface {

  /**
   * Provides getter for the connection instance.
   *
   * @return \Typesense\Client
   *   The Typesense client.
   *
   * @see https://typesense.org/docs/0.19.0/api/authentication.html
   *
   * @throws \Drupal\search_api_typesense\Api\SearchApiTypesenseException
   */
  public function connection();

  /**
   * Sets authentication parameters for the connection instance.
   *
   * @param string $api_key
   *   The read-write API key for connecting to the server or cluster.
   * @param array $nodes
   *   The Typesense server nodes.
   * @param int $connection_timeout_seconds
   *   The connection timout for the server or cluster (in seconds).
   */
  public function setAuthorization(string $api_key, array $nodes, int $connection_timeout_seconds);

  /**
   * Gets authentication parameters for connection instance.
   *
   * @return array
   *   Authorization details.
   */
  public function getAuthorization();

  /**
   * Searches specified collection for given string.
   *
   * @param string $collection_name
   *   The name of the collection to search.
   * @param array $parameters
   *   The array of query parameters.
   *
   * @return array
   *   The results array.
   *
   * @see https://typesense.org/docs/0.19.0/api/documents.html#search
   *
   * @throws \Drupal\search_api_typesense\Api\SearchApiTypesenseException
   */
  public function searchDocuments(string $collection_name, array $parameters);

  /**
   * Gets a Typesense collection.
   *
   * @param string $collection_name
   *
   * @return Typesense\Collection|null
   *   The collection, or NULL if none was found.
   *
   * @see https://typesense.org/docs/0.19.0/api/collections.html#retrieve-a-collection
   *
   * @throws \Drupal\search_api_typesense\Api\SearchApiTypesenseExceptiono
   */
  public function retrieveCollection(string $collection_name);

  /**
   * Creates a Typesense collection.
   *
   * @param array $schema
   *   A typesense schema.
   *
   * @return Typesense\Collection
   *
   * @see https://typesense.org/docs/0.19.0/api/collections.html#create-a-collection
   *
   * @throws \Drupal\search_api_typesense\Api\SearchApiTypesenseException
   */
  public function createCollection(array $schema);

  /**
   * Removes a Typesense index.
   *
   * @param string $collection_name
   *   The name of the index to remove.
   *
   * @return Typesense\Collection
   *
   * @see https://typesense.org/docs/0.19.0/api/collections.html#drop-a-collection
   *
   * @throws \Drupal\search_api_typesense\Api\SearchApiTypesenseException
   */
  public function dropCollection(string $collection_name);

  /**
   * Lists all collections.
   *
   * @return array
   *   The set of collections for the server.
   *
   * @see https://typesense.org/docs/0.19.0/api/collections.html#list-all-collections
   *
   * @throws \Drupal\search_api_typesense\Api\SearchApiTypesenseException
   */
  public function retrieveCollections();

  /**
   * Adds a document to a collection, or updates an existing document.
   *
   * @param string $collection_name
   *   The collection to create the new document on.
   *
   * @return array
   *   The newly added/updated document.
   *
   * @see https://typesense.org/docs/0.19.0/api/documents.html#index-a-document
   * @see https://typesense.org/docs/0.19.0/api/documents.html#upsert
   *
   * @throws \Drupal\search_api_typesense\Api\SearchApiTypesenseException
   */
  public function createDocument(string $collection_name, array $document);

  /**
   * Retrieves a specific indexd document.
   *
   * @param string $collection_name
   *   The name of the collection to query for the document.
   * @param string $id
   *   The id of the document to retrieve.
   *
   * @return array
   *   The retrieved document.
   *
   * @see https://typesense.org/docs/0.19.0/api/documents.html#retrieve-a-document
   *
   * @throws \Drupal\search_api_typesense\Api\SearchApiTypesenseException
   */
  public function retrieveDocument(string $collection_name, string $id);

  /**
   * Deletes a specific indexed document.
   *
   * @param string $collection_name
   *   The name of the collection containing the document.
   * @param string $id
   *   The id of the document to delete.
   *
   * @return array
   *   The deleted document.
   *
   * @see https://typesense.org/docs/0.19.0/api/documents.html#delete-documents
   *
   * @throws \Drupal\search_api_typesense\Api\SearchApiTypesenseException
   */
  public function deleteDocument(string $collection_name, string $id);

  /**
   * Deletes all documents satisfying a certain condition.
   *
   * @param string $collection_name
   *   The name of the collection containing the documents.
   * @param array $filter_condition
   *   The condition specifying which documents to delete.
   *
   * @return array
   *   An array containing the quantity of documents deleted.
   *
   * @see https://typesense.org/docs/0.19.0/api/documents.html#delete-by-query
   *
   * @throws \Drupal\search_api_typesense\Api\SearchApiTypesenseException
   */
  public function deleteDocuments(string $collection_name, array $filter_condition);

  /**
   * Adds a synonym to a collection, or updates an existing synonym.
   *
   * @param string $collection_name
   *   The collection to create the new synonym on.
   * @param string $id
   *   The id of the synonym to create.
   *
   * @return array
   *   The newly added/updated synonym.
   *
   * @see https://typesense.org/docs/0.20.0/api/synonyms.html#create-or-update-a-synonym
   * @see https://typesense.org/docs/0.20.0/api/synonyms.html#multi-way-synonym
   * @see https://typesense.org/docs/0.20.0/api/synonyms.html#arguments
   *
   * @throws \Drupal\search_api_typesense\Api\SearchApiTypesenseException
   */
  public function createSynonym(string $collection_name, string $id, array $synonym);

  /**
   * Retrieves a specific indexed synonym.
   *
   * @param string $collection_name
   *   The name of the collection to query for the synonym.
   * @param string $id
   *   The id of the synonym to retrieve.
   *
   * @return array
   *   The retrieved synonym.
   *
   * @see https://typesense.org/docs/0.20.0/api/synonyms.html#retrieve-a-synonym
   *
   * @throws \Drupal\search_api_typesense\Api\SearchApiTypesenseException
   */
  public function retrieveSynonym(string $collection_name, string $id);

  /**
   * Retrieves all indexed synonyms.
   *
   * @param string $collection_name
   *   The name of the collection to query for the synonym.
   *
   * @return array
   *   The retrieved synonyms.
   *
   * @see https://typesense.org/docs/0.20.0/api/synonyms.html#list-all-synonyms
   *
   * @throws \Drupal\search_api_typesense\Api\SearchApiTypesenseException
   */
  public function retrieveSynonyms(string $collection_name);

  /**
   * Deletes a specific indexed synonym.
   *
   * @param string $collection_name
   *   The name of the collection containing the synonym.
   * @param string $id
   *   The id of the synonym to delete.
   *
   * @return array
   *   The deleted synonym.
   *
   * @see https://typesense.org/docs/0.20.0/api/synonyms.html#delete-a-synonym
   *
   * @throws \Drupal\search_api_typesense\Api\SearchApiTypesenseException
   */
  public function deleteSynonym(string $collection_name, string $id);

  /**
   * Returns the health of the Typesense server.
   *
   * @return array
   *   An array containing a boolean 'ok': [ 'ok' => TRUE ].
   *
   * @throws \Drupal\search_api_typesense\Api\SearchApiTypesenseException
   */
  public function retrieveHealth();

  /**
   * Returns the debug info from the Typesense server.
   *
   * @return array
   *   An associative array containing two keys, 'state', and 'version'.
   *
   * @throws \Drupal\search_api_typesense\Api\SearchApiTypesenseException
   */
  public function retrieveDebug();

  /**
   * Returns current server keys.
   *
   * @return array
   *   The array of the server's keys.
   *
   * @throws \Drupal\search_api_typesense\Api\SearchApiTypesenseException
   */
  public function getKeys();

  /**
   * Prepares items for typesense-indexing.
   *
   * @param string $value
   *   The incoming entity value from Drupal.
   * @param string $type
   *   The specified data type from the Search API index configuration.
   *
   * @return array $value
   *   The prepared item, ready for Typesense indexing.
   *
   * @throws \Drupal\search_api_typesense\Api\SearchApiTypesenseException
   */
  public function prepareItemValue($value, $type);

}
