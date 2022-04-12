<?php

namespace Drupal\search_api_typesense\Plugin\search_api\processor;

use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api\Item\FieldInterface;
use Drupal\search_api\Plugin\search_api\data_type\value\TextValueInterface;
use Drupal\search_api\Processor\FieldsProcessorPluginBase;
use Drupal\search_api\Utility\DataTypeHelperInterface;
use Drupal\search_api\Utility\Utility;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;

/**
 * Configures Typesense schema using search_api_typesense fields.
 *
 * @SearchApiProcessor(
 *   id = "typesense_schema",
 *   label = @Translation("Typesense Schema"),
 *   description = @Translation("Used to set field metadata for Typesense schema creation. Note that changing these settings requires the Typesense collection (index) to be recreated and all content reindexed."),
 *   stages = {
 *     "add_properties" = 0
 *   },
 *   enabled = true,
 *   locked = true,
 *   hidden = false,
 * )
 */
class TypesenseSchema extends FieldsProcessorPluginBase {

  /**
   * The data type helper.
   *
   * @var \Drupal\search_api\Utility\DataTypeHelperInterface|null
   */
  protected $dataTypeHelper;

  /**
   * Retrieves the data type helper.
   *
   * @return \Drupal\search_api\Utility\DataTypeHelperInterface
   *   The data type helper.
   */
  public function getDataTypeHelper() {
    return $this->dataTypeHelper ?: \Drupal::service('search_api.data_type_helper');
  }

  /**
   * Sets the data type helper.
   *
   * @param \Drupal\search_api\Utility\DataTypeHelperInterface $data_type_helper
   *   The new data type helper.
   *
   * @return $this
   */
  public function setDataTypeHelper(DataTypeHelperInterface $data_type_helper) {
    $this->dataTypeHelper = $data_type_helper;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $configuration = parent::defaultConfiguration();

    $configuration += [];

    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $index = $this->getIndex();

    $form['warning'] = [
      '#markup' => '<div class="messages messages--warning"><h3>Warning</h3><p>Marking a field as <code>facet</code>, <code>optional</code>, or <code>default_sorting_field</code> changes the Typesense schema. This in turn means that the Typesense collection (the index) must be recreated and all content fully re-indexed.</p><p>Please be sure you want to do this&mdash;especially on large indexes.</p></div>',
    ];

    $form['schema']['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Collection name'),
      '#description' => $this->t('The name of the Typesense collection to be created. This field is read-only.'),
      '#default_value' => $index->id(),
      '#attributes' => [
        'readonly' => 'readonly',
      ],
      '#size' => 25,
    ];

    // Build the default sorting field setting field.
    $form['schema']['default_sorting_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default sorting field'),
      '#description' => $this->t('This field will be used to sort results by default. See the <a href=":typesense_api">Typesense API</a> for more information.', [
        ':typesense_api' => 'https://typesense.org/docs/0.21.0/guide/ranking-and-relevance.html#default-ranking-order',
      ]),
      '#default_value' => $this->configuration['schema']['default_sorting_field'] ?? NULL,
    ];

    $form['schema_fields'] = [
      '#type' => 'fieldgroup',
    ];

    // Then, build individual setting fields for configuring facet and optional
    // fields in this index.
    foreach ($index->getFields() as $field) {
      // Store the field type while we process the field.
      $field_type = $field->getType();

      // These options ONLY make sense for Typesense field types.
      if (strpos($field_type, 'typesense_') !== 0) {
        continue;
      }

      $group_id = $field->getFieldIdentifier();

      $form['schema']['fields'][$group_id] = [
        '#type' => 'fieldgroup',
        '#title' => $field->getLabel(),
      ];

      $form['schema']['fields'][$group_id]['facet'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Facet'),
        '#default_value' => $this->configuration['schema']['fields'][$group_id]['facet'] ?? NULL,
      ];

      $form['schema']['fields'][$group_id]['optional'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Optional'),
        '#default_value' => $this->configuration['schema']['fields'][$group_id]['optional'] ?? NULL,
        '#states' => [
          'invisible' => [
            ':input[name="processors[typesense_schema][settings][schema][default_sorting_field]"]' => [
              'value' => $group_id,
            ],
          ],
          'disabled' => [
            ':input[name="processors[typesense_schema][settings][schema][default_sorting_field]"]' => [
              'value' => $group_id,
            ],
          ],
        ],
      ];

      $form['schema']['fields'][$group_id]['type'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Datatype'),
        '#description' => $this->t('The Typesense data type of the indexed field. This field is read-only.'),
        '#default_value' => $this->getTypesenseDatatype($field_type) ?? NULL,
        '#attributes' => [
          'readonly' => 'readonly',
        ],
        '#size' => 10,
      ];
    }

    // $form['generated_schema'] = [
    //   '#type' => 'details',
    //   '#title' => $this->t('Generated Typesense schema'),
    //   '#description' => $this->t('When this form was last saved, the Typesense schema was configured as shown here. <strong>Note</strong>: this field is only used for reference'),
    //   '#open' => FALSE,
    // ];

    // // We're going to need to build the Typesense schema elsewhere in order to
    // // display it here.
    // $form['generated_schema']['code'] = [
    //   '#type' => 'inline_template',
    //   '#template' => '<pre><code>{{ schema }}</code></pre>',
    //   '#context' => [
    //     'schema' => var_export($this->getTypesenseSchema(), TRUE),
    //   ],
    // ];

    return $form;
  }

  /**
   * Gets array of field ids and names for all numeric fields in this index.
   *
   * Typesense only sorts by numeric values, so this function returns only the
   * fields whose declared datatype is one of int32, int32[], float, or float[].
   *
   * The returned value is suitable for use in #select form api objects'
   * #options arrays.
   *
   * @return array $sorting_field_options
   */
  public function getSortingFieldOptions() {
    $sorting_field_options = [];

    foreach ($this->getIndex()->getFields() as $field) {
      $field_type = $field->getType();
      if (!empty(preg_match('/^typesense_(float|int32)/', $field_type))) {
        $sorting_field_options[$field->getFieldIdentifier()] = sprintf(
          '%s (%s)',
          $field->getLabel(),
          $this->getTypesenseDatatype($field_type)
        );
      }
    }

    return $sorting_field_options;
  }

  /**
   * Gets the native Typesense datatype from the module value.
   *
   * @param $search_api_typesense_datatype
   *   The search_api_typesense datatype.
   *
   * @return $typesense_datatype
   *   The Typesense datatype.
   */
  public function getTypesenseDatatype($search_api_typesense_datatype) {
    return str_replace('typesense_', '', $search_api_typesense_datatype);
  }

  /**
   * Transforms the form data from the processor into a Typesense schema.
   *
   * @return array $typesense_schema
   *   The schema based on the current configuration of the processor.
   */
  public function getTypesenseSchema() {
    if (!isset($this->configuration['schema'])) {
      return [];
    }

    // Start with the known properties.
    $typesense_schema = [
      'name' => $this->configuration['schema']['name'],
      'fields' => [],
    ];

    // Typesense' default_sorting_field value is now optional. Don't add i
    // unless we have a value for it.
    if (isset($this->configuration['schema']['default_sorting_field']) && !empty($this->configuration['schema']['default_sorting_field'])) {
      $typesense_schema['default_sorting_field'] = $this->configuration['schema']['default_sorting_field'];
    }

    // Then add each field in turn.
    foreach ($this->configuration['schema']['fields'] as $name => $field) {
      // Start the current field's properties.
      $field_properties = [
        'name' => $name,
        'type' => $field['type'],
      ];

      // The field might be a facet.
      if (!empty($field['facet'])) {
        $field_properties['facet'] = TRUE;
      }

      // The field might be optional.
      if (!empty($field['optional'])) {
        $field_properties['optional'] = TRUE;
      }

      // Add the completed field to the list.
      $typesense_schema['fields'][] = $field_properties;
    }

    // Return the completed schema.
    return $typesense_schema;
  }

}
