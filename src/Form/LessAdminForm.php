<?php
/**
 * @file
 * Contains \Drupal\less\Form\LessAdminForm.
 */
 
namespace Drupal\less\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for LESS module settings.
 */
class LessAdminForm extends ConfigFormBase {
  
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'less_settings_form';
  }
  
  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['less.settings'];
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('less.settings');
    
    $form['less_flush'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Click this button to flag all LESS files and cache for regeneration.'),
    );
    
    $form['less_flush']['flush'] = array(
      '#type' => 'submit',
      '#value' => t('Flush LESS files and cache'),
      '#submit' => array('::_flush_less'),
    );
    
    $registered_engines = _less_get_engines();

    $less_engines = array();

    foreach ($registered_engines as $library => $engine) {

      $less_engines[] = libraries_detect($library);
    }
    
    $less_engine_element = array(
      '#type' => 'radios',
      '#title' => t('LESS engine'),
      '#options' => array(),
      '#required' => TRUE,
      '#default_value' => $config->get('less_engine') ?: 'lessphp',
    );
    
    foreach ($less_engines as $less_engine) {
      
      $less_engine_element['#options'][$less_engine['machine name']] = $less_engine['name'];
      
      $less_engine_element[$less_engine['machine name']] = array(
        '#type' => 'radio',
        '#title' => t('@engine_name - <a href="@vendor_url">@vendor_url</a>', array('@engine_name' => $less_engine['name'], '@vendor_url' => $less_engine['vendor url'])),
        '#return_value' => $less_engine['machine name'],
        '#description' => t('Missing - Click vendor link above to read installation instructions.'),
        '#disabled' => empty($less_engine['installed']),
      );
      
      if ($less_engine['installed']) {
        $less_engine_element[$less_engine['machine name']]['#description'] = t('v%version Installed', array('%version' => $less_engine['version']));
      }
      
    }
    
    $form['less_engine'] = $less_engine_element;
    
    
    $lessautoprefixer_library = libraries_detect('lessautoprefixer');
    
    $form[LESS_AUTOPREFIXER] = array(
      '#type' => 'checkbox',
      '#title' => t('Use @name - <a href="@vendor_url">@vendor_url</a>', array('@name' => $lessautoprefixer_library['name'], '@vendor_url' => $lessautoprefixer_library['vendor url'])),
      '#description' => t('Enable automatic prefixing of vendor CSS extensions.'),
      '#default_value' => $config->get(LESS_AUTOPREFIXER) ?: FALSE,
      '#disabled' => empty($lessautoprefixer_library['installed']),
    );
    
    if ($lessautoprefixer_library['installed']) {
      $form[LESS_AUTOPREFIXER]['#description'] .= '<br />'. t('v%version Installed', array('%version' => $lessautoprefixer_library['version']));
    }
    
    $form['developer_options'] = array(
      '#type' => 'fieldset',
      '#title' => t('Developer Options'),
      '#collapsible' => TRUE,
      '#collapsed' => !($config->get(LESS_DEVEL) ?: FALSE),
    );
    
    $form['developer_options'][LESS_DEVEL] = array(
      '#type' => 'checkbox',
      '#title' => t('Developer Mode'),
      '#description' => t('Enable developer mode to ensure LESS files are regenerated every page load.'),
      '#default_value' => $config->get(LESS_DEVEL) ?: FALSE,
    );
    
    $form['developer_options'][LESS_SOURCE_MAPS] = array(
      '#type' => 'checkbox',
      '#title' => t('Source Maps'),
      '#description' => t('Enable source maps output while "Developer Mode" is enabled.'),
      '#default_value' => $config->get(LESS_SOURCE_MAPS) ?: FALSE,
      '#states' => array(
        'enabled' => array(
          ':input[name="' . LESS_DEVEL . '"]' => array('checked' => TRUE),
        ),
      ),
    );
    
    $form['developer_options'][LESS_WATCH] = array(
      '#type' => 'checkbox',
      '#title' => t('Watch Mode'),
      '#description' => t('Enable watch mode while developer mode is active to automatically reload styles when changes are detected, including changes to @import-ed files. Does not cause a page reload.'),
      '#default_value' => $config->get(LESS_WATCH) ?: FALSE,
      '#states' => array(
        'enabled' => array(
          ':input[name="' . LESS_DEVEL . '"]' => array('checked' => TRUE),
        ),
      ),
    );
    
    return parent::buildForm($form, $form_state);
  }
  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('less.settings')->set('less_engine', $values['less_engine'])->save();
    $this->config('less.settings')->set(LESS_AUTOPREFIXER, $values[LESS_AUTOPREFIXER])->save();
    $this->config('less.settings')->set(LESS_DEVEL, $values[LESS_DEVEL])->save();
    $this->config('less.settings')->set(LESS_SOURCE_MAPS, $values[LESS_SOURCE_MAPS])->save();
    $this->config('less.settings')->set(LESS_WATCH, $values[LESS_WATCH])->save();
  }
  
  /**
   * Submit handler for cache clear button.
   */
  public function _flush_less(array $form, FormStateInterface $form_state) {
    less_flush_caches();
    drupal_set_message(t('LESS files and cache cleared.'), 'status');
    drupal_flush_all_caches();
  }
  
} 


