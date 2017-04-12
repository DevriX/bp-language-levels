<?php

if ( !defined('ABSPATH') ) {
    exit ( 'Direct Access Not Allowed!' . PHP_EOL );
}

class BpLanguageLevelsAdmin
{
    public $b;

    public static function instance() {
        static $instance = null;
        
        if ( null === $instance ) {
            $instance = new BpLanguageLevelsAdmin;
            $instance->setup();
        }

        return $instance;
    }

    private function setup()
    {
        $this->b = bp_language_levels();

    }

    public function init()
    {
        add_action('admin_menu', array($this, 'pages'));
        add_action('admin_menu', array($this, 'maybeUpdate'));
        add_action('admin_enqueue_scripts', array($this, 'scripts'));
    }

    public function pages()
    {
        add_submenu_page(
            'options-general.php',
            __('BuddyPress Language Levels', $this->b->domain),
            'BP Languages',
            'manage_options',
            'bp-languages',
            array($this, 'screen')
        );
    }

    public function scripts()
    {
        wp_register_script('bpll-admin', $this->b->plugin_url . 'assets/js/admin.js', array('jquery'), $this->b->version);
    }

    public function maybeUpdate()
    {
        if ( !isset($_POST['bl_save'], $_POST['bl_nonce']) )
            return;

        if ( !wp_verify_nonce($_POST['bl_nonce'], 'bl_admin') )
            return;

        $levels = $languages = null;

        if ( !empty($_POST['levels']) && is_array($_POST['levels']) ) {
            foreach ( $_POST['levels'] as $i=>$lvl ) {
                if ( !is_numeric($i) )
                    continue;

                $lvl['name'] = sanitize_text_field($lvl['name']);
                $lvl['icon'] = esc_attr($lvl['icon']);

                if ( !trim($lvl['name']) )
                    continue;

                $levels[] = $lvl;
            }
        }

        if ( !empty($_POST['languages']) && is_array($_POST['languages']) ) {
            foreach ( $_POST['languages'] as $lang ) {
                $lang = sanitize_text_field($lang);

                if ( !trim($lang) )
                    continue;

                $languages[] = $lang;
            }
        }

        $opt = array(
            'levels' => $levels,
            'languages' => $languages
        );

        if ( empty($opt['levels']) && empty($opt['languages']) ) {
            delete_option('bp_language_levels_options');
        } else {
            update_option('bp_language_levels_options', $opt);
        }

        $this->b->loadOpt();
    }

    public function screen()
    {
        wp_enqueue_script('bpll-admin');

        ?>
        <div class="wrap">
        <form method="post" id="poststuff">
            <div id="postbox-container" class="postbox-container">
                <div class="meta-box-sortables ui-sortable" id="normal-sortables">

                    <div class="postbox">
                        <h3 class="hndle"><span><?php _e('Languages', $this->b->domain); ?></span></h3>
                        <div class="inside">

                            <span class="lang-tpl" style="display:none">
                                <div class="lang-tpl-cont" style="margin-bottom: 5px">
                                    <input type="text" name="languages[]" placeholder="<?php esc_attr_e('Language', $this->b->domain); ?>" />
                                    <span class="button lang-tpl-rm" title="<?php esc_attr_e('remove language', $this->b->domain); ?>">&times;</span>
                                    </tr>
                                </div>
                            </span>
                            
                            <div class="bp-langs">
                                <?php if ( $this->b->opt['languages'] ) : ?>

                                    <?php foreach ( $this->b->opt['languages'] as $lang ) :?>

                                        <div class="lang-tpl-cont" style="margin-bottom: 5px">
                                            <input type="text" name="languages[]" placeholder="<?php esc_attr_e('Language', $this->b->domain); ?>"  value="<?php echo esc_attr($lang); ?>" />
                                            <span class="button lang-tpl-rm" title="<?php esc_attr_e('remove language', $this->b->domain); ?>">&times;</span>
                                            </tr>
                                        </div>

                                    <?php endforeach; ?>

                                <?php endif; ?>
                            </div>

                            <br/>

                            <span class="button add-lang"><?php _e('Add Language', $this->b->domain); ?></span>
                            
                        </div>
                    </div>

                    <div class="postbox">
                        <h3 class="hndle"><span><?php _e('Language Levels', $this->b->domain); ?></span></h3>
                        <div class="inside">
                            <span class="lvl-tpl" style="display:none">
                                <table class="lvl-tpl-cont">
                                    <tr>
                                        <td><?php _e('Level Name', $this->b->domain); ?></td>
                                        <td><input type="text" name="levels[__id__][name]" /></td>
                                    </tr>
                                    <tr>
                                        <td><?php _e('Icon URL', $this->b->domain); ?></td>
                                        <td><input type="text" name="levels[__id__][icon]" /></td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td><span class="button lvl-tpl-rm" title="<?php esc_attr_e('remove level', $this->b->domain); ?>">&times;</span></td>
                                    </tr>
                                </table>
                            </span>
                            
                            <div class="lang-levels">
                                <?php if ( $this->b->opt['levels'] ) : ?>

                                    <?php foreach ( $this->b->opt['levels'] as $level ) : $randy = rand(999,9999); ?>

                                        <table class="lvl-tpl-cont">
                                            <tr>
                                                <td><?php _e('Level Name', $this->b->domain); ?></td>
                                                <td><input type="text" name="levels[<?php echo $randy; ?>][name]" value="<?php echo esc_attr($level['name']); ?>" /></td>
                                            </tr>
                                            <tr>
                                                <td><?php _e('Icon URL', $this->b->domain); ?></td>
                                                <td><input type="text" name="levels[<?php echo $randy; ?>][icon]" value="<?php echo esc_attr($level['icon']); ?>" /></td>
                                            </tr>
                                            <tr>
                                                <td></td>
                                                <td><span class="button lvl-tpl-rm" title="<?php esc_attr_e('remove level', $this->b->domain); ?>">&times;</span></td>
                                            </tr>
                                        </table>

                                    <?php endforeach; ?>

                                <?php endif; ?>
                            </div>

                            <br/>

                            <span class="button add-lvl"><?php _e('Add Level', $this->b->domain); ?></span>
                        </div>
                    </div>

                    <div class="postbox">
                        <h3 class="hndle"><?php _e('Save Changes', $this->b->domain); ?></h3>
                        <div class="inside">
                            <p> 
                                <?php wp_nonce_field('bl_admin', 'bl_nonce'); ?>
                                <input type="submit" name="bl_save" class="button button-primary" value="<?php _e('Save Changes', $this->b->domain); ?>" />
                            </p>
                        </div>
                    </div>

                </div>
            </div>
        </form>

        </div>
        <?php
    }
}