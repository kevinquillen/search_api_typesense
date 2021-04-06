<?php

namespace Drupal\search_api_typesense\Plugin\search_api\data_type;

use Drupal\search_api\DataType\DataTypePluginBase;

/**
 * Provides a Typesense int64 data type.
 *
 * @SearchApiDataType(
 *   id = "typesense_int64",
 *   label = @Translation("Typesense: int64"),
 *   description = @Translation("A 64 bit integer."),
 *   fallback_type = "integer"
 * )
 */
class Int64DataType extends DataTypePluginBase {

  /**
   * {@inheritdoc
   */
  public function getValue($value) {
    return (int) $value;
  }

}
