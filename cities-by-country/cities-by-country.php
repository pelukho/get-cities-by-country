<?php
/*
 * Plugin Name:       Super Duper Plugin
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           1.0.0
 * Author:            Pelukho
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit; 
}

define( 'CBC_VERSION', '1.0.0' );

// Регистрируем пост тайп cities и taxonomy locations
function register_cities_post_type() {			
			
	register_post_type('cities', array(
		'label'               => 'Cities',
		'labels'              => array(
			'name'          => 'Cities',
			'singular_name' => 'City',
			'menu_name'     => 'Archive cities',
			'all_items'     => 'All cities',
			'add_new'       => 'Add city',
			'add_new_item'  => 'Add new city',
			'edit'          => 'Edit city',
			'edit_item'     => 'Edit city',
			'new_item'      => 'New city',
		),
		'description'         => 'Description',
		'public'              => true,
		'publicly_queryable'  => true,
		'show_ui'             => true,
		'show_in_rest'        => false,
		'rest_base'           => '',
		'show_in_menu'        => true,
		'exclude_from_search' => false,
		'capability_type'     => 'post',
		'map_meta_cap'        => true,
		'hierarchical'        => true,
		'has_archive'         => true,
		'query_var'           => true,
		'supports'            => array( 'title', 'editor', 'thumbnail', 'custom-fields' ),
		'taxonomies'          => array( 'locations' ),
	) );

	register_taxonomy('locations', array('cities'), array(
		'label'                 => 'Locations',
		'labels'                => array(
			'name'              => 'Locations',
			'singular_name'     => 'Location',
			'search_items'      => 'Search Locations',
			'all_items'         => 'All Locations',
			'view_item '        => 'View Locations',
			'parent_item'       => 'Parent Locations',
			'parent_item_colon' => 'Parent Location:',
			'edit_item'         => 'Edit Location',
			'update_item'       => 'Update Location',
			'add_new_item'      => 'Add New Location',
			'new_item_name'     => 'New Location Name',
			'menu_name'         => 'Location',
		),
		'description'           => '', 
		'public'                => true,
		'publicly_queryable'    => null, 
		'show_in_nav_menus'     => true, 
		'show_ui'               => true, 
		'show_in_menu'          => true, 
		'show_tagcloud'         => true, 
		'show_in_rest'          => null, 
		'rest_base'             => null,
		'hierarchical'          => true,
		'update_count_callback' => '',
		'rewrite'               => true,
		'capabilities'          => array(),
		'meta_box_cb'           => null, 
		'show_admin_column'     => false, 
		'_builtin'              => false,
		'show_in_quick_edit'    => null
	) );
}
add_action( 'init', 'register_cities_post_type' );

// Подключаем бутстрап
function cbc_enqueue() {		
	wp_enqueue_style( 'cities-by-country-css', plugins_url() . '/cities-by-country/css/bootstrap.min.css', array(), CBC_VERSION, 'all' );
	wp_enqueue_script( 'cities-by-country-js', plugins_url() . '/cities-by-country/js/bootstrap.min.js', array( 'jquery' ), CBC_VERSION, false );
}
add_action('wp_enqueue_scripts', 'cbc_enqueue');

// Хуки активации и деактивации
register_activation_hook( __FILE__, 'cbc_activate' );
register_deactivation_hook( __FILE__, 'cbc_deactivate' );

// Регистрируем пост тайп, и обновляем ссылки
function cbc_activate() {	
	flush_rewrite_rules();
}

// После деактивации обновляем ссылки
function cbc_deactivate(){
	flush_rewrite_rules();
}

$country_code = [];

// Функция, которая определяет местоположение пользователя, при помощи api
function get_country_code(){
	$get_user_info = file_get_contents('https://www.iplocate.io/api/lookup/'. $_SERVER['REMOTE_ADDR'] .'/json');
	$country_code = json_decode($get_user_info, true);
	return $country_code['country_code'];
}

// Шорткод вывода списка городов
function get_cities($page = 1){
	// Api key
	$key = '9hdAmEFddAQ2zhDLo3UmBgKvemcilWc5';
	// Получаем страну
	$country = get_country_code();
	// Определяем страницу пагинации
	$page = get_query_var('page') ? get_query_var('page') : 1;	
	// Формируем урл для запроса
	$url = 'http://geohelper.info/api/v1/cities?locale[lang]=ru&filter[countryIso]='. $country .'&pagination[limit]=20&pagination[page]='. $page .'&order[dir]=asc&apiKey='. $key;
	// Получаем ответ
	$cities = file_get_contents($url);
	$cities = json_decode($cities, true);
	if($cities['success']){
		$html = '';
		$class_prev = '';
		$class_next = '';
		if(get_query_var('page') > 1){
			$i = get_query_var('page') * 20;
		} else {
			$i = 1;
		}
		if( $cities['pagination']['totalPageCount'] == get_query_var('page')){
			$next_page = get_query_var('page'); 
			$class_next = 'disabled';
		} else {
			$next_page = get_query_var('page') == 0 ? get_query_var('page') + 2 : get_query_var('page') + 1; 
		}
		if( get_query_var('page') <= 1){
			$prev_page = get_query_var('page'); 
			$class_prev = 'disabled';
		} else {
			$prev_page = get_query_var('page') - 1;
		}
	  	ob_start();

		$html = '<table class="table table-hover">
		<thead>
	    <tr>
	      <th scope="col">#</th>
	      <th scope="col">Название </th>
	      <th scope="col">Тип</th>
	      <th scope="col">Индекс</th>
	    </tr>
	  </thead>
	  <tbody>';        
	 
	  	foreach($cities['result'] as $city){	
			$post_code = isset($city['postCode']) ? $city['postCode'] : "";
			$html .= '<tr>
					  <th scope="row">'. $i .'</th>
					  <td>'. $city['name'] .'</td>
					  <td>'. $city['localityType']['name'] .'</td>
					  <td>'. $post_code .'</td>
					</tr>';
			$i++;
	  	}
		$html .= '
			</tbody>
		</table>';
		
		$html .= '
		<nav aria-label="Page navigation example">
		  <ul class="pagination justify-content-end">
			<li class="page-item '. $class_prev .'">
			  <a class="page-link" href="?page='. $prev_page .'" tabindex="-1">Previous</a>
			</li>
			<li class="page-item '. $class_next .'">
			  <a class="page-link" href="?page='. $next_page .'">Next</a>
			</li>
		  </ul>
		</nav>';
		
		echo $html;
	  	
		return ob_get_clean();
	} else {
		return 'Произошла ошибка!';
	}
}
add_shortcode('display_sities', 'get_cities');