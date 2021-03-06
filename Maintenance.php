<?php
/*
Plugin Name: Maintenance
Description: Enabling maintenance mode of the website
Version: 1.0
Author: Dmitry Yakovlev
Author URI: http://dimayakovlev.ru/
*/

$thisfile = basename(__FILE__, '.php');

register_plugin(
  $thisfile,
  'Maintenance',
  '1.0',
  'Dmitry Yakovlev',
  'http://dimayakovlev.ru',
  'Включение режима технического обслуживание веб-сайта',
  '',
  ''
);

add_action('index-pretemplate', function() {
  global $dataw;
  global $TEMPLATE;
  global $USR;
  
  if ($dataw->maintenance == '1' && $USR == null) {
    $protocol = ('HTTP/1.1' == $_SERVER['SERVER_PROTOCOL']) ? 'HTTP/1.1' : 'HTTP/1.0';
    header($protocol . ' 503 Service Unavailable', true, 503);
    header('Retry-After: 3600');
    if ($dataw->maintenance_ignore_template != '1' && is_readable($maintenance_template = GSTHEMESPATH . $TEMPLATE . '/maintenance.php')) {
      include_once $maintenance_template;
    } else {
?><!DOCTYPE html>
<html lang="<?php echo get_site_lang(true); ?>">
  <head>
    <meta charset="utf-8">
    <title><?php get_site_name(); ?></title>
  </head>
  <body>
    <?php
      get_maintenance_message();
      if (function_exists('dyYandexMetrika')) echo dyYandexMetrika();
    ?>
  </body>
</html>
<?php
    }
    die; 
  }
});

add_action('settings-website-extras', function() {
  global $TEMPLATE;
  $dataw = getXML(GSDATAOTHERPATH . 'website.xml');
?>
<div class="section" id="maintenance">
  <p class="inline">
    <input type="checkbox" name="maintenance" value="1"<?php echo $dataw->maintenance == '1' ? ' checked="checked"' : ''; ?>>
    <label for="maintenance"><strong>Включить техническое обслуживание</strong> - Страницы веб-сайта будут недоступны для посетителей</label>
  </p>
<?php
  if (is_readable(GSTHEMESPATH . $TEMPLATE . '/maintenance.php')) {
?>
  <p class="inline">
    <input type="checkbox" name="maintenance_ignore_template" value="1"<?php echo $dataw->maintenance_ignore_template == '1' ? ' checked="checked"' : ''; ?>>
    <label for="maintenance_ignore_template">Не использовать шаблон <em>maintenance.php</em> темы оформления</label>
  </p>
<?php
  }
?>
  <p>
    <label for="maintenance_message">Текст сообщения для посетителей:</label>
    <textarea name="maintenance_message" class="text short charlimit" style="height: 62px;"<?php if ($dataw->maintenance == '1') echo ' required';?>><?php get_maintenance_message(); ?></textarea>
  </p>
</div>
<script>
  $(document).ready(function() {
    $('input[name="maintenance"]').click(function() {
      $('textarea[name="maintenance_message"]').prop('required', $(this).prop('checked'));
    });
  });
</script>
<?php
});

add_action('settings-website', function () {
  global $xmls;
  
  $xmls->addChild('maintenance', isset($_POST['maintenance']));
  $xmls->addChild('maintenance_ignore_template', isset($_POST['maintenance_ignore_template']));
  $xmls->addChild('maintenance_message')->addCData(isset($_POST['maintenance_message']) ? safe_slash_html($_POST['maintenance_message']) : '');
  
});

/**
 * Get Maintenance Message
 * 
 * This will echo or return the website maintenance message
 */
function get_maintenance_message($echo = true) {
  global $dataw;
 
  if ($echo) {
    echo strip_decode($dataw->maintenance_message);
  } else {
    return strip_decode($dataw->maintenance_message);
  }
}
