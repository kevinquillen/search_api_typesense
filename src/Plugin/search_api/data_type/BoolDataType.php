<?php

namespace Drupal\search_api_typesense\Plugin\search_api\data_type;

use Drupal\search_api\DataType\DataTypePluginBase;

/**
 * Provides a Typesense bool data type.
 *
 * @SearchApiDataType(
 *   id = "typesense_bool",
 *   label = @Translation("Typesense: bool"),
 *   description = @Translation("A boolean"),
 *   fallback_type = "boolean"
 * )
 */
class BoolDataType extends DataTypePluginBase {

  /**
   * {@inheritdoc}
   */
  public function getValue($value) {
    return (boolean) $value;
  }

}
