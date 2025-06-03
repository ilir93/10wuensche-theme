<?php
/**
 * Template Name: Category Page
 * Description: A category page template with custom navigation links
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
        echo generate_webpage_schema(get_the_ID(), 'category');
        
        // Get custom meta values
        $custom_h1_title = get_post_meta(get_the_ID(), '_category_h1_title', true);
        $custom_meta_description = get_post_meta(get_the_ID(), '_category_meta_description', true);
        $category_groups = get_post_meta(get_the_ID(), '_category_groups', true);
        $custom_subtitle_h2 = get_post_meta(get_the_ID(), '_category_subtitle_h2', true);
        $custom_content = get_post_meta(get_the_ID(), '_category_content', true);
        $custom_image = get_post_meta(get_the_ID(), '_category_image', true);
        $custom_image_alt = get_post_meta(get_the_ID(), '_category_image_alt', true);
        $faq_title = get_post_meta(get_the_ID(), '_category_faq_title', true);
        $faq_items = get_post_meta(get_the_ID(), '_category_faq_items', true);
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
            
            <!-- Category Groups -->
            <?php if (!empty($category_groups) && is_array($category_groups)) : ?>
                <div class="category-navigation">
                    <?php foreach ($category_groups as $group) : 
                        if (!empty($group['title']) && !empty($group['links']) && is_array($group['links'])) : ?>
                            <div class="category-group">
                                <h2 class="category-group-title"><?php echo esc_html($group['title']); ?></h2>
                                <div class="category-links">
                                    <?php foreach ($group['links'] as $link) : 
                                        if (!empty($link['page_id']) || !empty($link['custom_url'])) : 
                                            $url = !empty($link['custom_url']) ? $link['custom_url'] : get_permalink($link['page_id']);
                                            ?>
                                            <a href="<?php echo esc_url($url); ?>" class="category-link-item">
                                                <span class="category-emoji"><?php echo esc_html($link['emoji']); ?></span>
                                                <span class="category-text"><?php echo esc_html($link['text']); ?></span>
                                            </a>
                                        <?php endif;
                                    endforeach; ?>
                                </div>
                            </div>
                        <?php endif;
                    endforeach; ?>
                </div>
            <?php endif; ?>
            
            <!-- Subtitle H2 -->
            <?php if (!empty($custom_subtitle_h2)) : ?>
                <h2 class="page-subtitle-h2"><?php echo esc_html($custom_subtitle_h2); ?></h2>
            <?php endif; ?>
            
            <!-- Content -->
            <?php if (!empty($custom_content)) : ?>
                <div class="page-content-1">
                    <?php echo wp_kses_post($custom_content); ?>
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
        </article>
        <?php
    endwhile;
    ?>
</main>

<?php get_footer(); ?>