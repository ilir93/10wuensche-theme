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
                        <a href="<?php echo esc_url(home_url('/')); ?>">Home</a>
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
        </article>
        <?php
    endwhile;
    ?>
</main>

<?php get_footer(); ?>