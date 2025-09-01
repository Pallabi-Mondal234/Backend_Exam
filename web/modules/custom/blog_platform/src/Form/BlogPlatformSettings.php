<?php

namespace Drupal\blog_platform\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Admin config for blog platform API filters.
 */
class BlogPlatformSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'blog_platform_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['blog_platform.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('blog_platform.settings');

    $form['api_date_from'] = [
      '#type' => 'date',
      '#title' => $this->t('Default API date from'),
      '#default_value' => $config->get('api_date_from'),
    ];

    $form['api_date_to'] = [
      '#type' => 'date',
      '#title' => $this->t('Default API date to'),
      '#default_value' => $config->get('api_date_to'),
    ];

    // Authors: allow selection of multiple authors by uid (simple text list of uids)
    $form['api_authors'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default authors (comma separated uids)'),
      '#default_value' => $config->get('api_authors') ?? '',
      '#description' => $this->t('Enter user IDs comma separated, or leave empty for all authors.'),
    ];

    // Tags: allow comma separated term IDs.
    $form['api_tags'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default tags (comma separated term ids)'),
      '#default_value' => $config->get('api_tags') ?? '',
      '#description' => $this->t('Enter tag term IDs comma separated, or leave empty for all tags.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('blog_platform.settings')
      ->set('api_date_from', $form_state->getValue('api_date_from'))
      ->set('api_date_to', $form_state->getValue('api_date_to'))
      ->set('api_authors', $form_state->getValue('api_authors'))
      ->set('api_tags', $form_state->getValue('api_tags'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
