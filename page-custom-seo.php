<?php
/**
 * Template Name: Custom SEO Page
 * Description: A custom page template with SEO fields
 */

get_header(); ?>

<main id="main" class="site-content site-container" role="main">
    <?php
    while (have_posts()) : the_post();
        
        // Prepare breadcrumb data for schema
        $breadcrumb_data = array(
            array('name' => 'Home', 'url' => home_url('/'))
        );
        
        $ancestors = get_post_ancestors($post->ID);
        if ($ancestors) {
            $ancestors = array_reverse($ancestors);
            foreach ($ancestors as $ancestor) {
                $breadcrumb_data[] = array(
                    'name' => get_the_title($ancestor),
                    'url' => get_permalink($ancestor)
                );
            }
        }
        $breadcrumb_data[] = array(
            'name' => get_the_title(),
            'url' => get_permalink()
        );
        
        // Output schemas
        echo generate_breadcrumb_schema($breadcrumb_data);
        echo generate_article_schema(get_the_ID());
        
        // Get custom meta values
        $custom_h1_title = get_post_meta(get_the_ID(), '_custom_h1_title', true);
        $custom_meta_description = get_post_meta(get_the_ID(), '_custom_meta_description', true);
        $custom_subtitle_h2 = get_post_meta(get_the_ID(), '_custom_subtitle_h2', true);
        $custom_content_1 = get_post_meta(get_the_ID(), '_custom_content_1', true);
        $custom_image = get_post_meta(get_the_ID(), '_custom_image', true);
        $custom_image_alt = get_post_meta(get_the_ID(), '_custom_image_alt', true);
        $internal_links = get_post_meta(get_the_ID(), '_internal_links', true);
        $custom_subtitle_h3 = get_post_meta(get_the_ID(), '_custom_subtitle_h3', true);
        $custom_content_2 = get_post_meta(get_the_ID(), '_custom_content_2', true);
        $faq_title = get_post_meta(get_the_ID(), '_faq_title', true);
        $faq_items = get_post_meta(get_the_ID(), '_faq_items', true);
        $wishes = get_post_meta(get_the_ID(), '_custom_wishes', true);
        ?>
        
        <article id="page-<?php the_ID(); ?>" <?php post_class(); ?>>
            <!-- Breadcrumb -->
            <nav class="breadcrumb" aria-label="Breadcrumb">
                <ol class="breadcrumb-list">
                    <li class="breadcrumb-item">
                        <a href="<?php echo esc_url(home_url('/')); ?>">🏠</a>
                    </li>
                    <?php
                    // Get parent pages if any
                    $ancestors = get_post_ancestors($post->ID);
                    if ($ancestors) {
                        $ancestors = array_reverse($ancestors);
                        foreach ($ancestors as $ancestor) {
                            ?>
                            <li class="breadcrumb-item">
                                <a href="<?php echo get_permalink($ancestor); ?>"><?php echo get_the_title($ancestor); ?></a>
                            </li>
                            <?php
                        }
                    }
                    ?>
                    <li class="breadcrumb-item active" aria-current="page">
                        <?php the_title(); ?>
                    </li>
                </ol>
            </nav>
            
            <!-- Custom H1 Title -->
            <h1 class="page-title">
                <?php 
                // Use custom H1 if set, otherwise use page title
                if (!empty($custom_h1_title)) {
                    echo esc_html($custom_h1_title);
                } else {
                    the_title();
                }
                ?>
            </h1>
            
            <!-- Meta Description -->
            <?php if (!empty($custom_meta_description)) : ?>
                <div class="page-meta-description">
                    <?php echo esc_html($custom_meta_description); ?>
                </div>
            <?php endif; ?>
            
            <!-- Wishes Box -->
            <?php if (!empty($wishes) && is_array($wishes)) : ?>
                <div class="wishes-box">
                    <?php foreach ($wishes as $index => $wish) : ?>
                        <div class="wish-item" data-wish-index="<?php echo $index; ?>">
                            <div class="wish-header">
                                <span class="wish-number"><?php echo esc_html(get_theme_translation('wish_number')); ?><?php echo ($index + 1); ?></span>
                                <span class="wish-status"><span class="share-count"><?php echo esc_html($wish['share_count'] ?? 0); ?></span> <?php echo esc_html(get_theme_translation('times_shared')); ?></span>
                            </div>
                            <p class="wish-text"><?php echo esc_html($wish['text']); ?></p>
                            <div class="wish-share-buttons">
                                <button type="button" class="share-btn share-copy" data-text="<?php echo esc_attr($wish['text']); ?>"><?php echo esc_html(get_theme_translation('copy_with_icon')); ?></button>
                                <a href="https://wa.me/?text=<?php echo urlencode($wish['text']); ?>" target="_blank" class="share-btn share-whatsapp">WhatsApp</a>
                                <a href="https://t.me/share/url?text=<?php echo urlencode($wish['text']); ?>" target="_blank" class="share-btn share-telegram">Telegram</a>
                                <a href="fb-messenger://share?link=<?php echo urlencode(get_permalink()); ?>&app_id=123456789" class="share-btn share-messenger">Messenger</a>
                                <a href="sms:?&body=<?php echo urlencode($wish['text']); ?>" class="share-btn share-sms">SMS</a>
                                <a href="mailto:?subject=<?php echo urlencode(get_theme_translation('birthday_wish')); ?>&body=<?php echo urlencode($wish['text']); ?>" class="share-btn share-email"><?php echo esc_html(get_theme_translation('email')); ?></a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    // Handle copy buttons
                    document.querySelectorAll('.share-copy').forEach(function(btn) {
                        btn.addEventListener('click', function() {
                            var text = this.getAttribute('data-text');
                            var originalText = this.innerHTML;
                            var button = this;
                            
                            // Copy to clipboard
                            if (navigator.clipboard) {
                                navigator.clipboard.writeText(text).then(function() {
                                    // Change button text to copied
                                    button.innerHTML = '<?php echo esc_js(get_theme_translation('copied_with_check')); ?>';
                                    
                                    // Change back after 2 seconds
                                    setTimeout(function() {
                                        button.innerHTML = originalText;
                                    }, 2000);
                                });
                            } else {
                                // Fallback for older browsers
                                var temp = document.createElement('textarea');
                                temp.value = text;
                                document.body.appendChild(temp);
                                temp.select();
                                document.execCommand('copy');
                                document.body.removeChild(temp);
                                
                                // Change button text to copied
                                button.innerHTML = '<?php echo esc_js(get_theme_translation('copied_with_check')); ?>';
                                
                                // Change back after 2 seconds
                                setTimeout(function() {
                                    button.innerHTML = originalText;
                                }, 2000);
                            }
                            
                            updateShareCount(this);
                        });
                    });
                    
                    // Update share count for all share buttons
                    document.querySelectorAll('.share-btn').forEach(function(btn) {
                        btn.addEventListener('click', function() {
                            updateShareCount(this);
                        });
                    });
                    
                    function updateShareCount(button) {
                        var wishItem = button.closest('.wish-item');
                        var wishIndex = wishItem.getAttribute('data-wish-index');
                        var countElement = wishItem.querySelector('.share-count');
                        var currentCount = parseInt(countElement.textContent);
                        var newCount = currentCount + 1;
                        countElement.textContent = newCount;
                        
                        // Save to server via AJAX
                        var data = new FormData();
                        data.append('action', 'update_wish_share_count');
                        data.append('post_id', '<?php echo get_the_ID(); ?>');
                        data.append('wish_index', wishIndex);
                        data.append('share_count', newCount);
                        data.append('nonce', '<?php echo wp_create_nonce("update_wish_share"); ?>');
                        
                        fetch('<?php echo admin_url("admin-ajax.php"); ?>', {
                            method: 'POST',
                            body: data
                        });
                    }
                });
                </script>
            <?php endif; ?>
            
            <!-- Subtitle H2 -->
            <?php if (!empty($custom_subtitle_h2)) : ?>
                <h2 class="page-subtitle-h2"><?php echo esc_html($custom_subtitle_h2); ?></h2>
            <?php endif; ?>
            
            <!-- Content 1 -->
            <?php if (!empty($custom_content_1)) : ?>
                <div class="page-content-1">
                    <?php echo wp_kses_post($custom_content_1); ?>
                </div>
            <?php endif; ?>
            
            <!-- Image -->
            <?php if (!empty($custom_image)) : 
                $image_url = wp_get_attachment_image_src($custom_image, 'medium');
                if ($image_url) : ?>
                    <div class="page-image">
                        <img src="<?php echo esc_url($image_url[0]); ?>" 
                             alt="<?php echo esc_attr($custom_image_alt); ?>" 
                             width="300" 
                             height="250"
                             loading="lazy">
                    </div>
                <?php endif;
            endif; ?>
            
            <!-- Internal Links -->
            <?php if (!empty($internal_links) && is_array($internal_links)) : ?>
                <div class="internal-links-section">
                    <h3><?php echo esc_html(get_theme_translation('more_information')); ?></h3>
                    <div class="internal-links-grid">
                        <?php foreach ($internal_links as $link) : 
                            if (!empty($link['page_id'])) : ?>
                                <a href="<?php echo get_permalink($link['page_id']); ?>" class="internal-link-item">
                                    <span class="link-arrow">→</span>
                                    <span class="link-text"><?php echo esc_html($link['text']); ?></span>
                                </a>
                            <?php endif;
                        endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Subtitle H3 -->
            <?php if (!empty($custom_subtitle_h3)) : ?>
                <h3 class="page-subtitle-h3"><?php echo esc_html($custom_subtitle_h3); ?></h3>
            <?php endif; ?>
            
            <!-- Content 2 -->
            <?php if (!empty($custom_content_2)) : ?>
                <div class="page-content-2">
                    <?php echo wp_kses_post($custom_content_2); ?>
                </div>
            <?php endif; ?>
            
            <!-- FAQ Title -->
            <?php if (!empty($faq_title)) : ?>
                <h2 class="faq-title"><?php echo esc_html($faq_title); ?></h2>
            <?php endif; ?>
            
            <!-- FAQ Items with Schema Markup -->
            <?php if (!empty($faq_items) && is_array($faq_items)) : ?>
                <div class="faq-section" itemscope itemtype="https://schema.org/FAQPage">
                    <?php foreach ($faq_items as $faq) : ?>
                        <div class="faq-item" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
                            <h3 class="faq-question" itemprop="name"><?php echo esc_html($faq['question']); ?></h3>
                            <div class="faq-answer" itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                                <div itemprop="text">
                                    <?php echo wp_kses_post(wpautop($faq['answer'])); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <!-- Last Updated Date -->
                    <div class="faq-last-updated">
                        <?php echo esc_html(get_theme_translation('last_updated')); ?>: <?php echo date_i18n('j. F Y', get_post_modified_time('U', false, get_the_ID())); ?>
                    </div>
                </div>
                
                <!-- FAQ Schema JSON-LD -->
                <script type="application/ld+json">
                {
                    "@context": "https://schema.org",
                    "@type": "FAQPage",
                    "mainEntity": [
                        <?php 
                        $total_faqs = count($faq_items);
                        foreach ($faq_items as $index => $faq) : ?>
                        {
                            "@type": "Question",
                            "name": <?php echo json_encode($faq['question']); ?>,
                            "acceptedAnswer": {
                                "@type": "Answer",
                                "text": <?php echo json_encode($faq['answer']); ?>
                            }
                        }<?php echo ($index < $total_faqs - 1) ? ',' : ''; ?>
                        <?php endforeach; ?>
                    ]
                }
                </script>
            <?php endif; ?>
            
            <!-- Share this article -->
            <div class="share-article">
                <h3><?php echo esc_html(get_theme_translation('share_article')); ?></h3>
                <div class="share-buttons">
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(get_permalink()); ?>" 
                       target="_blank" 
                       rel="noopener noreferrer" 
                       class="share-button share-facebook">
                        Facebook
                    </a>
                    <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(get_permalink()); ?>&text=<?php echo urlencode(get_the_title()); ?>" 
                       target="_blank" 
                       rel="noopener noreferrer" 
                       class="share-button share-x">
                        X
                    </a>
                    <a href="mailto:?subject=<?php echo rawurlencode(get_the_title()); ?>&body=<?php echo rawurlencode(get_permalink()); ?>" 
                       class="share-button share-email">
                        <?php echo esc_html(get_theme_translation('email')); ?>
                    </a>
                </div>
            </div>
            
            <!-- Author Info Section -->
            <?php 
            $author_id = get_the_author_meta('ID');
            $author_first_name = get_the_author_meta('first_name', $author_id);
            $author_bio = get_the_author_meta('description', $author_id);
            
            // If first name is empty, fall back to display name
            if (empty($author_first_name)) {
                $author_first_name = get_the_author_meta('display_name', $author_id);
            }
            
            // Only show author section if we have a name and bio
            if (!empty($author_first_name) && !empty($author_bio)) : ?>
                <div class="author-info-section">
                    <h3><?php echo esc_html(get_theme_translation('about_author')); ?></h3>
                    <div class="author-info">
                        <div class="author-name"><?php echo esc_html($author_first_name); ?></div>
                        <div class="author-bio">
                            <?php 
                            // Limit bio to approximately 50 words
                            $bio_words = explode(' ', $author_bio);
                            $limited_bio = implode(' ', array_slice($bio_words, 0, 50));
                            
                            // Add ellipsis if bio was truncated
                            if (count($bio_words) > 50) {
                                $limited_bio .= '...';
                            }
                            
                            echo esc_html($limited_bio); 
                            ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="page-content">
                <?php 
                // Remove the_content() since we're not using the editor
                // Content will come from custom fields instead
                ?>
            </div>
        </article>
        <?php
    endwhile;
    ?>
</main>

<?php get_footer(); ?>