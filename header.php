<!DOCTYPE html>
<html <?php language_attributes(); ?> lang="de-CH">
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="site-header">
    <div class="header-container site-container">
        <div class="site-branding">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="site-logo" id="home-link">
                <?php echo esc_html(get_theme_emoji()); ?> <?php echo esc_html(get_theme_site_name()); ?>
            </a>
        </div>
        <nav class="main-navigation" role="navigation">
            <button class="nav-arrow nav-arrow-left" type="button" aria-label="<?php echo esc_attr(get_theme_translation('scroll_left')); ?>">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                    <path d="M11 2L5 8l6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                </svg>
            </button>
            <div class="nav-scroll-container">
                <?php
                wp_nav_menu(array(
                    'theme_location' => 'primary',
                    'menu_class'     => 'menu-items',
                    'container'      => false,
                    'fallback_cb'    => false,
                    'depth'          => 1,
                ));
                ?>
            </div>
            <button class="nav-arrow nav-arrow-right" type="button" aria-label="<?php echo esc_attr(get_theme_translation('scroll_right')); ?>">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                    <path d="M5 2l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                </svg>
            </button>
        </nav>
    </div>
</header>