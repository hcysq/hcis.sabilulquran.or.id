<?php
/**
 * Template Name: Blank Page
 * Description: Template tanpa header dan footer untuk dashboard HRIS
 *
 * @package YSQ
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: system-ui, 'Segoe UI', Roboto, sans-serif;
            color: #0f172a;
            background: #f5f7fb;
        }
    </style>
    <?php wp_head(); ?>
</head>
<body <?php body_class('blank-template'); ?>>
<?php wp_body_open(); ?>

<?php
while (have_posts()) :
    the_post();
    the_content();
endwhile;
?>

<?php wp_footer(); ?>
</body>
</html>
