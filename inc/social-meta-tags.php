<?php
/**
 * Open Graph and Social Meta Tags Functions
 */

// Add Open Graph and Twitter meta tags
function theme_add_social_meta_tags() {
    if (!is_singular()) {
        return;
    }
    
    global $post;
    
    // Get the template
    $template = get_post_meta($post->ID, '_wp_page_template', true);
    
    // Default values
    $og_title = get_the_title();
    $og_description = get_the_excerpt();
    $og_url = get_permalink();
    $og_site_name = get_bloginfo('name');
    $og_locale = 'de_CH';
    
    // Get custom values based on template
    if ($template == 'page-custom-seo.php') {
        $custom_h1 = get_post_meta($post->ID, '_custom_h1_title', true);
        $custom_desc = get_post_meta($post->ID, '_custom_meta_description', true);
        $custom_image_id = get_post_meta($post->ID, '_custom_image', true);
        
        if (!empty($custom_h1)) {
            $og_title = $custom_h1;
        }
        if (!empty($custom_desc)) {
            $og_description = $custom_desc;
        }
        
        // Check for custom OG image first
        $og_image_id = get_post_meta($post->ID, '_og_image_id', true);
        if (empty($og_image_id) && !empty($custom_image_id)) {
            $og_image_id = $custom_image_id;
        }
        
    } elseif ($template == 'page-category.php') {
        $custom_h1 = get_post_meta($post->ID, '_category_h1_title', true);
        $custom_desc = get_post_meta($post->ID, '_category_meta_description', true);
        $custom_image_id = get_post_meta($post->ID, '_category_image', true);
        
        if (!empty($custom_h1)) {
            $og_title = $custom_h1;
        }
        if (!empty($custom_desc)) {
            $og_description = $custom_desc;
        }
        
        // Check for custom OG image first
        $og_image_id = get_post_meta($post->ID, '_og_image_id', true);
        if (empty($og_image_id) && !empty($custom_image_id)) {
            $og_image_id = $custom_image_id;
        }
    }
    
    // Generate or get OG image
    $og_image_url = '';
    if (!empty($og_image_id)) {
        $og_image_url = wp_get_attachment_url($og_image_id);
    } else {
        // Auto-generate OG image if not set
        $generated_image_id = theme_generate_og_image($post->ID, $og_title);
        if ($generated_image_id) {
            $og_image_url = wp_get_attachment_url($generated_image_id);
            // Save for future use
            update_post_meta($post->ID, '_og_image_id', $generated_image_id);
        }
    }
    
    // Fallback to featured image
    if (empty($og_image_url) && has_post_thumbnail()) {
        $og_image_url = get_the_post_thumbnail_url($post->ID, 'large');
    }
    
    // Output Open Graph tags
    echo '<meta property="og:title" content="' . esc_attr($og_title) . '" />' . "\n";
    echo '<meta property="og:description" content="' . esc_attr($og_description) . '" />' . "\n";
    echo '<meta property="og:url" content="' . esc_url($og_url) . '" />' . "\n";
    echo '<meta property="og:site_name" content="' . esc_attr($og_site_name) . '" />' . "\n";
    echo '<meta property="og:locale" content="' . esc_attr($og_locale) . '" />' . "\n";
    echo '<meta property="og:type" content="article" />' . "\n";
    
    if (!empty($og_image_url)) {
        echo '<meta property="og:image" content="' . esc_url($og_image_url) . '" />' . "\n";
        echo '<meta property="og:image:width" content="1200" />' . "\n";
        echo '<meta property="og:image:height" content="630" />' . "\n";
    }
    
    // Output Twitter Card tags
    echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
    echo '<meta name="twitter:title" content="' . esc_attr($og_title) . '" />' . "\n";
    echo '<meta name="twitter:description" content="' . esc_attr($og_description) . '" />' . "\n";
    if (!empty($og_image_url)) {
        echo '<meta name="twitter:image" content="' . esc_url($og_image_url) . '" />' . "\n";
    }
}
add_action('wp_head', 'theme_add_social_meta_tags', 5);

// Generate OG image automatically
function theme_generate_og_image($post_id, $title) {
    // Check if GD library is available
    if (!function_exists('imagecreatetruecolor')) {
        return false;
    }
    
    // Create image 1200x630 (Facebook recommended)
    $width = 1200;
    $height = 630;
    $image = imagecreatetruecolor($width, $height);
    
    // Colors
    $white = imagecolorallocate($image, 255, 255, 255);
    $black = imagecolorallocate($image, 0, 0, 0);
    $gray = imagecolorallocate($image, 100, 100, 100);
    
    // Fill with white background
    imagefilledrectangle($image, 0, 0, $width, $height, $white);
    
    // Add border
    $border_width = 20;
    imagerectangle($image, $border_width, $border_width, $width - $border_width - 1, $height - $border_width - 1, $gray);
    
    // Add site name at top
    $site_name = get_theme_site_name();
    $site_emoji = get_theme_emoji();
    
    // Use built-in font for site name
    imagestring($image, 5, 60, 40, $site_emoji . ' ' . $site_name, $gray);
    
    // Add title in center with word wrap
    $padding = 100;
    $max_width = $width - ($padding * 2);
    
    // Simple word wrap for built-in font
    $words = explode(' ', $title);
    $lines = array();
    $current_line = '';
    
    foreach ($words as $word) {
        $test_line = $current_line . $word . ' ';
        if (strlen($test_line) * 20 > $max_width) { // Approximate width
            if (!empty($current_line)) {
                $lines[] = trim($current_line);
                $current_line = $word . ' ';
            } else {
                $lines[] = $word;
                $current_line = '';
            }
        } else {
            $current_line = $test_line;
        }
    }
    if (!empty($current_line)) {
        $lines[] = trim($current_line);
    }
    
    // Center the text block vertically
    $line_height = 60;
    $text_height = count($lines) * $line_height;
    $y_start = ($height - $text_height) / 2;
    
    // Draw each line centered
    foreach ($lines as $i => $line) {
        $text_width = strlen($line) * 20; // Approximate width
        $x = ($width - $text_width) / 2;
        $y = $y_start + ($i * $line_height) + 40;
        
        // Use built-in font with larger size
        imagestring($image, 5, $x, $y, $line, $black);
    }
    
    // Save image
    $upload_dir = wp_upload_dir();
    $filename = 'og-image-' . $post_id . '-' . time() . '.png';
    $filepath = $upload_dir['path'] . '/' . $filename;
    
    imagepng($image, $filepath);
    imagedestroy($image);
    
    // Create attachment
    $attachment = array(
        'post_mime_type' => 'image/png',
        'post_title' => 'OG Image - ' . $title,
        'post_content' => '',
        'post_status' => 'inherit'
    );
    
    $attach_id = wp_insert_attachment($attachment, $filepath, $post_id);
    
    // Generate metadata
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $attach_data = wp_generate_attachment_metadata($attach_id, $filepath);
    wp_update_attachment_metadata($attach_id, $attach_data);
    
    return $attach_id;
}

// Add OG image field to page editor
function add_og_image_meta_box() {
    add_meta_box(
        'og_image_meta_box',
        'Social Media Image (Open Graph)',
        'og_image_meta_box_callback',
        'page',
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'add_og_image_meta_box');

// OG image meta box callback
function og_image_meta_box_callback($post) {
    wp_nonce_field('og_image_meta_box', 'og_image_meta_box_nonce');
    
    $og_image_id = get_post_meta($post->ID, '_og_image_id', true);
    ?>
    
    <div style="margin-bottom: 10px;">
        <p style="margin-bottom: 10px;">This image will be shown when sharing on social media (WhatsApp, Facebook, Twitter, etc.)</p>
        
        <div id="og_image_preview" style="margin-bottom: 10px;">
            <?php if ($og_image_id) : 
                $image_url = wp_get_attachment_image_src($og_image_id, 'medium');
                if ($image_url) : ?>
                    <img src="<?php echo esc_url($image_url[0]); ?>" style="max-width: 100%; height: auto;">
                <?php endif;
            else : ?>
                <p style="padding: 20px; background: #f0f0f0; text-align: center; color: #666;">
                    No custom image set.<br>
                    An image with the page title will be<br>
                    automatically generated.
                </p>
            <?php endif; ?>
        </div>
        
        <button type="button" id="og_image_button" class="button button-primary" style="width: 100%;">
            <?php echo $og_image_id ? 'Change Image' : 'Select Custom Image'; ?>
        </button>
        
        <?php if ($og_image_id) : ?>
            <button type="button" id="remove_og_image" class="button" style="width: 100%; margin-top: 5px;">
                Remove Image (Use Auto-Generated)
            </button>
        <?php endif; ?>
        
        <input type="hidden" id="og_image_id" name="og_image_id" value="<?php echo esc_attr($og_image_id); ?>">
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        var ogMediaUploader;
        
        $('#og_image_button').on('click', function(e) {
            e.preventDefault();
            
            if (ogMediaUploader) {
                ogMediaUploader.open();
                return;
            }
            
            ogMediaUploader = wp.media({
                title: 'Select Social Media Image',
                button: {
                    text: 'Use this image'
                },
                multiple: false,
                library: {
                    type: 'image'
                }
            });
            
            ogMediaUploader.on('select', function() {
                var attachment = ogMediaUploader.state().get('selection').first().toJSON();
                $('#og_image_id').val(attachment.id);
                
                // Update preview
                var img = '<img src="' + attachment.url + '" style="max-width: 100%; height: auto;">';
                $('#og_image_preview').html(img);
                $('#og_image_button').text('Change Image');
                
                // Show remove button
                if ($('#remove_og_image').length === 0) {
                    $('#og_image_button').after('<button type="button" id="remove_og_image" class="button" style="width: 100%; margin-top: 5px;">Remove Image (Use Auto-Generated)</button>');
                }
            });
            
            ogMediaUploader.open();
        });
        
        $(document).on('click', '#remove_og_image', function() {
            $('#og_image_id').val('');
            $('#og_image_preview').html('<p style="padding: 20px; background: #f0f0f0; text-align: center; color: #666;">No custom image set.<br>An image with the page title will be<br>automatically generated.</p>');
            $('#og_image_button').text('Select Custom Image');
            $(this).remove();
        });
    });
    </script>
    
    <?php
}

// Save OG image meta box data
function save_og_image_meta_box_data($post_id) {
    // Check if nonce is set
    if (!isset($_POST['og_image_meta_box_nonce'])) {
        return;
    }
    
    // Verify nonce
    if (!wp_verify_nonce($_POST['og_image_meta_box_nonce'], 'og_image_meta_box')) {
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
    
    // Save OG image ID
    if (isset($_POST['og_image_id'])) {
        update_post_meta($post_id, '_og_image_id', absint($_POST['og_image_id']));
    }
}
add_action('save_post', 'save_og_image_meta_box_data');
