<?php

namespace Drupal\search_api_typesense\Plugin\search_api\data_type;

use Drupal\search_api\DataType\DataTypePluginBase;

/**
 * Provides a Typesense int32 data type.
 *
 * @SearchApiDataType(
 *   id = "typesense_int32",
 *   label = @Translation("Typesense: int32"),
 *   description = @Translation("A 32 bit integer."),
 *   fallback_type = "integer"
 * )
 */
class Int32DataType extends DataTypePluginBase {

  /**
   * {@inheritdoc}
   */
  public function getValue($value) {
    return (int) $value & 0xFFFFFFFF;
  }

}
