<?php
/**
 * Plugin Name: Simple 301 Article Redirect
 * Description: A Very Simple 301 Article Redirection.
 * Version: 1.0
 * Author: Brantell
 * Author URI: https://brantell.com
 **/
function Redirect301_VALIDATE( $url ) {
    $url = esc_url($url,null,null);
    return preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $url);
}

add_action( 'template_redirect', 'Redirect301' );

function Redirect301 (){
    global $wp_query, $wp_query_saved;
    $postid = get_queried_object_id();
    $redirect = esc_url(get_post_meta($postid, 'redirect', true),null,null);
    if(!empty($redirect)){
        if(Redirect301_VALIDATE($redirect)) {
            header("HTTP/1.1 301 Moved Permanently");
            header("Location: " . $redirect);
            exit();
        }
    }
}

add_action( 'add_meta_boxes', 'Redirect301MetaBoxWrapper',2 );
function Redirect301MetaBoxWrapper()
{
    add_meta_box('Redirect301MB', "Redirect 301", 'Redirect301MetaBox', 'post','side');
}

function Redirect301MetaBox( $post ) {

    wp_nonce_field( plugin_basename( __FILE__ ), 'R310_TO_ADDRESS_NONCE' );

    $option = get_post_meta($post->ID, 'redirect', true) ? get_post_meta($post->ID, 'redirect', true): "";
    echo '<label for="R310_TO_ADDRESS" style="font-weight:600;margin-bottom: 10px; margin-top:10px; display: block">';
    echo "Redirect to this URL";
    echo '</label> ';
    echo '<input type="url" style="display:block; width:100%; padding:5px 10px; font-size:18px;" placeholder="https://" id="R310_TO_ADDRESS" name="R310_TO_ADDRESS" value="'.($option).'" />';
}

add_action( 'save_post', 'Redirect301SaveSettings' );
function Redirect301SaveSettings( $post_id ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
        return;

    if ( !wp_verify_nonce( $_POST['R310_TO_ADDRESS_NONCE'], plugin_basename( __FILE__ ) ) )
        return;

    if ( 'page' == $_POST['post_type'] )
    {
        if ( !current_user_can( 'edit_page', $post_id ) )
            return;
    } else {
        if ( !current_user_can( 'edit_post', $post_id ) )
            return;
    }

    if (isset($_POST['R310_TO_ADDRESS'])){
        $option = sanitize_text_field(trim($_POST['R310_TO_ADDRESS']));
        update_post_meta($post_id,'redirect',$option);
    }

}

function Redirect301ColumnHead($columns){
    $columns['redirection'] = 'Redirection';
    return $columns;
}
add_filter('manage_posts_columns', 'Redirect301ColumnHead');

function Redirect301ColumnContent($column_name, $post_ID) {
    if ($column_name == 'redirection') {
        $redirect = get_post_meta($post_ID, 'redirect', true);
        if(!empty($redirect)) {
            if (Redirect301_VALIDATE($redirect)) {
                echo "Yes";
                echo "<br /><hr />";
                echo "<div style='font-size:10px; line-height: 12px;'>{$redirect}</div>";
            }else {
                echo "No - Invalid URL";
                echo "<br /><hr />";
                echo "<div style='font-size:10px; line-height: 12px;'>{$redirect}</div>";
            }
        }else{ echo "No"; }
    }
}
add_action('manage_posts_custom_column', 'Redirect301ColumnContent', 10, 2);

function Redirect301ColumnHeadSortable( $columns ) {
    $columns['redirection'] = 'redirection';
    return $columns;
}
add_filter( 'manage_edit-post_sortable_columns', 'Redirect301ColumnHeadSortable' );
function Redirect301ColumnFilter() {
    global $typenow;
    global $wp_query;
    $selected = isset($_GET["redirection"]) ? sanitize_text_field($_GET["redirection"]) : "all";
   ?>
    <select name="redirection" id="redirection">
        <option value="all" <?php if($selected == "all") echo "selected" ?>>All Redirection</option>
        <option value="yes" <?php if($selected == "yes") echo "selected" ?>>Redirected</option>
        <option value="no" <?php if($selected == "no") echo "selected" ?>>Not Redirected</option>
    </select>
<?php
}
add_action( 'restrict_manage_posts', 'Redirect301ColumnFilter' );

function Redirect301ColumnFilterQuery( $query ) {
    global $pagenow;
    $value = isset( $_GET['redirection'] ) ? sanitize_text_field($_GET['redirection']) : 'all';
    if ( is_admin() && $pagenow=='edit.php' && $value != 'all') {
        if($value == "no") {
            $meta_query = array(
                'relation' => 'OR',
                array(
                    'key' => 'redirect',
                    'compare' => 'NOT EXISTS', // see note above
                ),
                array(
                    'key' => 'redirect',
                    'compare' => "=",
                    'value' => ''
                ),
                array(
                    'key' => 'redirect',
                    'compare' => "NOT LIKE",
                    'value' => 'http'
                ),
            );
        }
        if($value == "yes") {
            $meta_query = array(
                array(
                    'key' => 'redirect',
                    'compare' => "LIKE",
                    'value' => 'http'
                )
            );
        }
        $query->set( 'meta_query', $meta_query );
    }
}
add_filter( 'parse_query', 'Redirect301ColumnFilterQuery' );