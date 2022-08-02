<?php

/*
############################################################
Custom WooCommerce API for posting bulk products
@Author: Janib Soomro
@Contact: soomrojb@gmail.com | +92-333-3640375
@Dated: 07/16/2022
@Updated: 08/03/2022
############################################################
*/

/* return count of exact matched products with slug */
if (!function_exists('search_woo_slug')) :
    function search_woo_slug($data) {
            $itemArgs = array(
                'post_type' => $data['ptype'],
                'name' => $data['slug']
            );
            $query = new WP_Query($itemArgs);
            $response = new WP_REST_Response( array("count"=>$query->post_count) );
            $response->set_status(200);
            return $response;
    }
endif;

/* add product with variations and attributes */
if (!function_exists('add_variable_product')) :
    function add_variable_product( WP_REST_Request $request)
    {
        $data = $request->get_params();
        $allvariations = $data['variations'];
        $allimages = $data['images'];
        $allcategories = $data['categories'];
        $attachIds = array();
        $categoryIds = array();
        $attachtab = $data['attachments'];
        $focuskeywords = $data['focus_keywords'];
        $specifications = $data['specifications'];
        $attributes = array();
        $brandid = 0;
 
        $postname = $data['product_title'];
        $author = empty( $data['author'] ) ? '1' : $data['author'];
        $product_slug = $data['product_slug'];

        /* search product by slug/post_name */
        $post_status = get_page_by_path( $product_slug, OBJECT, 'product' );
        if (empty($post_status))
        {
            /* post new product */
            $post_data = array(
                "post_author"       =>  $author,
                "post_name"         =>  $product_slug,
                "post_title"        =>  $postname,
                "post_content"      =>  $data["long_description"],
                "post_excerpt"      =>  $data["short_description"],
                "post_status"       =>  "draft",
                "ping_status"       =>  "closed",
                "post_type"         =>  "product"
            );

            // Creating the product (post data)
            $product_id = wp_insert_post( $post_data );

            // add/update categories
            if (is_array($allcategories) && !empty($allcategories))
            {
                include_once( ABSPATH . "/wp-admin/includes/taxonomy.php" );

                $categoryIds = [];
                $lastcatid = 0;
                $cattaxonomy = "product_cat";
                foreach ($allcategories as $category) {
                    $catgid = term_exists($category, $cattaxonomy);
                    if ($catgid) {
                        $categoryIds[] = $catgid['term_id'];
                        $lastcatid = $catgid['term_id'];
                    } else {
                        $catgarray = array(
                            "taxonomy" => $cattaxonomy,
                            "cat_name" => $category,
                            "category_description" => sanitize_title($category),
                            "category_parent" => $lastcatid
                        );
                        $catgid = wp_insert_category($catgarray);
                        $lastcatid = $catgid;
                        $categoryIds[] = $catgid;
                    }
                    wp_set_object_terms( $product_id, intval($lastcatid), $cattaxonomy );
                }
            }

            if (is_array($allvariations) && empty($allvariations))
            {
                /* update simple product basic details */
                $modifiers = array("sku","price","sale_price","regular_price","purchase_note","sold_individually");
                foreach ($modifiers as $modifier)
                {
                    if (isset($data[$modifier]))
                    {
                        $status = get_post_meta($product_id, '_'.$modifier );
                        if (empty($status)) {
                            add_post_meta( $product_id, '_'.$modifier, $data[$modifier] );
                        } else {
                            update_post_meta( $product_id, '_'.$modifier, $data[$modifier] );                    
                        }
                    }
                }
            
            } else {
                /* add variation along with attributes and price */
                wp_set_object_terms($product_id, 'variable', 'product_type');
                $product = wc_get_product($product_id);
                $variation_post_schema = array(
                    'post_title'  => $product->get_name(),
                    'post_name'   => 'product-'.$product_id.'-variation',
                    'post_status' => 'publish',
                    'post_parent' => $product_id,
                    'post_type'   => 'product_variation',
                    'guid'        => $product->get_permalink()
                );

                foreach( $allvariations as $variation )
                {
                    $variation_post = $variation_post_schema;
                    $variation_id = wp_insert_post( $variation_post );
                    $variationobj = new WC_Product_Variation( $variation_id );

                    foreach ($variation['attributes'] as $attrname => $attrvalues )
                    {
                        $taxonomy = 'pa_' . wc_sanitize_taxonomy_name($attrname);
                        if(!taxonomy_exists( $taxonomy)) {
                            $attribute_id = wc_create_attribute(array(
                                'name'         => ucfirst($attrname),
                                'slug'         => wc_sanitize_taxonomy_name($attrname),
                                'type'         => 'select',
                                'order_by'     => 'menu_order',
                                'has_archives' => false,
                            ));

                            register_taxonomy(
                                $taxonomy,
                                'product_variation',
                                array(
                                    'hierarchical' => false,
                                    'label' => ucfirst($attrname),
                                    'query_var' => true,
                                    'rewrite' => array('slug' => sanitize_title($attrname)),
                                )
                            );
                        }

                        $option_term_ids = array();
                        foreach($attrvalues as $option) {
                            if (!term_exists($option, $taxonomy)) {
                                wp_insert_term($option, $taxonomy);
                            }

                            wp_set_object_terms( $product_id, $option, $taxonomy, true );
                            $option_term_ids[] = get_term_by( 'name', $option, $taxonomy )->term_id;

                            /* assign variable labels */
                            $term_slug = get_term_by('name', $option, $taxonomy )->slug;
                            $post_term_names = wp_get_post_terms( $product_id, $taxonomy, array('fields' => 'names') );
                            if( !in_array($option, $post_term_names)) {
                                wp_set_post_terms( $product_id, $option, $taxonomy, true );
                            }
                            update_post_meta( $variation_id, 'attribute_'.$taxonomy, $term_slug );
                        }
                        $attributes[$taxonomy] = array(
                            'name'              => $taxonomy,
                            'value'             => $option_term_ids,
                            'is_visible'        => true,
                            'is_variation'      => true,
                            'is_taxonomy'       => '1'
                        );
                    }

                    if (isset($variation['sku'])) {
                        $variationobj->set_sku( $variation['sku'] );
                    }
                    if (isset($variation['sale_price'])) {
                        $variationobj->set_sale_price( $variation['sale_price'] );
                    }
                    if (isset($variation['regular_price'])) {
                        $variationobj->set_regular_price( $variation['regular_price'] );
                    }
                    $variationobj->set_manage_stock(false);
                    $variationobj->set_weight('');
                    $variationobj->save();
                }
            }

            /* static specifications */
            if (is_array($specifications) && !empty($specifications))
            {
                $specs_attributes = array();
                foreach ($specifications as $attrname => $attrvalues )
                {
                    $taxonomy = 'pa_' . wc_sanitize_taxonomy_name($attrname);
                    if(!taxonomy_exists( $taxonomy)) {
                        $attribute_id = wc_create_attribute(array(
                            'name'         => ucfirst($attrname),
                            'slug'         => wc_sanitize_taxonomy_name($attrname),
                            'type'         => 'select',
                            'order_by'     => 'menu_order',
                            'has_archives' => false,
                        ));

                        register_taxonomy(
                            $taxonomy,
                            'product_variation',
                            array(
                                'hierarchical' => false,
                                'label' => ucfirst($attrname),
                                'query_var' => true,
                                'rewrite' => array('slug' => sanitize_title($attrname)),
                            )
                        );
                    }

                    $option_term_ids = array();
                    foreach($attrvalues as $option) {
                        if (!term_exists($option, $taxonomy)) {
                            wp_insert_term($option, $taxonomy);
                        }

                        wp_set_object_terms( $product_id, $option, $taxonomy, true );
                        $option_term_ids[] = get_term_by( 'name', $option, $taxonomy )->term_id;

                        /* assign variable labels */
                        $term_slug = get_term_by('name', $option, $taxonomy )->slug;
                        $post_term_names = wp_get_post_terms( $product_id, $taxonomy, array('fields' => 'names') );
                        if( !in_array($option, $post_term_names)) {
                            wp_set_post_terms( $product_id, $option, $taxonomy, true );
                        }
                    }
                    $attributes[$taxonomy] = array(
                        'name'              => $taxonomy,
                        'value'             => $option_term_ids,
                        'is_visible'        => true,
                        'is_variation'      => false,
                        'is_taxonomy'       => '1'
                    );
                }
            }

            /* update variable and non-variable specifications at once */
            update_post_meta( $product_id, '_product_attributes', $attributes );

            /* update images */
            if (is_array($allimages) && !empty($allimages))
            {
                include_once( ABSPATH . 'wp-admin/includes/image.php' );
                foreach ($allimages as $imageurl)
                {
                    $rawimgname = end(explode('/', $imageurl));
                    $imageextn = end(explode(".", $rawimgname));
                    $uniq_name = date('dmY').''.(int) microtime(true); 
                    $filename = $uniq_name.'.'.$imageextn;
                    $uploaddir = wp_upload_dir();
                    $uploadfile = $uploaddir['path'] . '/' . $filename;

                    /* doesnt work for few websites including 3m.com */
                    /*
                        $contents= file_get_contents($imageurl);
                        $savefile = fopen($uploadfile, 'w');
                        fwrite($savefile, $contents);
                        fclose($savefile);
                    */

                    /* reliable method for downloading images */
                    $authority_url = explode("/", $imageurl)[2];
                    $fp = fopen ($uploadfile, 'w+');
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $imageurl);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_FILE, $fp);
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        'authority' => $authority_url,
                        'accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
                        'accept-language' => 'en-US,en;q=0.9',
                        'cache-control' => 'no-cache',
                        'dnt' => '1',
                        'pragma' => 'no-cache',
                        'sec-fetch-dest' => 'document',
                        'sec-fetch-mode' => 'navigate',
                        'sec-fetch-site' => 'none',
                        'sec-fetch-user' => '?1',
                        'upgrade-insecure-requests' => '1',
                        'user-agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/103.0.0.0 Safari/537.36',
                        'Accept-Encoding' => 'gzip',
                    ]);
                    $response = curl_exec($ch);
                    curl_close($ch);
                    fclose($fp);
                    
                    $wp_filetype = wp_check_filetype(basename($filename), null );
                    $attachment = array(
                        'post_mime_type' => $wp_filetype['type'],
                        'post_title' => $filename,
                        'post_content' => '',
                        'post_status' => 'inherit'
                    );
                    
                    $attach_id = wp_insert_attachment( $attachment, $uploadfile );
                    $attachIds[] = $attach_id;
                    $imagenew = get_post( $attach_id );
                    $fullsizepath = get_attached_file( $imagenew->ID );
                    $attach_data = wp_generate_attachment_metadata( $attach_id, $fullsizepath );
                    wp_update_attachment_metadata( $attach_id, $attach_data ); 
                }

                /* attach images */
                if (!empty($attachIds))
                {
                    $thumbnail = $attachIds[0];
                    $updattachids = array_shift($attachIds);
                    add_post_meta( $product_id, "_thumbnail_id", $thumbnail );
                    add_post_meta( $product_id, "_product_image_gallery", join(",",$attachIds) );
                }
            }

            /* document attachment tab */
            if (isset($attachtab) && $attachtab != "")
            {
                $status = get_post_meta($product_id, '_woodmart_product_custom_tab_title' );
                if (empty($status)) {
                    add_post_meta( $product_id, "_woodmart_product_custom_tab_title", "Document Attachments" );
                }

                $status = get_post_meta($product_id, '_woodmart_product_custom_tab_content' );
                if (empty($status)) {
                    add_post_meta( $product_id, "_woodmart_product_custom_tab_content", $attachtab );
                }
            }

            /* add yoast canonical url */
            if (isset($data['canonical_url'])) {
                $status = get_post_meta($product_id, '_yoast_wpseo_canonical' );
                if (empty($status)) {
                    add_post_meta( $product_id, "_yoast_wpseo_canonical", $data['canonical_url'] );
                }
            }

            /* add yoast meta title */
            if (isset($data['meta_title'])) {
                $status = get_post_meta($product_id, '_yoast_wpseo_title' );
                if (empty($status)) {
                    add_post_meta( $product_id, "_yoast_wpseo_title", $data['meta_title'] );
                }
            }

            /* add yoast meta description */
            if (isset($data['meta_description'])) {
                $status = get_post_meta($product_id, '_yoast_wpseo_metadesc' );
                if (empty($status)) {
                    add_post_meta( $product_id, "_yoast_wpseo_metadesc", $data['meta_description'] );
                }
            }

            /* add yoast focus keywords */
            if (isset($data['focus_keyword'])) {
                $status = get_post_meta($product_id, '_yoast_wpseo_focuskw' );
                if (empty($status)) {
                    add_post_meta( $product_id, "_yoast_wpseo_focuskw", $data['focus_keyword'] );
                }
            }

            /* add yith brand */
            if (isset($data['yith_brand'])) {
                $brandid = term_exists($data['yith_brand'], "yith_product_brand");
                wp_set_object_terms( $product_id, intval($brandid['term_id']), "yith_product_brand" );
            }

            $response = new WP_REST_Response( array("product_id" => $product_id, "attachIds" => $attachIds, "post_data" => $post_data) );
            $response->set_status(200);
            return $response;
        
        } else {

            /* product already exists */
            $product_id = $post_status->ID;
            $product = wc_get_product($product_id);
            $attributes = $product->attributes;
            if (isset($attributes) && empty($attributes)) {
                /* update simple product basic details */
                $modifiers = array("sku","price","sale_price","regular_price","purchase_note","sold_individually");
                foreach ($modifiers as $modifier)
                {
                    if (isset($data[$modifier]))
                    {
                        $status = get_post_meta($product_id, '_'.$modifier );
                        if (empty($status)) {
                            add_post_meta( $product_id, '_'.$modifier, $data[$modifier] );
                        } else {
                            update_post_meta( $product_id, '_'.$modifier, $data[$modifier] );                    
                        }
                    }
                }
            } else {
                /* variable product */
            }

            $response = new WP_REST_Response( array("product_id" => $product_id) );
            $response->set_status(200);
            return $response;
        
        }
    }
endif;

if ( ! function_exists('register_woo_extended') ) :
    function register_woo_extended()
    {
        register_rest_route(
            'extwoo',
            '/searchslug/(?P<ptype>([a-z]+))/(?P<slug>[a-z0-9\-_]+)',
            array(
                'methods'=> WP_REST_Server::READABLE,
                'callback'=> 'search_woo_slug',
                'permission_callback' => '__return_true',
            )
        );

        register_rest_route(
            'extwoo',
            '/add-variable-product',
            array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => 'add_variable_product',
                'permission_callback' => '__return_true',
            )
        );
    }
endif;

add_action( 'rest_api_init', 'register_woo_extended' );

/* ##################################################### */
