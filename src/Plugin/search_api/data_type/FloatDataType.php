<?php

namespace Drupal\search_api_typesense\Plugin\search_api\data_type;

use Drupal\search_api\DataType\DataTypePluginBase;

/**
 * Provides a Typesense float data type.
 *
 * @SearchApiDataType(
 *   id = "typesense_float",
 *   label = @Translation("Typesense: float"),
 *   description = @Translation("A float"),
 *   fallback_type = "decimal"
 * )
 */
class FloatDataType extends DataTypePluginBase {

  /**
   * {@inheritdoc}
   */
  public function getValue($value) {
    return (float) $value;
  }

}
