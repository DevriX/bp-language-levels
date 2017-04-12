<?php
/*
Plugin Name: BuddyPress Language Levels
Plugin URI: https://samelh.com
Description: BuddyPress Language Levels
Version: 0.1
Author: Samuel Elh
Author URI: https://samelh.com
License: GPLv2 or later
Text Domain: bp-language-levels
*/

if ( !defined('ABSPATH') ) {
    exit ( 'Direct Access Not Allowed!' . PHP_EOL );
}

class BpLanguageLevels
{
    public $admin;

    public $plugin_file, $plugin_url, $plugin_base;
    public $version = '0.1';
    public $domain;

    public $opt_defaults;
    public $opt;

    public static function instance() {
        static $instance = null;
        
        if ( null === $instance ) {
            $instance = new BpLanguageLevels;
            $instance->setup();
        }

        return $instance;
    }

    private function setup()
    {
        $this->register();

        $this->loadTextDomain();

        $this->loadOpt();

        $this->autoload();

        add_action('wp_enqueue_scripts', array($this, 'scripts'));

        if ( is_admin() ) {
            $this->admin->init();
        }

        // embed
        add_action('bp_after_signup_profile_fields', array($this, 'signupField'));

        // validate
        add_action('bp_actions', array($this, 'maybeValidateSignup'));

        // store lang in wp_signups
        add_filter('bp_signup_usermeta', array($this, 'signupLang'));
        
        // sotre lang upon activation
        add_filter('bp_core_activated_user', array($this, 'signupLangActivate'), 10, 3);

        // add langs menu to profile settings
        add_action('bp_core_general_settings_before_submit', array($this, 'profileSettingsField'));

        // save lang fields
        add_action('bp_core_general_settings_after_save', array($this, 'saveProfileSettings'));

        // show langs
        add_action('bp_profile_header_meta', array($this, 'profileLangs2'));

        // add_action('BpMembersTabs_render_after_tabs_content', array($this, 'profileLangs2'));
    }

    private function register()
    {
        $this->domain = 'bp-language-levels';
        $this->plugin_file = __FILE__;
        $this->plugin_base = plugin_basename($this->plugin_file);
        $this->plugin_url = plugin_dir_url($this->plugin_file);
    }

    public function loadTextDomain()
    {
        return load_plugin_textdomain($this->domain, false, dirname($this->plugin_base).'/languages');
    }

    public function loadOpt()
    {
        $this->opt_defaults = array(
            'levels' => array(
                array()
            ),
            'languages' => array()
        );

        $this->opt = wp_parse_args(
            get_option('bp_language_levels_options'),
            $this->opt_defaults
        );
    }

    public function scripts()
    {
        wp_register_script('bpll-register', $this->plugin_url . 'assets/js/register.js', array('jquery'), $this->version);

        if ( function_exists('bp_is_current_component') && bp_is_current_component( 'register' ) ) {
            wp_enqueue_script('bpll-register');
        }
    }

    private function autoload()
    {
        require_once dirname(__FILE__) . '/admin.php';

        $this->admin = BpLanguageLevelsAdmin::instance();
    }

    public function maybeValidateSignup()
    {
        if ( !function_exists('bp_is_current_component') || !bp_is_current_component( 'register' ) )
            return;

        $this->validateSignup();
    }

    private function validateSignup()
    {
        global $bpll;

        $bpll = array();

        if ( !isset($_POST['bpll_lang']) || (empty($_POST['bpll_lang']) || !is_array($_POST['bpll_lang'])) )
            return;

        $all_levels = array();

        foreach ( $this->opt['levels'] as $level ) {
            $all_levels[] = $level['name'];
        }

        $all_languages = array();

        foreach ( $_POST['bpll_lang'] as $i=>$l ) {
            if ( !is_numeric($i) ) {
                unset($_POST['bpll_lang'][$i]);
                continue;
            }

            if ( !in_array($l['name'], $this->opt['languages']) ) {
                unset($_POST['bpll_lang'][$i]);
                continue;
            }

            if ( $l['level'] ) {
                if ( !in_array($l['level'], $all_levels) ) {
                    $_POST['bpll_lang'][$i]['level'] = null;
                }
            } else {
                $_POST['bpll_lang'][$i]['level'] = null;
            }

            if ( $all_languages && in_array($l['name'], $all_languages) ) {
                unset($_POST['bpll_lang'][$i]);
                continue;
            } else {
                $all_languages[] = $l['name'];
            }
        }

        $_POST['bpll_lang'] = array_values($_POST['bpll_lang']);

        $bpll = $_POST['bpll_lang'];
    }

    public function signupField()
    {
        global $bpll;

        if ( empty($this->opt['languages']) )
            return;

        wp_enqueue_script('bpll-register');

        ?>

        <div class="editfield field_languages visibility-public" style="overflow: hidden; clear: both;">
            
            <label><?php _e('Languages you know', $this->domain); ?></label>

            <div style="display: none; margin-bottom: 5px" class="lang-tpl lang-container">
                <select name="bpll_lang[__id__][name]">
                    <option value=""><?php _e('&mdash; Choose Language &mdash;'); ?></option>
                    
                    <?php foreach ( $this->opt['languages'] as $lang ) : ?>
                        <option><?php echo esc_attr($lang); ?></option>
                    <?php endforeach; ?>
                </select>

                <select name="bpll_lang[__id__][level]">
                    <option value=""><?php _e('&mdash; Select your Level &mdash;'); ?></option>
                    
                    <?php foreach ( $this->opt['levels'] as $lvl ) : ?>
                        <option><?php echo esc_attr($lvl['name']); ?></option>
                    <?php endforeach; ?>
                </select>

                <a href="javascript:;" title="<?php esc_attr_e('Remove language', $this->domain); ?>" class="rm-lang"><?php _e('[-]', $this->domain); ?></a>
            </div>

            <div class="langs-cont">
                
                <?php if ( $bpll ) : ?>

                    <?php foreach ( $bpll as $l ) : $randy = rand(999, 9999); ?>

                        <div style="margin-bottom: 5px" class="lang-tpl lang-container">
                            <select name="bpll_lang[<?php echo $randy; ?>][name]">
                                <option value=""><?php _e('&mdash; Choose Language &mdash;'); ?></option>
                                
                                <?php foreach ( $this->opt['languages'] as $lang ) : ?>
                                    <option <?php selected($lang, $l['name']); ?>><?php echo esc_attr($lang); ?></option>
                                <?php endforeach; ?>
                            </select>

                            <select name="bpll_lang[<?php echo $randy; ?>][level]">
                                <option value=""><?php _e('&mdash; Select your Level &mdash;'); ?></option>
                                
                                <?php foreach ( $this->opt['levels'] as $lvl ) : ?>
                                    <option <?php selected($lvl['name'], $l['level']); ?>><?php echo esc_attr($lvl['name']); ?></option>
                                <?php endforeach; ?>
                            </select>

                            <a href="javascript:;" title="<?php esc_attr_e('Remove language', $this->domain); ?>" class="rm-lang"><?php _e('[-]', $this->domain); ?></a>
                        </div>

                    <?php endforeach; ?>

                <?php endif; ?>

            </div>

            <a href="javascript:;" class="add-lang"><?php _e('[&plus; Add Languages]', $this->domain); ?></a>

        </div>

        <?php
    }

    public function signupLang($usermeta)
    {
        global $bpll;
        
        if ( $bpll && !empty($bpll) ) {
            $usermeta['bpll'] = $bpll;
        }

        return $usermeta;
    }

    public function signupLangActivate( $user_id, $key, $user )
    {
        if ( isset( $user['meta']['bpll'] ) && $user_id && is_array($user['meta']['bpll']) ) {
            return update_user_meta($user_id, 'bp_language_levels', $user['meta']['bpll']);
        }
    }

    public function getUserLangs($user_id)
    {
        $langs = (array) get_user_meta($user_id, 'bp_language_levels', true);

        $langs = array_filter($langs, 'is_array');

        return $langs;
    }

    public function profileSettingsField()
    {
        $user_id = bp_displayed_user_id();

        $bpll = $this->getUserLangs($user_id);

        if ( empty($this->opt['languages']) )
            return;

        wp_enqueue_script('bpll-register');

        ?>

        <div class="seven columns editfield field_languages visibility-public">
            
            <label><?php _e('Languages you know', $this->domain); ?></label>

            <div style="display: none; margin-bottom: 5px" class="lang-tpl lang-container">
                <select name="bpll_lang[__id__][name]">
                    <option value=""><?php _e('&mdash; Choose Language &mdash;'); ?></option>
                    
                    <?php foreach ( $this->opt['languages'] as $lang ) : ?>
                        <option><?php echo esc_attr($lang); ?></option>
                    <?php endforeach; ?>
                </select>

                <select name="bpll_lang[__id__][level]">
                    <option value=""><?php _e('&mdash; Select your Level &mdash;'); ?></option>
                    
                    <?php foreach ( $this->opt['levels'] as $lvl ) : ?>
                        <option><?php echo esc_attr($lvl['name']); ?></option>
                    <?php endforeach; ?>
                </select>

                <a href="javascript:;" title="<?php esc_attr_e('Remove language', $this->domain); ?>" class="rm-lang"><?php _e('[-]', $this->domain); ?></a>
            </div>

            <div class="langs-cont">
                
                <?php if ( $bpll ) : ?>

                    <?php foreach ( $bpll as $l ) : $randy = rand(999, 9999); ?>

                        <div style="margin-bottom: 5px" class="lang-tpl lang-container">
                            <select name="bpll_lang[<?php echo $randy; ?>][name]">
                                <option value=""><?php _e('&mdash; Choose Language &mdash;'); ?></option>
                                
                                <?php foreach ( $this->opt['languages'] as $lang ) : ?>
                                    <option <?php selected($lang, $l['name']); ?>><?php echo esc_attr($lang); ?></option>
                                <?php endforeach; ?>
                            </select>

                            <select name="bpll_lang[<?php echo $randy; ?>][level]">
                                <option value=""><?php _e('&mdash; Select your Level &mdash;'); ?></option>
                                
                                <?php foreach ( $this->opt['levels'] as $lvl ) : ?>
                                    <option <?php selected($lvl['name'], $l['level']); ?>><?php echo esc_attr($lvl['name']); ?></option>
                                <?php endforeach; ?>
                            </select>

                            <a href="javascript:;" title="<?php esc_attr_e('Remove language', $this->domain); ?>" class="rm-lang"><?php _e('[-]', $this->domain); ?></a>
                        </div>

                    <?php endforeach; ?>

                <?php endif; ?>

            </div>

            <a href="javascript:;" class="add-lang"><?php _e('[&plus; Add Languages]', $this->domain); ?></a>

        </div>

        <?php
    }

    public function saveProfileSettings()
    {
        $user_id = bp_displayed_user_id();

        $this->validateSignup();

        global $bpll;

        if ( $bpll && !empty($bpll) ) {
            update_user_meta($user_id, 'bp_language_levels', $bpll);
        } else {
            delete_user_meta($user_id, 'bp_language_levels');
        }

        // show success
        bp_core_add_message(__('Your languages were successfully updated.', $this->domain), 'success');
    }

    public function profileLangs()
    {
        // get user id
        $user_id = bp_displayed_user_id();

        $langs = $this->getUserLangs($user_id);

        if ( !$langs )
            return;

        print '<strong class="bp_language_levels-heading">' . __('Languages:', $this->domain) . '</strong>';

        print '<ul class="bp_language_levels">';

        foreach ( $langs as $lang ) {

            print '<li>';

            if ( $lang['level'] ) {
                $lang['level'] = $this->getLevel($lang['level']);

                if ( !empty($lang['level']['name']) ) {

                    print '<span class="level">';

                    if ( $lang['level']['icon'] ) {
                        printf ( '<img src="%s" alt="%s" />', esc_url($lang['level']['icon']), $lang['level']['name'] );
                    }

                    print $lang['level']['name'];

                    print '</span>';

                }
            }

            print $lang['name'];

            print '</li>';
        }

        print '</ul>';
    }

    public function profileLangs2()
    {
        // get user id
        $user_id = bp_displayed_user_id();

        $langs = $this->getUserLangs($user_id);

        if ( !$langs )
            return;

        ?>

        <p active="" id="bp-languages-snippet" class="active regulartab">

            <dl class="dl-horizontal">
                <dt class="bp-field-langs bp-field-id-1"><?php _e('Languages', $this->domain); ?></dt>

                <dd class="bp-field-value bp-field-id-1">
                    <?php foreach ( $langs as $lang ) : ?>

                            <?php if ( $lang['level'] ) {
                                $lang['level'] = $this->getLevel($lang['level']);

                                if ( !empty($lang['level']['name']) ) {
                                    print '<li>';

                                    if ( $lang['level']['icon'] ) {
                                        printf ( '<img src="%1$s" alt="%2$s" title="%2$s" />', esc_url($lang['level']['icon']), $lang['level']['name'] );

                                        print $lang['name'];
                                    } else {
                                        print $lang['name'];

                                        print " <em>({$lang['level']['name']})</em>";
                                    }

                                    print '</li>';
                                }
                            } ?>

                    <?php endforeach; ?>

                </dd>

            </dl>

        </p>

        <?php
    }

    public function getLevel($name)
    {
        if ( !$this->opt['levels'] )
            return array();

        foreach ( $this->opt['levels'] as $level ) {
            if ( $level['name'] == $name )
                return $level;
        }

        return array();
    }
}

function bp_language_levels() {
    return BpLanguageLevels::instance();
}

add_action('plugins_loaded', 'bp_language_levels');