<?php

namespace Drupal\dc_content_import\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ConnectionSettingsForm.
 *
 * @package Drupal\dc_content_import\Form
 */
class ConnectionSettingsForm extends ConfigFormBase {

  private $editableConfigName = 'dc_content_import.connection_config';


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dc_content_import_connection_settings';
  }


  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return [
      $this->editableConfigName,
    ];
  }


  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config($this->editableConfigName);
    $api_url = $config->get('themovied_api_url');
    $api_key = $config->get('themovied_api_key');

    $form['themovied_api_url'] = [
      '#type' => 'url',
      '#title' => $this->t('themovied API Url'),
      '#default_value' => !empty($api_url) ? $api_url : '',
      '#description' => $this->t('Enter the URL of themovied_api_url API'),
      '#required' => TRUE,
    ];
    $form['themovied_api_key'] = [
      '#title' => $this->t('themovied API Key'),
      '#type' => 'textfield',
      '#default_value' => !empty($api_key) ? $api_key : '',
      '#description' => $this->t('Enter the Key to connect to themovied API'),
      '#required' => TRUE,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save Configuration'),
    ];

    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }


  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $api_url = $form_state->getValue('themovied_api_url', '');
    $api_key = $form_state->getValue('themovied_api_key', '');

    $this->configFactory->getEditable($this->editableConfigName)
      ->set('themovied_api_url', $api_url)
      ->save();

    $this->configFactory->getEditable($this->editableConfigName)
      ->set('themovied_api_key', $api_key)
      ->save();

    parent::submitForm($form, $form_state);
  }
}
