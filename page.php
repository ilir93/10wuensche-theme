<?php
/**
 * Page Template
 */
get_header(); ?>

<main id="main" class="site-content site-container" role="main">
    <?php
    while (have_posts()) : the_post();
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
            
            <h1 class="page-title"><?php the_title(); ?></h1>
            <div class="page-content">
                <?php the_content(); ?>
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
                    <h3>Über den Autor</h3>
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
        </article>
        <?php
    endwhile;
    ?>
</main>

<?php get_footer(); ?>