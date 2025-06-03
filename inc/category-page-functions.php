<?php
/**
 * Category Page Template Functions
 */

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
    $image_type = get_post_meta($post->ID, '_category_image_type', true);
    $uploaded_image_id = get_post_meta($post->ID, '_category_uploaded_image', true);
    
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
    
    // Default image type to auto if not set
    if (empty($image_type)) {
        $image_type = 'auto';
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
                <input type="radio" name="category_image_type" value="auto" <?php echo ($image_type == 'auto') ? 'checked' : ''; ?>>
                <strong>Auto-generate from H1 title</strong>
            </label>
            <label style="display: block;">
                <input type="radio" name="category_image_type" value="upload" <?php echo ($image_type == 'upload') ? 'checked' : ''; ?>>
                <strong>Upload custom image</strong>
            </label>
        </div>
        
        <div style="display: flex; gap: 15px; align-items: flex-start;">
            <div style="flex: 1;">
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
    
    // Save Image Type
    if (isset($_POST['category_image_type'])) {
        update_post_meta($post_id, '_category_image_type', sanitize_text_field($_POST['category_image_type']));
    } else {
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
