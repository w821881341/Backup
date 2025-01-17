<?php
/**
 * Template Name: 文章存档
 *
 * @package    YEAHZAN
 * @subpackage ZanBlog
 * @since      ZanBlog 3.0.1
 */
?>

<?php get_header(); ?>
<section id="zan-bodyer">
	<div class="container">
    <section class="row">
      <section class="col-md-8" id="mainstay"> 
        <article class="zan-article">
          <!-- 面包屑 -->
          <div class="breadcrumb">
            <?php 
              if( function_exists( 'bcn_display' ) ) {
                echo '<i class="fa fa-map-marker"></i> ';
                bcn_display();
              }
            ?>
          </div>
          <!-- 面包屑结束 -->
          <?php while ( $wp_query->have_posts() ) : $wp_query->the_post(); ?>
            <?php zan_archives_list(); ?>
          <?php endwhile; ?>
        </article>
      </section>
      <?php get_sidebar(); ?>
    </section>
	</div>
</section>
<?php get_footer(); ?>
</body>
</html>