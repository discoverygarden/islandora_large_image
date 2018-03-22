<?php

namespace Drupal\islandora_large_image\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

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

    foreach (Element::children($form) as $variable) {
      $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
    }
    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['islandora_large_image.settings'];
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    module_load_include('inc', 'islandora', 'includes/utilities');
    module_load_include('inc', 'islandora_large_image', 'includes/utilities');
    $get_default_value = function($name, $default) use(&$form_state) {
      // @FIXME
// // @FIXME
// // The correct configuration object could not be determined. You'll need to
// // rewrite this call manually.
// return isset($form_state['values'][$name]) ? $form_state['values'][$name] : variable_get($name, $default);

    };
    $imagemagick_supports_jp2000 = islandora_large_image_check_imagemagick_for_jpeg2000();
    $kakadu = $get_default_value('islandora_kakadu_url', '/usr/bin/kdu_compress');
    $form = [
      'islandora_lossless' => [
        '#type' => 'checkbox',
        '#title' => t("Create Lossless Derivatives"),
        '#default_value' => $get_default_value('islandora_lossless', FALSE),
        '#description' => t('Lossless derivatives are of higher quality but adversely affect browser performance.'),
      ],
      // Defaults to trying to use Kakadu if ImageMagick does not support JP2Ks.
    'islandora_use_kakadu' => [
        '#type' => 'checkbox',
        '#title' => t("Use Kakadu for image compression"),
        '#disabled' => !$imagemagick_supports_jp2000,
        '#default_value' => $get_default_value('islandora_use_kakadu', !$imagemagick_supports_jp2000) || !$imagemagick_supports_jp2000,
        '#description' => t("@kakadu offers faster derivative creation than the standard ImageMagick package. %magick_info", [
          '@kakadu' => \Drupal::l(t('Kakadu'), \Drupal\Core\Url::fromUri('http://www.kakadusoftware.com/')),
          '%magick_info' => $imagemagick_supports_jp2000 ?
          t('ImageMagick reports support for JPEG 2000.') :
          t('ImageMagick does not report support for JPEG 2000.'),
        ]),
      ],
      'islandora_large_image_uncompress_tiff' => [
        '#type' => 'checkbox',
        '#title' => t('Uncompress TIFF files prior to creating JP2 datastreams'),
        '#description' => t('The version of Kakadu shipped with djatoka does not support compressed TIFFs; therefore, it is likely desirable to uncompress the TIFF so Kakadu does not encounter an error. This will not change the original TIFF stored in the OBJ datastream. Only disable this if you are completely sure!'),
        '#default_value' => $get_default_value('islandora_large_image_uncompress_tiff', TRUE),
        '#states' => [
          'visible' => [
            ':input[name="islandora_use_kakadu"]' => [
              'checked' => TRUE
              ]
            ]
          ],
      ],
      'islandora_kakadu_url' => [
        '#type' => 'textfield',
        '#title' => t("Path to Kakadu"),
        '#default_value' => $kakadu,
        '#description' => t('Path to the kdu_compress executable.<br/>@msg', [
          '@msg' => islandora_executable_available_message($kakadu)
          ]),
        '#prefix' => '<div id="kakadu-wrapper">',
        '#suffix' => '</div>',
        '#ajax' => [
          'callback' => 'islandora_update_kakadu_url_div',
          'wrapper' => 'kakadu-wrapper',
          'effect' => 'fade',
          'event' => 'blur',
          'progress' => [
            'type' => 'throbber'
            ],
        ],
        '#states' => [
          'visible' => [
            ':input[name="islandora_use_kakadu"]' => [
              'checked' => TRUE
              ]
            ]
          ],
      ],
    ];
    module_load_include('inc', 'islandora', 'includes/solution_packs');
    $form += islandora_viewers_form('islandora_large_image_viewers', 'image/jp2');
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['reset'] = [
      '#type' => 'submit',
      '#value' => t('Reset to defaults'),
      '#weight' => 1,
      '#submit' => [
        'islandora_large_image_admin_submit'
        ],
    ];
    return parent::buildForm($form, $form_state);
  }

  public function _submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $op = $form_state->get(['clicked_button', '#id']);
    switch ($op) {
      case 'edit-reset':
        \Drupal::config('islandora_large_image.settings')->clear('islandora_large_image_viewers')->save();
        break;
    }
  }

}
