<?php

/**
 * @file
 * Utility functions.
 */

use Drupal\imagemagick\ImagemagickExecArguments;

/**
 * Determine if ImageMagick appears to support JPEG-2000.
 *
 * @return bool
 *   If JPEG-2000 appears in the list of supported formats.
 */
function islandora_large_image_check_imagemagick_for_jpeg2000() {
  $supports =& drupal_static(__FUNCTION__);

  if ($supports === NULL) {
    $command_output = NULL;

    $exec_manager = \Drupal::service('imagemagick.exec_manager');
    $args = new ImagemagickExecArguments($exec_manager);
    $args->addArgument('-list format');
    $ret = -1;
    $exec_manager->execute('convert', $args, $command_output, $ret);
    $command_output = explode(PHP_EOL, $command_output);
    $filtered = preg_filter("/.*JP2.*JPEG-2000\s+File\s+Format\s+Syntax/", "\\0", $command_output);
    $supports = FALSE;
    foreach ($filtered as $f) {
      $s = [];
      if (preg_match_all('/([r-][w-].)/', $f, $s)) {
        if (strpos($s[1][0], 'w') !== FALSE) {
          $supports = TRUE;
          break;
        }
      }
    }
  }

  return $supports;
}

/**
 * Determine the bit depth of the image file.
 *
 * @param string $file_uri
 *   The file uri to determine the bit depth of.
 *
 * @return bool|int
 *   The bit depth of the image if the file exists, FALSE otherwise.
 */
function islandora_large_image_get_bit_depth($file_uri) {
  module_load_include('inc', 'islandora_large_image', 'includes/derivatives');
  if (file_exists($file_uri)) {
    $file_path = escapeshellarg(\Drupal::service('file_system')->realpath($file_uri));
    $identify = islandora_large_image_get_identify();
    return exec(escapeshellcmd("$identify -format %[depth] ") . $file_path);
  }
  else {
    return FALSE;
  }
}
