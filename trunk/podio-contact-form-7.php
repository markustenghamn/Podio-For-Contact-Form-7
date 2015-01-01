<?php
/*
Plugin Name: Podio for Contact Form 7
Plugin URI: http://MarkusTenghamn.com/podio-for-contact-form-7
Description: Integrates Podio with Contact Form 7
Version: 1.1
Author: Markus Tenghamn
Author URI: http://markustenghamn.com
License: GPL2
*/

(@include(plugin_dir_path(__FILE__) . 'lib/PodioAPI.php')) or die("The library could not be included");

add_action("wpcf7_before_send_mail", "add_to_podio");

add_action('admin_menu', 'podio_cf7_menu');

function add_to_podio($cf7)
{

    $clientid = get_option('pfcf7-Client-ID');
    $clientsecret = get_option('pfcf7-Client-Secret');
    $appid = get_option('pfcf7-App-ID');
    $apptoken = get_option('pfcf7-App-Token');
    $contacts = get_option('pfcf7-User-ID');
    $extrafield = get_option('pfcf7-extra-field');
    $removecf7fields = get_option('pfcf7-CF7fields');
    $debug = get_option('pfcf7-Debugging');
    $resultarr = array();

    if (version_compare(WPCF7_VERSION, '3.9.2') >= 0) {
        $submission = WPCF7_Submission::get_instance();
        if ($submission) {
            $data = array();
            //$data['title'] = $submission->title();
            $data['posted_data'] =& $submission->get_posted_data();
            $data['uploaded_files'] = $submission->uploaded_files();
            $cf7 = (object)$data;
            $cf7->posted_data['debug'] = "";
        }
        if ($debug) {
            $cf7->posted_data['debug'] .= 'Debug: The CF7 Version is higher than 3.9.2';
        }
    }

    if (strlen($clientid) > 1 && strlen($clientsecret) > 1 && strlen($appid) > 1 && strlen($apptoken) > 1) {

        $error = false;
        try {
            Podio::setup($clientid, $clientsecret);

            Podio::authenticate('app', array('app_id' => $appid, 'app_token' => $apptoken));
            $access_token = Podio::$oauth->access_token;
        } catch (PodioError $e) {
            $error = true;
            if ($debug) {
                $cf7->posted_data['debug'] .= 'Debug: PODIO ERROR! - ' . $e->body['error'] . ' ' . $e->body['error_description'];
            }
        }

        $used = array();
        $appinfo = PodioApp::get($appid, $attributes = array());
        foreach ($cf7->posted_data as $key => $val) {
            $add = true;
            $exclude = false;
            if ($removecf7fields) {
                $checkarray = array('_wpcf7', '_wpcf7_version', '_wpcf7_unit_tag', '_wpcf7_is_ajax_call', '_wpnonce', '_wpcf7_locale');
                foreach ($checkarray as $check) {
                    if ($check == $key) {
                        $exclude = true;
                    }
                }

            }
            if (!$exclude) {
                $found = false;
                foreach ($appinfo->fields as $c) {
                    $type = $c->type;
                    if ($type == 'text' || $type == 'location') {
                    $field = $c->external_id;
                    if (trim($field) == trim($key) && !$found) {
                        if ($extrafield == $field) {
                            $resultarr['fields'][$extrafield] .= $val;
                        } else {
                            $resultarr['fields'][$field] = $val;
                        }
                        $found = true;
                    }
                    }
                }
                if (!$found) {
                    if (strlen($extrafield) > 0) {
                        if (isset($resultarr['fields'][$extrafield])) {
                            $resultarr['fields'][$extrafield] .= $key.' - '.$val.'<br/>';
                        } else {
                            $resultarr['fields'][$extrafield] = $key.' - '.$val.'<br/>';
                        }
                    }
                }


            }

        }
        if (!$error && count($resultarr) > 0) {
            try {
                $res = PodioItem::create($appid, $resultarr);
            } catch (PodioError $e) {
                if ($debug) {
                    $cf7->posted_data['debug'] .= "<p>Debug: There was an error. The API responded with the error type <b>" . $e->body['error'] . "</b> and the message <b>" . $e->body['error_description'] . "</b> <a href=''>Retry</a></p>";
                }
            }
        }
    }
    return true;
}

function podio_cf7_menu()
{
    add_menu_page('Podio for CF7 Settings', 'Podio for CF7', 'administrator', __FILE__, 'pfcf7_settings_page', plugins_url('/images/icon.png', __FILE__));

    add_action('admin_init', 'register_pfcf7_settings');
}

function register_pfcf7_settings()
{
    //register settings
    register_setting('pfcf7-settings-group', 'pfcf7-Client-ID');
    register_setting('pfcf7-settings-group', 'pfcf7-Client-Secret');
    register_setting('pfcf7-settings-group', 'pfcf7-App-ID');
    register_setting('pfcf7-settings-group', 'pfcf7-App-Token');
    register_setting('pfcf7-settings-group', 'pfcf7-Space-URL');
    register_setting('pfcf7-settings-group', 'pfcf7-User-ID');
    register_setting('pfcf7-settings-group', 'pfcf7-Debugging');
    register_setting('pfcf7-settings-group', 'pfcf7-fields');
    register_setting('pfcf7-settings-group', 'pfcf7-extra-field');
    register_setting('pfcf7-settings-group', 'pfcf7-CF7fields');
}

function pfcf7_settings_page()
{
    ?>
    <div class="wrap">
        <h2>Podio for Contact Form 7</h2>

        <p>
            This plugin will take all the fields from contact form 7 and attempt to send this
            information to podio. Make sure your fields external id's match the field id's in
            contact form 7, otherwise these id's will be added to the extra field.
        </p>

        <form method="post" action="options.php">
            <?php settings_fields('pfcf7-settings-group'); ?>
            <?php do_settings_sections('pfcf7-settings-group'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Podio Client ID</th>
                    <td><input type="text" name="pfcf7-Client-ID" value="<?php echo get_option('pfcf7-Client-ID'); ?>"/>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">Podio Client Secret</th>
                    <td><input type="text" name="pfcf7-Client-Secret"
                               value="<?php echo get_option('pfcf7-Client-Secret'); ?>"/></td>
                </tr>

                <tr valign="top">
                    <th scope="row">Podio App ID</th>
                    <td><input type="text" name="pfcf7-App-ID" value="<?php echo get_option('pfcf7-App-ID'); ?>"/></td>
                </tr>

                <tr valign="top">
                    <th scope="row">Podio App Token</th>
                    <td><input type="text" name="pfcf7-App-Token" value="<?php echo get_option('pfcf7-App-Token'); ?>"/>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">Podio App URL</th>
                    <td><input type="text" name="pfcf7-Space-URL" value="<?php echo get_option('pfcf7-Space-URL'); ?>"/>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">User ID for Sales-Agent (optional)</th>
                    <td><input type="text" name="pfcf7-User-ID" value="<?php echo get_option('pfcf7-User-ID'); ?>"/>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">Extra info field external id (usually notes)</th>
                    <td><input type="text" name="pfcf7-extra-field"
                               value="<?php echo get_option('pfcf7-extra-field'); ?>"/>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">Remove CF7 fields</th>
                    <td><input type="checkbox" name="pfcf7-CF7fields"
                               value="debug" <?php if (get_option('pfcf7-CF7fields') == 'debug') {
                            echo 'CHECKED';
                        } ?>/>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">Enable Debugging</th>
                    <td><input type="checkbox" name="pfcf7-Debugging"
                               value="debug" <?php if (get_option('pfcf7-Debugging') == 'debug') {
                            echo 'CHECKED';
                        } ?>/>
                    </td>
                </tr>

            </table>

            <?php submit_button();

            $clientid = get_option('pfcf7-Client-ID');
            $clientsecret = get_option('pfcf7-Client-Secret');
            $appid = get_option('pfcf7-App-ID');
            $apptoken = get_option('pfcf7-App-Token');
            $debug = get_option('pfcf7-Debugging');
            $spaceurl = get_option('pfcf7-Space-URL');
            $contacts = get_option('pfcf7-User-ID');

            echo 'Podio Version ' . WPCF7_VERSION . '<br/>';
            echo 'Podio info: ';

            $error = false;
            try {

                Podio::setup($clientid, $clientsecret);

                Podio::authenticate('app', array('app_id' => $appid, 'app_token' => $apptoken));

                $access_token = Podio::$oauth->access_token;
            } catch (PodioError $e) {
                $error = true;
                echo 'Error! - ' . $e;
            }
            if (!$error) {

                try {
                    $item = PodioItem::filter($appid, $attributes = array(
                        'limit' => 20,
                        'sort_by' => 'created_on'
                    ));
                    echo 'Working';
                    if ($debug) {
                        echo '<br/>';
                        $appinfo = PodioApp::get($appid, $attributes = array());
                        $i = 0;
                        foreach ($appinfo->fields as $c) {
                            echo 'field ' . $i . ': ' . $c->external_id . '<br/>';
                            $i++;
                        }
                    }

                } catch (PodioError $e) {
                    echo "<p>There was an error. The API responded with the error type <b>" . $e->body['error'] . "</b> and the message <b>" . $e->body['error_description'] . "</b> <a href=''>Retry</a></p>";
                }
                $cf7 = array(
                    'posted_data' => array(
                        'company' => 'hello',
                        'notes' => 'this be a message'
                    )
                );
            } else {
                echo 'Unable to connect to Podio';
            }
            ?>

        </form>
    </div>
<?php
}

