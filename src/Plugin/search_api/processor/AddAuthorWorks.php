<?php 

namespace Drupal\digitalia_muni_search_api_fields\Plugin\search_api\processor;

use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Plugin\search_api\processor\Property\AddURLProperty;

/**
 * Adds the item's URL to the indexed data.
 *
 * @SearchApiProcessor(
 * id = "add_author_works",
 * label = @Translation("Author works"),
 * description = @Translation("Author works (author, corporate author, contributor)."),
 * stages = {
 * "add_properties" = 0,
 * },
 * locked = true,
 * hidden = false,
 * )
 */
class AddAuthorWorks extends ProcessorPluginBase {
  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];
    if (!$datasource) {
      $definition = [
        'label' => $this->t('Author works'),
        'description' => $this->t('Author works (author, corporate author, contributor)'),
        'is_list' => TRUE,
        'type' => 'integer',
        'processor_id' => $this->getPluginId(),
      ];
      $properties['search_api_author_works'] = new AddURLProperty($definition);
    }
    return $properties;
  }
  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    $nid = $item->getOriginalObject()->getEntity()->id();

    $author_of = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['field_author' => $nid]);
    $corporate_author_of = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['field_author_corporate' => $nid]);
    $contributor_of = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['field_contributor' => $nid]);

    if ($author_of or $corporate_author_of or $contributor_of) {
      $fields = $item->getFields(FALSE);
      $fields = $this->getFieldsHelper()
        ->filterForPropertyPath($fields, NULL, 'search_api_author_works');

      foreach ($fields as $field) {
        $config = $field->getConfiguration();

        foreach ($author_of as $node) {
          $field->addValue($node->id());
        }

        foreach ($corporate_author_of as $node) {
          $field->addValue($node->id());
        }

        foreach ($contributor_of as $node) {
          $field->addValue($node->id());
        }
      }
    }
  }
}
