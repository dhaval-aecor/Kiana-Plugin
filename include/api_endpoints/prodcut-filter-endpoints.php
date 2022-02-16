<?php
function wp_rest_filterproducts_endpoint_handler($request = null) {
    global $wpdb;
    $header = $request->get_headers();
    

    if(!isset($header['authorization'][0]) || $header['authorization'][0]=='')
    {
      $errors['invalid_user'] = 'User is not logedin';
    }
    $token = str_replace('Bearer ', '', $header['authorization'][0]);
    $user_id = validate_token($token);
    if(!$user_id){
      $errors['invalid_token'] = 'token is not valid';
    }

    $wl_list = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}yith_wcwl_lists WHERE user_id=".$user_id,ARRAY_A);
    $wl_items = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}yith_wcwl WHERE wishlist_id=".$wl_list['ID']."$condition",ARRAY_A);


    $output = array();
    
    $params = $request->get_params();
    $category = $params['category'];
    $tags = $params['tags'];

    $filters  = $params['filter'];
    $per_page = (isset($params['per_page']) && $params['per_page']>0) ? $params['per_page'] : 10;
    $page   = (isset($params['page']) && $params['page']>0) ? $params['page'] : 1;
    $order    = $params['order'];
    $orderby  = $params['orderby'];
    $featured = $params['featured'];
    $search_term = $params['search'];
    
    // Use default arguments.
    $args = [
      'post_type'      => 'product',
      'posts_per_page' => 10,
      'post_status'    => 'publish',
      'paged'          => 1,
    ];
    if(isset($params['search'])){
      $args['s'] = $search_term;
    }

    // Order condition. ASC/DESC.
    if ( ! empty( $order ) ) {
      $args['order'] = $order;
    }

    // Orderby condition. Name/Price.
    if ( ! empty( $orderby ) ) {
      if ( $orderby === 'price' ) {
        $args['orderby'] = 'meta_value_num';
        $args['meta_key']  = '_price';
      } elseif($orderby === 'popularity') {
        $args['orderby'] = 'meta_value_num';
        $args['meta_key']  = 'total_sales';
      } elseif($orderby === 'rating') {
        $args['orderby'] = 'meta_value_num';
        $args['meta_key']  = '_wc_average_rating';
      } else {
        $args['orderby'] = $orderby;
      }
    }
     
    
        // If filter buy category or attributes.
    if ( ! empty( $category ) || ! empty( $filters ) || ! empty( $tags ) ) {
      $args['tax_query']['relation'] = 'AND';

      // Tags filter.
      if ( ! empty( $tags ) ) {
        $args['tax_query'][] = [
          'taxonomy' => 'product_tag',
          'field'    => 'slug',
          'terms'    => [ $tags ],
        ];
      }

      // Category filter.
      if ( ! empty( $category ) ) {
        $args['tax_query'][] = [
          'taxonomy' => 'product_cat',
          'field'    => 'slug',
          'terms'    => [ $category ],
        ];
      }

      // Attributes filter.
      if ( ! empty( $filters ) ) {
        foreach ( $filters as $filter_key => $filter_value ) {
          if ( $filter_key === 'min_price' || $filter_key === 'max_price' ) {
            continue;
          }
          $args['tax_query'][] = [
            'taxonomy' => $filter_key,
            'field'    => 'term_taxonomy_id',
            'terms'    => \explode( ',', $filter_value ),
            'operator' => 'IN',
          ];
        }
      }

      // Min / Max price filter.
      if ( isset( $filters['min_price'] ) || isset( $filters['max_price'] ) ) {
        $price_request = [];

        if ( isset( $filters['min_price'] ) ) {
          $price_request['min_price'] = $filters['min_price'];
        }

        if ( isset( $filters['max_price'] ) ) {
          $price_request['max_price'] = $filters['max_price'];
        }

        $args['meta_query'][] = \wc_get_min_max_price_meta_query( $price_request );
        }
    }
    if(isset($params['featured']))
    {
      if($featured == 'true') {
        $args['tax_query'][] = [
            'taxonomy' => 'product_visibility',
            'field'    => 'name',
            'terms'    => 'featured',
            'operator' => 'IN',
          ];
      } else {
        $args['tax_query'][] = [
            'taxonomy' => 'product_visibility',
            'field'    => 'name',
            'terms'    => 'featured',
            'operator' => 'NOT IN',
          ];
      }
    }
    
    if(isset($params['type']))
    {
      $args['tax_query'][] = [
            'taxonomy' => 'product_type',
            'field'    => 'slug',
            'terms'    => $params['type'],
            'operator' => 'IN',
          ];
    }

    if(isset($params['sku']))
    {
      $args['meta_query'][] = [
        'key' => '_sku',
        'compare' => 'LIKE',
        'value' => $params['sku']
      ];
    }
    $args1 = $args; 
    $args1['tax_query'][] = [
        'taxonomy' => 'product_visibility',
        'field'    => 'name',
        'terms'    => array('outofstock'),
        'operator' => 'NOT IN',
      ];
      // Posts per page.
    if ( ! empty( $per_page ) ) {
      $args1['posts_per_page'] = $per_page;
    }

    // Pagination, starts from 1.
    if ( ! empty( $page ) ) {
      $args1['paged'] = $page;
    }

    $args3 = $args; 
    $args3['tax_query'][] = [
        'taxonomy' => 'product_visibility',
        'field'    => 'name',
        'terms'    => array('outofstock'),
        'operator' => 'NOT IN',
      ];
    $args3['paged'] = 1;
    $args3['posts_per_page'] = $per_page;
    $the_query3 = new \WP_Query( $args3 );
    wp_reset_postdata();
    
    $the_query = new \WP_Query( $args1 );
    $filter_price = get_filtered_price_custom($args);

    // if ( ! $the_query->have_posts() ) {
    //   return $output;
    // }
    // print_r(count($the_query->posts));
    $per_page_count = count($the_query->posts);
    $q1_last_page = $the_query3->max_num_pages;
    $q1_total_item = $the_query3->found_posts;

    while ( $the_query->have_posts() ) {
        $the_query->the_post();
        $product = wc_get_product( get_the_ID() );  
        $wcproduct = array();
        $on_sale = false;
        $in_wishlist = false;
        $data = $product->get_data();
        $prod = get_post( $product->get_id() );
        $image_ids=array();
        $image_ids[] = $product->get_image_id();
        $gallery = $product->get_gallery_image_ids();
        $imag = array();
        $images_list = array();

        foreach ($gallery as $key => $img) {
          array_push($image_ids, $img);
        }
        // print_r($product->get_sale_price());
        if($product->get_sale_price()){
          $on_sale = true;
          $befor_sale = $product->get_regular_price();
          $after_sale = $product->get_sale_price();
        }
        foreach ($image_ids as $key => $image) {
          $img = get_post($image);
          $imag = array(
            "id" => $img->ID,
                "date_created" => date('Y-m-d\TH:i:s',strtotime($img->post_date)),
                "date_created_gmt" => date('Y-m-d\TH:i:s',strtotime($img->post_date_gmt)),
                "date_modified" => date('Y-m-d\TH:i:s',strtotime($img->post_modified)),
                "date_modified_gmt" => date('Y-m-d\TH:i:s',strtotime($img->post_modified_gmt)),
                "src" => $img->guid,
                "name" => $img->post_title,
                "alt" => $img->post_title
          );
          array_push($images_list, $imag);
        }
        $categories = get_the_terms( $product->get_id(), 'product_cat' );
        $prod_cats = array();
        foreach ($categories as $cat) {
            $prod_cat = array(
              "id" => $cat->term_id,
              "name" => $cat->name,
              "slug" => $cat->slug,
              "parent" => $cat->parent,
              "description" => $cat->description,
              "display" => $cat->display,
              "menu_order" => $cat->menu_order,
              "count" => $cat->count,
            );
            array_push($prod_cats, $prod_cat);
        }
        
        $wcproduct['id'] = $product->get_id();
        $wcproduct['name'] = $product->get_name();
        $wcproduct['slug'] = $product->get_slug();
        $wcproduct['permalink'] = get_permalink( $product->get_id() );
        $wcproduct['date_created'] = date('Y-m-d\TH:i:s',strtotime($prod->post_date));
        $wcproduct['date_created_gmt'] = date('Y-m-d\TH:i:s',strtotime($prod->post_date_gmt));
        $wcproduct['date_modified'] = date('Y-m-d\TH:i:s',strtotime($prod->post_modified));
        $wcproduct['date_modified_gmt'] = date('Y-m-d\TH:i:s',strtotime($prod->post_modified_gmt));
        $wcproduct['type'] = $product->get_type();
        $wcproduct['status'] = $product->get_status();
        $wcproduct['featured'] = $product->get_featured();
        $wcproduct['description'] = $product->get_description();
        $wcproduct['category'] = $prod_cats;
        $wcproduct['short_description'] = $product->get_short_description();
        $wcproduct['sku'] = $product->get_sku();
        $wcproduct['price'] = $product->get_price();
        $wcproduct['regular_price'] = $product->get_regular_price();
        $wcproduct['sale_price'] = $product->get_sale_price();
        $wcproduct['total_sales'] = $product->get_total_sales();
        $wcproduct['stock_status'] = $product->get_stock_status();

        $product_variation = $product->get_children();
        $var_data = array();
        $lowest_sale_price = (int)$product->get_regular_price();
        foreach ($product_variation as $key => $var_id) {
          $variation = wc_get_product( $var_id );
          $attributes = array();
          foreach ($variation->get_attributes() as $key => $at) {
            $attribute = array(
              'name' => wc_attribute_label($key),
              'value' => $at
            );
          }
          if($on_sale==false){
            if($variation->get_sale_price())
            {
              $on_sale = true;
              $befor_sale = $variation->get_regular_price();
              $after_sale = $variation->get_sale_price();
            } else {
              $on_sale = false;
              $befor_sale = $variation->get_regular_price();
              $after_sale = $variation->get_sale_price();
            }
          }
          if($lowest_sale_price==0)
          {
            if($on_sale==true)
            {
              $lowest_sale_price = $variation->get_sale_price();
            } else {
              $lowest_sale_price = $variation->get_price();
            }
          } else {
            if($variation->get_sale_price()) {
              if((int)$variation->get_sale_price()<$lowest_sale_price){
                $lowest_sale_price = (int)$variation->get_sale_price();
              }
            }
          }

          $var = array(
            'id' => $var_id,
            'name' => $variation->get_name(),
            'sku' => $variation->get_sku(),
            'attributes' => $attribute,
            'price'=> $variation->get_price(),
            'regular_price'=> $variation->get_regular_price(),
            'sale_price'=> $variation->get_sale_price(),
          );
          array_push($var_data, $var);
        }
        $wcproduct['on_sale'] = $on_sale;
        $wcproduct['price_befor_sale'] = $befor_sale;
        $wcproduct['price_after_sale'] = $after_sale;
        $sale_per = round(($befor_sale-$lowest_sale_price)*100/$befor_sale);
        $wcproduct['sale_percentage'] = $sale_per;
        $wcproduct['variation_detail'] = $var_data;
        if ( has_term( 'new-arrivals', 'product_tag', $product->get_id() ) ) {
            $wcproduct['is_new'] = true;
        } else {
          $wcproduct['is_new'] = false;
        }
        $wcproduct['size_chart'] = 'https://www.kianafashion.com/wp-content/uploads/2020/10/New-Size-Chart.jpg';
        

        $in_wishlist = is_in_array($product->get_id(),$wl_items,'prod_id');

        $wl_items_id = null;
        if($in_wishlist!==false){
          $key = array_search($product->get_id(),array_column($wl_items,'prod_id'));
          $item_id = $wl_items[$key]['ID'];
          $wl_item_id = $item_id;
        }
        if($in_wishlist===false){
          $post_child = $product->get_children();
          foreach ($post_child as $key => $child_id) {
            $in_wishlist = is_in_array($child_id,$wl_items,'prod_id');
            if ($in_wishlist) {
              // var_dump($in_wishlist);
                $key = array_search($child_id,array_column($wl_items,'prod_id'));
                $item_id = $wl_items[$key]['ID'];
                $wl_item_id = $item_id;
                break;
            }
          }
        }
        if($in_wishlist===true)
        {
          $wcproduct["in_wishlist"] = true;
          $wcproduct["wishlist_id"] = $wl_list['ID'];
          $wcproduct["wl_item_id"] = $wl_item_id;
        } else {
          $wcproduct["in_wishlist"] = false;
        }


        $wcproduct['images'] = $images_list;
                    
        $items[] = $wcproduct;
    }

    wp_reset_postdata();

    $args2 = $args; 
    $args2['tax_query'][] = [
        'taxonomy' => 'product_visibility',
        'field'    => 'name',
        'terms'    => array('outofstock'),
        'operator' => 'IN',
      ];
      // Posts per page.
    if ( ! empty( $per_page ) ) {
      $args2['posts_per_page'] = -1;
    }
    // print_r($page);
    // print_r($q1_last_page);
    if ( ! empty( $page ) ) {
      $args2['paged'] = 1;
    }
    if($page>=$q1_last_page)
    {
      if($page==$q1_last_page)
      {
        // print_r("Last Page");
        $remain_count = $per_page - $per_page_count;
        if($remain_count>0){
          $args2['posts_per_page'] = $remain_count;
        } else {
          $args2['posts_per_page'] = 0;
        }
      } elseif ($page>$q1_last_page) {
        $last_page_amo=floor($q1_total_item/$q1_last_page);
        $remain_count = $per_page-$last_page_amo;
        $page_gap = $page-$q1_last_page;
        if($page_gap==1){
          $offset = $remain_count;
        } else {
          $offset = ($page_gap-1)*$per_page+$remain_count;
        }
        // print_r($offset);
        $args2['offset'] = $offset; 
        $args2['posts_per_page'] = $per_page;
      }
    }

    $the_query2 = new \WP_Query( $args2 );
    if($page>=$q1_last_page)
    {
        while ( $the_query2->have_posts() ) {
            $the_query2->the_post();
            $product = wc_get_product( get_the_ID() );  
            $wcproduct = array();
            $on_sale = false;
            $in_wishlist = false;
            $data = $product->get_data();
            $prod = get_post( $product->get_id() );
            $image_ids=array();
            $image_ids[] = $product->get_image_id();
            $gallery = $product->get_gallery_image_ids();
            $imag = array();
            $images_list = array();
    
            foreach ($gallery as $key => $img) {
              array_push($image_ids, $img);
            }
            // print_r($product->get_sale_price());
            if($product->get_sale_price()){
              $on_sale = true;
              $befor_sale = $product->get_regular_price();
              $after_sale = $product->get_sale_price();
            }
            foreach ($image_ids as $key => $image) {
              $img = get_post($image);
              $imag = array(
                "id" => $img->ID,
                    "date_created" => date('Y-m-d\TH:i:s',strtotime($img->post_date)),
                    "date_created_gmt" => date('Y-m-d\TH:i:s',strtotime($img->post_date_gmt)),
                    "date_modified" => date('Y-m-d\TH:i:s',strtotime($img->post_modified)),
                    "date_modified_gmt" => date('Y-m-d\TH:i:s',strtotime($img->post_modified_gmt)),
                    "src" => $img->guid,
                    "name" => $img->post_title,
                    "alt" => $img->post_title
              );
              array_push($images_list, $imag);
            }
            $categories = get_the_terms( $product->get_id(), 'product_cat' );
            $prod_cats = array();
            foreach ($categories as $cat) {
                $prod_cat = array(
                  "id" => $cat->term_id,
                  "name" => $cat->name,
                  "slug" => $cat->slug,
                  "parent" => $cat->parent,
                  "description" => $cat->description,
                  "display" => $cat->display,
                  "menu_order" => $cat->menu_order,
                  "count" => $cat->count,
                );
                array_push($prod_cats, $prod_cat);
            }
            
            $wcproduct['id'] = $product->get_id();
            $wcproduct['name'] = $product->get_name();
            $wcproduct['slug'] = $product->get_slug();
            $wcproduct['permalink'] = get_permalink( $product->get_id() );
            $wcproduct['date_created'] = date('Y-m-d\TH:i:s',strtotime($prod->post_date));
            $wcproduct['date_created_gmt'] = date('Y-m-d\TH:i:s',strtotime($prod->post_date_gmt));
            $wcproduct['date_modified'] = date('Y-m-d\TH:i:s',strtotime($prod->post_modified));
            $wcproduct['date_modified_gmt'] = date('Y-m-d\TH:i:s',strtotime($prod->post_modified_gmt));
            $wcproduct['type'] = $product->get_type();
            $wcproduct['status'] = $product->get_status();
            $wcproduct['featured'] = $product->get_featured();
            $wcproduct['description'] = $product->get_description();
            $wcproduct['category'] = $prod_cats;
            $wcproduct['short_description'] = $product->get_short_description();
            $wcproduct['sku'] = $product->get_sku();
            $wcproduct['price'] = $product->get_price();
            $wcproduct['regular_price'] = $product->get_regular_price();
            $wcproduct['sale_price'] = $product->get_sale_price();
            $wcproduct['total_sales'] = $product->get_total_sales();
            $wcproduct['stock_status'] = $product->get_stock_status();
    
            $product_variation = $product->get_children();
            $var_data = array();
            $lowest_sale_price = (int)$product->get_regular_price();
            foreach ($product_variation as $key => $var_id) {
              $variation = wc_get_product( $var_id );
              $attributes = array();
              foreach ($variation->get_attributes() as $key => $at) {
                $attribute = array(
                  'name' => wc_attribute_label($key),
                  'value' => $at
                );
              }
              if($on_sale==false){
                if($variation->get_sale_price())
                {
                  $on_sale = true;
                  $befor_sale = $variation->get_regular_price();
                  $after_sale = $variation->get_sale_price();
                } else {
                  $on_sale = false;
                  $befor_sale = $variation->get_regular_price();
                  $after_sale = $variation->get_sale_price();
                }
              }
              if($lowest_sale_price==0)
              {
                if($on_sale==true)
                {
                  $lowest_sale_price = $variation->get_sale_price();
                } else {
                  $lowest_sale_price = $variation->get_price();
                }
              } else {
                if($variation->get_sale_price()) {
                  if((int)$variation->get_sale_price()<$lowest_sale_price){
                    $lowest_sale_price = (int)$variation->get_sale_price();
                  }
                }
              }
    
              $var = array(
                'id' => $var_id,
                'name' => $variation->get_name(),
                'sku' => $variation->get_sku(),
                'attributes' => $attribute,
                'price'=> $variation->get_price(),
                'regular_price'=> $variation->get_regular_price(),
                'sale_price'=> $variation->get_sale_price(),
              );
              array_push($var_data, $var);
            }
            $wcproduct['on_sale'] = $on_sale;
            $wcproduct['price_befor_sale'] = $befor_sale;
            $wcproduct['price_after_sale'] = $after_sale;
            $sale_per = ceil(($befor_sale-$lowest_sale_price)*100/$befor_sale);
            $wcproduct['sale_percentage '] = $sale_per;
            $wcproduct['variation_detail'] = $var_data;
            if ( has_term( 'new-arrivals', 'product_tag', $product->get_id() ) ) {
                $wcproduct['is_new'] = true;
            } else {
              $wcproduct['is_new'] = false;
            }
            $wcproduct['size_chart'] = 'https://www.kianafashion.com/wp-content/uploads/2020/10/New-Size-Chart.jpg';
            
    
            $in_wishlist = is_in_array($product->get_id(),$wl_items,'prod_id');
    
            $wl_items_id = null;
            if($in_wishlist!==false){
              $key = array_search($product->get_id(),array_column($wl_items,'prod_id'));
              $item_id = $wl_items[$key]['ID'];
              $wl_item_id = $item_id;
            }
            if($in_wishlist===false){
              $post_child = $product->get_children();
              foreach ($post_child as $key => $child_id) {
                $in_wishlist = is_in_array($child_id,$wl_items,'prod_id');
                if ($in_wishlist) {
                  // var_dump($in_wishlist);
                    $key = array_search($child_id,array_column($wl_items,'prod_id'));
                    $item_id = $wl_items[$key]['ID'];
                    $wl_item_id = $item_id;
                    break;
                }
              }
            }
            if($in_wishlist===true)
            {
              $wcproduct["in_wishlist"] = true;
              $wcproduct["wishlist_id"] = $wl_list['ID'];
              $wcproduct["wl_item_id"] = $wl_item_id;
            } else {
              $wcproduct["in_wishlist"] = false;
            }
    
    
            $wcproduct['images'] = $images_list;
                        
            $items[] = $wcproduct;
        }
        wp_reset_postdata();
    }

    if ( !$the_query->have_posts() && !$the_query2->have_posts() ) {
      return $output;
    }

    $total_item = $the_query2->found_posts+$the_query3->found_posts;
    // $per_page
    $total_pages = ceil($total_item/$per_page);
    

    $output['page_meta'] = array(
      'per_page' => $per_page,
      'total_items' => $total_item,
      'total_pages' => $total_pages,
      'current_page' => $page,
      'last_page' => $total_pages,
      'first_page' => 1,
    );
    $output['items'] = $items;

    return new WP_REST_Response($output, 200);
}

function get_price_range()
{
  $filter_price = get_filtered_price_custom();
  $output = array(
      'min_price' => $filter_price->min_price,
      'max_price' => $filter_price->max_price,
    );
  return new WP_REST_Response($output, 200);
}

function get_filtered_price_custom($args=null) {
    global $wpdb;
    if(empty($args)){
      $args = WC()->query->get_main_query()->query_vars;
    }
    $tax_query  = isset( $args['tax_query'] ) ? $args['tax_query'] : array();
    $meta_query = isset( $args['meta_query'] ) ? $args['meta_query'] : array();

    if ( ! is_post_type_archive( 'product' ) && ! empty( $args['taxonomy'] ) && ! empty( $args['term'] ) ) {
      $tax_query[] = WC()->query->get_main_tax_query();
    }

    foreach ( $meta_query + $tax_query as $key => $query ) {
      if ( ! empty( $query['price_filter'] ) || ! empty( $query['rating_filter'] ) ) {
        unset( $meta_query[ $key ] );
      }
    }

    $meta_query = new WP_Meta_Query( $meta_query );
    $tax_query  = new WP_Tax_Query( $tax_query );
    $search     = WC_Query::get_main_search_query_sql();

    $meta_query_sql   = $meta_query->get_sql( 'post', $wpdb->posts, 'ID' );
    $tax_query_sql    = $tax_query->get_sql( $wpdb->posts, 'ID' );
    $search_query_sql = $search ? ' AND ' . $search : '';

    $sql = "
      SELECT min( min_price ) as min_price, MAX( max_price ) as max_price
      FROM {$wpdb->wc_product_meta_lookup}
      WHERE product_id IN (
        SELECT ID FROM {$wpdb->posts}
        " . $tax_query_sql['join'] . $meta_query_sql['join'] . "
        WHERE {$wpdb->posts}.post_type IN ('" . implode( "','", array_map( 'esc_sql', apply_filters( 'woocommerce_price_filter_post_type', array( 'product' ) ) ) ) . "')
        AND {$wpdb->posts}.post_status = 'publish'
        " . $tax_query_sql['where'] . $meta_query_sql['where'] . $search_query_sql . '
      )';

    $sql = apply_filters( 'woocommerce_price_filter_sql', $sql, $meta_query_sql, $tax_query_sql );

    return $wpdb->get_row( $sql ); // WPCS: unprepared SQL ok.
  }