<?php
/**
 * Template Name: Homepage
 * Description: Homepage template with category cards and features
 */

get_header(); ?>

<main id="main" class="site-content site-container" role="main">
    <?php
    while (have_posts()) : the_post();
        
        // Get custom meta values
        $hero_title = get_post_meta(get_the_ID(), '_homepage_hero_title', true);
        $hero_subtitle = get_post_meta(get_the_ID(), '_homepage_hero_subtitle', true);
        $category_cards = get_post_meta(get_the_ID(), '_homepage_category_cards', true);
        $features_title = get_post_meta(get_the_ID(), '_homepage_features_title', true);
        $features = get_post_meta(get_the_ID(), '_homepage_features', true);
        ?>
        
        <article id="page-<?php the_ID(); ?>" <?php post_class(); ?>>
            <!-- Hero Section -->
            <div class="homepage-hero">
                <?php if (!empty($hero_title)) : ?>
                    <h1 class="hero-title"><?php echo esc_html($hero_title); ?></h1>
                <?php endif; ?>
                
                <?php if (!empty($hero_subtitle)) : ?>
                    <p class="hero-subtitle"><?php echo esc_html($hero_subtitle); ?></p>
                <?php endif; ?>
            </div>
            
            <!-- Category Cards -->
            <?php if (!empty($category_cards) && is_array($category_cards)) : ?>
                <div class="category-cards-grid">
                    <?php foreach ($category_cards as $card) : 
                        if (!empty($card['title'])) : 
                            $url = !empty($card['custom_url']) ? $card['custom_url'] : (!empty($card['page_id']) ? get_permalink($card['page_id']) : '#');
                            ?>
                            <a href="<?php echo esc_url($url); ?>" class="category-card">
                                <div class="category-card-content">
                                    <span class="category-card-emoji"><?php echo esc_html($card['emoji']); ?></span>
                                    <h2 class="category-card-title"><?php echo esc_html($card['title']); ?></h2>
                                    <span class="category-card-count"><?php echo esc_html($card['count']); ?></span>
                                </div>
                            </a>
                        <?php endif;
                    endforeach; ?>
                </div>
            <?php endif; ?>
            
            <!-- Features Section -->
            <?php if (!empty($features_title) || !empty($features)) : ?>
                <div class="homepage-features">
                    <?php if (!empty($features_title)) : ?>
                        <h2 class="features-title"><?php echo esc_html($features_title); ?></h2>
                    <?php endif; ?>
                    
                    <?php if (!empty($features) && is_array($features)) : ?>
                        <div class="features-list">
                            <?php foreach ($features as $feature) : 
                                if (!empty($feature['text'])) : ?>
                                    <div class="feature-item">
                                        <span class="feature-checkmark">✓</span>
                                        <span class="feature-text"><?php echo esc_html($feature['text']); ?></span>
                                    </div>
                                <?php endif;
                            endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </article>
        <?php
    endwhile;
    ?>
</main>

<?php get_footer(); ?>