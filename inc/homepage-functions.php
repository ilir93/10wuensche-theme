<?php
/**
 * Homepage Template Functions
 */

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
