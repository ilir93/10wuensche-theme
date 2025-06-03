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
