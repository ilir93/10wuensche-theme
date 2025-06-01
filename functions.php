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
// CUSTOM SEO PAGE TEMPLATE FUNCTIONALITY
// ============================================

// Remove editor for Custom SEO Page template
function custom_seo_remove_editor() {
    $post_id = isset($_GET['post']) ? $_GET['post'] : (isset($_POST['post_ID']) ? $_POST['post_ID'] : false);
    if (!$post_id) return;
    
    $template = get_post_meta($post_id, '_wp_page_template', true);
    
    if ($template == 'page-custom-seo.php') {
        remove_post_type_support('page', 'editor');
    }
}
add_action('init', 'custom_seo_remove_editor');

// Add meta description to page head
function custom_seo_add_meta_description() {
    if (is_page()) {
        global $post;
        $template = get_post_meta($post->ID, '_wp_page_template', true);
        
        if ($template == 'page-custom-seo.php') {
            $meta_description = get_post_meta($post->ID, '_custom_meta_description', true);
            
            if (!empty($meta_description)) {
                echo '<meta name="description" content="' . esc_attr($meta_description) . '">' . "\n";
            }
        }
    }
}
add_action('wp_head', 'custom_seo_add_meta_description', 1);

// Add meta boxes for custom SEO page template
function custom_seo_add_meta_boxes() {
    add_meta_box(
        'custom_seo_fields',
        'SEO Custom Fields',
        'custom_seo_meta_box_callback',
        'page',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'custom_seo_add_meta_boxes');

// Make meta box available in Block Editor
function custom_seo_add_meta_boxes_gutenberg() {
    add_meta_box(
        'custom_seo_fields',
        'SEO Custom Fields',
        'custom_seo_meta_box_callback',
        'page',
        'normal',
        'high',
        array(
            '__back_compat_meta_box' => false,
        )
    );
}
add_action('add_meta_boxes_page', 'custom_seo_add_meta_boxes_gutenberg');

// Meta box callback function
function custom_seo_meta_box_callback($post) {
    // Add nonce for security
    wp_nonce_field('custom_seo_meta_box', 'custom_seo_meta_box_nonce');
    
    // Get existing values
    $custom_h1_title = get_post_meta($post->ID, '_custom_h1_title', true);
    $custom_meta_description = get_post_meta($post->ID, '_custom_meta_description', true);
    $custom_subtitle_h2 = get_post_meta($post->ID, '_custom_subtitle_h2', true);
    $custom_content_1 = get_post_meta($post->ID, '_custom_content_1', true);
    $custom_image = get_post_meta($post->ID, '_custom_image', true);
    $custom_image_alt = get_post_meta($post->ID, '_custom_image_alt', true);
    $internal_links = get_post_meta($post->ID, '_internal_links', true);
    $custom_subtitle_h3 = get_post_meta($post->ID, '_custom_subtitle_h3', true);
    $custom_content_2 = get_post_meta($post->ID, '_custom_content_2', true);
    $faq_title = get_post_meta($post->ID, '_faq_title', true);
    $faq_items = get_post_meta($post->ID, '_faq_items', true);
    $wishes = get_post_meta($post->ID, '_custom_wishes', true);
    
    // Ensure wishes is an array
    if (!is_array($wishes)) {
        $wishes = array();
    }
    
    // Ensure internal links is an array
    if (!is_array($internal_links)) {
        $internal_links = array();
    }
    
    // Ensure FAQ items is an array
    if (!is_array($faq_items)) {
        $faq_items = array();
    }
    
    // If no custom H1 is set, use the page title
    if (empty($custom_h1_title) && !empty($post->post_title)) {
        $custom_h1_title = $post->post_title;
    }
    ?>
    
    <div style="margin-bottom: 20px;">
        <label for="custom_h1_title" style="display: block; font-weight: bold; margin-bottom: 5px;">
            1. Custom H1 Title
        </label>
        <input type="text" 
               id="custom_h1_title" 
               name="custom_h1_title" 
               value="<?php echo esc_attr($custom_h1_title); ?>" 
               style="width: 100%; padding: 8px; font-size: 14px;"
               placeholder="Enter custom H1 title (defaults to page title)">
        <p style="color: #666; font-size: 12px; margin-top: 5px;">
            This H1 will be displayed on the page. It automatically uses the page title but you can customize it if needed for SEO.
        </p>
        <button type="button" id="sync_h1_title" style="margin-top: 5px; padding: 5px 10px; background: #2271b1; color: white; border: none; border-radius: 3px; cursor: pointer;">
            Sync with Page Title
        </button>
    </div>
    
    <div style="margin-bottom: 20px;">
        <label for="custom_meta_description" style="display: block; font-weight: bold; margin-bottom: 5px;">
            2. Meta Description
        </label>
        <textarea id="custom_meta_description" 
                  name="custom_meta_description" 
                  style="width: 100%; padding: 8px; font-size: 14px; min-height: 60px;"
                  maxlength="120"
                  placeholder="Enter meta description for SEO (max 120 characters)"><?php echo esc_textarea($custom_meta_description); ?></textarea>
        <p style="color: #666; font-size: 12px; margin-top: 5px;">
            <span id="meta_description_count">0</span>/120 characters
        </p>
    </div>
    
    <div style="margin-bottom: 20px;">
        <label style="display: block; font-weight: bold; margin-bottom: 5px;">
            3. Wishes Box
        </label>
        <div id="wishes_container" style="position: relative;">
            <?php 
            if (!empty($wishes)) {
                foreach ($wishes as $index => $wish) {
                    $wish_number = $index + 1;
                    ?>
                    <div class="wish-backend-item" data-index="<?php echo $index; ?>" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 10px; border-radius: 5px; cursor: move; background: white;">
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                            <span style="cursor: move; color: #999;">☰</span>
                            <span style="font-weight: bold; color: #666;">Wunsch #<span class="wish-number-display"><?php echo $wish_number; ?></span></span>
                            <input type="hidden" name="custom_wishes[<?php echo $index; ?>][number]" class="wish-number-input" value="<?php echo $wish_number; ?>">
                        </div>
                        <div style="margin-bottom: 10px;">
                            <input type="text" 
                                   name="custom_wishes[<?php echo $index; ?>][text]" 
                                   value="<?php echo esc_attr($wish['text'] ?? ''); ?>" 
                                   style="width: 100%; padding: 8px; font-size: 14px;"
                                   placeholder="Enter wish text">
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <span style="font-size: 12px; color: #666; font-weight: bold;">
                                    <?php echo esc_html($wish['share_count'] ?? 0); ?> mal geteilt
                                </span>
                                <input type="hidden" 
                                       name="custom_wishes[<?php echo $index; ?>][share_count]" 
                                       value="<?php echo esc_attr($wish['share_count'] ?? 0); ?>">
                            </div>
                            <button type="button" class="remove-wish" style="padding: 5px 10px; background: #d63638; color: white; border: none; border-radius: 3px; cursor: pointer;">
                                Remove
                            </button>
                        </div>
                    </div>
                    <?php
                }
            }
            ?>
        </div>
        <button type="button" id="add_wish" style="margin-top: 10px; padding: 8px 15px; background: #00a32a; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 16px;">
            + Add Wish
        </button>
        <p style="color: #666; font-size: 12px; margin-top: 5px;">
            Drag and drop to reorder wishes. Most shared wishes should be placed at the top.
        </p>
    </div>
    
    <div style="margin-bottom: 20px;">
        <label for="custom_subtitle_h2" style="display: block; font-weight: bold; margin-bottom: 5px;">
            4. Subtitle H2
        </label>
        <input type="text" 
               id="custom_subtitle_h2" 
               name="custom_subtitle_h2" 
               value="<?php echo esc_attr($custom_subtitle_h2); ?>" 
               style="width: 100%; padding: 8px; font-size: 14px;"
               placeholder="Enter H2 subtitle">
        <p style="color: #666; font-size: 12px; margin-top: 5px;">
            This H2 subtitle will be displayed after the wishes box.
        </p>
    </div>
    
    <div style="margin-bottom: 20px;">
        <label for="custom_content_1" style="display: block; font-weight: bold; margin-bottom: 5px;">
            5. Content (WYSIWYG Editor)
        </label>
        <?php 
        wp_editor($custom_content_1, 'custom_content_1', array(
            'textarea_name' => 'custom_content_1',
            'media_buttons' => true,
            'textarea_rows' => 10,
            'teeny' => false,
            'quicktags' => true
        ));
        ?>
        <p style="color: #666; font-size: 12px; margin-top: 5px;">
            Main content area with full formatting options.
        </p>
    </div>
    
    <div style="margin-bottom: 20px;">
        <label style="display: block; font-weight: bold; margin-bottom: 5px;">
            6. Image 300x250 (Auto-generated)
        </label>
        <div style="display: flex; gap: 15px; align-items: flex-start;">
            <div style="flex: 1;">
                <div id="image_preview" style="margin-bottom: 10px;">
                    <canvas id="auto_image_canvas" width="300" height="250" style="border: 1px solid #ddd; background: #000;"></canvas>
                </div>
                <button type="button" id="generate_image_button" class="button button-primary" style="margin-bottom: 10px;">
                    Generate Image from H1
                </button>
                <input type="hidden" id="custom_image_data" name="custom_image_data" value="">
            </div>
            <div style="flex: 1;">
                <label for="custom_image_alt" style="display: block; margin-bottom: 5px;">Alt Text (auto-filled from H1):</label>
                <input type="text" 
                       id="custom_image_alt" 
                       name="custom_image_alt" 
                       value="<?php echo esc_attr($custom_image_alt ?: $custom_h1_title); ?>" 
                       style="width: 100%; padding: 8px; font-size: 14px;"
                       placeholder="Auto-filled from H1 title">
            </div>
        </div>
        <p style="color: #666; font-size: 12px; margin-top: 5px;">
            Click "Generate Image" to create a 300x250 image with black background and H1 text.
        </p>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // Auto-generate image on load if H1 exists
        function generateImage() {
            var h1Text = $('#custom_h1_title').val() || $('#title').val();
            var canvas = document.getElementById('auto_image_canvas');
            var ctx = canvas.getContext('2d');
            
            // Clear canvas
            ctx.fillStyle = '#000000';
            ctx.fillRect(0, 0, 300, 250);
            
            // Set text properties
            ctx.fillStyle = '#FFFFFF';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            
            // Word wrap function
            function wrapText(text, maxWidth) {
                var words = text.split(' ');
                var lines = [];
                var currentLine = '';
                
                ctx.font = '24px Arial';
                
                for (var i = 0; i < words.length; i++) {
                    var testLine = currentLine + words[i] + ' ';
                    var metrics = ctx.measureText(testLine);
                    var testWidth = metrics.width;
                    
                    if (testWidth > maxWidth && i > 0) {
                        lines.push(currentLine.trim());
                        currentLine = words[i] + ' ';
                    } else {
                        currentLine = testLine;
                    }
                }
                lines.push(currentLine.trim());
                return lines;
            }
            
            if (h1Text) {
                var lines = wrapText(h1Text, 260);
                var lineHeight = 30;
                var totalHeight = lines.length * lineHeight;
                var startY = (250 - totalHeight) / 2 + lineHeight / 2;
                
                ctx.font = '24px Arial';
                for (var i = 0; i < lines.length; i++) {
                    ctx.fillText(lines[i], 150, startY + (i * lineHeight));
                }
                
                // Save canvas data
                $('#custom_image_data').val(canvas.toDataURL('image/png'));
                
                // Auto-fill alt text
                $('#custom_image_alt').val(h1Text);
            }
        }
        
        // Generate on button click
        $('#generate_image_button').on('click', generateImage);
        
        // Auto-generate when H1 changes
        $('#custom_h1_title').on('input', generateImage);
        
        // Generate on load
        generateImage();
    });
    </script>
    
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Auto-sync when page title changes
        var titleField = $('#title');
        var h1Field = $('#custom_h1_title');
        var originalH1 = h1Field.val();
        var userModified = false;
        
        // Track if user has manually modified the H1
        h1Field.on('input', function() {
            userModified = ($(this).val() !== titleField.val());
        });
        
        // Auto-sync only if user hasn't modified
        titleField.on('input', function() {
            if (!userModified || h1Field.val() === '') {
                h1Field.val($(this).val());
            }
        });
        
        // Manual sync button
        $('#sync_h1_title').on('click', function() {
            h1Field.val(titleField.val());
            userModified = false;
        });
        
        // Character count for meta description
        var metaDescField = $('#custom_meta_description');
        var metaDescCount = $('#meta_description_count');
        
        function updateCharCount() {
            var count = metaDescField.val().length;
            metaDescCount.text(count);
            
            // Change color based on count
            if (count > 120) {
                metaDescCount.css('color', '#d63638');
            } else if (count > 100) {
                metaDescCount.css('color', '#dba617');
            } else {
                metaDescCount.css('color', '#00a32a');
            }
        }
        
        // Update count on load
        updateCharCount();
        
        // Update count on input
        metaDescField.on('input', updateCharCount);
        
        // Wishes functionality
        var wishesContainer = $('#wishes_container');
        
        // Make wishes sortable
        if (typeof jQuery.ui !== 'undefined' && jQuery.ui.sortable) {
            wishesContainer.sortable({
                handle: 'span:first-child',
                update: function(event, ui) {
                    // Update wish numbers after reordering
                    updateWishNumbers();
                }
            });
        }
        
        function updateWishNumbers() {
            wishesContainer.find('.wish-backend-item').each(function(index) {
                var newNumber = index + 1;
                $(this).find('.wish-number-display').text(newNumber);
                $(this).find('.wish-number-input').val(newNumber);
                
                // Update input names to maintain correct array indices
                $(this).find('input').each(function() {
                    var name = $(this).attr('name');
                    if (name) {
                        name = name.replace(/\[\d+\]/, '[' + index + ']');
                        $(this).attr('name', name);
                    }
                });
            });
        }
        
        var wishTemplate = function(index) {
            var wishNumber = index + 1;
            return '<div class="wish-backend-item" data-index="' + index + '" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 10px; border-radius: 5px; cursor: move; background: white;">' +
                   '<div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">' +
                   '<span style="cursor: move; color: #999;">☰</span>' +
                   '<span style="font-weight: bold; color: #666;">Wunsch #<span class="wish-number-display">' + wishNumber + '</span></span>' +
                   '<input type="hidden" name="custom_wishes[' + index + '][number]" class="wish-number-input" value="' + wishNumber + '">' +
                   '</div>' +
                   '<div style="margin-bottom: 10px;">' +
                   '<input type="text" name="custom_wishes[' + index + '][text]" style="width: 100%; padding: 8px; font-size: 14px;" placeholder="Enter wish text">' +
                   '</div>' +
                   '<div style="display: flex; justify-content: space-between; align-items: center;">' +
                   '<div style="display: flex; align-items: center; gap: 10px;">' +
                   '<span style="font-size: 12px; color: #666; font-weight: bold;">0 mal geteilt</span>' +
                   '<input type="hidden" name="custom_wishes[' + index + '][share_count]" value="0">' +
                   '</div>' +
                   '<button type="button" class="remove-wish" style="padding: 5px 10px; background: #d63638; color: white; border: none; border-radius: 3px; cursor: pointer;">Remove</button>' +
                   '</div>' +
                   '</div>';
        };
        
        // Add wish button
        $('#add_wish').on('click', function() {
            var currentCount = wishesContainer.find('.wish-backend-item').length;
            wishesContainer.append(wishTemplate(currentCount));
            updateWishNumbers();
        });
        
        // Remove wish button (using delegation for dynamically added elements)
        $(document).on('click', '.remove-wish', function() {
            $(this).closest('.wish-backend-item').remove();
            updateWishNumbers();
        });
    });
    </script>
    
    <div style="margin-bottom: 20px;">
        <label style="display: block; font-weight: bold; margin-bottom: 5px;">
            7. Internal Links
        </label>
        <div id="internal_links_container">
            <?php 
            if (!empty($internal_links)) {
                foreach ($internal_links as $index => $link) {
                    ?>
                    <div class="internal-link-item" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 10px; border-radius: 5px;">
                        <div style="margin-bottom: 10px;">
                            <label style="display: block; margin-bottom: 5px;">Link Text:</label>
                            <input type="text" 
                                   name="internal_links[<?php echo $index; ?>][text]" 
                                   value="<?php echo esc_attr($link['text'] ?? ''); ?>" 
                                   style="width: 100%; padding: 8px; font-size: 14px;"
                                   placeholder="e.g., Lustige Geburtstagswünsche">
                        </div>
                        <div style="margin-bottom: 10px;">
                            <label style="display: block; margin-bottom: 5px;">Select Page:</label>
                            <?php 
                            wp_dropdown_pages(array(
                                'name' => 'internal_links[' . $index . '][page_id]',
                                'selected' => $link['page_id'] ?? 0,
                                'show_option_none' => '-- Select Page --',
                                'echo' => 1,
                                'style' => 'width: 100%; padding: 8px;'
                            ));
                            ?>
                        </div>
                        <button type="button" class="remove-internal-link" style="padding: 5px 10px; background: #d63638; color: white; border: none; border-radius: 3px; cursor: pointer;">
                            Remove Link
                        </button>
                    </div>
                    <?php
                }
            }
            ?>
        </div>
        <button type="button" id="add_internal_link" style="margin-top: 10px; padding: 8px 15px; background: #00a32a; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 16px;">
            + Add Internal Link
        </button>
        <p style="color: #666; font-size: 12px; margin-top: 5px;">
            Add internal links to other pages on your site. These will be displayed in a styled section.
        </p>
    </div>
    
    <div style="margin-bottom: 20px;">
        <label for="custom_subtitle_h3" style="display: block; font-weight: bold; margin-bottom: 5px;">
            8. Subtitle H3
        </label>
        <input type="text" 
               id="custom_subtitle_h3" 
               name="custom_subtitle_h3" 
               value="<?php echo esc_attr($custom_subtitle_h3); ?>" 
               style="width: 100%; padding: 8px; font-size: 14px;"
               placeholder="Enter H3 subtitle">
        <p style="color: #666; font-size: 12px; margin-top: 5px;">
            This H3 subtitle will be displayed after the internal links.
        </p>
    </div>
    
    <div style="margin-bottom: 20px;">
        <label for="custom_content_2" style="display: block; font-weight: bold; margin-bottom: 5px;">
            9. Content (WYSIWYG Editor)
        </label>
        <?php 
        wp_editor($custom_content_2, 'custom_content_2', array(
            'textarea_name' => 'custom_content_2',
            'media_buttons' => true,
            'textarea_rows' => 10,
            'teeny' => false,
            'quicktags' => true
        ));
        ?>
        <p style="color: #666; font-size: 12px; margin-top: 5px;">
            Second content area with full formatting options.
        </p>
    </div>
    
    <div style="margin-bottom: 20px;">
        <label for="faq_title" style="display: block; font-weight: bold; margin-bottom: 5px;">
            10. FAQ Title
        </label>
        <input type="text" 
               id="faq_title" 
               name="faq_title" 
               value="<?php echo esc_attr($faq_title); ?>" 
               style="width: 100%; padding: 8px; font-size: 14px;"
               placeholder="e.g., Häufig gestellte Fragen">
        <p style="color: #666; font-size: 12px; margin-top: 5px;">
            Title for the FAQ section.
        </p>
    </div>
    
    <div style="margin-bottom: 20px;">
        <label style="display: block; font-weight: bold; margin-bottom: 5px;">
            11. FAQ Questions and Answers (Schema Markup)
        </label>
        <div id="faq_container">
            <?php 
            if (!empty($faq_items)) {
                foreach ($faq_items as $index => $faq) {
                    ?>
                    <div class="faq-item" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 10px; border-radius: 5px;">
                        <div style="margin-bottom: 10px;">
                            <label style="display: block; margin-bottom: 5px;">Question:</label>
                            <input type="text" 
                                   name="faq_items[<?php echo $index; ?>][question]" 
                                   value="<?php echo esc_attr($faq['question'] ?? ''); ?>" 
                                   style="width: 100%; padding: 8px; font-size: 14px;"
                                   placeholder="Enter FAQ question">
                        </div>
                        <div style="margin-bottom: 10px;">
                            <label style="display: block; margin-bottom: 5px;">Answer:</label>
                            <textarea name="faq_items[<?php echo $index; ?>][answer]" 
                                      style="width: 100%; padding: 8px; font-size: 14px; min-height: 80px;"
                                      placeholder="Enter FAQ answer"><?php echo esc_textarea($faq['answer'] ?? ''); ?></textarea>
                        </div>
                        <button type="button" class="remove-faq" style="padding: 5px 10px; background: #d63638; color: white; border: none; border-radius: 3px; cursor: pointer;">
                            Remove FAQ
                        </button>
                    </div>
                    <?php
                }
            }
            ?>
        </div>
        <button type="button" id="add_faq" style="margin-top: 10px; padding: 8px 15px; background: #00a32a; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 16px;">
            + Add FAQ
        </button>
        <p style="color: #666; font-size: 12px; margin-top: 5px;">
            FAQ items will be marked up with schema.org structured data for rich snippets in search results.
        </p>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // FAQ functionality
        var faqContainer = $('#faq_container');
        var faqIndex = faqContainer.find('.faq-item').length;
        
        var faqTemplate = function(index) {
            return '<div class="faq-item" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 10px; border-radius: 5px;">' +
                   '<div style="margin-bottom: 10px;">' +
                   '<label style="display: block; margin-bottom: 5px;">Question:</label>' +
                   '<input type="text" name="faq_items[' + index + '][question]" style="width: 100%; padding: 8px; font-size: 14px;" placeholder="Enter FAQ question">' +
                   '</div>' +
                   '<div style="margin-bottom: 10px;">' +
                   '<label style="display: block; margin-bottom: 5px;">Answer:</label>' +
                   '<textarea name="faq_items[' + index + '][answer]" style="width: 100%; padding: 8px; font-size: 14px; min-height: 80px;" placeholder="Enter FAQ answer"></textarea>' +
                   '</div>' +
                   '<button type="button" class="remove-faq" style="padding: 5px 10px; background: #d63638; color: white; border: none; border-radius: 3px; cursor: pointer;">Remove FAQ</button>' +
                   '</div>';
        };
        
        // Add FAQ button
        $('#add_faq').on('click', function() {
            faqContainer.append(faqTemplate(faqIndex));
            faqIndex++;
        });
        
        // Remove FAQ button
        $(document).on('click', '.remove-faq', function() {
            $(this).closest('.faq-item').remove();
        });
    });
    </script>
    
    <script>
    jQuery(document).ready(function($) {
        // Internal links functionality
        var linksContainer = $('#internal_links_container');
        var linkIndex = linksContainer.find('.internal-link-item').length;
        
        var linkTemplate = function(index) {
            return '<div class="internal-link-item" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 10px; border-radius: 5px;">' +
                   '<div style="margin-bottom: 10px;">' +
                   '<label style="display: block; margin-bottom: 5px;">Link Text:</label>' +
                   '<input type="text" name="internal_links[' + index + '][text]" style="width: 100%; padding: 8px; font-size: 14px;" placeholder="e.g., Lustige Geburtstagswünsche">' +
                   '</div>' +
                   '<div style="margin-bottom: 10px;">' +
                   '<label style="display: block; margin-bottom: 5px;">Select Page:</label>' +
                   '<select name="internal_links[' + index + '][page_id]" style="width: 100%; padding: 8px;">' +
                   '<option value="">-- Select Page --</option>' +
                   <?php
                   $pages = get_pages();
                   foreach ($pages as $page) {
                       echo "'<option value=\"" . $page->ID . "\">" . esc_js($page->post_title) . "</option>' +";
                   }
                   ?>
                   '</select>' +
                   '</div>' +
                   '<button type="button" class="remove-internal-link" style="padding: 5px 10px; background: #d63638; color: white; border: none; border-radius: 3px; cursor: pointer;">Remove Link</button>' +
                   '</div>';
        };
        
        // Add link button
        $('#add_internal_link').on('click', function() {
            linksContainer.append(linkTemplate(linkIndex));
            linkIndex++;
        });
        
        // Remove link button
        $(document).on('click', '.remove-internal-link', function() {
            $(this).closest('.internal-link-item').remove();
        });
    });
    </script>
    
    <?php
}

// Save meta box data
function custom_seo_save_meta_box_data($post_id) {
    // Check if nonce is set
    if (!isset($_POST['custom_seo_meta_box_nonce'])) {
        return;
    }
    
    // Verify nonce
    if (!wp_verify_nonce($_POST['custom_seo_meta_box_nonce'], 'custom_seo_meta_box')) {
        return;
    }
    
    // If this is an autosave, don't do anything
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // Check user permissions
    if (!current_user_can('edit_page', $post_id)) {
        return;
    }
    
    // Save H1 Title
    if (isset($_POST['custom_h1_title'])) {
        update_post_meta($post_id, '_custom_h1_title', sanitize_text_field($_POST['custom_h1_title']));
    }
    
    // Save Meta Description
    if (isset($_POST['custom_meta_description'])) {
        $meta_desc = sanitize_textarea_field($_POST['custom_meta_description']);
        // Enforce 120 character limit
        $meta_desc = substr($meta_desc, 0, 120);
        update_post_meta($post_id, '_custom_meta_description', $meta_desc);
    }
    
    // Save Wishes
    if (isset($_POST['custom_wishes'])) {
        $wishes = array();
        foreach ($_POST['custom_wishes'] as $wish) {
            if (!empty($wish['text'])) {
                $wishes[] = array(
                    'number' => sanitize_text_field($wish['number'] ?? '1'),
                    'text' => sanitize_text_field($wish['text']),
                    'share_count' => absint($wish['share_count'] ?? 0)
                );
            }
        }
        update_post_meta($post_id, '_custom_wishes', $wishes);
    } else {
        // If no wishes submitted, save empty array
        update_post_meta($post_id, '_custom_wishes', array());
    }
    
    // Save Subtitle H2
    if (isset($_POST['custom_subtitle_h2'])) {
        update_post_meta($post_id, '_custom_subtitle_h2', sanitize_text_field($_POST['custom_subtitle_h2']));
    }
    
    // Save Content 1
    if (isset($_POST['custom_content_1'])) {
        update_post_meta($post_id, '_custom_content_1', wp_kses_post($_POST['custom_content_1']));
    }
    
    // Save Image and Alt Text
    if (isset($_POST['custom_image_data']) && !empty($_POST['custom_image_data'])) {
        // Get H1 title for filename
        $h1_title = get_post_meta($post_id, '_custom_h1_title', true) ?: get_the_title($post_id);
        $filename = sanitize_file_name($h1_title) . '.png';
        
        // Process base64 image
        $image_data = $_POST['custom_image_data'];
        $image_data = str_replace('data:image/png;base64,', '', $image_data);
        $image_data = str_replace(' ', '+', $image_data);
        $decoded_image = base64_decode($image_data);
        
        // Upload to media library
        $upload_dir = wp_upload_dir();
        $upload_path = $upload_dir['path'] . '/' . $filename;
        $upload_url = $upload_dir['url'] . '/' . $filename;
        
        file_put_contents($upload_path, $decoded_image);
        
        // Check if image already exists
        $existing_image = get_page_by_title($h1_title, OBJECT, 'attachment');
        
        if (!$existing_image) {
            // Create attachment
            $attachment = array(
                'post_mime_type' => 'image/png',
                'post_title' => $h1_title,
                'post_content' => '',
                'post_status' => 'inherit'
            );
            
            $attach_id = wp_insert_attachment($attachment, $upload_path, $post_id);
            
            // Generate metadata
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $attach_data = wp_generate_attachment_metadata($attach_id, $upload_path);
            wp_update_attachment_metadata($attach_id, $attach_data);
            
            update_post_meta($post_id, '_custom_image', $attach_id);
        } else {
            update_post_meta($post_id, '_custom_image', $existing_image->ID);
        }
    }
    
    if (isset($_POST['custom_image_alt'])) {
        update_post_meta($post_id, '_custom_image_alt', sanitize_text_field($_POST['custom_image_alt']));
    }
    
    // Save Internal Links
    if (isset($_POST['internal_links'])) {
        $links = array();
        foreach ($_POST['internal_links'] as $link) {
            if (!empty($link['text']) && !empty($link['page_id'])) {
                $links[] = array(
                    'text' => sanitize_text_field($link['text']),
                    'page_id' => absint($link['page_id'])
                );
            }
        }
        update_post_meta($post_id, '_internal_links', $links);
    } else {
        update_post_meta($post_id, '_internal_links', array());
    }
    
    // Save Subtitle H3
    if (isset($_POST['custom_subtitle_h3'])) {
        update_post_meta($post_id, '_custom_subtitle_h3', sanitize_text_field($_POST['custom_subtitle_h3']));
    }
    
    // Save Content 2
    if (isset($_POST['custom_content_2'])) {
        update_post_meta($post_id, '_custom_content_2', wp_kses_post($_POST['custom_content_2']));
    }
    
    // Save FAQ Title
    if (isset($_POST['faq_title'])) {
        update_post_meta($post_id, '_faq_title', sanitize_text_field($_POST['faq_title']));
    }
    
    // Save FAQ Items
    if (isset($_POST['faq_items'])) {
        $faqs = array();
        foreach ($_POST['faq_items'] as $faq) {
            if (!empty($faq['question']) && !empty($faq['answer'])) {
                $faqs[] = array(
                    'question' => sanitize_text_field($faq['question']),
                    'answer' => sanitize_textarea_field($faq['answer'])
                );
            }
        }
        update_post_meta($post_id, '_faq_items', $faqs);
    } else {
        update_post_meta($post_id, '_faq_items', array());
    }
}
add_action('save_post', 'custom_seo_save_meta_box_data');

// Make custom fields available to Yoast SEO
function custom_seo_add_yoast_variables() {
    wpseo_register_var_replacement(
        '%%custom_h1%%',
        'custom_seo_get_h1_title',
        'advanced',
        'Custom H1 title from meta box'
    );
}
add_action('wpseo_register_extra_replacements', 'custom_seo_add_yoast_variables');

// Function to get H1 title for Yoast
function custom_seo_get_h1_title() {
    global $post;
    if ($post) {
        $custom_h1 = get_post_meta($post->ID, '_custom_h1_title', true);
        return !empty($custom_h1) ? $custom_h1 : get_the_title($post->ID);
    }
    return '';
}

// Add JavaScript to show meta box only when custom template is selected
function custom_seo_admin_script() {
    global $post;
    if ($post && $post->post_type == 'page') {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // For Classic Editor
            function toggleCustomSEOMetaBox() {
                var template = $('#page_template').val();
                if (template == 'page-custom-seo.php') {
                    $('#custom_seo_fields').show();
                } else {
                    $('#custom_seo_fields').hide();
                }
            }
            
            // Check on page load
            toggleCustomSEOMetaBox();
            
            // Check when template changes
            $('#page_template').on('change', toggleCustomSEOMetaBox);
            
            // For Block Editor (Gutenberg)
            if (wp && wp.data && wp.data.select('core/editor')) {
                const { select, subscribe } = wp.data;
                
                let wasSaving = select('core/editor').isSavingPost();
                let wasAutosaving = select('core/editor').isAutosavingPost();
                let wasPreviewingPost = select('core/editor').isPreviewingPost();
                
                subscribe(() => {
                    const editor = select('core/editor');
                    const isSaving = editor.isSavingPost();
                    const isAutosaving = editor.isAutosavingPost();
                    const isPreviewingPost = editor.isPreviewingPost();
                    const template = editor.getEditedPostAttribute('template');
                    
                    // Toggle meta box visibility based on template
                    if (template === 'page-custom-seo.php') {
                        $('#custom_seo_fields').show();
                    } else {
                        $('#custom_seo_fields').hide();
                    }
                    
                    wasSaving = isSaving;
                    wasAutosaving = isAutosaving;
                    wasPreviewingPost = isPreviewingPost;
                });
            }
        });
        </script>
        <?php
    }
}
add_action('admin_footer', 'custom_seo_admin_script');

// AJAX handler to update wish share count
function update_wish_share_count() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'update_wish_share')) {
        wp_die();
    }
    
    $post_id = intval($_POST['post_id']);
    $wish_index = intval($_POST['wish_index']);
    $share_count = intval($_POST['share_count']);
    
    // Get current wishes
    $wishes = get_post_meta($post_id, '_custom_wishes', true);
    
    if (is_array($wishes) && isset($wishes[$wish_index])) {
        $wishes[$wish_index]['share_count'] = $share_count;
        update_post_meta($post_id, '_custom_wishes', $wishes);
    }
    
    wp_die();
}
add_action('wp_ajax_update_wish_share_count', 'update_wish_share_count');
add_action('wp_ajax_nopriv_update_wish_share_count', 'update_wish_share_count');

// Make custom fields content available to Yoast SEO analysis
function custom_seo_add_to_yoast_analysis($content, $post) {
    if ($post && get_post_meta($post->ID, '_wp_page_template', true) == 'page-custom-seo.php') {
        // Add H1
        $h1 = get_post_meta($post->ID, '_custom_h1_title', true);
        if ($h1) {
            $content .= ' <h1>' . $h1 . '</h1> ';
        }
        
        // Add meta description
        $meta_desc = get_post_meta($post->ID, '_custom_meta_description', true);
        if ($meta_desc) {
            $content .= ' <p>' . $meta_desc . '</p> ';
        }
        
        // Add wishes
        $wishes = get_post_meta($post->ID, '_custom_wishes', true);
        if (!empty($wishes) && is_array($wishes)) {
            foreach ($wishes as $wish) {
                if (!empty($wish['text'])) {
                    $content .= ' <p>' . $wish['text'] . '</p> ';
                }
            }
        }
        
        // Add H2
        $h2 = get_post_meta($post->ID, '_custom_subtitle_h2', true);
        if ($h2) {
            $content .= ' <h2>' . $h2 . '</h2> ';
        }
        
        // Add content 1
        $content1 = get_post_meta($post->ID, '_custom_content_1', true);
        if ($content1) {
            $content .= ' ' . wp_strip_all_tags($content1) . ' ';
        }
    }
    
    return $content;
}
add_filter('wpseo_pre_analysis_post_content', 'custom_seo_add_to_yoast_analysis', 10, 2);

// Also add for the content analysis
add_filter('wpseo_content_analysis_post_content', 'custom_seo_add_to_yoast_analysis', 10, 2);

// Force Yoast to reanalyze when custom fields are saved
function custom_seo_force_yoast_reanalyze($post_id) {
    $template = get_post_meta($post_id, '_wp_page_template', true);
    if ($template == 'page-custom-seo.php') {
        // Trigger Yoast reanalysis
        delete_post_meta($post_id, '_yoast_wpseo_linkdex');
    }
}
add_action('save_post', 'custom_seo_force_yoast_reanalyze', 20);