<?php

namespace Drupal\search_api_glossary\Plugin\facets\processor;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\facets\FacetInterface;
use Drupal\facets\Processor\BuildProcessorInterface;
use Drupal\facets\Processor\ProcessorPluginBase;

/**
 * Provides a processor to show only items with letter in Glossary AZ.
 *
 * @FacetsProcessor(
 *   id = "glossaryaz_letters_items_processor",
 *   label = @Translation("Display only items with letter in Glossary AZ"),
 *   description = @Translation("Option to show only items with letter in Glossary AZ."),
 *   stages = {
 *     "build" = 10
 *   }
 * )
 */
class GlossaryAZLettersItemsProcessor extends ProcessorPluginBase implements BuildProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet, array $results) {

    /** @var \Drupal\facets\Result\ResultInterface $result */
    foreach ($results as $key => $result) {
      $result_glossary = $result->getDisplayValue();
      if ($result_glossary instanceof TranslatableMarkup) {
        continue;
      }

      // If item not a letter remove them from sample array.
      if (!ctype_alpha($result_glossary)) {
        unset($results[$key]);
      }
    }

    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsFacet(FacetInterface $facet) {
    // Check if
    // 1) The correct widget is chosen for the facet
    // 2) If the glossary processor is enabled in Search API index.
    $widget = $facet->getWidget()['type'];
    $search_processors = $facet->getFacetSource()->getIndex()->getProcessors();

    if ($widget == 'glossaryaz' && array_key_exists('glossary', $search_processors)) {
      // Glossary processor is enabled.
      return TRUE;
    }

    return FALSE;
  }
}