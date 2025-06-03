<?php
/**
 * Theme Functions
 */

// Theme Setup
function theme_setup() {
    // Add theme support
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
    ));
    
    // Register navigation menu
    register_nav_menus(array(
        'primary' => esc_html__('Primary Menu', 'textdomain'),
    ));
    
    // Disable emojis for better performance
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('wp_print_styles', 'print_emoji_styles');
    
    // Remove unnecessary meta tags
    remove_action('wp_head', 'wp_generator');
    remove_action('wp_head', 'wlwmanifest_link');
    remove_action('wp_head', 'rsd_link');
}
add_action('after_setup_theme', 'theme_setup');

// Enqueue Scripts and Styles
function theme_scripts() {
    // Enqueue main stylesheet
    wp_enqueue_style('theme-style', get_stylesheet_uri(), array(), '1.0.0');
    
    // Enqueue navigation arrows script with defer for better performance
    wp_enqueue_script('nav-arrows', get_template_directory_uri() . '/js/nav-arrows.js', array(), '1.0.0', true);
}
add_action('wp_enqueue_scripts', 'theme_scripts');

// Enqueue admin scripts for sortable functionality
function theme_admin_scripts($hook) {
    if ($hook == 'post.php' || $hook == 'post-new.php') {
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_media();
        
        // Add inline script to ensure media is ready
        wp_add_inline_script('media-upload', '
            jQuery(document).ready(function($) {
                // Ensure wp.media is available
                if (typeof wp !== "undefined" && wp.media) {
                    console.log("WordPress media library is loaded");
                } else {
                    console.error("WordPress media library is NOT loaded");
                }
            });
        ');
    }
}
add_action('admin_enqueue_scripts', 'theme_admin_scripts');

// Add defer attribute to navigation script
function add_defer_attribute($tag, $handle) {
    if ('nav-arrows' === $handle) {
        return str_replace(' src', ' defer src', $tag);
    }
    return $tag;
}
add_filter('script_loader_tag', 'add_defer_attribute', 10, 2);

// Remove Query Strings from Static Resources
function remove_query_strings($src) {
    if (strpos($src, '?ver=')) {
        $src = remove_query_arg('ver', $src);
    }
    return $src;
}
add_filter('style_loader_src', 'remove_query_strings', 10, 2);
add_filter('script_loader_src', 'remove_query_strings', 10, 2);

// Disable WordPress Block Library CSS for better performance
function disable_block_css() {
    wp_dequeue_style('wp-block-library');
    wp_dequeue_style('wp-block-library-theme');
}
add_action('wp_enqueue_scripts', 'disable_block_css', 100);

// Disable REST API for non-logged in users (improves security and performance)
add_filter('rest_authentication_errors', function($result) {
    if (!empty($result)) {
        return $result;
    }
    if (!is_user_logged_in()) {
        return new WP_Error('rest_not_logged_in', 'You are not currently logged in.', array('status' => 401));
    }
    return $result;
});

// Remove oEmbed
function disable_embeds_code_init() {
    remove_action('rest_api_init', 'wp_oembed_register_route');
    remove_filter('oembed_dataparse', 'wp_filter_oembed_result', 10);
    remove_action('wp_head', 'wp_oembed_add_discovery_links');
    remove_action('wp_head', 'wp_oembed_add_host_js');
}
add_action('init', 'disable_embeds_code_init', 9999);

// Limit post revisions
if (!defined('WP_POST_REVISIONS')) {
    define('WP_POST_REVISIONS', 3);
}

// ============================================
// SCHEMA.ORG MARKUP FUNCTIONS
// ============================================

/**
 * Generate BreadcrumbList Schema markup
 */
function generate_breadcrumb_schema($breadcrumb_items) {
    if (empty($breadcrumb_items)) return '';
    
    $schema = array(
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => array()
    );
    
    foreach ($breadcrumb_items as $position => $item) {
        $schema['itemListElement'][] = array(
            '@type' => 'ListItem',
            'position' => $position + 1,
            'name' => $item['name'],
            'item' => $item['url']
        );
    }
    
    return '<script type="application/ld+json">' . json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
}

/**
 * Generate Article Schema markup for Custom SEO Pages
 */
function generate_article_schema($post_id) {
    $schema = array(
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => get_post_meta($post_id, '_custom_h1_title', true) ?: get_the_title($post_id),
        'description' => get_post_meta($post_id, '_custom_meta_description', true),
        'datePublished' => get_the_date('c', $post_id),
        'dateModified' => get_the_modified_date('c', $post_id),
        'author' => array(
            '@type' => 'Person',
            'name' => get_the_author_meta('display_name', get_post_field('post_author', $post_id))
        ),
        'publisher' => array(
            '@type' => 'Organization',
            'name' => get_bloginfo('name'),
            'logo' => array(
                '@type' => 'ImageObject',
                'url' => get_site_icon_url() ?: get_theme_mod('custom_logo')
            )
        )
    );
    
    // Add image if exists
    $image_id = get_post_meta($post_id, '_custom_image', true);
    if ($image_id) {
        $image_url = wp_get_attachment_url($image_id);
        if ($image_url) {
            $schema['image'] = $image_url;
        }
    }
    
    // Add mainEntityOfPage
    $schema['mainEntityOfPage'] = array(
        '@type' => 'WebPage',
        '@id' => get_permalink($post_id)
    );
    
    return '<script type="application/ld+json">' . json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
}

/**
 * Generate WebPage Schema markup for Category Pages
 */
function generate_webpage_schema($post_id, $template_type = 'category') {
    $schema = array(
        '@context' => 'https://schema.org',
        '@type' => 'WebPage',
        'url' => get_permalink($post_id),
        'datePublished' => get_the_date('c', $post_id),
        'dateModified' => get_the_modified_date('c', $post_id),
        'publisher' => array(
            '@type' => 'Organization',
            'name' => get_bloginfo('name')
        )
    );
    
    // Set name and description based on template type
    if ($template_type === 'category') {
        $schema['name'] = get_post_meta($post_id, '_category_h1_title', true) ?: get_the_title($post_id);
        $schema['description'] = get_post_meta($post_id, '_category_meta_description', true);
    } else {
        $schema['name'] = get_the_title($post_id);
        $schema['description'] = get_the_excerpt($post_id);
    }
    
    return '<script type="application/ld+json">' . json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
}

// ============================================
// TRANSLATION FUNCTIONS
// ============================================

/**
 * Get translated string
 */
function get_theme_translation($key, $default = '') {
    $translations = get_option('theme_translations', array());
    $current_lang = get_option('theme_current_language', 'de');
    
    if (isset($translations[$current_lang][$key])) {
        return $translations[$current_lang][$key];
    }
    
    // Return default German text if no translation found
    $defaults = array(
        // Wish-related strings
        'wish_number' => 'Wunsch #',
        'times_shared' => 'mal geteilt',
        'copy' => 'Kopieren',
        'copied' => 'Kopiert!',
        'copy_with_icon' => '📋 Kopieren',
        'copied_with_check' => '✅ Kopiert!',
        
        // Share buttons
        'share_article' => 'Artikel teilen',
        'email' => 'E-Mail',
        
        // Page elements
        'more_information' => 'Weitere Informationen',
        'about_author' => 'Über den Autor',
        'last_updated' => 'Letzte Aktualisierung',
        
        // Navigation
        'scroll_left' => 'Scroll left',
        'scroll_right' => 'Scroll right',
        
        // Wish email subject
        'birthday_wish' => 'Geburtstagswunsch'
    );
    
    return isset($defaults[$key]) ? $defaults[$key] : $default;
}

// ============================================
// THEME SETTINGS PAGE
// ============================================

// Add Theme Settings to Appearance menu
function theme_settings_add_admin_menu() {
    add_theme_page(
        'Theme Settings',           // Page title
        'Theme Settings',           // Menu title
        'manage_options',           // Capability
        'theme-settings',           // Menu slug
        'theme_settings_page_html'  // Callback function
    );
}
add_action('admin_menu', 'theme_settings_add_admin_menu');

// Register settings
function theme_settings_init() {
    // Register settings
    register_setting('theme_settings', 'theme_site_emoji');
    register_setting('theme_settings', 'theme_site_name');
    register_setting('theme_settings', 'theme_google_analytics');
    register_setting('theme_settings', 'theme_google_search_console');
    register_setting('theme_settings', 'theme_bing_webmaster');
    register_setting('theme_settings', 'theme_microsoft_clarity');
    register_setting('theme_settings', 'theme_google_adsense');
    register_setting('theme_settings', 'theme_current_language');
    register_setting('theme_settings', 'theme_translations');
    
    // Add settings section
    add_settings_section(
        'theme_settings_section',
        'Site Identity Settings',
        'theme_settings_section_callback',
        'theme_settings'
    );
    
    // Add emoji field
    add_settings_field(
        'theme_site_emoji',
        'Site Emoji',
        'theme_site_emoji_render',
        'theme_settings',
        'theme_settings_section'
    );
    
    // Add site name field
    add_settings_field(
        'theme_site_name',
        'Site Name',
        'theme_site_name_render',
        'theme_settings',
        'theme_settings_section'
    );
    
    // Add analytics section
    add_settings_section(
        'theme_analytics_section',
        'Analytics & Webmaster Tools',
        'theme_analytics_section_callback',
        'theme_settings'
    );
    
    // Add Google Analytics field
    add_settings_field(
        'theme_google_analytics',
        'Google Analytics',
        'theme_google_analytics_render',
        'theme_settings',
        'theme_analytics_section'
    );
    
    // Add Google Search Console field
    add_settings_field(
        'theme_google_search_console',
        'Google Search Console',
        'theme_google_search_console_render',
        'theme_settings',
        'theme_analytics_section'
    );
    
    // Add Bing Webmaster field
    add_settings_field(
        'theme_bing_webmaster',
        'Bing Webmaster Tools',
        'theme_bing_webmaster_render',
        'theme_settings',
        'theme_analytics_section'
    );
    
    // Add Microsoft Clarity field
    add_settings_field(
        'theme_microsoft_clarity',
        'Microsoft Clarity',
        'theme_microsoft_clarity_render',
        'theme_settings',
        'theme_analytics_section'
    );
    
    // Add Google AdSense field
    add_settings_field(
        'theme_google_adsense',
        'Google AdSense',
        'theme_google_adsense_render',
        'theme_settings',
        'theme_analytics_section'
    );
    
    // Add translation section
    add_settings_section(
        'theme_translation_section',
        'Language & Translations',
        'theme_translation_section_callback',
        'theme_settings'
    );
    
    // Add language selector field
    add_settings_field(
        'theme_current_language',
        'Current Language',
        'theme_current_language_render',
        'theme_settings',
        'theme_translation_section'
    );
    
    // Add translations field
    add_settings_field(
        'theme_translations',
        'Translations',
        'theme_translations_render',
        'theme_settings',
        'theme_translation_section'
    );
}
add_action('admin_init', 'theme_settings_init');

// Section callback
function theme_settings_section_callback() {
    echo '<p>Customize your site\'s emoji and name that appear in the header.</p>';
}

// Emoji field render
function theme_site_emoji_render() {
    $emoji = get_option('theme_site_emoji', '🎁');
    ?>
    <input type="text" name="theme_site_emoji" value="<?php echo esc_attr($emoji); ?>" style="width: 100px;">
    <p class="description">Enter an emoji or leave empty for no emoji. Default: 🎁</p>
    <p class="description" style="margin-top: 10px;">
        <strong>Popular emojis:</strong><br>
        🎁 🎉 🎈 🎊 ✨ 🌟 ⭐ 💫 🔥 💎 🏠 🏡 🏢 🏪 🛍️ 🎯 🚀 💡 📍 🌐
    </p>
    <?php
}

// Site name field render
function theme_site_name_render() {
    $name = get_option('theme_site_name', '10wuensche.ch');
    ?>
    <input type="text" name="theme_site_name" value="<?php echo esc_attr($name); ?>" style="width: 300px;">
    <p class="description">Enter your site name. Default: 10wuensche.ch</p>
    <?php
}

// Analytics section callback
function theme_analytics_section_callback() {
    echo '<p>Add verification codes and tracking scripts for analytics and webmaster tools.</p>';
}

// Google Analytics field render
function theme_google_analytics_render() {
    $ga_code = get_option('theme_google_analytics', '');
    ?>
    <input type="text" name="theme_google_analytics" value="<?php echo esc_attr($ga_code); ?>" style="width: 400px;" placeholder="G-XXXXXXXXXX or UA-XXXXXXXXX-X">
    <p class="description">Enter your Google Analytics Measurement ID (starts with G-) or Tracking ID (starts with UA-)</p>
    <?php
}

// Google Search Console field render
function theme_google_search_console_render() {
    $gsc_code = get_option('theme_google_search_console', '');
    ?>
    <textarea name="theme_google_search_console" style="width: 600px; height: 100px;" placeholder='<meta name="google-site-verification" content="your-verification-code" />'><?php echo esc_textarea($gsc_code); ?></textarea>
    <p class="description">Paste the entire meta tag provided by Google Search Console for verification</p>
    <?php
}

// Bing Webmaster field render
function theme_bing_webmaster_render() {
    $bing_code = get_option('theme_bing_webmaster', '');
    ?>
    <textarea name="theme_bing_webmaster" style="width: 600px; height: 100px;" placeholder='<meta name="msvalidate.01" content="your-verification-code" />'><?php echo esc_textarea($bing_code); ?></textarea>
    <p class="description">Paste the entire meta tag provided by Bing Webmaster Tools for verification</p>
    <?php
}

// Microsoft Clarity field render
function theme_microsoft_clarity_render() {
    $clarity_code = get_option('theme_microsoft_clarity', '');
    ?>
    <input type="text" name="theme_microsoft_clarity" value="<?php echo esc_attr($clarity_code); ?>" style="width: 400px;" placeholder="xxxxxxxxxx">
    <p class="description">Enter your Microsoft Clarity Project ID (found in Settings > Setup)</p>
    <?php
}

// Google AdSense field render
function theme_google_adsense_render() {
    $adsense_code = get_option('theme_google_adsense', '');
    ?>
    <textarea name="theme_google_adsense" style="width: 600px; height: 150px;" placeholder='<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-XXXXXXXXXXXXXXXX" crossorigin="anonymous"></script>'><?php echo esc_textarea($adsense_code); ?></textarea>
    <p class="description">Paste your Google AdSense verification script (the entire &lt;script&gt; tag)</p>
    <?php
}

// Translation section callback
function theme_translation_section_callback() {
    echo '<p>Manage theme language and translations for UI elements.</p>';
}

// Current language field render
function theme_current_language_render() {
    $current_lang = get_option('theme_current_language', 'de');
    ?>
    <select name="theme_current_language" id="theme_current_language">
        <option value="de" <?php selected($current_lang, 'de'); ?>>Deutsch (DE)</option>
        <option value="en" <?php selected($current_lang, 'en'); ?>>English (EN)</option>
        <option value="sq" <?php selected($current_lang, 'sq'); ?>>Shqip (Albanian)</option>
    </select>
    <p class="description">Select the active language for your theme</p>
    <?php
}

// Translations field render
function theme_translations_render() {
    $translations = get_option('theme_translations', array());
    $current_lang = get_option('theme_current_language', 'de');
    
    // Default German strings
    $default_strings = array(
        'wish_number' => 'Wunsch #',
        'times_shared' => 'mal geteilt',
        'copy' => 'Kopieren',
        'copied' => 'Kopiert!',
        'copy_with_icon' => '📋 Kopieren',
        'copied_with_check' => '✅ Kopiert!',
        'share_article' => 'Artikel teilen',
        'email' => 'E-Mail',
        'more_information' => 'Weitere Informationen',
        'about_author' => 'Über den Autor',
        'last_updated' => 'Letzte Aktualisierung',
        'scroll_left' => 'Scroll left',
        'scroll_right' => 'Scroll right',
        'birthday_wish' => 'Geburtstagswunsch'
    );
    
    // English translations
    $english_defaults = array(
        'wish_number' => 'Wish #',
        'times_shared' => 'times shared',
        'copy' => 'Copy',
        'copied' => 'Copied!',
        'copy_with_icon' => '📋 Copy',
        'copied_with_check' => '✅ Copied!',
        'share_article' => 'Share Article',
        'email' => 'E-Mail',
        'more_information' => 'More Information',
        'about_author' => 'About the Author',
        'last_updated' => 'Last Updated',
        'scroll_left' => 'Scroll left',
        'scroll_right' => 'Scroll right',
        'birthday_wish' => 'Birthday Wish'
    );
    
    // Albanian translations
    $albanian_defaults = array(
        'wish_number' => 'Dëshira #',
        'times_shared' => 'herë e ndarë',
        'copy' => 'Kopjo',
        'copied' => 'U kopjua!',
        'copy_with_icon' => '📋 Kopjo',
        'copied_with_check' => '✅ U kopjua!',
        'share_article' => 'Ndaj Artikullin',
        'email' => 'E-Mail',
        'more_information' => 'Më shumë informacion',
        'about_author' => 'Rreth Autorit',
        'last_updated' => 'Përditësimi i fundit',
        'scroll_left' => 'Lëviz majtas',
        'scroll_right' => 'Lëviz djathtas',
        'birthday_wish' => 'Urim ditëlindje'
    );
    
    // Initialize translations if empty
    if (empty($translations['de'])) {
        $translations['de'] = $default_strings;
    }
    if (empty($translations['en'])) {
        $translations['en'] = $english_defaults;
    }
    if (empty($translations['sq'])) {
        $translations['sq'] = $albanian_defaults;
    }
    
    // Get language name
    $language_names = array(
        'de' => 'German',
        'en' => 'English',
        'sq' => 'Albanian'
    );
    $current_language_name = isset($language_names[$current_lang]) ? $language_names[$current_lang] : 'Unknown';
    
    ?>
    <div id="translation-fields">
        <p class="description" style="margin-bottom: 15px;">Edit translations for the <strong><?php echo $current_language_name; ?></strong> language:</p>
        
        <table class="form-table" style="background: #f5f5f5; padding: 10px; border-radius: 5px;">
            <?php foreach ($default_strings as $key => $default_value) : 
                $value = isset($translations[$current_lang][$key]) ? $translations[$current_lang][$key] : 
                         ($current_lang === 'en' ? $english_defaults[$key] : 
                         ($current_lang === 'sq' ? $albanian_defaults[$key] : $default_value));
                ?>
                <tr>
                    <th scope="row" style="width: 200px;">
                        <label for="trans_<?php echo esc_attr($key); ?>">
                            <?php echo esc_html(ucwords(str_replace('_', ' ', $key))); ?>
                        </label>
                    </th>
                    <td>
                        <input type="text" 
                               id="trans_<?php echo esc_attr($key); ?>" 
                               name="theme_translations[<?php echo esc_attr($current_lang); ?>][<?php echo esc_attr($key); ?>]" 
                               value="<?php echo esc_attr($value); ?>" 
                               style="width: 300px;">
                        <span style="color: #666; margin-left: 10px;">Default: <?php echo esc_html($default_value); ?></span>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        
        <?php 
        // Store other language translations as hidden fields
        foreach ($translations as $lang => $lang_translations) : 
            if ($lang !== $current_lang) :
                foreach ($lang_translations as $key => $value) : ?>
                    <input type="hidden" 
                           name="theme_translations[<?php echo esc_attr($lang); ?>][<?php echo esc_attr($key); ?>]" 
                           value="<?php echo esc_attr($value); ?>">
                <?php endforeach;
            endif;
        endforeach; ?>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // Reload page when language is changed to show correct translations
        $('#theme_current_language').on('change', function() {
            $('<input>').attr({
                type: 'hidden',
                name: 'action',
                value: 'update'
            }).appendTo('form');
            
            $('<input>').attr({
                type: 'hidden',
                name: 'option_page',
                value: 'theme_settings'
            }).appendTo('form');
            
            $('form').submit();
        });
    });
    </script>
    <?php
}

// Settings page HTML
function theme_settings_page_html() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Check if settings were saved
    if (isset($_GET['settings-updated'])) {
        add_settings_error('theme_settings_messages', 'theme_settings_message', 'Settings Saved', 'updated');
    }
    
    // Show error/update messages
    settings_errors('theme_settings_messages');
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('theme_settings');
            do_settings_sections('theme_settings');
            submit_button('Save Settings');
            ?>
        </form>
        
        <hr style="margin-top: 40px;">
        
        <h2>How to Use</h2>
        <p>These settings control the emoji and site name that appear in your website header.</p>
        <ul style="list-style: disc; margin-left: 20px;">
            <li><strong>Site Emoji:</strong> This emoji appears before your site name in the header</li>
            <li><strong>Site Name:</strong> This is the clickable text that links to your homepage</li>
        </ul>
        
        <h3>Preview:</h3>
        <div style="padding: 20px; background: #f1f1f1; border-radius: 5px; display: inline-block;">
            <span style="font-size: 20px; font-weight: 700;">
                <?php echo esc_html(get_option('theme_site_emoji', '🎁')); ?> 
                <?php echo esc_html(get_option('theme_site_name', '10wuensche.ch')); ?>
            </span>
        </div>
    </div>
    <?php
}

// Helper functions to get the settings
function get_theme_emoji() {
    return get_option('theme_site_emoji', '🎁');
}

function get_theme_site_name() {
    return get_option('theme_site_name', '10wuensche.ch');
}

// Add tracking codes to wp_head
function theme_add_tracking_codes() {
    // Google Analytics
    $ga_code = get_option('theme_google_analytics', '');
    if (!empty($ga_code)) {
        if (strpos($ga_code, 'G-') === 0) {
            // GA4
            ?>
            <!-- Google Analytics -->
            <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr($ga_code); ?>"></script>
            <script>
              window.dataLayer = window.dataLayer || [];
              function gtag(){dataLayer.push(arguments);}
              gtag('js', new Date());
              gtag('config', '<?php echo esc_js($ga_code); ?>');
            </script>
            <?php
        } elseif (strpos($ga_code, 'UA-') === 0) {
            // Universal Analytics
            ?>
            <!-- Google Analytics -->
            <script>
              (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
              (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
              m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
              })(window,document,'script','https://www.google-analytics.com/analytics.js','ga');
              ga('create', '<?php echo esc_js($ga_code); ?>', 'auto');
              ga('send', 'pageview');
            </script>
            <?php
        }
    }
    
    // Google Search Console
    $gsc_code = get_option('theme_google_search_console', '');
    if (!empty($gsc_code)) {
        echo $gsc_code . "\n";
    }
    
    // Bing Webmaster Tools
    $bing_code = get_option('theme_bing_webmaster', '');
    if (!empty($bing_code)) {
        echo $bing_code . "\n";
    }
    
    // Microsoft Clarity
    $clarity_code = get_option('theme_microsoft_clarity', '');
    if (!empty($clarity_code)) {
        ?>
        <!-- Microsoft Clarity -->
        <script type="text/javascript">
        (function(c,l,a,r,i,t,y){
            c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
            t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
            y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
        })(window, document, "clarity", "script", "<?php echo esc_js($clarity_code); ?>");
        </script>
        <?php
    }
    
    // Google AdSense
    $adsense_code = get_option('theme_google_adsense', '');
    if (!empty($adsense_code)) {
        echo "<!-- Google AdSense -->\n";
        echo $adsense_code . "\n";
    }
}
add_action('wp_head', 'theme_add_tracking_codes', 1);

// ============================================
// INCLUDE TEMPLATE-SPECIFIC FUNCTIONS
// ============================================

// Include custom template functions
require_once get_template_directory() . '/inc/custom-seo-functions.php';
require_once get_template_directory() . '/inc/category-page-functions.php';
require_once get_template_directory() . '/inc/homepage-functions.php';

// ============================================
// AJAX HANDLERS
// ============================================

// Handle wish share count updates
function update_wish_share_count() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'update_wish_share')) {
        wp_die('Security check failed');
    }
    
    $post_id = intval($_POST['post_id']);
    $wish_index = intval($_POST['wish_index']);
    $share_count = intval($_POST['share_count']);
    
    // Get current wishes
    $wishes = get_post_meta($post_id, '_custom_wishes', true);
    
    if (is_array($wishes) && isset($wishes[$wish_index])) {
        $wishes[$wish_index]['share_count'] = $share_count;
        update_post_meta($post_id, '_custom_wishes', $wishes);
        wp_send_json_success(array('count' => $share_count));
    } else {
        wp_send_json_error('Wish not found');
    }
}
add_action('wp_ajax_update_wish_share_count', 'update_wish_share_count');
add_action('wp_ajax_nopriv_update_wish_share_count', 'update_wish_share_count');
