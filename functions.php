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
            6. Image 300x250
        </label>
        
        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 10px;">
                <input type="radio" name="custom_image_type" value="auto" <?php echo (!get_post_meta($post->ID, '_custom_image_type', true) || get_post_meta($post->ID, '_custom_image_type', true) == 'auto') ? 'checked' : ''; ?>>
                <strong>Auto-generate from H1 title</strong>
            </label>
            <label style="display: block;">
                <input type="radio" name="custom_image_type" value="upload" <?php echo (get_post_meta($post->ID, '_custom_image_type', true) == 'upload') ? 'checked' : ''; ?>>
                <strong>Upload custom image</strong>
            </label>
        </div>
        
        <div style="display: flex; gap: 15px; align-items: flex-start;">
            <div style="flex: 1;">
                <!-- Auto-generated image section -->
                <div id="auto_image_section" style="<?php echo (get_post_meta($post->ID, '_custom_image_type', true) == 'upload') ? 'display: none;' : ''; ?>">
                    <div id="image_preview" style="margin-bottom: 10px;">
                        <canvas id="auto_image_canvas" width="300" height="250" style="border: 1px solid #ddd; background: #000;"></canvas>
                    </div>
                    <button type="button" id="generate_image_button" class="button button-primary" style="margin-bottom: 10px;">
                        Generate Image from H1
                    </button>
                    <input type="hidden" id="custom_image_data" name="custom_image_data" value="">
                </div>
                
                <!-- Upload image section -->
                <div id="upload_image_section" style="<?php echo (get_post_meta($post->ID, '_custom_image_type', true) != 'upload') ? 'display: none;' : ''; ?>">
                    <div id="uploaded_image_preview" style="margin-bottom: 10px;">
                        <?php 
                        $uploaded_image_id = get_post_meta($post->ID, '_custom_uploaded_image', true);
                        if ($uploaded_image_id) {
                            echo wp_get_attachment_image($uploaded_image_id, array(300, 250));
                        } else {
                            echo '<div style="width: 300px; height: 250px; border: 2px dashed #ddd; display: flex; align-items: center; justify-content: center; color: #999;">No image selected</div>';
                        }
                        ?>
                    </div>
                    <button type="button" id="upload_image_button" class="button button-primary">
                        Select Image
                    </button>
                    <button type="button" id="remove_uploaded_image" class="button" style="<?php echo $uploaded_image_id ? '' : 'display: none;'; ?>">
                        Remove Image
                    </button>
                    <input type="hidden" id="custom_uploaded_image" name="custom_uploaded_image" value="<?php echo esc_attr($uploaded_image_id); ?>">
                </div>
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
            Choose to auto-generate a 300x250 image with black background and H1 text, or upload your own custom image.
        </p>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // Handle image type selection
        $('input[name="custom_image_type"]').on('change', function() {
            if ($(this).val() === 'auto') {
                $('#auto_image_section').show();
                $('#upload_image_section').hide();
                generateImage(); // Auto-generate when switching to auto
            } else {
                $('#auto_image_section').hide();
                $('#upload_image_section').show();
            }
        });
        
        // Media uploader for custom image
        var mediaUploader;
        
        $('#upload_image_button').on('click', function(e) {
            e.preventDefault();
            
            if (mediaUploader) {
                mediaUploader.open();
                return;
            }
            
            mediaUploader = wp.media({
                title: 'Select Image',
                button: {
                    text: 'Use this image'
                },
                multiple: false,
                library: {
                    type: 'image'
                }
            });
            
            mediaUploader.on('select', function() {
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                $('#custom_uploaded_image').val(attachment.id);
                
                // Update preview
                var img = '<img src="' + attachment.url + '" style="max-width: 300px; max-height: 250px;">';
                $('#uploaded_image_preview').html(img);
                $('#remove_uploaded_image').show();
                
                // Auto-fill alt text if empty
                if (!$('#custom_image_alt').val()) {
                    $('#custom_image_alt').val(attachment.alt || attachment.title || '');
                }
            });
            
            mediaUploader.open();
        });
        
        $('#remove_uploaded_image').on('click', function() {
            $('#custom_uploaded_image').val('');
            $('#uploaded_image_preview').html('<div style="width: 300px; height: 250px; border: 2px dashed #ddd; display: flex; align-items: center; justify-content: center; color: #999;">No image selected</div>');
            $(this).hide();
        });
        
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
    if (isset($_POST['custom_image_type'])) {
        update_post_meta($post_id, '_custom_image_type', sanitize_text_field($_POST['custom_image_type']));
    }
    
    if (isset($_POST['custom_image_type']) && $_POST['custom_image_type'] == 'upload') {
        // Save uploaded image
        if (isset($_POST['custom_uploaded_image']) && !empty($_POST['custom_uploaded_image'])) {
            update_post_meta($post_id, '_custom_uploaded_image', absint($_POST['custom_uploaded_image']));
            update_post_meta($post_id, '_custom_image', absint($_POST['custom_uploaded_image']));
        }
    } else {
        // Save auto-generated image
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

// ============================================
// YOAST SEO INTEGRATION FOR CUSTOM FIELDS
// ============================================

// Add custom fields content to Yoast SEO analysis
function custom_seo_add_to_yoast_analysis($content, $post) {
    if ($post && get_post_meta($post->ID, '_wp_page_template', true) == 'page-custom-seo.php') {
        
        // Start with empty content
        $custom_content = '';
        
        // Add H1
        $h1 = get_post_meta($post->ID, '_custom_h1_title', true);
        if ($h1) {
            $custom_content .= '<h1>' . $h1 . '</h1> ';
        }
        
        // Add meta description as introduction paragraph
        $meta_desc = get_post_meta($post->ID, '_custom_meta_description', true);
        if ($meta_desc) {
            $custom_content .= '<p>' . $meta_desc . '</p> ';
        }
        
        // Add wishes
        $wishes = get_post_meta($post->ID, '_custom_wishes', true);
        if (!empty($wishes) && is_array($wishes)) {
            foreach ($wishes as $wish) {
                if (!empty($wish['text'])) {
                    $custom_content .= '<p>' . $wish['text'] . '</p> ';
                }
            }
        }
        
        // Add H2
        $h2 = get_post_meta($post->ID, '_custom_subtitle_h2', true);
        if ($h2) {
            $custom_content .= '<h2>' . $h2 . '</h2> ';
        }
        
        // Add content 1
        $content1 = get_post_meta($post->ID, '_custom_content_1', true);
        if ($content1) {
            $custom_content .= $content1 . ' ';
        }
        
        // Add image with alt text
        $image_id = get_post_meta($post->ID, '_custom_image', true);
        $image_alt = get_post_meta($post->ID, '_custom_image_alt', true);
        if ($image_id && $image_alt) {
            $custom_content .= '<img src="placeholder.jpg" alt="' . esc_attr($image_alt) . '" /> ';
        }
        
        // Add internal links
        $internal_links = get_post_meta($post->ID, '_internal_links', true);
        if (!empty($internal_links) && is_array($internal_links)) {
            foreach ($internal_links as $link) {
                if (!empty($link['page_id']) && !empty($link['text'])) {
                    $link_url = get_permalink($link['page_id']);
                    $custom_content .= '<a href="' . esc_url($link_url) . '">' . esc_html($link['text']) . '</a> ';
                }
            }
        }
        
        // Add H3
        $h3 = get_post_meta($post->ID, '_custom_subtitle_h3', true);
        if ($h3) {
            $custom_content .= '<h3>' . $h3 . '</h3> ';
        }
        
        // Add content 2
        $content2 = get_post_meta($post->ID, '_custom_content_2', true);
        if ($content2) {
            $custom_content .= $content2 . ' ';
        }
        
        // Add FAQ title
        $faq_title = get_post_meta($post->ID, '_faq_title', true);
        if ($faq_title) {
            $custom_content .= '<h2>' . $faq_title . '</h2> ';
        }
        
        // Add FAQ items
        $faq_items = get_post_meta($post->ID, '_faq_items', true);
        if (!empty($faq_items) && is_array($faq_items)) {
            foreach ($faq_items as $faq) {
                if (!empty($faq['question'])) {
                    $custom_content .= '<h3>' . $faq['question'] . '</h3> ';
                }
                if (!empty($faq['answer'])) {
                    $custom_content .= '<p>' . $faq['answer'] . '</p> ';
                }
            }
        }
        
        // Return the custom content instead of the original
        return $custom_content;
    }
    
    return $content;
}

// Hook into multiple Yoast filters for better compatibility
add_filter('wpseo_pre_analysis_post_content', 'custom_seo_add_to_yoast_analysis', 10, 2);
add_filter('wpseo_content_analysis_post_content', 'custom_seo_add_to_yoast_analysis', 10, 2);

// Add JavaScript to update Yoast analysis in real-time
function custom_seo_yoast_js() {
    global $post;
    if ($post && $post->post_type == 'page' && get_post_meta($post->ID, '_wp_page_template', true) == 'page-custom-seo.php') {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            if (typeof YoastSEO !== 'undefined' && YoastSEO.app) {
                
                // Custom content modification for Yoast
                YoastSEO.app.registerPlugin('customSEOContent', {status: 'ready'});
                
                YoastSEO.app.registerModification('content', function(content) {
                    var customContent = '';
                    
                    // Get H1
                    var h1 = $('#custom_h1_title').val();
                    if (h1) {
                        customContent += '<h1>' + h1 + '</h1> ';
                    }
                    
                    // Get meta description
                    var metaDesc = $('#custom_meta_description').val();
                    if (metaDesc) {
                        customContent += '<p>' + metaDesc + '</p> ';
                    }
                    
                    // Get wishes
                    $('.wish-backend-item').each(function() {
                        var wishText = $(this).find('input[name*="[text]"]').val();
                        if (wishText) {
                            customContent += '<p>' + wishText + '</p> ';
                        }
                    });
                    
                    // Get H2
                    var h2 = $('#custom_subtitle_h2').val();
                    if (h2) {
                        customContent += '<h2>' + h2 + '</h2> ';
                    }
                    
                    // Get content 1 from TinyMCE
                    if (typeof tinyMCE !== 'undefined' && tinyMCE.get('custom_content_1')) {
                        var content1 = tinyMCE.get('custom_content_1').getContent();
                        if (content1) {
                            customContent += content1 + ' ';
                        }
                    }
                    
                    // Get image alt text
                    var imageAlt = $('#custom_image_alt').val();
                    if (imageAlt) {
                        customContent += '<img src="placeholder.jpg" alt="' + imageAlt + '" /> ';
                    }
                    
                    // Get internal links
                    $('.internal-link-item').each(function() {
                        var linkText = $(this).find('input[name*="[text]"]').val();
                        var linkPage = $(this).find('select[name*="[page_id]"]').val();
                        if (linkText && linkPage) {
                            customContent += '<a href="internal-link">' + linkText + '</a> ';
                        }
                    });
                    
                    // Get H3
                    var h3 = $('#custom_subtitle_h3').val();
                    if (h3) {
                        customContent += '<h3>' + h3 + '</h3> ';
                    }
                    
                    // Get content 2 from TinyMCE
                    if (typeof tinyMCE !== 'undefined' && tinyMCE.get('custom_content_2')) {
                        var content2 = tinyMCE.get('custom_content_2').getContent();
                        if (content2) {
                            customContent += content2 + ' ';
                        }
                    }
                    
                    // Get FAQ title
                    var faqTitle = $('#faq_title').val();
                    if (faqTitle) {
                        customContent += '<h2>' + faqTitle + '</h2> ';
                    }
                    
                    // Get FAQ items
                    $('.faq-item').each(function() {
                        var question = $(this).find('input[name*="[question]"]').val();
                        var answer = $(this).find('textarea[name*="[answer]"]').val();
                        if (question) {
                            customContent += '<h3>' + question + '</h3> ';
                        }
                        if (answer) {
                            customContent += '<p>' + answer + '</p> ';
                        }
                    });
                    
                    return customContent;
                }, 'customSEOContent', 5);
                
                // Trigger reanalysis when fields change
                function triggerYoastReanalysis() {
                    if (YoastSEO.app.refresh) {
                        YoastSEO.app.refresh();
                    }
                }
                
                // Monitor all custom fields for changes
                $('#custom_h1_title, #custom_meta_description, #custom_subtitle_h2, #custom_subtitle_h3, #faq_title, #custom_image_alt').on('input', function() {
                    setTimeout(triggerYoastReanalysis, 500);
                });
                
                // Monitor dynamic fields
                $(document).on('input', '.wish-backend-item input, .internal-link-item input, .internal-link-item select, .faq-item input, .faq-item textarea', function() {
                    setTimeout(triggerYoastReanalysis, 500);
                });
                
                // Monitor TinyMCE editors
                if (typeof tinyMCE !== 'undefined') {
                    tinyMCE.on('AddEditor', function(e) {
                        e.editor.on('change keyup', function() {
                            setTimeout(triggerYoastReanalysis, 500);
                        });
                    });
                }
            }
        });
        </script>
        <?php
    }
}
add_action('admin_footer', 'custom_seo_yoast_js');

// Force Yoast to recognize custom meta description
function custom_seo_yoast_meta_description($description, $presentation) {
    if (is_page()) {
        global $post;
        if ($post && get_post_meta($post->ID, '_wp_page_template', true) == 'page-custom-seo.php') {
            $custom_description = get_post_meta($post->ID, '_custom_meta_description', true);
            if (!empty($custom_description)) {
                return $custom_description;
            }
        }
    }
    return $description;
}
add_filter('wpseo_metadesc', 'custom_seo_yoast_meta_description', 10, 2);
add_filter('wpseo_opengraph_desc', 'custom_seo_yoast_meta_description', 10, 2);

// Add custom title support for Yoast
function custom_seo_yoast_title($title) {
    if (is_page()) {
        global $post;
        if ($post && get_post_meta($post->ID, '_wp_page_template', true) == 'page-custom-seo.php') {
            $custom_h1 = get_post_meta($post->ID, '_custom_h1_title', true);
            if (!empty($custom_h1)) {
                // You can modify this to add your site name if needed
                return $custom_h1;
            }
        }
    }
    return $title;
}
add_filter('wpseo_title', 'custom_seo_yoast_title');

// Make sure Yoast recognizes images from custom fields
function custom_seo_yoast_add_images($images, $post_id) {
    $template = get_post_meta($post_id, '_wp_page_template', true);
    if ($template == 'page-custom-seo.php') {
        $image_id = get_post_meta($post_id, '_custom_image', true);
        if ($image_id) {
            $image_url = wp_get_attachment_url($image_id);
            if ($image_url) {
                $images[] = array(
                    'url' => $image_url
                );
            }
        }
    }
    return $images;
}
add_filter('wpseo_sitemap_urlimages', 'custom_seo_yoast_add_images', 10, 2);

// Force Yoast to reanalyze when custom fields are saved
function custom_seo_force_yoast_reanalyze($post_id) {
    $template = get_post_meta($post_id, '_wp_page_template', true);
    if ($template == 'page-custom-seo.php') {
        // Trigger Yoast reanalysis
        delete_post_meta($post_id, '_yoast_wpseo_linkdex');
    }
}
add_action('save_post', 'custom_seo_force_yoast_reanalyze', 20);

// ============================================
// CATEGORY PAGE TEMPLATE FUNCTIONALITY
// ============================================

// Remove editor for Category Page template
function category_page_remove_editor() {
    $post_id = isset($_GET['post']) ? $_GET['post'] : (isset($_POST['post_ID']) ? $_POST['post_ID'] : false);
    if (!$post_id) return;
    
    $template = get_post_meta($post_id, '_wp_page_template', true);
    
    if ($template == 'page-category.php') {
        remove_post_type_support('page', 'editor');
    }
}
add_action('init', 'category_page_remove_editor');

// Add meta description to page head for category pages
function category_page_add_meta_description() {
    if (is_page()) {
        global $post;
        $template = get_post_meta($post->ID, '_wp_page_template', true);
        
        if ($template == 'page-category.php') {
            $meta_description = get_post_meta($post->ID, '_category_meta_description', true);
            
            if (!empty($meta_description)) {
                echo '<meta name="description" content="' . esc_attr($meta_description) . '">' . "\n";
            }
        }
    }
}
add_action('wp_head', 'category_page_add_meta_description', 1);

// Add meta boxes for category page template
function category_page_add_meta_boxes() {
    add_meta_box(
        'category_page_fields',
        'Category Page Fields',
        'category_page_meta_box_callback',
        'page',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'category_page_add_meta_boxes');

// Meta box callback function
function category_page_meta_box_callback($post) {
    // Add nonce for security
    wp_nonce_field('category_page_meta_box', 'category_page_meta_box_nonce');
    
    // Get existing values
    $custom_h1_title = get_post_meta($post->ID, '_category_h1_title', true);
    $custom_meta_description = get_post_meta($post->ID, '_category_meta_description', true);
    $category_groups = get_post_meta($post->ID, '_category_groups', true);
    $custom_subtitle_h2 = get_post_meta($post->ID, '_category_subtitle_h2', true);
    $custom_content = get_post_meta($post->ID, '_category_content', true);
    $custom_image = get_post_meta($post->ID, '_category_image', true);
    $custom_image_alt = get_post_meta($post->ID, '_category_image_alt', true);
    $faq_title = get_post_meta($post->ID, '_category_faq_title', true);
    $faq_items = get_post_meta($post->ID, '_category_faq_items', true);
    
    // Ensure category groups is an array
    if (!is_array($category_groups)) {
        $category_groups = array();
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
        <label for="category_h1_title" style="display: block; font-weight: bold; margin-bottom: 5px;">
            1. H1 Title
        </label>
        <input type="text" 
               id="category_h1_title" 
               name="category_h1_title" 
               value="<?php echo esc_attr($custom_h1_title); ?>" 
               style="width: 100%; padding: 8px; font-size: 14px;"
               placeholder="Enter H1 title">
    </div>
    
    <div style="margin-bottom: 20px;">
        <label for="category_meta_description" style="display: block; font-weight: bold; margin-bottom: 5px;">
            2. Meta Description
        </label>
        <textarea id="category_meta_description" 
                  name="category_meta_description" 
                  style="width: 100%; padding: 8px; font-size: 14px; min-height: 60px;"
                  maxlength="160"
                  placeholder="Enter meta description for SEO (max 160 characters)"><?php echo esc_textarea($custom_meta_description); ?></textarea>
        <p style="color: #666; font-size: 12px; margin-top: 5px;">
            <span id="category_meta_description_count">0</span>/160 characters
        </p>
    </div>
    
    <div style="margin-bottom: 20px;">
        <label style="display: block; font-weight: bold; margin-bottom: 5px;">
            3. Categories (Groups and Links)
        </label>
        <div id="category_groups_container">
            <?php 
            if (!empty($category_groups)) {
                foreach ($category_groups as $group_index => $group) {
                    ?>
                    <div class="category-group-item" style="border: 2px solid #2271b1; padding: 20px; margin-bottom: 20px; border-radius: 5px; background: #f0f8ff;">
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Group Title:</label>
                            <input type="text" 
                                   name="category_groups[<?php echo $group_index; ?>][title]" 
                                   value="<?php echo esc_attr($group['title'] ?? ''); ?>" 
                                   style="width: 100%; padding: 8px; font-size: 14px;"
                                   placeholder="e.g., NACH GESCHLECHT">
                        </div>
                        
                        <div class="category-links-container">
                            <label style="display: block; margin-bottom: 10px; font-weight: bold;">Links:</label>
                            <?php 
                            if (!empty($group['links']) && is_array($group['links'])) {
                                foreach ($group['links'] as $link_index => $link) {
                                    ?>
                                    <div class="category-link-item" style="border: 1px solid #ddd; padding: 10px; margin-bottom: 10px; border-radius: 3px; background: white;">
                                        <div style="display: grid; grid-template-columns: 80px 1fr 1fr auto; gap: 10px; align-items: center;">
                                            <input type="text" 
                                                   name="category_groups[<?php echo $group_index; ?>][links][<?php echo $link_index; ?>][emoji]" 
                                                   value="<?php echo esc_attr($link['emoji'] ?? ''); ?>" 
                                                   style="padding: 8px; font-size: 14px;"
                                                   placeholder="Emoji">
                                            <input type="text" 
                                                   name="category_groups[<?php echo $group_index; ?>][links][<?php echo $link_index; ?>][text]" 
                                                   value="<?php echo esc_attr($link['text'] ?? ''); ?>" 
                                                   style="padding: 8px; font-size: 14px;"
                                                   placeholder="Link text">
                                            <select name="category_groups[<?php echo $group_index; ?>][links][<?php echo $link_index; ?>][page_id]" style="padding: 8px;">
                                                <option value="">-- Select Page or use URL below --</option>
                                                <?php
                                                $pages = get_pages();
                                                foreach ($pages as $page) {
                                                    $selected = (isset($link['page_id']) && $link['page_id'] == $page->ID) ? 'selected' : '';
                                                    echo '<option value="' . $page->ID . '" ' . $selected . '>' . esc_html($page->post_title) . '</option>';
                                                }
                                                ?>
                                            </select>
                                            <button type="button" class="remove-category-link" style="padding: 5px 10px; background: #d63638; color: white; border: none; border-radius: 3px; cursor: pointer;">Remove</button>
                                        </div>
                                        <div style="margin-top: 10px;">
                                            <input type="text" 
                                                   name="category_groups[<?php echo $group_index; ?>][links][<?php echo $link_index; ?>][custom_url]" 
                                                   value="<?php echo esc_attr($link['custom_url'] ?? ''); ?>" 
                                                   style="width: 100%; padding: 8px; font-size: 14px;"
                                                   placeholder="OR enter custom URL (optional)">
                                        </div>
                                    </div>
                                    <?php
                                }
                            }
                            ?>
                        </div>
                        <button type="button" class="add-category-link" data-group-index="<?php echo $group_index; ?>" style="margin-top: 10px; padding: 8px 15px; background: #00a32a; color: white; border: none; border-radius: 3px; cursor: pointer;">
                            + Add Link
                        </button>
                        <button type="button" class="remove-category-group" style="margin-top: 10px; margin-left: 10px; padding: 8px 15px; background: #d63638; color: white; border: none; border-radius: 3px; cursor: pointer;">
                            Remove Group
                        </button>
                    </div>
                    <?php
                }
            }
            ?>
        </div>
        <button type="button" id="add_category_group" style="margin-top: 10px; padding: 8px 15px; background: #2271b1; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 16px;">
            + Add Category Group
        </button>
    </div>
    
    <div style="margin-bottom: 20px;">
        <label for="category_subtitle_h2" style="display: block; font-weight: bold; margin-bottom: 5px;">
            4. H2 Subtitle
        </label>
        <input type="text" 
               id="category_subtitle_h2" 
               name="category_subtitle_h2" 
               value="<?php echo esc_attr($custom_subtitle_h2); ?>" 
               style="width: 100%; padding: 8px; font-size: 14px;"
               placeholder="Enter H2 subtitle">
    </div>
    
    <div style="margin-bottom: 20px;">
        <label for="category_content" style="display: block; font-weight: bold; margin-bottom: 5px;">
            5. Content
        </label>
        <?php 
        wp_editor($custom_content, 'category_content', array(
            'textarea_name' => 'category_content',
            'media_buttons' => true,
            'textarea_rows' => 10,
            'teeny' => false,
            'quicktags' => true
        ));
        ?>
    </div>
    
    <div style="margin-bottom: 20px;">
        <label style="display: block; font-weight: bold; margin-bottom: 5px;">
            6. Image 300x250
        </label>
        
        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 10px;">
                <input type="radio" name="category_image_type" value="auto" <?php echo (!get_post_meta($post->ID, '_category_image_type', true) || get_post_meta($post->ID, '_category_image_type', true) == 'auto') ? 'checked' : ''; ?>>
                <strong>Auto-generate from H1 title</strong>
            </label>
            <label style="display: block;">
                <input type="radio" name="category_image_type" value="upload" <?php echo (get_post_meta($post->ID, '_category_image_type', true) == 'upload') ? 'checked' : ''; ?>>
                <strong>Upload custom image</strong>
            </label>
        </div>
        
        <div style="display: flex; gap: 15px; align-items: flex-start;">
            <div style="flex: 1;">
                <?php 
                $image_type = get_post_meta($post->ID, '_category_image_type', true);
                if (empty($image_type)) {
                    $image_type = 'auto'; // Default to auto if empty
                }
                ?>
                
                <!-- Auto-generated image section -->
                <div id="category_auto_image_section" style="<?php echo ($image_type === 'upload') ? 'display: none;' : ''; ?>">
                    <div id="category_image_preview" style="margin-bottom: 10px;">
                        <canvas id="category_auto_image_canvas" width="300" height="250" style="border: 1px solid #ddd; background: #000;"></canvas>
                    </div>
                    <button type="button" id="generate_category_image_button" class="button button-primary" style="margin-bottom: 10px;">
                        Generate Image from H1
                    </button>
                    <input type="hidden" id="category_image_data" name="category_image_data" value="">
                </div>
                
                <!-- Upload image section -->
                <div id="category_upload_image_section" style="<?php echo ($image_type !== 'upload') ? 'display: none;' : ''; ?>">
                    <div id="category_uploaded_image_preview" style="margin-bottom: 10px;">
                        <?php 
                        $uploaded_image_id = get_post_meta($post->ID, '_category_uploaded_image', true);
                        if ($uploaded_image_id) {
                            echo wp_get_attachment_image($uploaded_image_id, array(300, 250));
                        } else {
                            echo '<div style="width: 300px; height: 250px; border: 2px dashed #ddd; display: flex; align-items: center; justify-content: center; color: #999;">No image selected</div>';
                        }
                        ?>
                    </div>
                    <button type="button" id="category_upload_image_button" class="button button-primary">
                        Select Image
                    </button>
                    <button type="button" id="category_remove_uploaded_image" class="button" style="<?php echo $uploaded_image_id ? '' : 'display: none;'; ?>">
                        Remove Image
                    </button>
                    <input type="hidden" id="category_uploaded_image" name="category_uploaded_image" value="<?php echo esc_attr($uploaded_image_id); ?>">
                </div>
            </div>
            <div style="flex: 1;">
                <label for="category_image_alt" style="display: block; margin-bottom: 5px;">Alt Text (auto-filled from H1):</label>
                <input type="text" 
                       id="category_image_alt" 
                       name="category_image_alt" 
                       value="<?php echo esc_attr($custom_image_alt ?: $custom_h1_title); ?>" 
                       style="width: 100%; padding: 8px; font-size: 14px;"
                       placeholder="Auto-filled from H1 title">
            </div>
        </div>
        <p style="color: #666; font-size: 12px; margin-top: 5px;">
            Choose to auto-generate a 300x250 image with black background and H1 text, or upload your own custom image.
        </p>
    </div>
    
    <div style="margin-bottom: 20px;">
        <label for="category_faq_title" style="display: block; font-weight: bold; margin-bottom: 5px;">
            7. FAQ Title
        </label>
        <input type="text" 
               id="category_faq_title" 
               name="category_faq_title" 
               value="<?php echo esc_attr($faq_title); ?>" 
               style="width: 100%; padding: 8px; font-size: 14px;"
               placeholder="e.g., Häufig gestellte Fragen">
    </div>
    
    <div style="margin-bottom: 20px;">
        <label style="display: block; font-weight: bold; margin-bottom: 5px;">
            8. FAQ Questions and Answers
        </label>
        <div id="category_faq_container">
            <?php 
            if (!empty($faq_items)) {
                foreach ($faq_items as $index => $faq) {
                    ?>
                    <div class="category-faq-item" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 10px; border-radius: 5px;">
                        <div style="margin-bottom: 10px;">
                            <label style="display: block; margin-bottom: 5px;">Question:</label>
                            <input type="text" 
                                   name="category_faq_items[<?php echo $index; ?>][question]" 
                                   value="<?php echo esc_attr($faq['question'] ?? ''); ?>" 
                                   style="width: 100%; padding: 8px; font-size: 14px;"
                                   placeholder="Enter FAQ question">
                        </div>
                        <div style="margin-bottom: 10px;">
                            <label style="display: block; margin-bottom: 5px;">Answer:</label>
                            <textarea name="category_faq_items[<?php echo $index; ?>][answer]" 
                                      style="width: 100%; padding: 8px; font-size: 14px; min-height: 80px;"
                                      placeholder="Enter FAQ answer"><?php echo esc_textarea($faq['answer'] ?? ''); ?></textarea>
                        </div>
                        <button type="button" class="remove-category-faq" style="padding: 5px 10px; background: #d63638; color: white; border: none; border-radius: 3px; cursor: pointer;">
                            Remove FAQ
                        </button>
                    </div>
                    <?php
                }
            }
            ?>
        </div>
        <button type="button" id="add_category_faq" style="margin-top: 10px; padding: 8px 15px; background: #00a32a; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 16px;">
            + Add FAQ
        </button>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // Handle image type selection
        $('input[name="category_image_type"]').on('change', function() {
            console.log('Category image type changed to:', $(this).val());
            if ($(this).val() === 'auto') {
                $('#category_auto_image_section').show();
                $('#category_upload_image_section').hide();
                generateCategoryImage(); // Auto-generate when switching to auto
            } else {
                $('#category_auto_image_section').hide();
                $('#category_upload_image_section').show();
            }
        });
        
        // Media uploader for custom image
        var categoryMediaUploader;
        
        $('#category_upload_image_button').on('click', function(e) {
            e.preventDefault();
            
            if (categoryMediaUploader) {
                categoryMediaUploader.open();
                return;
            }
            
            categoryMediaUploader = wp.media({
                title: 'Select Image',
                button: {
                    text: 'Use this image'
                },
                multiple: false,
                library: {
                    type: 'image'
                }
            });
            
            categoryMediaUploader.on('select', function() {
                var attachment = categoryMediaUploader.state().get('selection').first().toJSON();
                $('#category_uploaded_image').val(attachment.id);
                
                // Update preview
                var img = '<img src="' + attachment.url + '" style="max-width: 300px; max-height: 250px;">';
                $('#category_uploaded_image_preview').html(img);
                $('#category_remove_uploaded_image').show();
                
                // Auto-fill alt text if empty
                if (!$('#category_image_alt').val()) {
                    $('#category_image_alt').val(attachment.alt || attachment.title || '');
                }
            });
            
            categoryMediaUploader.open();
        });
        
        $('#category_remove_uploaded_image').on('click', function() {
            $('#category_uploaded_image').val('');
            $('#category_uploaded_image_preview').html('<div style="width: 300px; height: 250px; border: 2px dashed #ddd; display: flex; align-items: center; justify-content: center; color: #999;">No image selected</div>');
            $(this).hide();
        });
        
        // Auto-generate image for category page
        function generateCategoryImage() {
            var h1Text = $('#category_h1_title').val() || $('#title').val();
            var canvas = document.getElementById('category_auto_image_canvas');
            if (!canvas) return; // Exit if canvas doesn't exist
            
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
                $('#category_image_data').val(canvas.toDataURL('image/png'));
                
                // Auto-fill alt text
                $('#category_image_alt').val(h1Text);
            }
        }
        
        // Generate on button click
        $('#generate_category_image_button').on('click', generateCategoryImage);
        
        // Auto-generate when H1 changes
        $('#category_h1_title').on('input', generateCategoryImage);
        
        // Generate on load only if auto mode is selected
        if ($('input[name="category_image_type"]:checked').val() !== 'upload') {
            generateCategoryImage();
        }
        
        // Character count for meta description
        var metaDescField = $('#category_meta_description');
        var metaDescCount = $('#category_meta_description_count');
        
        function updateCharCount() {
            var count = metaDescField.val().length;
            metaDescCount.text(count);
            
            if (count > 160) {
                metaDescCount.css('color', '#d63638');
            } else if (count > 140) {
                metaDescCount.css('color', '#dba617');
            } else {
                metaDescCount.css('color', '#00a32a');
            }
        }
        
        updateCharCount();
        metaDescField.on('input', updateCharCount);
        
        // Category groups functionality
        var groupsContainer = $('#category_groups_container');
        var groupIndex = groupsContainer.find('.category-group-item').length;
        
        // Add category group
        $('#add_category_group').on('click', function() {
            var groupHtml = '<div class="category-group-item" style="border: 2px solid #2271b1; padding: 20px; margin-bottom: 20px; border-radius: 5px; background: #f0f8ff;">' +
                '<div style="margin-bottom: 15px;">' +
                '<label style="display: block; margin-bottom: 5px; font-weight: bold;">Group Title:</label>' +
                '<input type="text" name="category_groups[' + groupIndex + '][title]" style="width: 100%; padding: 8px; font-size: 14px;" placeholder="e.g., NACH GESCHLECHT">' +
                '</div>' +
                '<div class="category-links-container">' +
                '<label style="display: block; margin-bottom: 10px; font-weight: bold;">Links:</label>' +
                '</div>' +
                '<button type="button" class="add-category-link" data-group-index="' + groupIndex + '" style="margin-top: 10px; padding: 8px 15px; background: #00a32a; color: white; border: none; border-radius: 3px; cursor: pointer;">+ Add Link</button>' +
                '<button type="button" class="remove-category-group" style="margin-top: 10px; margin-left: 10px; padding: 8px 15px; background: #d63638; color: white; border: none; border-radius: 3px; cursor: pointer;">Remove Group</button>' +
                '</div>';
            
            groupsContainer.append(groupHtml);
            groupIndex++;
        });
        
        // Remove category group
        $(document).on('click', '.remove-category-group', function() {
            $(this).closest('.category-group-item').remove();
        });
        
        // Add category link
        $(document).on('click', '.add-category-link', function() {
            var groupIdx = $(this).data('group-index');
            var linksContainer = $(this).siblings('.category-links-container');
            var linkIndex = linksContainer.find('.category-link-item').length;
            
            var linkHtml = '<div class="category-link-item" style="border: 1px solid #ddd; padding: 10px; margin-bottom: 10px; border-radius: 3px; background: white;">' +
                '<div style="display: grid; grid-template-columns: 80px 1fr 1fr auto; gap: 10px; align-items: center;">' +
                '<input type="text" name="category_groups[' + groupIdx + '][links][' + linkIndex + '][emoji]" style="padding: 8px; font-size: 14px;" placeholder="Emoji">' +
                '<input type="text" name="category_groups[' + groupIdx + '][links][' + linkIndex + '][text]" style="padding: 8px; font-size: 14px;" placeholder="Link text">' +
                '<select name="category_groups[' + groupIdx + '][links][' + linkIndex + '][page_id]" style="padding: 8px;">' +
                '<option value="">-- Select Page or use URL below --</option>' +
                <?php
                $pages = get_pages();
                foreach ($pages as $page) {
                    echo "'<option value=\"" . $page->ID . "\">" . esc_js($page->post_title) . "</option>' +";
                }
                ?>
                '</select>' +
                '<button type="button" class="remove-category-link" style="padding: 5px 10px; background: #d63638; color: white; border: none; border-radius: 3px; cursor: pointer;">Remove</button>' +
                '</div>' +
                '<div style="margin-top: 10px;">' +
                '<input type="text" name="category_groups[' + groupIdx + '][links][' + linkIndex + '][custom_url]" style="width: 100%; padding: 8px; font-size: 14px;" placeholder="OR enter custom URL (optional)">' +
                '</div>' +
                '</div>';
            
            linksContainer.append(linkHtml);
        });
        
        // Remove category link
        $(document).on('click', '.remove-category-link', function() {
            $(this).closest('.category-link-item').remove();
        });
        
        // FAQ functionality
        var faqContainer = $('#category_faq_container');
        var faqIndex = faqContainer.find('.category-faq-item').length;
        
        var faqTemplate = function(index) {
            return '<div class="category-faq-item" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 10px; border-radius: 5px;">' +
                   '<div style="margin-bottom: 10px;">' +
                   '<label style="display: block; margin-bottom: 5px;">Question:</label>' +
                   '<input type="text" name="category_faq_items[' + index + '][question]" style="width: 100%; padding: 8px; font-size: 14px;" placeholder="Enter FAQ question">' +
                   '</div>' +
                   '<div style="margin-bottom: 10px;">' +
                   '<label style="display: block; margin-bottom: 5px;">Answer:</label>' +
                   '<textarea name="category_faq_items[' + index + '][answer]" style="width: 100%; padding: 8px; font-size: 14px; min-height: 80px;" placeholder="Enter FAQ answer"></textarea>' +
                   '</div>' +
                   '<button type="button" class="remove-category-faq" style="padding: 5px 10px; background: #d63638; color: white; border: none; border-radius: 3px; cursor: pointer;">Remove FAQ</button>' +
                   '</div>';
        };
        
        $('#add_category_faq').on('click', function() {
            faqContainer.append(faqTemplate(faqIndex));
            faqIndex++;
        });
        
        $(document).on('click', '.remove-category-faq', function() {
            $(this).closest('.category-faq-item').remove();
        });
    });
    </script>
    
    <?php
}

// Save meta box data
function category_page_save_meta_box_data($post_id) {
    // Check if nonce is set
    if (!isset($_POST['category_page_meta_box_nonce'])) {
        return;
    }
    
    // Verify nonce
    if (!wp_verify_nonce($_POST['category_page_meta_box_nonce'], 'category_page_meta_box')) {
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
    if (isset($_POST['category_h1_title'])) {
        update_post_meta($post_id, '_category_h1_title', sanitize_text_field($_POST['category_h1_title']));
    }
    
    // Save Meta Description
    if (isset($_POST['category_meta_description'])) {
        $meta_desc = sanitize_textarea_field($_POST['category_meta_description']);
        $meta_desc = substr($meta_desc, 0, 160);
        update_post_meta($post_id, '_category_meta_description', $meta_desc);
    }
    
    // Save Category Groups
    if (isset($_POST['category_groups'])) {
        $groups = array();
        foreach ($_POST['category_groups'] as $group) {
            if (!empty($group['title'])) {
                $clean_group = array(
                    'title' => sanitize_text_field($group['title']),
                    'links' => array()
                );
                
                if (!empty($group['links']) && is_array($group['links'])) {
                    foreach ($group['links'] as $link) {
                        if (!empty($link['text'])) {
                            $clean_group['links'][] = array(
                                'emoji' => sanitize_text_field($link['emoji'] ?? ''),
                                'text' => sanitize_text_field($link['text']),
                                'page_id' => absint($link['page_id'] ?? 0),
                                'custom_url' => esc_url_raw($link['custom_url'] ?? '')
                            );
                        }
                    }
                }
                
                if (!empty($clean_group['links'])) {
                    $groups[] = $clean_group;
                }
            }
        }
        update_post_meta($post_id, '_category_groups', $groups);
    } else {
        update_post_meta($post_id, '_category_groups', array());
    }
    
    // Save Subtitle H2
    if (isset($_POST['category_subtitle_h2'])) {
        update_post_meta($post_id, '_category_subtitle_h2', sanitize_text_field($_POST['category_subtitle_h2']));
    }
    
    // Save Content
    if (isset($_POST['category_content'])) {
        update_post_meta($post_id, '_category_content', wp_kses_post($_POST['category_content']));
    }
    
    // Save Image Type (ADD THIS BEFORE IMAGE SAVING)
    if (isset($_POST['category_image_type'])) {
        update_post_meta($post_id, '_category_image_type', sanitize_text_field($_POST['category_image_type']));
    } else {
        // Default to 'auto' if not set
        update_post_meta($post_id, '_category_image_type', 'auto');
    }
    
    // Save Image and Alt Text
    if (isset($_POST['category_image_type']) && $_POST['category_image_type'] == 'upload') {
        // Save uploaded image
        if (isset($_POST['category_uploaded_image']) && !empty($_POST['category_uploaded_image'])) {
            update_post_meta($post_id, '_category_uploaded_image', absint($_POST['category_uploaded_image']));
            update_post_meta($post_id, '_category_image', absint($_POST['category_uploaded_image']));
        }
    } else {
        // Save auto-generated image
        if (isset($_POST['category_image_data']) && !empty($_POST['category_image_data'])) {
            // Get H1 title for filename
            $h1_title = get_post_meta($post_id, '_category_h1_title', true) ?: get_the_title($post_id);
            $filename = sanitize_file_name($h1_title) . '-category.png';
            
            // Process base64 image
            $image_data = $_POST['category_image_data'];
            $image_data = str_replace('data:image/png;base64,', '', $image_data);
            $image_data = str_replace(' ', '+', $image_data);
            $decoded_image = base64_decode($image_data);
            
            // Upload to media library
            $upload_dir = wp_upload_dir();
            $upload_path = $upload_dir['path'] . '/' . $filename;
            $upload_url = $upload_dir['url'] . '/' . $filename;
            
            file_put_contents($upload_path, $decoded_image);
            
            // Check if image already exists
            $existing_image = get_page_by_title($h1_title . '-category', OBJECT, 'attachment');
            
            if (!$existing_image) {
                // Create attachment
                $attachment = array(
                    'post_mime_type' => 'image/png',
                    'post_title' => $h1_title . '-category',
                    'post_content' => '',
                    'post_status' => 'inherit'
                );
                
                $attach_id = wp_insert_attachment($attachment, $upload_path, $post_id);
                
                // Generate metadata
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                $attach_data = wp_generate_attachment_metadata($attach_id, $upload_path);
                wp_update_attachment_metadata($attach_id, $attach_data);
                
                update_post_meta($post_id, '_category_image', $attach_id);
            } else {
                update_post_meta($post_id, '_category_image', $existing_image->ID);
            }
        }
    }
    
    if (isset($_POST['category_image_alt'])) {
        update_post_meta($post_id, '_category_image_alt', sanitize_text_field($_POST['category_image_alt']));
    }
    
    // Save FAQ Title
    if (isset($_POST['category_faq_title'])) {
        update_post_meta($post_id, '_category_faq_title', sanitize_text_field($_POST['category_faq_title']));
    }
    
    // Save FAQ Items
    if (isset($_POST['category_faq_items'])) {
        $faqs = array();
        foreach ($_POST['category_faq_items'] as $faq) {
            if (!empty($faq['question']) && !empty($faq['answer'])) {
                $faqs[] = array(
                    'question' => sanitize_text_field($faq['question']),
                    'answer' => sanitize_textarea_field($faq['answer'])
                );
            }
        }
        update_post_meta($post_id, '_category_faq_items', $faqs);
    } else {
        update_post_meta($post_id, '_category_faq_items', array());
    }
}
add_action('save_post', 'category_page_save_meta_box_data');

// Add JavaScript to show meta box only when category template is selected
function category_page_admin_script() {
    global $post;
    if ($post && $post->post_type == 'page') {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            function toggleCategoryMetaBox() {
                var template = $('#page_template').val();
                if (template == 'page-category.php') {
                    $('#category_page_fields').show();
                } else {
                    $('#category_page_fields').hide();
                }
            }
            
            toggleCategoryMetaBox();
            $('#page_template').on('change', toggleCategoryMetaBox);
            
            // For Block Editor
            if (wp && wp.data && wp.data.select('core/editor')) {
                const { select, subscribe } = wp.data;
                
                subscribe(() => {
                    const template = select('core/editor').getEditedPostAttribute('template');
                    
                    if (template === 'page-category.php') {
                        $('#category_page_fields').show();
                    } else {
                        $('#category_page_fields').hide();
                    }
                });
            }
        });
        </script>
        <?php
    }
}
add_action('admin_footer', 'category_page_admin_script');

// ============================================
// YOAST SEO INTEGRATION FOR CATEGORY PAGES
// ============================================

// Add category page fields content to Yoast SEO analysis
function category_page_add_to_yoast_analysis($content, $post) {
    if ($post && get_post_meta($post->ID, '_wp_page_template', true) == 'page-category.php') {
        
        // Start with empty content
        $custom_content = '';
        
        // Add H1
        $h1 = get_post_meta($post->ID, '_category_h1_title', true);
        if ($h1) {
            $custom_content .= '<h1>' . $h1 . '</h1> ';
        }
        
        // Add meta description as introduction paragraph
        $meta_desc = get_post_meta($post->ID, '_category_meta_description', true);
        if ($meta_desc) {
            $custom_content .= '<p>' . $meta_desc . '</p> ';
        }
        
        // Add category groups and links
        $category_groups = get_post_meta($post->ID, '_category_groups', true);
        if (!empty($category_groups) && is_array($category_groups)) {
            foreach ($category_groups as $group) {
                if (!empty($group['title'])) {
                    $custom_content .= '<h2>' . $group['title'] . '</h2> ';
                }
                if (!empty($group['links']) && is_array($group['links'])) {
                    foreach ($group['links'] as $link) {
                        if (!empty($link['text'])) {
                            $custom_content .= '<a href="#">' . $link['text'] . '</a> ';
                        }
                    }
                }
            }
        }
        
        // Add H2
        $h2 = get_post_meta($post->ID, '_category_subtitle_h2', true);
        if ($h2) {
            $custom_content .= '<h2>' . $h2 . '</h2> ';
        }
        
        // Add content
        $content_field = get_post_meta($post->ID, '_category_content', true);
        if ($content_field) {
            $custom_content .= $content_field . ' ';
        }
        
        // Add image with alt text
        $image_id = get_post_meta($post->ID, '_category_image', true);
        $image_alt = get_post_meta($post->ID, '_category_image_alt', true);
        if ($image_id && $image_alt) {
            $custom_content .= '<img src="placeholder.jpg" alt="' . esc_attr($image_alt) . '" /> ';
        }
        
        // Add FAQ title
        $faq_title = get_post_meta($post->ID, '_category_faq_title', true);
        if ($faq_title) {
            $custom_content .= '<h2>' . $faq_title . '</h2> ';
        }
        
        // Add FAQ items
        $faq_items = get_post_meta($post->ID, '_category_faq_items', true);
        if (!empty($faq_items) && is_array($faq_items)) {
            foreach ($faq_items as $faq) {
                if (!empty($faq['question'])) {
                    $custom_content .= '<h3>' . $faq['question'] . '</h3> ';
                }
                if (!empty($faq['answer'])) {
                    $custom_content .= '<p>' . $faq['answer'] . '</p> ';
                }
            }
        }
        
        // Return the custom content instead of the original
        return $custom_content;
    }
    
    return $content;
}

// Hook into Yoast filters for category pages
add_filter('wpseo_pre_analysis_post_content', 'category_page_add_to_yoast_analysis', 10, 2);
add_filter('wpseo_content_analysis_post_content', 'category_page_add_to_yoast_analysis', 10, 2);

// Add JavaScript to update Yoast analysis in real-time for category pages
function category_page_yoast_js() {
    global $post;
    if ($post && $post->post_type == 'page' && get_post_meta($post->ID, '_wp_page_template', true) == 'page-category.php') {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            if (typeof YoastSEO !== 'undefined' && YoastSEO.app) {
                
                // Custom content modification for Yoast
                YoastSEO.app.registerPlugin('categoryPageContent', {status: 'ready'});
                
                YoastSEO.app.registerModification('content', function(content) {
                    var customContent = '';
                    
                    // Get H1
                    var h1 = $('#category_h1_title').val();
                    if (h1) {
                        customContent += '<h1>' + h1 + '</h1> ';
                    }
                    
                    // Get meta description
                    var metaDesc = $('#category_meta_description').val();
                    if (metaDesc) {
                        customContent += '<p>' + metaDesc + '</p> ';
                    }
                    
                    // Get category groups and links
                    $('.category-group-item').each(function() {
                        var groupTitle = $(this).find('input[name*="[title]"]').val();
                        if (groupTitle) {
                            customContent += '<h2>' + groupTitle + '</h2> ';
                        }
                        
                        $(this).find('.category-link-item').each(function() {
                            var linkText = $(this).find('input[name*="[text]"]').val();
                            if (linkText) {
                                customContent += '<a href="#">' + linkText + '</a> ';
                            }
                        });
                    });
                    
                    // Get H2
                    var h2 = $('#category_subtitle_h2').val();
                    if (h2) {
                        customContent += '<h2>' + h2 + '</h2> ';
                    }
                    
                    // Get content from TinyMCE
                    if (typeof tinyMCE !== 'undefined' && tinyMCE.get('category_content')) {
                        var contentField = tinyMCE.get('category_content').getContent();
                        if (contentField) {
                            customContent += contentField + ' ';
                        }
                    }
                    
                    // Get image alt text
                    var imageAlt = $('#category_image_alt').val();
                    if (imageAlt) {
                        customContent += '<img src="placeholder.jpg" alt="' + imageAlt + '" /> ';
                    }
                    
                    // Get FAQ title
                    var faqTitle = $('#category_faq_title').val();
                    if (faqTitle) {
                        customContent += '<h2>' + faqTitle + '</h2> ';
                    }
                    
                    // Get FAQ items
                    $('.category-faq-item').each(function() {
                        var question = $(this).find('input[name*="[question]"]').val();
                        var answer = $(this).find('textarea[name*="[answer]"]').val();
                        if (question) {
                            customContent += '<h3>' + question + '</h3> ';
                        }
                        if (answer) {
                            customContent += '<p>' + answer + '</p> ';
                        }
                    });
                    
                    return customContent;
                }, 'categoryPageContent', 5);
                
                // Trigger reanalysis when fields change
                function triggerYoastReanalysis() {
                    if (YoastSEO.app.refresh) {
                        YoastSEO.app.refresh();
                    }
                }
                
                // Monitor all custom fields for changes
                $('#category_h1_title, #category_meta_description, #category_subtitle_h2, #category_faq_title, #category_image_alt').on('input', function() {
                    setTimeout(triggerYoastReanalysis, 500);
                });
                
                // Monitor dynamic fields
                $(document).on('input', '.category-group-item input, .category-link-item input, .category-faq-item input, .category-faq-item textarea', function() {
                    setTimeout(triggerYoastReanalysis, 500);
                });
                
                // Monitor TinyMCE editors
                if (typeof tinyMCE !== 'undefined') {
                    tinyMCE.on('AddEditor', function(e) {
                        e.editor.on('change keyup', function() {
                            setTimeout(triggerYoastReanalysis, 500);
                        });
                    });
                }
            }
        });
        </script>
        <?php
    }
}
add_action('admin_footer', 'category_page_yoast_js');

// Force Yoast to recognize category page meta description
function category_page_yoast_meta_description($description, $presentation) {
    if (is_page()) {
        global $post;
        if ($post && get_post_meta($post->ID, '_wp_page_template', true) == 'page-category.php') {
            $custom_description = get_post_meta($post->ID, '_category_meta_description', true);
            if (!empty($custom_description)) {
                return $custom_description;
            }
        }
    }
    return $description;
}
add_filter('wpseo_metadesc', 'category_page_yoast_meta_description', 10, 2);
add_filter('wpseo_opengraph_desc', 'category_page_yoast_meta_description', 10, 2);

// Add custom title support for Yoast on category pages
function category_page_yoast_title($title) {
    if (is_page()) {
        global $post;
        if ($post && get_post_meta($post->ID, '_wp_page_template', true) == 'page-category.php') {
            $custom_h1 = get_post_meta($post->ID, '_category_h1_title', true);
            if (!empty($custom_h1)) {
                return $custom_h1;
            }
        }
    }
    return $title;
}
add_filter('wpseo_title', 'category_page_yoast_title');

// Make sure Yoast recognizes images from category page custom fields
function category_page_yoast_add_images($images, $post_id) {
    $template = get_post_meta($post_id, '_wp_page_template', true);
    if ($template == 'page-category.php') {
        $image_id = get_post_meta($post_id, '_category_image', true);
        if ($image_id) {
            $image_url = wp_get_attachment_url($image_id);
            if ($image_url) {
                $images[] = array(
                    'url' => $image_url
                );
            }
        }
    }
    return $images;
}
add_filter('wpseo_sitemap_urlimages', 'category_page_yoast_add_images', 10, 2);

// Force Yoast to reanalyze when category page fields are saved
function category_page_force_yoast_reanalyze($post_id) {
    $template = get_post_meta($post_id, '_wp_page_template', true);
    if ($template == 'page-category.php') {
        // Trigger Yoast reanalysis
        delete_post_meta($post_id, '_yoast_wpseo_linkdex');
    }
}
add_action('save_post', 'category_page_force_yoast_reanalyze', 20);

// ============================================
// HOMEPAGE TEMPLATE FUNCTIONALITY
// ============================================

// Remove editor for Homepage template
function homepage_remove_editor() {
    $post_id = isset($_GET['post']) ? $_GET['post'] : (isset($_POST['post_ID']) ? $_POST['post_ID'] : false);
    if (!$post_id) return;
    
    $template = get_post_meta($post_id, '_wp_page_template', true);
    
    if ($template == 'page-homepage.php') {
        remove_post_type_support('page', 'editor');
    }
}
add_action('init', 'homepage_remove_editor');

// Add meta boxes for homepage template
function homepage_add_meta_boxes() {
    add_meta_box(
        'homepage_fields',
        'Homepage Fields',
        'homepage_meta_box_callback',
        'page',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'homepage_add_meta_boxes');

// Meta box callback function
function homepage_meta_box_callback($post) {
    // Add nonce for security
    wp_nonce_field('homepage_meta_box', 'homepage_meta_box_nonce');
    
    // Get existing values
    $hero_title = get_post_meta($post->ID, '_homepage_hero_title', true);
    $hero_subtitle = get_post_meta($post->ID, '_homepage_hero_subtitle', true);
    $category_cards = get_post_meta($post->ID, '_homepage_category_cards', true);
    $features_title = get_post_meta($post->ID, '_homepage_features_title', true);
    $features = get_post_meta($post->ID, '_homepage_features', true);
    
    // Ensure arrays
    if (!is_array($category_cards)) {
        $category_cards = array();
    }
    if (!is_array($features)) {
        $features = array();
    }
    ?>
    
    <div style="margin-bottom: 20px;">
        <label for="homepage_hero_title" style="display: block; font-weight: bold; margin-bottom: 5px;">
            1. Hero Title
        </label>
        <input type="text" 
               id="homepage_hero_title" 
               name="homepage_hero_title" 
               value="<?php echo esc_attr($hero_title); ?>" 
               style="width: 100%; padding: 8px; font-size: 14px;"
               placeholder="e.g., Schweizerdeutsche Wünsche für jeden Anlass">
    </div>
    
    <div style="margin-bottom: 20px;">
        <label for="homepage_hero_subtitle" style="display: block; font-weight: bold; margin-bottom: 5px;">
            2. Hero Subtitle
        </label>
        <textarea id="homepage_hero_subtitle" 
                  name="homepage_hero_subtitle" 
                  style="width: 100%; padding: 8px; font-size: 14px; min-height: 60px;"
                  placeholder="e.g., Entdecken Sie über 70'000 handverlesene Wünsche..."><?php echo esc_textarea($hero_subtitle); ?></textarea>
    </div>
    
    <div style="margin-bottom: 20px;">
        <label style="display: block; font-weight: bold; margin-bottom: 5px;">
            3. Category Cards
        </label>
        <div id="category_cards_container">
            <?php 
            if (!empty($category_cards)) {
                foreach ($category_cards as $index => $card) {
                    ?>
                    <div class="category-card-item" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 10px; border-radius: 5px;">
                        <div style="display: grid; grid-template-columns: 80px 1fr 150px auto; gap: 10px; align-items: center;">
                            <input type="text" 
                                   name="homepage_category_cards[<?php echo $index; ?>][emoji]" 
                                   value="<?php echo esc_attr($card['emoji'] ?? ''); ?>" 
                                   style="padding: 8px; font-size: 14px;"
                                   placeholder="Emoji">
                            <input type="text" 
                                   name="homepage_category_cards[<?php echo $index; ?>][title]" 
                                   value="<?php echo esc_attr($card['title'] ?? ''); ?>" 
                                   style="padding: 8px; font-size: 14px;"
                                   placeholder="Title (e.g., Geburtstag)">
                            <input type="text" 
                                   name="homepage_category_cards[<?php echo $index; ?>][count]" 
                                   value="<?php echo esc_attr($card['count'] ?? ''); ?>" 
                                   style="padding: 8px; font-size: 14px;"
                                   placeholder="Count (e.g., 60,500 Wünsche)">
                            <button type="button" class="remove-category-card" style="padding: 5px 10px; background: #d63638; color: white; border: none; border-radius: 3px; cursor: pointer;">Remove</button>
                        </div>
                        <div style="margin-top: 10px;">
                            <select name="homepage_category_cards[<?php echo $index; ?>][page_id]" style="width: 100%; padding: 8px;">
                                <option value="">-- Select Page or use URL below --</option>
                                <?php
                                $pages = get_pages();
                                foreach ($pages as $page) {
                                    $selected = (isset($card['page_id']) && $card['page_id'] == $page->ID) ? 'selected' : '';
                                    echo '<option value="' . $page->ID . '" ' . $selected . '>' . esc_html($page->post_title) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div style="margin-top: 10px;">
                            <input type="text" 
                                   name="homepage_category_cards[<?php echo $index; ?>][custom_url]" 
                                   value="<?php echo esc_attr($card['custom_url'] ?? ''); ?>" 
                                   style="width: 100%; padding: 8px; font-size: 14px;"
                                   placeholder="OR enter custom URL (optional)">
                        </div>
                    </div>
                    <?php
                }
            }
            ?>
        </div>
        <button type="button" id="add_category_card" style="margin-top: 10px; padding: 8px 15px; background: #00a32a; color: white; border: none; border-radius: 3px; cursor: pointer;">
            + Add Category Card
        </button>
    </div>
    
    <div style="margin-bottom: 20px;">
        <label for="homepage_features_title" style="display: block; font-weight: bold; margin-bottom: 5px;">
            4. Features Title
        </label>
        <input type="text" 
               id="homepage_features_title" 
               name="homepage_features_title" 
               value="<?php echo esc_attr($features_title); ?>" 
               style="width: 100%; padding: 8px; font-size: 14px;"
               placeholder="e.g., Warum 10wuensche.ch?">
    </div>
    
    <div style="margin-bottom: 20px;">
        <label style="display: block; font-weight: bold; margin-bottom: 5px;">
            5. Features
        </label>
        <div id="features_container">
            <?php 
            if (!empty($features)) {
                foreach ($features as $index => $feature) {
                    ?>
                    <div class="feature-item" style="border: 1px solid #ddd; padding: 10px; margin-bottom: 10px; border-radius: 5px;">
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <input type="text" 
                                   name="homepage_features[<?php echo $index; ?>][text]" 
                                   value="<?php echo esc_attr($feature['text'] ?? ''); ?>" 
                                   style="flex: 1; padding: 8px; font-size: 14px;"
                                   placeholder="Feature text (e.g., 100% Schweizerdeutsch)">
                            <button type="button" class="remove-feature" style="padding: 5px 10px; background: #d63638; color: white; border: none; border-radius: 3px; cursor: pointer;">Remove</button>
                        </div>
                    </div>
                    <?php
                }
            }
            ?>
        </div>
        <button type="button" id="add_feature" style="margin-top: 10px; padding: 8px 15px; background: #00a32a; color: white; border: none; border-radius: 3px; cursor: pointer;">
            + Add Feature
        </button>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // Category cards
        var cardsContainer = $('#category_cards_container');
        var cardIndex = cardsContainer.find('.category-card-item').length;
        
        $('#add_category_card').on('click', function() {
            var cardHtml = '<div class="category-card-item" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 10px; border-radius: 5px;">' +
                '<div style="display: grid; grid-template-columns: 80px 1fr 150px auto; gap: 10px; align-items: center;">' +
                '<input type="text" name="homepage_category_cards[' + cardIndex + '][emoji]" style="padding: 8px; font-size: 14px;" placeholder="Emoji">' +
                '<input type="text" name="homepage_category_cards[' + cardIndex + '][title]" style="padding: 8px; font-size: 14px;" placeholder="Title (e.g., Geburtstag)">' +
                '<input type="text" name="homepage_category_cards[' + cardIndex + '][count]" style="padding: 8px; font-size: 14px;" placeholder="Count (e.g., 60,500 Wünsche)">' +
                '<button type="button" class="remove-category-card" style="padding: 5px 10px; background: #d63638; color: white; border: none; border-radius: 3px; cursor: pointer;">Remove</button>' +
                '</div>' +
                '<div style="margin-top: 10px;">' +
                '<select name="homepage_category_cards[' + cardIndex + '][page_id]" style="width: 100%; padding: 8px;">' +
                '<option value="">-- Select Page or use URL below --</option>' +
                <?php
                $pages = get_pages();
                foreach ($pages as $page) {
                    echo "'<option value=\"" . $page->ID . "\">" . esc_js($page->post_title) . "</option>' +";
                }
                ?>
                '</select>' +
                '</div>' +
                '<div style="margin-top: 10px;">' +
                '<input type="text" name="homepage_category_cards[' + cardIndex + '][custom_url]" style="width: 100%; padding: 8px; font-size: 14px;" placeholder="OR enter custom URL (optional)">' +
                '</div>' +
                '</div>';
            
            cardsContainer.append(cardHtml);
            cardIndex++;
        });
        
        $(document).on('click', '.remove-category-card', function() {
            $(this).closest('.category-card-item').remove();
        });
        
        // Features
        var featuresContainer = $('#features_container');
        var featureIndex = featuresContainer.find('.feature-item').length;
        
        $('#add_feature').on('click', function() {
            var featureHtml = '<div class="feature-item" style="border: 1px solid #ddd; padding: 10px; margin-bottom: 10px; border-radius: 5px;">' +
                '<div style="display: flex; gap: 10px; align-items: center;">' +
                '<input type="text" name="homepage_features[' + featureIndex + '][text]" style="flex: 1; padding: 8px; font-size: 14px;" placeholder="Feature text (e.g., 100% Schweizerdeutsch)">' +
                '<button type="button" class="remove-feature" style="padding: 5px 10px; background: #d63638; color: white; border: none; border-radius: 3px; cursor: pointer;">Remove</button>' +
                '</div>' +
                '</div>';
            
            featuresContainer.append(featureHtml);
            featureIndex++;
        });
        
        $(document).on('click', '.remove-feature', function() {
            $(this).closest('.feature-item').remove();
        });
    });
    </script>
    
    <?php
}

// Save meta box data
function homepage_save_meta_box_data($post_id) {
    // Check if nonce is set
    if (!isset($_POST['homepage_meta_box_nonce'])) {
        return;
    }
    
    // Verify nonce
    if (!wp_verify_nonce($_POST['homepage_meta_box_nonce'], 'homepage_meta_box')) {
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
    
    // Save Hero Title
    if (isset($_POST['homepage_hero_title'])) {
        update_post_meta($post_id, '_homepage_hero_title', sanitize_text_field($_POST['homepage_hero_title']));
    }
    
    // Save Hero Subtitle
    if (isset($_POST['homepage_hero_subtitle'])) {
        update_post_meta($post_id, '_homepage_hero_subtitle', sanitize_textarea_field($_POST['homepage_hero_subtitle']));
    }
    
    // Save Category Cards
    if (isset($_POST['homepage_category_cards'])) {
        $cards = array();
        foreach ($_POST['homepage_category_cards'] as $card) {
            if (!empty($card['title'])) {
                $cards[] = array(
                    'emoji' => sanitize_text_field($card['emoji'] ?? ''),
                    'title' => sanitize_text_field($card['title']),
                    'count' => sanitize_text_field($card['count'] ?? ''),
                    'page_id' => absint($card['page_id'] ?? 0),
                    'custom_url' => esc_url_raw($card['custom_url'] ?? '')
                );
            }
        }
        update_post_meta($post_id, '_homepage_category_cards', $cards);
    } else {
        update_post_meta($post_id, '_homepage_category_cards', array());
    }
    
    // Save Features Title
    if (isset($_POST['homepage_features_title'])) {
        update_post_meta($post_id, '_homepage_features_title', sanitize_text_field($_POST['homepage_features_title']));
    }
    
    // Save Features
    if (isset($_POST['homepage_features'])) {
        $features = array();
        foreach ($_POST['homepage_features'] as $feature) {
            if (!empty($feature['text'])) {
                $features[] = array(
                    'text' => sanitize_text_field($feature['text'])
                );
            }
        }
        update_post_meta($post_id, '_homepage_features', $features);
    } else {
        update_post_meta($post_id, '_homepage_features', array());
    }
}
add_action('save_post', 'homepage_save_meta_box_data');

// Add JavaScript to show meta box only when homepage template is selected
function homepage_admin_script() {
    global $post;
    if ($post && $post->post_type == 'page') {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            function toggleHomepageMetaBox() {
                var template = $('#page_template').val();
                if (template == 'page-homepage.php') {
                    $('#homepage_fields').show();
                } else {
                    $('#homepage_fields').hide();
                }
            }
            
            toggleHomepageMetaBox();
            $('#page_template').on('change', toggleHomepageMetaBox);
            
            // For Block Editor
            if (wp && wp.data && wp.data.select('core/editor')) {
                const { select, subscribe } = wp.data;
                
                subscribe(() => {
                    const template = select('core/editor').getEditedPostAttribute('template');
                    
                    if (template === 'page-homepage.php') {
                        $('#homepage_fields').show();
                    } else {
                        $('#homepage_fields').hide();
                    }
                });
            }
        });
        </script>
        <?php
    }
}
add_action('admin_footer', 'homepage_admin_script');

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