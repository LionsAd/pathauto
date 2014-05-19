<?php
/**
 * @file
 * Contains: Drupal\pathauto\Plugin\Field\FieldWidget\PathautoWidget
 */

namespace Drupal\pathauto\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\path\Plugin\Field\FieldWidget\PathWidget;

/**
 * Plugin implementation of the 'pathauto' widget.
 *
 * @FieldWidget(
 *   id = "pathauto",
 *   label = @Translation("Pathauto"),
 *   field_types = {
 *     "path"
 *   }
 * )
 */
class PathautoWidget extends PathWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, array &$form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $entity = $items->getEntity();

    // Taxonomy terms do not have an actual fieldset for path settings.
    // Merge in the defaults.
    // @todo Inpossible to do this in widget, use another solution
    /*
    $form['path'] += array(
      '#type' => 'fieldset',
      '#title' => t('URL path settings'),
      '#collapsible' => TRUE,
      '#collapsed' => empty($form['path']['alias']),
      '#group' => 'additional_settings',
      '#attributes' => array(
        'class' => array('path-form'),
      ),
      '#access' => \Drupal::currentUser()->hasPermission('create url aliases') || \Drupal::currentUser()->hasPermission('administer url aliases'),
      '#weight' => 30,
      '#tree' => TRUE,
      '#element_validate' => array('path_form_element_validate'),
    );*/



    $pattern = pathauto_pattern_load_by_entity($entity->getEntityTypeId(), $entity->bundle(), $entity->language()->getId());
    if (empty($pattern)) {
      return;
    }


    if (!isset($entity->path->pathauto)) {
      if (!empty($id)) {
        module_load_include('inc', 'pathauto');
        $path = \Drupal::service('path.alias_manager.cached')->getPathAlias($entity->getSystemPath(), $entity->language()->getId());
        $pathauto_alias = pathauto_create_alias($entity->getEntityType(), 'return', $entity->getSystemPath(), array($entity->getEntityType() => $entity), $entity->bundle(), $entity->language()->getId());
        $entity->path->pathauto = ($path != $entity->getSystemPath() && $path == $pathauto_alias);
      }
      else {
        $entity->path->pathauto = TRUE;
      }
    }

    $element['pathauto'] = array(
      '#type' => 'checkbox',
      '#title' => t('Generate automatic URL alias'),
      '#default_value' => $entity->path->pathauto,
      '#description' => t('Uncheck this to create a custom alias below.'),
      '#weight' => -1,
    );

    // Add JavaScript that will disable the path textfield when the automatic
    // alias checkbox is checked.
    $element['alias']['#states']['!enabled']['input[name="path[pathauto]"]'] = array('checked' => TRUE);


    // Override path.module's vertical tabs summary.
    $element['alias']['#attached']['js'] = array(
      'vertical-tabs' => drupal_get_path('module', 'pathauto') . '/pathauto.js',
    );

    // Add a shortcut link to configure URL alias patterns.
    if (drupal_valid_path('admin/config/search/path/patterns')) {
      $element['pathauto']['#description'] .= ' ' . l(t('Configure URL alias patterns.'), 'admin/config/search/path/patterns');
    }


    if ($entity->path->pathauto && !empty($entity->old_alias) && empty($entity->path->alias)) {
      $element['alias']['#default_value'] = $entity->old_alias;
      $entity->path->alias = $entity->old_alias;
    }


    // For Pathauto to remember the old alias and prevent the Path module from
    // deleting it when Pathauto wants to preserve it.
    if (!empty($entity->path->alias)) {
      $element['old_alias'] = array(
        '#type' => 'value',
        '#value' => $entity->path->alias,
      );
    }

    return $element;
  }
}