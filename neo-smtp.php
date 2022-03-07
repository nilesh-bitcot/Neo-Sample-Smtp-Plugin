<?php 
/**
 * @package Neo SMTP Basic Example
 * @version 1.0.0
 */
/*
Plugin Name: Neo SMTP Basic Example
Plugin URI: 
Description: simple SMTP testing plugin developed to use on a local development environment with Mailtrap details.
Author: Nilesh Kumar Chouhan
Author URI: https://www.linkedin.com/in/nilesh-kumar-chouhan-wp-dev/
Version: 1.0.0
*/

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

$plugin = plugin_basename(__FILE__); 
add_filter("plugin_action_links_$plugin", 'neo_smtp_settings_link' );
function neo_smtp_settings_link($links) { 
    $settings_link = '<a href="options-general.php?page=neo-smtp-setting">'.__('Settings').'</a>'; 
    $links[] = $settings_link;
    return $links; 
}

add_action( 'phpmailer_init', 'neo_smtp_mailer_configuration', 10, 1); 
function neo_smtp_mailer_configuration(PHPMailer $mailer){ 
    $host       = get_option('neo-smtp-host');
    $port       = get_option('neo-smtp-port');
    $username   = get_option('neo-smtp-username');
    $password   = get_option('neo-smtp-password');
    $from       = get_option('neo-smtp-from');
    $fromname   = get_option('neo-smtp-fromname');
    $debug      = get_option('neo-smtp-debug');

    if( empty($host) || empty($port) || empty($username) || empty($password) ) return;

	$mailer->IsSMTP(); 
	$mailer->Host = $host;
	$mailer->Port = $port; 
	$mailer->SMTPAuth = true;
	$mailer->Username = $username; 
	$mailer->Password = $password; 
	$mailer->CharSet = "utf-8"; 
	if( $from ) $mailer->From = $from; 
	if( $fromname ) $phpmailer->FromName = $fromname; 
    if( $debug == 'true' ) $mailer->SMTPDebug = 2; 	
    // Additional settings… 
	// $mailer->SMTPSecure = "tls"; // Choose SSL or TLS, if necessary for your server 
} 

add_action('wp_mail_failed', 'smtplog_mailer_errors', 10, 1); 
function smtplog_mailer_errors( $wp_error ){ 
	$fn = ABSPATH . '/mail.log';
	$fp = fopen($fn, 'a'); 
	fputs($fp, "Mailer Error: " . $wp_error->get_error_message() ."\n"); 
	fclose($fp); 
	// error_log( $error->get_error_message() );
} 


add_action( 'admin_menu', 'register_neo_dashboard_menu_page' );
function register_neo_dashboard_menu_page(){
    add_options_page(
        __('Neo SMTP Setting'),
        __('Neo SMTP'),
        'manage_options',
        'neo-smtp-setting',
        'neo_smtp_setting_content',
        6
    );
}


function neo_smtp_setting_content(){
    echo '<div class="wrap">
    <h1>NEO SMTP Basic Example</h1>
    <form method="post" action="options.php">';
        settings_fields( 'neo_smtp_settings' );
        do_settings_sections( 'neo-smtp-setting' );
        submit_button();
    echo '</form><a href="#" id="neo-smtp-test_mail">'.__('Send a Test Mail').'</a></div>';
}


add_action( 'admin_init',  'neo_smtp_register_setting' );

function neo_smtp_register_setting(){

    $fields = [
        'host' => [__('Host <small>(For MailTrap :<em>smtp.mailtrap.io</em></small>)')],
        'port' => [__('Port (<small>For MailTrap : <em>2525</em></small>)')],
        'username' => [__('Username')],
        'password' => [__('Password')],
        'from' => [__('From Email ID')],
        'fromname' => [__('From Name')]
    ];

    add_settings_section(
        'neo_smtp_setting_section_321',
        __('Add MailTrap or other SMTP details'),
        '',
        'neo-smtp-setting'
    );

    foreach ($fields as $key => $value) {
        register_setting(
            'neo_smtp_settings',
            'neo-smtp-'.$key,
            'sanitize_text_field'
        );

        add_settings_field(
            'neo-smtp-'.$key,
            $value[0],
            'neo_smtp_textbox_callback',
            'neo-smtp-setting',
            'neo_smtp_setting_section_321',
            array( 
                'neo-smtp-'.$key,
                'label_for' => 'neo-smtp-'.$key
            )
        );
        
    }

    register_setting(
        'neo_smtp_settings',
        'neo-smtp-debug',
        'sanitize_text_field'
    );

    add_settings_field(
        'neo-smtp-debug',
        __('Enable Debug'),
        'neo_smtp_checkbox_callback',
        'neo-smtp-setting',
        'neo_smtp_setting_section_321',
        array( 
            'neo-smtp-debug',
            'label_for' => 'neo-smtp-debug'
        )
    );
}


function neo_smtp_textbox_callback($args) {
    $option = get_option($args[0]);
    echo '<input type="text" id="'. $args[0] .'" name="'. $args[0] .'" value="' . $option . '" />';
}

function neo_smtp_checkbox_callback(){
    $name = 'neo-smtp-debug';
    $option = get_option($name);
    echo '<input type="checkbox" id="'. $name .'" name="'. $name .'" value="true" '.($option == 'true'? 'checked' :'' ).' />';
}

add_action('wp_ajax_neo_smtp_testing', 'wp_ajax_neo_smtp_testing_callback');
function wp_ajax_neo_smtp_testing_callback(){
    if(wp_mail(get_option('admin_email'),'testing', 'neo smtp testing mail')){
        echo 'sent';
    }else{
        'fail';
    }
    wp_die();
}

add_action('admin_footer', 'neo_smtp_test_script');
function neo_smtp_test_script(){
    ?>
    <script>
        jQuery(document).ready(function($){
            let id = 'neo-smtp-test_mail';
            let button = document.getElementById(id);
            button.addEventListener('click', function(e){
                e.preventDefault();
                jQuery.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>?action=neo_smtp_testing',
                    method:'GET',
                    async:false,
                    success:function( response ){
                        if(response == 'sent'){
                            alert('mail send successfully');
                        }else{
                            alert('mail failed, please check mail.log on root for details');
                        }
                        
                    }
                });
            });
        })
    </script>
    <?php
}