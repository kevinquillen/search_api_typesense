<?php

namespace Drupal\search_api_typesense\Commands;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\search_api\Utility\CommandHelper;
use Drupal\search_api_typesense\Api\SearchApiTypesenseServiceInterface;
use Drush\Commands\DrushCommands;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class SearchApiTypesenseCommands extends DrushCommands {

  /**
   * Typesense search arguments.
   */
  const SEARCH_ARGUMENTS = [
    // q and query_by are required, so we use them as the function arguments
    // while treating all the rest of the available arguments as options to
    // the command.
    //
    // 'q' => '*',
    // 'query_by' => '',
    'query-by-weights' => '',
    'prefix' => '',
    'filter-by' => '',
    'sort-by' => '',
    'facet-by' => '',
    'max-facet-values' => '',
    'facet-query' => '',
    'num-typos' => '',
    'page' => '',
    'per-page' => '',
    'group-by' => '',
    'group-limit' => '',
    'include-fields' => '',
    'exclude-fields' => '',
    'highlight-full-fields' => '',
    'highlight-affix-num-tokens' => '',
    'highlight-start-tag' => '',
    'highlight-end-tag' => '',
    'snippet-threshold' => '',
    'drop-tokens-threshold' => '',
    'typo-tokens-threshold' => '',
    'pinned-hits' => '',
    'hidden-hits' => '',
    'limit-hits' => '',
    // These params are native to this command.
    'debug-search' => FALSE,
  ];

  /**
   * The Typesense service.
   *
   * @var \Drupal\search_api_typesense\Api\SearchApiTypesenseServiceInterface
   */
  protected $typesense;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The command helper.
   *
   * @var \Drupal\search_api\Utility\CommandHelper
   */
  protected $commandHelper;

  /**
   * Class constructor.
   *
   * @param \Drupal\search_api_typesense\Api\SearchApiTypesenseServiceInterface $typesenseService
   *   The Typesense service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   */
  public function __construct(SearchApiTypesenseServiceInterface $typesense, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler, EventDispatcherInterface $event_dispatcher) {
    parent::__construct();

    $this->typesense = $typesense;
    $this->entityTypeManager = $entity_type_manager;
    $this->commandHelper = new CommandHelper($entity_type_manager, $module_handler, $event_dispatcher, 'dt');
  }

  /**
   * Gets auth info from the Backend and connects to the Typesense server.
   *
   * From the cli, we rely on the user to provide a collection name, so there's
   * no possibility of doing this in the constructor.
   *
   * Instead, the user provides the collection name which is identical to the
   * search_api index name. We use that to get the server, and we use that to
   * get the connection info, and get a connection from the Typesense service.
   *
   * @param string $collection_name
   *   The name of the collection that we want to query/manipulate.
   * @param bool $read_only
   *   Whether or not this is a read-only operation.
   *
   * @todo
   *   Consider rewriting the whole Typesense service subsystem so that we can
   *   rely on the same code here as is used in the Backend instead of largely
   *   reproducing the backend code here, in this method.
   */
  protected function getTypesenseConnection($collection_name, $read_only = TRUE) {
    // Get the set of available indexes.
    $indexes = $this->commandHelper->loadIndexes([$collection_name]);

    if (empty($indexes) || get_class($indexes[$collection_name]) !== 'Drupal\search_api\Entity\Index') {
      $this->output->writeln('<error>' . dt('Could not find an index named @collection_name', [
        '@collection_name' => $collection_name,
      ]) . '</error>');

      return FALSE;
    }

    // The the config from the backend this index belongs to.
    $server_auth = $indexes[$collection_name]->getServerInstance()->getBackendConfig();
    $api_key_key = $read_only ? 'ro_api_key' : 'rw_api_key';

    if (!isset($server_auth[$api_key_key], $server_auth['nodes'], $server_auth['connection_timeout_seconds'])) {
      $this->output->writeln('<error>' . dt('The server authorization credentials were not found or were incomplete.', [
        '@collection_name' => $collection_name,
      ]) . '</error>');

      return FALSE;
    }

    $api_key = $server_auth[$api_key_key];
    $nodes = array_filter($server_auth['nodes'], function($key) {
      return is_numeric($key);
    }, ARRAY_FILTER_USE_KEY);
    $connection_timeout_seconds = $server_auth['connection_timeout_seconds'];

    // Get the vars we need from the array.
    // extract($server_auth);

    // Connect to the server--there's no point going any further if we can't.
    $this->typesense->setAuthorization($api_key, $nodes, $connection_timeout_seconds);
  }

  /**
   * Constructs and executes a Typesense search query and tabulates results.
   *
   * @code
   *   $searchParameters = [
   *     'q'         => 'stark',
   *     'query_by'  => 'company_name',
   *     'filter_by' => 'num_employees:>100',
   *     'sort_by'   => 'num_employees:desc'
   *   ];
   *
   *   $client->collections['companies']->documents->search($searchParameters)
   * @endcode
   *
   * @param $collection_name
   *   The specific Typesense collection to query.
   * @param $q
   *   The query text to search for in the collection.
   *
   *   Use * as the search string to return all documents. This is typically
   *   useful when used in conjunction with filter_by.
   * @param $query_by
   *   One or more string / string[] fields that should be queried against.
   *   Separate multiple fields with a comma: company_name, country.
   * @param array $options
   *   These are the Typesense search arguments used to configure the query.
   * @option query-by-weights
   *  The relative weight to give each query_by field when ranking results.
   *  This can be used to boost fields in priority, when looking for matches.
   * @option prefix
   *   Boolean field to indicate that the last word in the query should be
   *   treated as a prefix, and not as a whole word. This is necessary for
   *   building autocomplete and instant search interfaces.
   * @option filter-by
   *   Filter conditions for refining your search results.
   * @option sort-by
   *   A list of numerical fields and their corresponding sort orders that will
   *   be used for ordering your results. Separate multiple fields with a comma.
   *   Up to 3 sort fields can be specified.
   * @option facet-by
   *   A list of fields that will be used for faceting your results on. Separate
   *   multiple fields with a comma.
   * @option max-facet-values
   *   Maximum number of facet values to be returned.
   * @option facet-query
   *   Facet values that are returned can now be filtered via this parameter.
   *   The matching facet text is also highlighted. For example, when faceting
   *   by category, you can set facet_query=category:shoe to return only facet
   *   values that contain the prefix "shoe".
   * @option num-typos
   *   Number of typographical errors (1 or 2) that would be tolerated.
   * @option page
   *   Results from this specific page number would be fetched.
   * @option per-page
   *   Number of results to fetch per page.
   * @option group-by NOT YET SUPPORTED
   *   You can aggregate search results into groups or buckets by specify one
   *   or more group_by fields. Separate multiple fields with a comma.
   * @option group-limit NOT YET SUPPORTED
   *   Maximum number of hits to be returned for every group. If the group_limit
   *   is set as K then only the top K hits in each group are returned in the
   *   response.
   * @option include-fields
   *   Comma-separated list of fields from the document to include in the search
   *   result.
   * @option exclude-fields
   *   Comma-separated list of fields from the document to exclude in the search
   *   result.
   * @option highlight-full-fields
   *   Comma separated list of fields which should be highlighted fully without
   *   snippeting.
   * @option highlight-affix-num-tokens
   *   The number of tokens that should surround the highlighted text on each
   *   side.
   * @option highlight-start-tag
   *   The start tag used for the highlighted snippets.
   * @option highlight-end-tag
   *   The end tag used for the highlighted snippets.
   * @option snippet-threshold
   *   Field values under this length will be fully highlighted, instead of
   *   showing a snippet of relevant portion.
   * @option drop-tokens-threshold
   *   If the number of results found for a specific query is less than this
   *   number, Typesense will attempt to drop the tokens in the query until
   *   enough results are found. Tokens that have the least individual hits are
   *   dropped first. Set drop_tokens_threshold to 0 to disable dropping of
   *   tokens.
   * @option typo-tokens-threshold
   *   If the number of results found for a specific query is less than this
   *   number, Typesense will attempt to look for tokens with more typos until
   *   enough results are found.
   * @option pinned-hits
   *   A list of records to unconditionally include in the search results at
   *   specific positions.
   * @option hidden-hits
   *   A list of records to unconditionally hide from search results.
   * @option limit-hits
   *   Maximum number of hits that can be fetched from the collection. Eg: 200
   * @option debug-search
   *   When set, the command prints the parameters received with the results.
   *
   * @usage search-api-typesense:query 'search_api_typesense_index' 'stark' 'company_name' --filter-by 'num_employees:>100' --sort-by 'num_employees:desc'
   *   Searches the Typesense collection search_api_typesense_index's field
   *   'company_name' for 'stark', filters the results to documents whose
   *   'num_employees' field is > 100, and sorts by the same field in descending
   *   order.
   *
   * @command search-api-typesense:query
   * @aliases sapi-ts
   *
   * @see https://typesense.org/docs/0.19.0/api/documents.html
   * @see https://typesense.org/docs/0.19.0/api/documents.html#arguments
   *
   * @todo
   *   - Permit --group-by and --group-limit to be used, preferably with a
   *     separator in the results table.
   *   - At a MINIMUM, move the server auth stuff out of this method into
   *     something we can re-use.
   *
   *     Even if we do that, We'll basically be reproducing the way to connect
   *     to the Typesense server here that we use in the Backend plugin. If we
   *     refactor the Typesense service to work more like search_api_solr's
   *     SolrConnector, we could presumably use that as a more direct route to
   *     the same destination...
   */
  public function query($collection_name, $q, $query_by, $options = self::SEARCH_ARGUMENTS) {
    // Do NOT pass certain options on to the Typesense service (because it
    // won't recognize them).
    $exclude_options = [
      'debug-search',
    ];

    // Get auth credentials for THIS server.
    $this->getTypesenseConnection($collection_name);

    // We'll always have a minimum of two arguments.
    $arguments = [
      'q' => $q,
      'query_by' => $query_by,
    ];

    // All other arguments are optional, AND need a bit of string manipulation.
    foreach ($options as $argument => $value) {
      if (array_key_exists($argument, self::SEARCH_ARGUMENTS) && !empty($value) && !in_array($argument, $exclude_options)) {
        $arguments[str_replace('-', '_', $argument)] = $value;
      }
    }

    // Retrieve some debug info for the output.
    $debug = $this->typesense->retrieveDebug();

    // Run the query.
    $results = $this->typesense->searchDocuments($collection_name, $arguments);

    //
    if (!empty($results['hits'])) {
      // If we have results, we have lots to say about them.
      //
      // We have at least one row of results, so initialize some vars and start
      // looping.
      //
      // We're including the text_match in every case.
      $rows = [];
      foreach ($results['hits'] as $index => $hit) {
        if ($index === 0) {
          $headers = array_keys($hit['document']);
          array_unshift($headers, 'text_match');
        }

        $row = array_values($hit['document']);
        array_unshift($row, $hit['text_match']);
        $rows[] = $row;
      }

      // Prepare a table.
      $output = new BufferedOutput();
      $table = new Table($output);
      $table->setHeaders($headers)->setRows($rows);
      $table->render();
      $table = $output->fetch();

      // Output the results.
      $this->output->write($table);
    }

    // Output the server version.
    $this->output->writeln(dt('Typesense server version @version.', [
      '@version' => $debug['version'],
    ]));

    // Output a summary of the search info.
    $this->output->writeln('');
    $this->output->writeln('<info>' . dt('@found results returned from @out_of indexed documents in @search_time_ms ms.'. '</info>', [
      '@found' => $results['found'],
      '@out_of' => $results['out_of'],
      '@search_time_ms' => $results['search_time_ms'],
    ]));

    // Output pager info.
    if ($results['found'] > 0) {
      $this->output->writeln(dt('Showing page @page of @pages.', [
        '@page' => $results['page'],
        '@pages' => ceil($results['found'] / $results['request_params']['per_page']),
      ]));
    }

    // Output debug info if we've been asked for it.
    if ($options['debug-search']) {
      $this->output->writeln('');
      $this->output->writeln('<info>' . dt('The following parameters were passed to typesense/typesense-php:') . '</info>');
      $this->output->writeln('');
      $this->output->writeln(var_export($arguments, TRUE));
    }
  }

}
