<?php

namespace Drupal\islandora_large_image\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Upload form when ingesting image objects.
 */
class ImageUpload extends FormBase {

  protected $fileEntityStorage;

  /**
   * Constructor.
   */
  public function __construct(EntityStorageInterface $file_entity_storage) {
    $this->fileEntityStorage = $file_entity_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('file')
    );
  }

  /**
   * Defines a file upload form for uploading large images.
   *
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'islandora_large_image_image_upload_form';
  }

  /**
   * Submit handler, adds uploaded file to ingest object.
   *
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $upload_size = min((int) ini_get('post_max_size'), (int) ini_get('upload_max_filesize'));
    $extensions = ['tiff tif jp2'];
    $upload_required = $this->config('islandora.settings')->get('islandora_require_obj_upload');

    return [
      'file' => [
        '#title' => $this->t('Image'),
        '#type' => 'managed_file',
        '#required' => $upload_required,
        '#description' => $this->t('Select image to upload.<br/>Files must be less than <strong>@size MB.</strong><br/>Allowed file types: <strong>@ext.</strong>', ['@size' => $upload_size, '@ext' => $extensions[0]]),
        '#default_value' => $form_state->getValue('file') ? $form_state->getValue('file') : NULL,
        '#upload_location' => 'temporary://',
        '#upload_validators' => [
          'file_validate_extensions' => $extensions,
          // Assume its specified in MB.
          'file_validate_size' => [$upload_size * 1024 * 1024],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $object = islandora_ingest_form_get_object($form_state);
    if ($form_state->getValue('file')) {
      if (empty($object['OBJ'])) {
        $ds = $object->constructDatastream('OBJ', 'M');
        $object->ingestDatastream($ds);
      }
      else {
        $ds = $object['OBJ'];
      }
      $file = $this->fileEntityStorage->load(reset($form_state->getValue('file')));
      $ds->setContentFromFile($file->getFileUri(), FALSE);
      $ds->label = $file->getFilename();
      $ds->mimetype = $file->getMimeType();
    }
  }

}
