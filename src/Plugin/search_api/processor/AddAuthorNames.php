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
 * id = "add_author_names",
 * label = @Translation("Author names"),
 * description = @Translation("Author and contributor names (preferred, other, and also in form [firstname] [surname])"),
 * stages = {
 * "add_properties" = 0,
 * },
 * locked = true,
 * hidden = false,
 * )
 */
class AddAuthorNames extends ProcessorPluginBase {
  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];
    if (!$datasource) {
      $definition = [
        'label' => $this->t('Author names'),
        'description' => $this->t('Author and contributor names (preferred, other, and also in form [firstname] [surname])'),
        'is_list' => TRUE,
        'type' => 'string',
        'processor_id' => $this->getPluginId(),
      ];
      $properties['search_api_author_names'] = new AddURLProperty($definition);
    }
    return $properties;
  }
  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    $node = $item->getOriginalObject()->getEntity();
    $authors = [];

    if ($node->hasField('field_author')) {
      $authors = $node->get('field_author')->getValue();
    }

    if ($node->hasField('field_author_corporate')) {
      $authors = array_merge($authors, $node->get('field_author_corporate')->getValue());
    }

    if ($node->hasField('field_contributor')) {
      $authors = array_merge($authors, $node->get('field_contributor')->getValue());
    }

    $results = [];
    foreach ($authors as $ref) {
      $author = \Drupal\node\Entity\Node::load((int)$ref['target_id']);
      if (!$author) {
        continue;
      }
      $author_id = $author->get('field_author_id')->getValue()[0]['target_id'];
      if ($author_id) {
        $names = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['field_author_id' => $author_id]);
        foreach ($names as $name) {
          array_push($results, $name->getTitle());
          array_push($results, $name->get('field_name_structured')->given.' '.$name->get('field_name_structured')->family);
        } 
      }
    }

    $result_string = implode(PHP_EOL, $results);
    \Drupal::logger('author names')->notice($node->id().': '.$result_string);

    $fields = $item->getFields(FALSE);
    $fields = $this->getFieldsHelper()
      ->filterForPropertyPath($fields, NULL, 'search_api_author_names');

    foreach ($fields as $field) {
      $field->addValue($result_string);
    }
  }
}
