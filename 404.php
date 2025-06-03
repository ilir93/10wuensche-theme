<?php
/**
 * The template for displaying 404 pages (not found)
 */

get_header(); ?>

<main id="main" class="site-content site-container" role="main">
    <article class="error-404 not-found">
        <header class="page-header">
            <h1 class="page-title">404 - Seite nicht gefunden</h1>
        </header>

        <div class="page-content">
            <p>Es tut uns leid, aber die gesuchte Seite konnte nicht gefunden werden. Möglicherweise wurde sie verschoben oder gelöscht.</p>
            
            <h2>Was können Sie tun?</h2>
            <ul>
                <li>Überprüfen Sie die URL auf Tippfehler</li>
                <li>Nutzen Sie die Navigation oben, um zu einer anderen Seite zu gelangen</li>
                <li>Kehren Sie zur <a href="<?php echo esc_url(home_url('/')); ?>">Startseite</a> zurück</li>
            </ul>

            <h2>Beliebte Kategorien</h2>
            <div class="popular-links">
                <?php
                // Get some popular pages
                $popular_pages = get_pages(array(
                    'sort_order' => 'DESC',
                    'sort_column' => 'menu_order',
                    'number' => 6,
                    'post_status' => 'publish'
                ));
                
                if ($popular_pages) : ?>
                    <div class="category-links">
                        <?php foreach ($popular_pages as $page) : ?>
                            <a href="<?php echo get_permalink($page->ID); ?>" class="category-link-item">
                                <span class="category-emoji">🎁</span>
                                <span class="category-text"><?php echo esc_html($page->post_title); ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </article>
</main>

<?php get_footer(); ?>