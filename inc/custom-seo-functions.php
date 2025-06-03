<?php
/**
 * Custom SEO Page Template Functions
 */

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
    
    // Ensure arrays
    if (!is_array($wishes)) {
        $wishes = array();
    }
    if (!is_array($internal_links)) {
        $internal_links = array();
    }
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
                                <label style="font-size: 12px;">Share count:</label>
                                <input type="number" 
                                       name="custom_wishes[<?php echo $index; ?>][share_count]" 
                                       value="<?php echo esc_attr($wish['share_count'] ?? 0); ?>"
                                       style="width: 80px; padding: 4px;">
                                <span style="font-size: 12px; color: #666;">mal geteilt</span>
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
            4. H2 Subtitle
        </label>
        <input type="text" 
               id="custom_subtitle_h2" 
               name="custom_subtitle_h2" 
               value="<?php echo esc_attr($custom_subtitle_h2); ?>" 
               style="width: 100%; padding: 8px; font-size: 14px;"
               placeholder="Enter H2 subtitle">
    </div>
    
    <div style="margin-bottom: 20px;">
        <label for="custom_content_1" style="display: block; font-weight: bold; margin-bottom: 5px;">
            5. Content Block 1
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
    </div>
    
    <div style="margin-bottom: 20px;">
        <label style="display: block; font-weight: bold; margin-bottom: 5px;">
            6. Image 300x250
        </label>
        <div style="display: flex; gap: 15px; align-items: flex-start;">
            <div style="flex: 1;">
                <div id="custom_image_preview" style="margin-bottom: 10px;">
                    <?php if ($custom_image) : 
                        echo wp_get_attachment_image($custom_image, array(300, 250));
                    else : ?>
                        <div style="width: 300px; height: 250px; border: 2px dashed #ddd; display: flex; align-items: center; justify-content: center; color: #999;">
                            No image selected
                        </div>
                    <?php endif; ?>
                </div>
                <button type="button" id="custom_image_button" class="button button-primary">
                    Select Image
                </button>
                <button type="button" id="remove_custom_image" class="button" style="<?php echo $custom_image ? '' : 'display: none;'; ?>">
                    Remove Image
                </button>
                <input type="hidden" id="custom_image" name="custom_image" value="<?php echo esc_attr($custom_image); ?>">
            </div>
            <div style="flex: 1;">
                <label for="custom_image_alt" style="display: block; margin-bottom: 5px;">Alt Text:</label>
                <input type="text" 
                       id="custom_image_alt" 
                       name="custom_image_alt" 
                       value="<?php echo esc_attr($custom_image_alt); ?>" 
                       style="width: 100%; padding: 8px; font-size: 14px;"
                       placeholder="Enter image alt text for SEO">
            </div>
        </div>
    </div>
    
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
                        <div style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 10px; align-items: center;">
                            <input type="text" 
                                   name="internal_links[<?php echo $index; ?>][text]" 
                                   value="<?php echo esc_attr($link['text'] ?? ''); ?>" 
                                   style="padding: 8px; font-size: 14px;"
                                   placeholder="Link text">
                            <select name="internal_links[<?php echo $index; ?>][page_id]" style="padding: 8px;">
                                <option value="">-- Select Page --</option>
                                <?php
                                $pages = get_pages();
                                foreach ($pages as $page) {
                                    $selected = (isset($link['page_id']) && $link['page_id'] == $page->ID) ? 'selected' : '';
                                    echo '<option value="' . $page->ID . '" ' . $selected . '>' . esc_html($page->post_title) . '</option>';
                                }
                                ?>
                            </select>
                            <button type="button" class="remove-internal-link" style="padding: 5px 10px; background: #d63638; color: white; border: none; border-radius: 3px; cursor: pointer;">
                                Remove
                            </button>
                        </div>
                    </div>
                    <?php
                }
            }
            ?>
        </div>
        <button type="button" id="add_internal_link" style="margin-top: 10px; padding: 8px 15px; background: #00a32a; color: white; border: none; border-radius: 3px; cursor: pointer;">
            + Add Internal Link
        </button>
    </div>
    
    <div style="margin-bottom: 20px;">
        <label for="custom_subtitle_h3" style="display: block; font-weight: bold; margin-bottom: 5px;">
            8. H3 Subtitle
        </label>
        <input type="text" 
               id="custom_subtitle_h3" 
               name="custom_subtitle_h3" 
               value="<?php echo esc_attr($custom_subtitle_h3); ?>" 
               style="width: 100%; padding: 8px; font-size: 14px;"
               placeholder="Enter H3 subtitle">
    </div>
    
    <div style="margin-bottom: 20px;">
        <label for="custom_content_2" style="display: block; font-weight: bold; margin-bottom: 5px;">
            9. Content Block 2
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
    </div>
    
    <div style="margin-bottom: 20px;">
        <label style="display: block; font-weight: bold; margin-bottom: 5px;">
            11. FAQ Questions and Answers
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
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // Character count for meta description
        var metaDescField = $('#custom_meta_description');
        var metaDescCount = $('#meta_description_count');
        
        function updateCharCount() {
            var count = metaDescField.val().length;
            metaDescCount.text(count);
            
            if (count > 120) {
                metaDescCount.css('color', '#d63638');
            } else if (count > 100) {
                metaDescCount.css('color', '#dba617');
            } else {
                metaDescCount.css('color', '#00a32a');
            }
        }
        
        updateCharCount();
        metaDescField.on('input', updateCharCount);
        
        // Wishes functionality
        var wishesContainer = $('#wishes_container');
        var wishIndex = wishesContainer.find('.wish-backend-item').length;
        
        // Make wishes sortable
        wishesContainer.sortable({
            handle: 'span:first-child',
            update: function(event, ui) {
                updateWishNumbers();
            }
        });
        
        function updateWishNumbers() {
            wishesContainer.find('.wish-backend-item').each(function(index) {
                $(this).find('.wish-number-display').text(index + 1);
            });
        }
        
        $('#add_wish').on('click', function() {
            var wishHtml = '<div class="wish-backend-item" data-index="' + wishIndex + '" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 10px; border-radius: 5px; cursor: move; background: white;">' +
                '<div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">' +
                '<span style="cursor: move; color: #999;">☰</span>' +
                '<span style="font-weight: bold; color: #666;">Wunsch #<span class="wish-number-display">' + (wishIndex + 1) + '</span></span>' +
                '</div>' +
                '<div style="margin-bottom: 10px;">' +
                '<input type="text" name="custom_wishes[' + wishIndex + '][text]" style="width: 100%; padding: 8px; font-size: 14px;" placeholder="Enter wish text">' +
                '</div>' +
                '<div style="display: flex; justify-content: space-between; align-items: center;">' +
                '<div style="display: flex; align-items: center; gap: 10px;">' +
                '<label style="font-size: 12px;">Share count:</label>' +
                '<input type="number" name="custom_wishes[' + wishIndex + '][share_count]" value="0" style="width: 80px; padding: 4px;">' +
                '<span style="font-size: 12px; color: #666;">mal geteilt</span>' +
                '</div>' +
                '<button type="button" class="remove-wish" style="padding: 5px 10px; background: #d63638; color: white; border: none; border-radius: 3px; cursor: pointer;">Remove</button>' +
                '</div>' +
                '</div>';
            
            wishesContainer.append(wishHtml);
            wishIndex++;
        });
        
        $(document).on('click', '.remove-wish', function() {
            $(this).closest('.wish-backend-item').remove();
            updateWishNumbers();
        });
        
        // Media uploader
        var mediaUploader;
        
        $('#custom_image_button').on('click', function(e) {
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
                $('#custom_image').val(attachment.id);
                
                // Update preview
                var img = '<img src="' + attachment.url + '" style="max-width: 300px; max-height: 250px;">';
                $('#custom_image_preview').html(img);
                $('#remove_custom_image').show();
            });
            
            mediaUploader.open();
        });
        
        $('#remove_custom_image').on('click', function() {
            $('#custom_image').val('');
            $('#custom_image_preview').html('<div style="width: 300px; height: 250px; border: 2px dashed #ddd; display: flex; align-items: center; justify-content: center; color: #999;">No image selected</div>');
            $(this).hide();
        });
        
        // Internal links functionality
        var linksContainer = $('#internal_links_container');
        var linkIndex = linksContainer.find('.internal-link-item').length;
        
        $('#add_internal_link').on('click', function() {
            var linkHtml = '<div class="internal-link-item" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 10px; border-radius: 5px;">' +
                '<div style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 10px; align-items: center;">' +
                '<input type="text" name="internal_links[' + linkIndex + '][text]" style="padding: 8px; font-size: 14px;" placeholder="Link text">' +
                '<select name="internal_links[' + linkIndex + '][page_id]" style="padding: 8px;">' +
                '<option value="">-- Select Page --</option>' +
                <?php
                $pages = get_pages();
                foreach ($pages as $page) {
                    echo "'<option value=\"" . $page->ID . "\">" . esc_js($page->post_title) . "</option>' +";
                }
                ?>
                '</select>' +
                '<button type="button" class="remove-internal-link" style="padding: 5px 10px; background: #d63638; color: white; border: none; border-radius: 3px; cursor: pointer;">Remove</button>' +
                '</div>' +
                '</div>';
            
            linksContainer.append(linkHtml);
            linkIndex++;
        });
        
        $(document).on('click', '.remove-internal-link', function() {
            $(this).closest('.internal-link-item').remove();
        });
        
        // FAQ functionality
        var faqContainer = $('#faq_container');
        var faqIndex = faqContainer.find('.faq-item').length;
        
        $('#add_faq').on('click', function() {
            var faqHtml = '<div class="faq-item" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 10px; border-radius: 5px;">' +
                '<div style="margin-bottom: 10px;">' +
                '<label style="display: block; margin-bottom: 5px;">Question:</label>' +
                '<input type="text" name="faq_items[' + faqIndex + '][question]" style="width: 100%; padding: 8px; font-size: 14px;" placeholder="Enter FAQ question">' +
                '</div>' +
                '<div style="margin-bottom: 10px;">' +
                '<label style="display: block; margin-bottom: 5px;">Answer:</label>' +
                '<textarea name="faq_items[' + faqIndex + '][answer]" style="width: 100%; padding: 8px; font-size: 14px; min-height: 80px;" placeholder="Enter FAQ answer"></textarea>' +
                '</div>' +
                '<button type="button" class="remove-faq" style="padding: 5px 10px; background: #d63638; color: white; border: none; border-radius: 3px; cursor: pointer;">Remove FAQ</button>' +
                '</div>';
            
            faqContainer.append(faqHtml);
            faqIndex++;
        });
        
        $(document).on('click', '.remove-faq', function() {
            $(this).closest('.faq-item').remove();
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
        $meta_desc = sanitize_text_field($_POST['custom_meta_description']);
        $meta_desc = substr($meta_desc, 0, 120); // Enforce 120 character limit
        update_post_meta($post_id, '_custom_meta_description', $meta_desc);
    }
    
    // Save Wishes
    if (isset($_POST['custom_wishes'])) {
        $wishes = array();
        foreach ($_POST['custom_wishes'] as $wish) {
            if (!empty($wish['text'])) {
                $wishes[] = array(
                    'text' => sanitize_text_field($wish['text']),
                    'share_count' => absint($wish['share_count'] ?? 0)
                );
            }
        }
        update_post_meta($post_id, '_custom_wishes', $wishes);
    } else {
        update_post_meta($post_id, '_custom_wishes', array());
    }
    
    // Save H2
    if (isset($_POST['custom_subtitle_h2'])) {
        update_post_meta($post_id, '_custom_subtitle_h2', sanitize_text_field($_POST['custom_subtitle_h2']));
    }
    
    // Save Content 1
    if (isset($_POST['custom_content_1'])) {
        update_post_meta($post_id, '_custom_content_1', wp_kses_post($_POST['custom_content_1']));
    }
    
    // Save Image
    if (isset($_POST['custom_image'])) {
        update_post_meta($post_id, '_custom_image', absint($_POST['custom_image']));
    }
    
    // Save Image Alt Text
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
    
    // Save H3
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

// Add JavaScript to show meta box only when custom SEO template is selected
function custom_seo_admin_script() {
    global $post;
    if ($post && $post->post_type == 'page') {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            function toggleSEOMetaBox() {
                var template = $('#page_template').val();
                if (template == 'page-custom-seo.php') {
                    $('#custom_seo_fields').show();
                } else {
                    $('#custom_seo_fields').hide();
                }
            }
            
            toggleSEOMetaBox();
            $('#page_template').on('change', toggleSEOMetaBox);
            
            // For Block Editor
            if (wp && wp.data && wp.data.select('core/editor')) {
                const { select, subscribe } = wp.data;
                
                subscribe(() => {
                    const template = select('core/editor').getEditedPostAttribute('template');
                    
                    if (template === 'page-custom-seo.php') {
                        $('#custom_seo_fields').show();
                    } else {
                        $('#custom_seo_fields').hide();
                    }
                });
            }
        });
        </script>
        <?php
    }
}
add_action('admin_footer', 'custom_seo_admin_script');

// ============================================
// YOAST SEO INTEGRATION
// ============================================

/**
 * Filter Yoast SEO content analysis to include our custom fields
 */
function custom_seo_filter_yoast_content($content, $post) {
    if (!$post || $post->post_type !== 'page') {
        return $content;
    }
    
    $template = get_post_meta($post->ID, '_wp_page_template', true);
    if ($template !== 'page-custom-seo.php') {
        return $content;
    }
    
    // Get all our custom fields
    $custom_h1_title = get_post_meta($post->ID, '_custom_h1_title', true);
    $custom_meta_description = get_post_meta($post->ID, '_custom_meta_description', true);
    $custom_subtitle_h2 = get_post_meta($post->ID, '_custom_subtitle_h2', true);
    $custom_content_1 = get_post_meta($post->ID, '_custom_content_1', true);
    $custom_subtitle_h3 = get_post_meta($post->ID, '_custom_subtitle_h3', true);
    $custom_content_2 = get_post_meta($post->ID, '_custom_content_2', true);
    $faq_title = get_post_meta($post->ID, '_faq_title', true);
    $faq_items = get_post_meta($post->ID, '_faq_items', true);
    $wishes = get_post_meta($post->ID, '_custom_wishes', true);
    $internal_links = get_post_meta($post->ID, '_internal_links', true);
    
    // Build content for Yoast analysis
    $yoast_content = '';
    
    // Add H1 (introduction)
    if (!empty($custom_h1_title)) {
        $yoast_content .= '<h1>' . $custom_h1_title . '</h1>\n\n';
    }
    
    // Add meta description as intro paragraph
    if (!empty($custom_meta_description)) {
        $yoast_content .= '<p>' . $custom_meta_description . '</p>\n\n';
    }
    
    // Add wishes text
    if (!empty($wishes) && is_array($wishes)) {
        foreach ($wishes as $wish) {
            if (!empty($wish['text'])) {
                $yoast_content .= '<p>' . $wish['text'] . '</p>\n';
            }
        }
        $yoast_content .= '\n';
    }
    
    // Add H2 and content 1
    if (!empty($custom_subtitle_h2)) {
        $yoast_content .= '<h2>' . $custom_subtitle_h2 . '</h2>\n\n';
    }
    if (!empty($custom_content_1)) {
        $yoast_content .= $custom_content_1 . '\n\n';
    }
    
    // Add internal links
    if (!empty($internal_links) && is_array($internal_links)) {
        $yoast_content .= '<h3>Weitere Informationen</h3>\n';
        foreach ($internal_links as $link) {
            if (!empty($link['page_id']) && !empty($link['text'])) {
                $url = get_permalink($link['page_id']);
                $yoast_content .= '<a href="' . esc_url($url) . '">' . esc_html($link['text']) . '</a>\n';
            }
        }
        $yoast_content .= '\n';
    }
    
    // Add H3 and content 2
    if (!empty($custom_subtitle_h3)) {
        $yoast_content .= '<h3>' . $custom_subtitle_h3 . '</h3>\n\n';
    }
    if (!empty($custom_content_2)) {
        $yoast_content .= $custom_content_2 . '\n\n';
    }
    
    // Add FAQ content
    if (!empty($faq_title)) {
        $yoast_content .= '<h2>' . $faq_title . '</h2>\n\n';
    }
    if (!empty($faq_items) && is_array($faq_items)) {
        foreach ($faq_items as $faq) {
            if (!empty($faq['question'])) {
                $yoast_content .= '<h3>' . $faq['question'] . '</h3>\n';
            }
            if (!empty($faq['answer'])) {
                $yoast_content .= '<p>' . $faq['answer'] . '</p>\n\n';
            }
        }
    }
    
    return $yoast_content;
}
add_filter('wpseo_pre_analysis_post_content', 'custom_seo_filter_yoast_content', 10, 2);

/**
 * Filter Yoast SEO meta description
 */
function custom_seo_filter_yoast_metadesc($meta_desc, $presentation) {
    if (!is_singular('page')) {
        return $meta_desc;
    }
    
    $post_id = get_queried_object_id();
    $template = get_post_meta($post_id, '_wp_page_template', true);
    
    if ($template === 'page-custom-seo.php') {
        $custom_meta_desc = get_post_meta($post_id, '_custom_meta_description', true);
        if (!empty($custom_meta_desc)) {
            return $custom_meta_desc;
        }
    }
    
    return $meta_desc;
}
add_filter('wpseo_metadesc', 'custom_seo_filter_yoast_metadesc', 10, 2);
add_filter('wpseo_opengraph_desc', 'custom_seo_filter_yoast_metadesc', 10, 2);

/**
 * Add custom image for Yoast SEO analysis
 */
function custom_seo_add_yoast_images($images, $post_id) {
    $template = get_post_meta($post_id, '_wp_page_template', true);
    
    if ($template === 'page-custom-seo.php') {
        $custom_image_id = get_post_meta($post_id, '_custom_image', true);
        if ($custom_image_id) {
            $image_url = wp_get_attachment_url($custom_image_id);
            $image_alt = get_post_meta($post_id, '_custom_image_alt', true);
            
            if ($image_url) {
                // Add image with alt text for Yoast to recognize
                $images[] = array(
                    'url' => $image_url,
                    'alt' => $image_alt
                );
            }
        }
    }
    
    return $images;
}
add_filter('wpseo_sitemap_urlimages', 'custom_seo_add_yoast_images', 10, 2);

/**
 * Make Yoast SEO recognize our custom fields in JavaScript
 */
function custom_seo_yoast_js_analysis() {
    if (!is_admin()) {
        return;
    }
    
    global $post;
    if (!$post || $post->post_type !== 'page') {
        return;
    }
    
    $template = get_post_meta($post->ID, '_wp_page_template', true);
    if ($template !== 'page-custom-seo.php') {
        return;
    }
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Wait for YoastSEO to be available
        if (typeof YoastSEO !== 'undefined' && typeof YoastSEO.app !== 'undefined') {
            
            // Custom content modification for Yoast analysis
            YoastSEO.app.registerPlugin('customSEOPlugin', {status: 'ready'});
            
            YoastSEO.app.registerModification('content', function(content) {
                var customContent = '';
                
                // Get H1 title
                var h1Title = $('#custom_h1_title').val();
                if (h1Title) {
                    customContent += '<h1>' + h1Title + '</h1>\n\n';
                }
                
                // Get meta description
                var metaDesc = $('#custom_meta_description').val();
                if (metaDesc) {
                    customContent += '<p>' + metaDesc + '</p>\n\n';
                }
                
                // Get wishes
                $('.wish-backend-item').each(function() {
                    var wishText = $(this).find('input[name*="[text]"]').val();
                    if (wishText) {
                        customContent += '<p>' + wishText + '</p>\n';
                    }
                });
                
                // Get H2
                var h2 = $('#custom_subtitle_h2').val();
                if (h2) {
                    customContent += '\n<h2>' + h2 + '</h2>\n\n';
                }
                
                // Get content 1 from TinyMCE
                if (typeof tinyMCE !== 'undefined' && tinyMCE.get('custom_content_1')) {
                    var content1 = tinyMCE.get('custom_content_1').getContent();
                    if (content1) {
                        customContent += content1 + '\n\n';
                    }
                }
                
                // Get internal links
                var hasInternalLinks = false;
                $('.internal-link-item').each(function() {
                    var linkText = $(this).find('input[name*="[text]"]').val();
                    var pageId = $(this).find('select[name*="[page_id]"]').val();
                    if (linkText && pageId) {
                        if (!hasInternalLinks) {
                            customContent += '<h3>Weitere Informationen</h3>\n';
                            hasInternalLinks = true;
                        }
                        customContent += '<a href="#">' + linkText + '</a>\n';
                    }
                });
                
                // Get H3
                var h3 = $('#custom_subtitle_h3').val();
                if (h3) {
                    customContent += '\n<h3>' + h3 + '</h3>\n\n';
                }
                
                // Get content 2 from TinyMCE
                if (typeof tinyMCE !== 'undefined' && tinyMCE.get('custom_content_2')) {
                    var content2 = tinyMCE.get('custom_content_2').getContent();
                    if (content2) {
                        customContent += content2 + '\n\n';
                    }
                }
                
                // Get FAQ title
                var faqTitle = $('#faq_title').val();
                if (faqTitle) {
                    customContent += '<h2>' + faqTitle + '</h2>\n\n';
                }
                
                // Get FAQ items
                $('.faq-item').each(function() {
                    var question = $(this).find('input[name*="[question]"]').val();
                    var answer = $(this).find('textarea[name*="[answer]"]').val();
                    if (question) {
                        customContent += '<h3>' + question + '</h3>\n';
                    }
                    if (answer) {
                        customContent += '<p>' + answer + '</p>\n\n';
                    }
                });
                
                // Check for image
                var imageId = $('#custom_image').val();
                var imageAlt = $('#custom_image_alt').val();
                if (imageId && imageAlt) {
                    customContent += '<img alt="' + imageAlt + '" />\n';
                }
                
                return customContent;
            }, 'customSEOPlugin', 5);
            
            // Trigger reanalysis when fields change
            $('#custom_h1_title, #custom_meta_description, #custom_subtitle_h2, #custom_subtitle_h3, #faq_title, #custom_image_alt').on('input change', function() {
                YoastSEO.app.refresh();
            });
            
            // Trigger reanalysis when wishes change
            $(document).on('input change', '.wish-backend-item input', function() {
                YoastSEO.app.refresh();
            });
            
            // Trigger reanalysis when internal links change
            $(document).on('input change', '.internal-link-item input, .internal-link-item select', function() {
                YoastSEO.app.refresh();
            });
            
            // Trigger reanalysis when FAQ changes
            $(document).on('input change', '.faq-item input, .faq-item textarea', function() {
                YoastSEO.app.refresh();
            });
            
            // Trigger reanalysis when TinyMCE content changes
            if (typeof tinyMCE !== 'undefined') {
                tinyMCE.on('AddEditor', function(e) {
                    e.editor.on('change keyup', function() {
                        YoastSEO.app.refresh();
                    });
                });
            }
        }
    });
    </script>
    <?php
}
add_action('admin_footer', 'custom_seo_yoast_js_analysis');
