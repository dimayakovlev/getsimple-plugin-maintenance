<?php
/*
Plugin Name: Maintenance
Description: Enabling maintenance mode of the website
Version: 1.0
Author: Dmitry Yakovlev
Author URI: http://dimayakovlev.ru/
*/

$thisfile = basename(__FILE__, '.php');

if (!is_frontend()) i18n_merge($thisfile) || i18n_merge($thisfile, 'en_US');

register_plugin(
  $thisfile,
  i18n_r($thisfile.'/TITLE'),
  '1.0',
  i18n_r($thisfile.'/AUTHOR'),
  'http://dimayakovlev.ru',
  i18n_r($thisfile.'/DESCRIPTION'),
  '',
  ''
);
/*
add_action('index-post-dataindex', function() {
  global $dataw;
  if ($dataw->maintenanceEnabled == '1') {
    // Отключения кэширования с использованием плагина Cache
    if (function_exists('isCache')) {
      if (isCache()) disableCache();
    }
  }
});
*/
add_action('index-pretemplate', function() {
  global $dataw, $TEMPLATE;
  
  if ($dataw->maintenanceEnabled == '1' && (!is_logged_in() || $dataw->maintenanceRegisteredUsers != '1')) {
    $protocol = ('HTTP/1.1' == $_SERVER['SERVER_PROTOCOL']) ? 'HTTP/1.1' : 'HTTP/1.0';
    header($protocol . ' 503 Service Unavailable', true, 503);
    header('Retry-After: 3600');
    if ($dataw->maintenanceUseTemplate == '1' && is_readable($maintenance_template = GSTHEMESPATH . $TEMPLATE . '/maintenance.php')) {
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
      get_maintenance_message(true);
      #if (function_exists('getYandexMetrika')) getYandexMetrika();
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
  $thisfile = basename(__FILE__, '.php');
  # Temporary solution
  if(isset($_POST['submitted'])) {
    i18n_merge($thisfile) || i18n_merge($thisfile, 'en_US');
    $dataw = getXML(GSDATAOTHERPATH . 'website.xml');
  } else {
    global $dataw;
  }
  #
  $maintenance_enabled = !empty($dataw->maintenanceEnabled) ? ' checked' : '';
  $maintenance_registered_users = !empty($dataw->maintenanceRegisteredUsers) ? ' checked' : '';
?>
<div class="section" id="maintenance">
  <h3><?php i18n($thisfile.'/TITLE'); ?></h3>
  <p class="inline">
    <input type="checkbox" name="maintenanceEnabled" value="1"<?php echo $maintenance_enabled; ?>>
    <label for="maintenanceEnabled"><strong><?php i18n($thisfile.'/ENABLE_MAINTENANCE'); ?></strong> - <?php i18n($thisfile.'/ENABLE_MAINTENANCE_LABEL'); ?></label>
  </p>
  <p class="inline">
    <input type="checkbox" name="maintenanceRegisteredUsers" value="1"<?php echo $maintenance_registered_users; ?>>
    <label for="maintenanceRegisteredUsers"><?php i18n($thisfile.'/IGNORE_MAINTENANCE'); ?></label>
  </p>
<?php
  if (is_readable(GSTHEMESPATH . $TEMPLATE . '/maintenance.php')) {
    $maintenance_use_template = !empty($dataw->maintenanceUseTemplate) ? ' checked' : '';
?>
  <p class="inline">
    <input type="checkbox" name="maintenanceUseTemplate" value="1"<?php echo $maintenance_use_template; ?>>
    <label for="maintenanceUseTemplate"><?php i18n($thisfile.'/USE_TEMPLATE'); ?></label>
  </p>
<?php
  }
?>
  <p>
    <label for="maintenanceMessage"><?php i18n($thisfile.'/MESSAGE_LABEL'); ?>:</label>
    <textarea name="maintenanceMessage" class="text short charlimit" style="height: 62px;"<?php if ($maintenance_enabled) echo ' required';?>><?php echo strip_decode($dataw->maintenanceMessage); ?></textarea>
  </p>
</div>
<script>
  $(document).ready(function() {
    $('input[name="maintenanceEnabled"]').click(function() {
      $('textarea[name="maintenanceMessage"]').prop('required', $(this).prop('checked'));
    });
<?php
  if ($maintenance_enabled) {
?>
    $('.bodycontent').before('<div class="notify maintenance-notification" style="display:block;"><?php echo sprintf(i18n_r($thisfile.'/MAINTENANCE_WARNING'), myself(false).'#maintenance'); ?></div>');
    $('.maintenance-notification').fadeOut(500).fadeIn(500);
<?php
  }
?>
  });
</script>
<?php
});

add_action('settings-website', function () {
  global $xmls;
  $xmls->addChild('maintenanceEnabled', isset($_POST['maintenanceEnabled']));
  $xmls->addChild('maintenanceRegisteredUsers', isset($_POST['maintenanceRegisteredUsers']));
  $xmls->addChild('maintenanceUseTemplate', isset($_POST['maintenanceUseTemplate']));
  $xmls->addChild('maintenanceMessage')->addCData(isset($_POST['maintenanceMessage']) ? safe_slash_html($_POST['maintenanceMessage']) : '');
});

/**
 * Get Maintenance Message
 * 
 * This will echo or return the website maintenance message
 */
function get_maintenance_message($echo = true) {
  global $dataw;
  if ($echo) {
    echo strip_decode($dataw->maintenanceMessage);
  } else {
    return strip_decode($dataw->maintenanceMessage);
  }
}

function isMaintenance() {
  global $dataw;
  if ($dataw->maintenanceEnabled == '1') {
    return true;
  } else {
    return false;
  }
}
