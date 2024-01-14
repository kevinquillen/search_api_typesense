<?php

namespace Drupal\search_api_typesense\Plugin\search_api\data_type;

use Drupal\search_api\DataType\DataTypePluginBase;

/**
 * Provides a Typesense auto data type.
 *
 * @SearchApiDataType(
 *   id = "typesense_auto",
 *   label = @Translation("Typesense: auto"),
 *   description = @Translation("Special type that automatically attempts to infer the data type based on the documents added to the collection. See automatic schema detection in the Typesense documentation."),
 *   fallback_type = "string"
 * )
 */
class AutoDataType extends DataTypePluginBase {

  /**
   * {@inheritdoc}
   */
  public function getValue($value) {
    return $value;
  }

}
