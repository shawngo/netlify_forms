<?php

/**
 * @file
 * Configuration form for Netlify Forms module settings.
 */

namespace Drupal\netlify_forms\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Netlify Forms settings.
 */
class NetlifyFormsConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['netlify_forms.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'netlify_forms_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('netlify_forms.settings');

    $form['api_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Netlify API Token'),
      '#description' => $this->t('Enter your Netlify personal access token.'),
      '#default_value' => $config->get('api_token'),
      '#required' => TRUE,
    ];

    $form['default_site_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default Site ID'),
      '#description' => $this->t('Default Netlify site ID (can be overridden per customer).'),
      '#default_value' => $config->get('default_site_id'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('netlify_forms.settings')
      ->set('api_token', $form_state->getValue('api_token'))
      ->set('default_site_id', $form_state->getValue('default_site_id'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
