<?php

namespace Drupal\search_api_glossary\Plugin\facets\widget;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\facets\FacetInterface;
use Drupal\facets\Result\ResultInterface;
use Drupal\facets\Plugin\facets\widget\LinksWidget;
use Drupal\facets\Widget\WidgetPluginBase;

/**
 * The GlossaryAZ widget.
 *
 * @FacetsWidget(
 *   id = "glossaryaz",
 *   label = @Translation("Glossary AZ"),
 *   description = @Translation("A simple widget that shows a Glossary AZ"),
 * )
 */
class GlossaryAZWidget extends LinksWidget {

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet) {
    $build = WidgetPluginBase::build($facet);
    $this->appendWidgetLibrary($build);

    $configuration = $facet->getWidget()['config'];
    $enable_default_theme = empty($configuration['enable_default_theme']) ? FALSE : (bool) $configuration['enable_default_theme'];

    if ($enable_default_theme) {
      $build['#attached'] = [
        'library' => [
          'search_api_glossary/drupal.search_api_glossary.facet_css',
        ],
      ];
    }

    return $build;
  }

  /**
   * Builds a facet result item.
   *
   * @param \Drupal\facets\Result\ResultInterface $result
   *   The result item.
   *
   * @return array
   *   The facet result item as a render array.
   */
  protected function buildResultItem(ResultInterface $result) {
      $item = parent::buildResultItem($result);
      $this->addGlossaryClass($item, $result);
  
      return $item;
    }
  
  /**
   * todo check if children processing actually works
   * @inheritdoc
   */
  protected function buildListItems(FacetInterface $facet, ResultInterface $result) {
    $items = parent::buildListItems($facet, $result);
    $this->addGlossaryClass($items, $result);

    return $items;
  }
 
  /**
   * Add the AZGlossary classes to the item
   *
   * @param array $item
   * @param $result
   */
  protected function addGlossaryClass(array &$item, $result) {
    $classes = ['glossaryaz'];

    if ($result->isActive()) {
      $classes[] = 'is-active';
      $item["#attributes"]["class"] = isset($item["#attributes"]["class"]) ? array_merge($item["#attributes"]["class"], ['is-active']) : ['is-active'];
    }
    else {
      $item["#attributes"]["class"] = isset($item["#attributes"]["class"]) ? array_merge($item["#attributes"]["class"], ['is-inactive']) : ['is-inactive'];
    }

    // Add result, no result classes.
    if ($result->getCount() == 0) {
      $classes[] = 'no-results';
    }
    else {
      $classes[] = 'yes-results';
    }

    $item["#wrapper_attributes"]["class"] = isset($item["#wrapper_attributes"]["class"]) ? array_merge($item["#wrapper_attributes"]["class"], $classes) : $classes;
  }

  /**
   * Returns the text or link for an item.
   *
   * @param \Drupal\facets\Result\ResultInterface $result
   *   A result item.
   *
   * @return array
   *   The item, as a renderable array.
   */
  protected function prepareLink(ResultInterface $result) {
    $configuration = $this->getConfiguration();
    $show_count = empty($configuration['show_count']) ? FALSE : (bool) $configuration['show_count'];

    $text = $result->getDisplayValue();

    // TODO revise this logic based on progress with
    // All items count is not correct when narrowing the results.
    // https://www.drupal.org/project/facets/issues/2692027
    // see https://git.drupalcode.org/project/facets/commit/21343a6
    if ($show_count && $result->getRawValue() != 'All') {
      $text .= ' (' . $result->getCount() . ')';
    }

    if (is_null($result->getUrl()) || $result->getCount() == 0) {
      $link = ['#markup' => $text];
    }
    else {
      $link = new Link($text, $result->getUrl());
      $link = $link->toRenderable();
    }

    return $link;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, FacetInterface $facet) {
    $form['show_count'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show count per Glossary item'),
    ];
    $form['enable_default_theme'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use default Glossary AZ Theme'),
    ];

    $config = $facet->getWidget()['config'];
    if (!is_null($config)) {
      if (isset($config['show_count'])) {
        $form['show_count']['#default_value'] = $config['show_count'];
      }
      if (isset($config['enable_default_theme'])) {
        $form['enable_default_theme']['#default_value'] = $config['enable_default_theme'];
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getQueryType() {
    return 'string';
  }

  /**
   * {@inheritdoc}
   */
  public function supportsFacet(FacetInterface $facet) {
    // Are we dealing with Glossary field?
    // See https://www.drupal.org/node/2877691.
    // Load up the search index and processor.
    $glossary_processor = $facet->getFacetSource()
      ->getIndex()
      ->getProcessor('glossary');

    // Name of the field to check against.
    $glossary_field_id = $facet->getFieldIdentifier();

    // Check if chosen field is glossary or not.
    // checkFieldName will return TRUE or FALSE
    // see Glossary::checkFieldName()
    $is_glossary_field = $glossary_processor->checkFieldName($glossary_field_id);
    return $is_glossary_field;
  }

}
