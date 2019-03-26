<?php
add_action( 'wp_enqueue_scripts', 'my_theme_enqueue_styles' );
function my_theme_enqueue_styles() {
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
}


add_action('init', 'register_services');
function register_services() {
    register_post_type('service', array(
        'labels' => array(
            'name'                => 'Service',
            'add_new'             => 'Add',
            'add_new_item'        => 'Add',
            'edit_item'           => 'Edit',
            'new_item'            => 'New',
            'view_item'           => 'View',
            'search_items'        => 'Search',
            'not_found'           => 'Not found',
            'not_found_in_trash'  => 'Not found',
            'menu_name'           => 'Service'
        ),
        'supports' => array('title', 'editor', 'thumbnail', 'excerpt'),
        'public' => true,
        'show_in_menu' => true,
        'menu_position' => 2,
        'show_in_nav_menus' => true,
        'has_archive' => true,
        'rewrite' => true,
        'taxonomies' => array('service', 'service_category')
    ));
    register_taxonomy('service_category', array('service'), array(
        'labels' => array(
            'name' => 'Category Service',
            'singular_name' => 'Category Service',
            'menu_name' => 'Category Service'
        ),
        'public' => true,
        'show_in_nav_menus' => true,
        'show_ui' => true,
        'show_tagcloud' => false,
        'hierarchical' => true,
        //'rewrite' =>  array( 'slug' => '/', 'with_front' => false),
        'rewrite' =>  array( 'slug' => 'service'),
        'query_var' => true
    ));
}



add_shortcode( 'select_services', 'services_func' );
function services_func( $atts ){
    // белый список параметров и значения по умолчанию
    $atts = shortcode_atts( array(

    ), $atts );

    $args = array( 'taxonomy'     => 'service_category', // название таксономии
        'orderby'      => 'name',  // сортируем по названиям
        'show_count'   => 0,       // не показываем количество записей
        'pad_counts'   => 0,

        'hierarchical' => 1,       // древовидное представление
        'hide_if_empty' => true,
        'value_field'  =>'slug' );

    $categories = get_categories($args);
    //print_r($categories);
    $output;
    $current_cat = $categories[0]->term_id;

    $output.= '<select name="category-services" id="category-services">';

    foreach ($categories as $value_cat) {
        $output.= '<option value="' . $value_cat->term_id . '">';
        $output.= $value_cat->name;
        $output.= '</option>';
    }
    $output.= '</select>';

    $args = array(
        'posts_per_page' => -1,
        'post_type' => 'service',
        'orderby' => 'date',

        'tax_query' =>  array(
            array(
                'field' =>'id',
                'taxonomy' => 'service_category',
                'terms' => $current_cat
            )
        )

    );
    $wp_query = new WP_Query($args);

    $output_single;

    $output_single.= '<select name="single-services" id="single-services">';

    while ( $wp_query->have_posts() ) {
        $wp_query->the_post();
        $output_single.= '<option value="' . get_the_ID() . '" data-link="'.get_the_permalink().'">';
        $output_single.= get_the_title();
        $output_single.= '</option>';
    }
    $output_single.= '</select>';
    wp_reset_postdata();

    return  '<div class="form-cover"><div class="container"><form id="select-services">'. '<div class="form-group">' .$output. '</div>' . '<div class="form-group">'. $output_single . '</div>'. '<div class="form-group"><input type="submit" value="לעבור"></div></form></div></div>';
}


add_action('wp_ajax_ajax_get_services', 'ajax_get_services_callback');
add_action('wp_ajax_nopriv_ajax_get_services', 'ajax_get_services_callback');
function ajax_get_services_callback() {

    $taxonomy_id = $_POST['category-services'];






    $args = array(
        'posts_per_page' => -1,
        'post_type' => 'service',
        'orderby' => 'date',

        'tax_query' =>  array(
            array(
                'field' =>'id',
                'taxonomy' => 'service_category',
                'terms' => $taxonomy_id
            )
        )

    );
    $wp_query = new WP_Query($args);



    while ( $wp_query->have_posts() ) {

        $wp_query->the_post();


        $response['posts'][] = array('id' => get_the_ID(), 'name' => get_the_title(), 'link'=> get_the_permalink());

    }

    wp_reset_postdata();

    wp_send_json($response);

    // выход нужен для того, чтобы в ответе не было ничего лишнего, только то что возвращает функция
    wp_die();
}



add_shortcode( 'services_content', 'services_content_func' );
function services_content_func( $atts ){
    // белый список параметров и значения по умолчанию
    $atts = shortcode_atts( array(
        'content' => 1

    ), $atts );


    $mass_text = array(
        1 =>  get_option('service_global_text_1'),
        2 =>  get_option('service_global_text_2'),
        3 =>  get_option('service_global_text_3'),
        4 =>  get_option('service_global_text_4'),
    );

    return do_shortcode($mass_text[$atts['content']]);




}

add_shortcode( 'service_title', 'services_title_func' );
function services_title_func( $atts ){
    $atts = shortcode_atts( array(


    ), $atts );

    return get_the_title();


}


include 'service-setting-page.php';

?>
<?php

function slider_option(){ ?>

    <script> (function($){

            $(document).ready(function() {
                var _form = $('#select-services');
                _form.submit(function(event) {
                    event.preventDefault();
                    _single_services = _form.find('#single-services option:selected');
                    //window.location.href = _single_services.data('link');
                    window.open(_single_services.data('link'), '_blank');
                });
                $('#category-services').on('change', '', function(event) {
                    _this =  $(this);


                    event.preventDefault();
                    $.ajax({
                        url: wysijaAJAX.ajaxurl,
                        type: 'POST',
                        dataType: 'json',
                        data: _form.serialize() + '&action=ajax_get_services',
                        beforeSend: function(){
                            _form.addClass('preload');
                            _form.append('<span class="preload-spinner"></span>')

                        }
                    })
                        .done(function(response) {

                            _output = '';


                            $.each(response.posts, function( index, value ) {
                                _output += '<option value="' + value.id + '" data-link="' + value.link + '">' +  value.name +'</option>';
                            });

                            $('#single-services').empty().append(_output);

                        })
                        .fail(function() {
                            console.log("error");
                        })
                        .always(function() {
                            _form.removeClass('preload');
                            _form.find('.preload-spinner').hide().remove();
                        });

                    /* Act on the event */
                });

            });

        })(jQuery);</script>

<?php } ?>

<?php

add_action('wp_footer','slider_option');
