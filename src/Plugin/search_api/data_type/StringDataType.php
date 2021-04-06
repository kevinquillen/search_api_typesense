<?php

namespace Drupal\search_api_typesense\Plugin\search_api\data_type;

use Drupal\search_api\DataType\DataTypePluginBase;

/**
 * Provides a Typesense string data type.
 *
 * @SearchApiDataType(
 *   id = "typesense_string",
 *   label = @Translation("Typesense: string"),
 *   description = @Translation("A string"),
 *   fallback_type = "string"
 * )
 */
class StringDataType extends DataTypePluginBase {

  /**
   * {@inheritdoc}
   */
  public function getValue($value) {
    return (string) $value;
  }

}
