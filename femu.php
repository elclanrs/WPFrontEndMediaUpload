<?php
/*
Plugin Name: Front End Media Upload
Description: Easy front-end iframe single file upload with preview
Author: Cedric Ruiz
*/

require_once('polyfills.php');

FrontEndMediaUpload::init();

class FrontEndMediaUpload
{
  static function init()
  {
    add_action('wp_enqueue_scripts', array(__CLASS__, 'assets'));

    add_action('wp_ajax_femu', array(__CLASS__, 'preview'));
    add_action('wp_ajax_nopriv_femu', array(__CLASS__, 'preview'));
  }

  static function assets()
  {
    wp_register_script('front-end-media-upload', plugins_url('femu.js', __FILE__), array('jquery'));
    wp_enqueue_script('front-end-media-upload');

    wp_register_style('front-end-media-upload', plugins_url('femu.css', __FILE__));
    wp_enqueue_style('front-end-media-upload');

    wp_localize_script('front-end-media-upload', 'FrontEndMediaUpload', array(
      'loader' => plugins_url('loading.gif', __FILE__),
    ));
  }

  static function form($name, $options = array())
  {
    $defaults = array(
      'extensions' => array('jpg'),
      'dimensions' => array(200,200)
    );
    $options = array_replace_recursive($defaults, $options);
    $filesize = (int) ini_get('upload_max_filesize');
  ?>
    <form id="<?= $name ?>" class="wp-femu-form" method="post" target="<?php echo 'wp-femu-iframe-'. $name ?>" action="<?= admin_url('admin-ajax.php?action=femu') ?>" enctype="multipart/form-data" >
      <div class="wp-femu-preview"><div class="wp-femu-preview-inner"><span>Preview</span></div></div>
      <iframe class='wp-femu-iframe' name="<?php echo 'wp-femu-iframe-'. $name ?>" src=""></iframe>
      <input type="file" id="wp-femu-file-<?= $name ?>" name="<?= $name ?>" />
      <input type="hidden" name="wp-femu-option-extensions" value="<?= base64_encode(serialize($options['extensions'])) ?>"/>
      <input type="hidden" name="wp-femu-option-dimensions" value="<?= base64_encode(serialize($options['dimensions'])) ?>"/>
      <input type="hidden" name="wp-femu-option-filesize" value="<?= $filesize ?>"/>
    </form>
  <?php
  }

  // Upload image to media gallery
  static function upload($filename)
  {
    $path = ABSPATH ."wp-content/uploads/temp/$filename";

    if (file_exists($path)) {
      $file_array = array('tmp_name' => $path, 'name' => $filename);
      return media_handle_sideload($file_array, null);
    }
    return false;
  }

  // Preview image in iframe
  static function preview()
  {
    $extensions = unserialize(base64_decode($_POST['wp-femu-option-extensions']));
    $dimensions = unserialize(base64_decode($_POST['wp-femu-option-dimensions']));

    foreach ($_FILES as $field => $file) {

      if ($file['error'] === UPLOAD_ERR_OK) {

        $id = "user_". uniqid();
        $temp = "wp-content/uploads/temp/$id-{$file['name']}";
        $path = ABSPATH . $temp;
        $preview = site_url('/') . $temp;

        // Check for valid extensions
        if (! self::checkExtensions($file, $extensions)) {
          $error = sprintf('The image must have a valid extension (%s).', implode(',', $extensions));
          echo sprintf('<span>%s</span>', $error);
          break;
        }

        // Move file to temp folder and setup correct permissions
        move_uploaded_file($file['tmp_name'], $path);
        $stat = stat(dirname($path));
        chmod($path, $stat['mode'] & 0000666);

        // Check image dimensions
        if (! self::checkDimensions($path, $dimensions)) {
          echo sprintf('<span>%s</span>', "The image dimensions are too small. Must be at least {$dimensions[0]}x{$dimensions[1]} px.");
          unlink($path);
          break;
        }

        self::cropImage($path, $dimensions);

        // Print image
        echo sprintf('<img class="wp-femu-image" src="%s" />', $preview);

      // Errors
      } elseif ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
        echo sprintf('<span>%s</span>','The filesize is too big.');
      } elseif ($file['error'] === UPLOAD_ERR_NO_FILE) {
        echo sprintf('<span>%s</span>', 'Preview');
      } else {
        echo sprintf('<span>%s</span>', 'An error ocurred, please try again.');
      }
    }

    exit;
  }

  static function checkExtensions($file, $extensions)
  {
    $filetype = wp_check_filetype($file['name']);
    return (bool) preg_match('/'. implode('|', $extensions) .'/', $filetype['ext']);
  }

  static function checkDimensions($path, $dimensions)
  {
    $size = getimagesize($path);
    return $size[0] > $dimensions[0] || $size[1] > $dimensions[1];
  }

  static function cropImage($path, $dimensions)
  {
    $image = wp_get_image_editor($path);
    $image->resize($dimensions[0], $dimensions[1], true);
    $image->save($path);
  }
}
