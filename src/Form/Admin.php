<?php

namespace Drupal\islandora_large_image\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Module settings form.
 */
class Admin extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'islandora_large_image_admin';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('islandora_large_image.settings');

    $config->set('islandora_kakadu_url', $form_state->getValue('islandora_kakadu_url'));
    $config->set('islandora_lossless', $form_state->getValue('islandora_lossless'));
    $config->set('islandora_use_kakadu', $form_state->getValue('islandora_use_kakadu'));
    $config->set('islandora_large_image_uncompress_tiff', $form_state->getValue('islandora_large_image_uncompress_tiff'));

    $config->save();

    islandora_set_viewer_info('islandora_large_image_viewers', $form_state->getValue('islandora_large_image_viewers'));
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['islandora_large_image.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form_state->loadInclude('islandora', 'inc', 'includes/solution_packs');
    $form_state->loadInclude('islandora', 'inc', 'includes/utilities');
    $form_state->loadInclude('islandora_large_image', 'inc', 'includes/utilities');
    $form_state->loadInclude('islandora_large_image', 'inc', 'includes/admin.form');

    $imagemagick_supports_jp2000 = islandora_large_image_check_imagemagick_for_jpeg2000();
    $kakadu = $form_state->getValue('islandora_kakadu_url') !== NULL ? $form_state->getValue('islandora_kakadu_url') : $this->config('islandora_large_image.settings')->get('islandora_kakadu_url');
    $form = [
      'islandora_lossless' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Create Lossless Derivatives'),
        '#default_value' => $this->config('islandora_large_image.settings')->get('islandora_lossless'),
        '#description' => $this->t('Lossless derivatives are of higher quality but adversely affect browser performance.'),
      ],
      'islandora_use_kakadu' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Use Kakadu for image compression'),
        '#default_value' => $this->config('islandora_large_image.settings')->get('islandora_use_kakadu'),
        '#description' => $this->t("@kakadu offers faster derivative creation than the standard ImageMagick package. %magick_info", [
          '@kakadu' => Link::fromTextAndUrl($this->t('Kakadu'), Url::fromUri('http://www.kakadusoftware.com/'))->toString(),
          '%magick_info' => $imagemagick_supports_jp2000 ?
          $this->t('ImageMagick reports support for JPEG 2000.') :
          $this->t('ImageMagick does not report support for JPEG 2000.'),
        ]),
      ],
      'islandora_large_image_uncompress_tiff' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Uncompress TIFF files prior to creating JP2 datastreams'),
        '#description' => $this->t('The version of Kakadu shipped with djatoka does not support compressed TIFFs; therefore, it is likely desirable to uncompress the TIFF so Kakadu does not encounter an error. This will not change the original TIFF stored in the OBJ datastream. Only disable this if you are completely sure!'),
        '#default_value' => $this->config('islandora_large_image.settings')->get('islandora_large_image_uncompress_tiff'),
        '#states' => [
          'visible' => [
            ':input[name="islandora_use_kakadu"]' => [
              'checked' => TRUE,
            ],
          ],
        ],
      ],
      'kdu' => [
        '#prefix' => '<div id="kakadu-wrapper">',
        '#suffix' => '</div>',
        '#type' => 'item',
        'islandora_kakadu_url' => [
          '#type' => 'textfield',
          '#title' => $this->t('Path to Kakadu'),
          '#default_value' => $kakadu,
          '#description' => $this->t('Path to the kdu_compress executable.'),
          '#ajax' => [
            'callback' => 'islandora_update_kakadu_url_div',
            'wrapper' => 'kakadu-wrapper',
            'effect' => 'fade',
            'event' => 'blur',
            'progress' => [
              'type' => 'throbber',
            ],
          ],
        ],
        'message' => ['#markup' => islandora_executable_available_message($kakadu)],
        '#states' => [
          'visible' => [
            ':input[name="islandora_use_kakadu"]' => [
              'checked' => TRUE,
            ],
          ],
        ],
      ],
    ];
    $form += islandora_viewers_form('islandora_large_image_viewers', 'image/jp2');
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];
    return $form;
  }

}
