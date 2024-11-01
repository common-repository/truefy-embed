<?php

/*
Plugin Name: Truefy Embed
Plugin URI: https://embed.truefy.ai/
Description: Your visual assets are valuable.Secure them by adding critical details directly into the pixels using our AI powered Invisible watermarking technology
Version: 1.1.0
Author: Truefy
License:  GNUGPLv3
*/
// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
    exit;

$PLUGIN_VERSION = '1.1.0';


register_activation_hook( __FILE__, 'truefy_embed_activate' );

function truefy_embed_activate(){
    update_option('truefy_embed_auto_watermarking_enabled','no');
    update_option('truefy_embed_auto_visible_self_brand_watermarking_enabled','no');
    update_option('truefy_embed_api_key','NO_API_KEY');
}

register_deactivation_hook( __FILE__, 'truefy_embed_activate' )
;





// Hook in the options page
add_action('admin_menu', 'truefy_embed_options_page');

// Hook the function to the upload handler
add_filter('wp_handle_upload', 'truefy_embed_upload_watermark');
// save_post hook to get captions
add_action('save_post','truefy_embed_save_post_hook');
// edit_attachments hooks for manual watermarking
add_action("edit_attachment", "truefy_embed_save_custom_meta_box_attachment", );


// Metabox
add_action( 'add_meta_boxes', 'truefy_embed_add_metabox' );

function truefy_embed_save_custom_meta_box_attachment($post_id)
{
    truefy_embed_error_log('save cutom metabox fired');
    if (!isset($_POST["truefy-meta-box-nonce"]) || !wp_verify_nonce($_POST["truefy-meta-box-nonce"], basename(__FILE__)))
        return $post_id;
//
//    if(!current_user_can("edit_post", $post_id))
//        return $post_id;

    if(defined("DOING_AUTOSAVE") && DOING_AUTOSAVE)
        return $post_id;

    $truefy_api_key = get_option('truefy_embed_api_key');
    $auto_visible_self_brand_watermarking_enabled = get_option('truefy_embed_auto_visible_self_brand_watermarking_enabled');
    $auto_visible_self_brand_watermarking_enabled = ($auto_visible_self_brand_watermarking_enabled == 'yes') ? true : false;
    if(isset($_POST["truefy-embed-apply-checkbox"]))
    {
        truefy_embed_error_log('truefy-embed-apply-watermark triggered');
        $fullsize_path = get_attached_file( $post_id );
        $image_old_uuid=get_post_meta($post_id,'truefy-old-watermarked',true);
        $content_disposition='attachment; filename='.basename($fullsize_path);
        if($auto_visible_self_brand_watermarking_enabled){
            $content_disposition='attachment; filename='.basename($fullsize_path).';visible_self_brand_watermarking=true';
        }
        // Re-watermarking image
        if($image_old_uuid!==""){
            $content_disposition='attachment; filename='.basename($fullsize_path).';image_old_uuid='.$image_old_uuid;
            if($auto_visible_self_brand_watermarking_enabled){
                $content_disposition='attachment; filename='.basename($fullsize_path).';image_old_uuid='.$image_old_uuid.';visible_self_brand_watermarking=true';
            }
        }
        truefy_embed_error_log($fullsize_path);
        truefy_embed_error_log($content_disposition);
        $url='https://api.truefy.ai/v1/integrations/wordpress/embed/uploadRaw';
        $image = file_get_contents( $fullsize_path );
        $mime = mime_content_type( $fullsize_path );
        truefy_embed_error_log($mime);
        $post_args= array(
            'headers' => array(
                'Content-Disposition' => $content_disposition,
                'Content-Type' => $mime,
                'x-api-key'=>$truefy_api_key
            ),
            'body' => $image
        ) ;
        $response = wp_remote_post($url,$post_args);
        $response=wp_remote_retrieve_body($response);
        truefy_embed_error_log(@$response);
        $json_response=json_decode($response,true);
        $image_url=$json_response['data']['image_url'];
        $image_uuid=$json_response['data']['image_uuid'];
        $ml_model_version = $json_response['data']['ml_model_version'];
        $tmp_file = download_url( $image_url );
        truefy_embed_error_log("--image-downloaded");
        copy($tmp_file,$fullsize_path);
        unlink( $tmp_file ); // must unlink afterwards
        truefy_embed_error_log("--image-saved");
        update_metadata( 'post', $post_id, 'truefy-is-watermarked', $image_uuid, '' );
        update_metadata('post', $post_id, 'truefy-ml-model-version', $ml_model_version, '');
        truefy_embed_error_log("**-end--resize-image-upload ".$image_uuid."\n");
        //Regenerate Thumbnails
        $attach_data = wp_generate_attachment_metadata( $post_id, $fullsize_path );
        wp_update_attachment_metadata( $post_id,  $attach_data );
        // Images are not rendering if the return is missing, when clicked on the media player, so commenting it out
//    apply_filters("wp_generate_attachment_metadata",$image_data,$image_uuid);
//    apply_filters("wp_generate_attachment_metadata",$image_data,$image_uuid);
//    $image_data['image_uuid']=$image_uuid;
//        add_filter('wp_generate_attachment_metadata',function ( $data ) use($image_uuid) {
//            truefy_embed_error_log("Applying metadata ".$image_uuid."\n");
//            truefy_embed_error_log($data);
//
//            $file_name=$data['file'];
//            $site_url=get_site_url();
//            truefy_embed_error_log($file_name);
//            truefy_embed_error_log($site_url);
//            $content_url=$site_url."/wp-content/uploads/".$file_name;
//            $attachment_id =  attachment_url_to_postid($content_url);
//            $post = get_post( $attachment_id );
//            $post_id = ( ! empty( $post ) ? (int) $post->post_parent : 0 );
//
//
////        update_post_meta( $attachment_id, $this->is_watermarked_metakey, 1 );
//            update_metadata( 'post', $attachment_id, 'truefy-is-watermarked', $image_uuid, '' );
//            truefy_embed_error_log("--Added Truefy Metadata-( ".$attachment_id."-".$image_uuid." )");
////    }
//
//            // pass forward attachment metadata , this is must, otherwise images are not rendering in Media Library
//            return $data;
//        });
    }
    if(isset($_POST["truefy-embed-remove-checkbox"]))
    {
        truefy_embed_error_log('truefy-embed-remove-watermark triggered');
        $fullsize_path = get_attached_file( $post_id );
        truefy_embed_error_log($fullsize_path);
        $image_uuid=get_post_meta($post_id,'truefy-is-watermarked',true);
        $api_key=$truefy_api_key;
        $request_body=json_encode(array('image_uuid'=>$image_uuid));
        truefy_embed_error_log($request_body);
        $url='https://api.truefy.ai/v1/integrations/wordpress/embed/removeWatermark';
        $post_args= array(
            'headers' => array(

                'Content-Type' => "application/json",
                'x-api-key'=>$api_key
            ),
            'body' => $request_body
        ) ;
        $response = wp_remote_post($url,$post_args);
        $response=wp_remote_retrieve_body($response);
        $json_response=json_decode($response,true);
        $original_image_url=$json_response['data']['original_image_url'];
        $tmp_file = download_url( $original_image_url );
        truefy_embed_error_log("--image-downloaded ".$original_image_url."\n");
        copy($tmp_file,$fullsize_path);
        unlink( $tmp_file ); // must unlink afterwards
        truefy_embed_error_log("--image-saved");
        update_metadata( 'post', $post_id, 'truefy-is-watermarked', null, '' );
        update_metadata('post', $post_id, 'truefy-ml-model-version', null, '');
        update_metadata('post', $post_id, 'truefy-visible-self-brand-watermark', null, '');
        update_metadata( 'post', $post_id, 'truefy-old-watermarked', $image_uuid, '' );
        truefy_embed_error_log("**-end--resize-image-upload ".$post_id."\n");
        //Regenerate Thumbnails
        $attach_data = wp_generate_attachment_metadata( $post_id, $fullsize_path );
        wp_update_attachment_metadata( $post_id,  $attach_data );

    }
    // Manual Visible Self Brand Watermark Modification request
    if(isset($_POST["truefy-embed-modify-vw-checkbox"]))
    {
        truefy_embed_error_log('truefy-embed-modify-vw-checkbox triggered');
        $image_uuid=get_post_meta($post_id,'truefy-is-watermarked',true);
        $fullsize_path = get_attached_file( $post_id );
        $request_body=json_encode(array('image_uuid'=>$image_uuid,'logo_watermark_location'=>$_POST["truefy-embed-vw-location"],
            'logo_watermark_opacity'=>$_POST["truefy-embed-vw-opacity"],
            'logo_watermark_resize_factor'=>$_POST["truefy-embed-vw-resize"],
            'logo_watermark_offset_x'=>$_POST['truefy-embed-vw-offset-x'],
            'logo_watermark_offset_y'=>$_POST['truefy-embed-vw-offset-y'],));
        $api_key=$truefy_api_key;

        truefy_embed_error_log($request_body);
        $url='https://api.truefy.ai/v1/integrations/wordpress/embed/modifyVisibleWatermark';
        $post_args= array(
            'headers' => array(

                'Content-Type' => "application/json",
                'x-api-key'=>$api_key
            ),
            'body' => $request_body
        ) ;
        $response = wp_remote_post($url,$post_args);
        $response=wp_remote_retrieve_body($response);
        $json_response=json_decode($response,true);
        $modified_image_url=$json_response['data']['url'];
        $tmp_file = download_url( $modified_image_url );
        truefy_embed_error_log("--image-downloaded ".$modified_image_url."\n");
        copy($tmp_file,$fullsize_path);
        unlink( $tmp_file ); // must unlink afterwards
        truefy_embed_error_log("--image-saved");
        update_metadata('post', $post_id, 'truefy-visible-self-brand-watermark', 'custom', '');
        truefy_embed_error_log("**-end--modify-visible-watermark ".$post_id."\n");
        //Regenerate Thumbnails
        $attach_data = wp_generate_attachment_metadata( $post_id, $fullsize_path );
        wp_update_attachment_metadata( $post_id,  $attach_data );

    }
    // Checking if caption is added for watermarked image
    $image_uuid=get_post_meta($post_id,'truefy-is-watermarked',true);
    if ($image_uuid!==""){
        truefy_embed_error_log("Caption Changed from Media Library");
        $image_data=get_post($post_id);
        truefy_embed_error_log($image_data);
        $image_caption=$image_data->post_excerpt;
        $file_name=$image_data->guid;
        $file_name=explode('/',$file_name);
        $file_name=end($file_name);
        $request_body=json_encode(array('image_uuid'=>$image_uuid,'metadata'=>array("image_caption"=>$image_caption,
            "file_name"=>$file_name)));
        truefy_embed_error_log($request_body);
        truefy_embed_api_post_image_metadata($truefy_api_key,$request_body);
    }

}




function truefy_embed_add_metabox() {

    add_meta_box(
        'truefy_embed_metabox', // metabox ID
        'Truefy Watermark', // title
        'truefy_embed_metabox_callback', // callback function
        'attachment', // post type or post types in array , we are dealing with attachments
        'side', // position (normal, side, advanced)
        'default' // priority (default, low, high, core)
    );

}

// it is a callback function which actually displays the content of the meta box
function truefy_embed_metabox_callback( $post ) {

    $post_mimetype=get_post_mime_type($post->ID);
    truefy_embed_error_log($post_mimetype);
    $valid_types = array('image/gif', 'image/png', 'image/jpeg', 'image/jpg');
    if (!in_array($post_mimetype, $valid_types)) {
        truefy_embed_error_log("--non-image-type-uploaded-( " . $post_mimetype . " )");
        ?>
        <h3>Watermark cannot be performed for this type of media</h3>
        <?php
        return;
    }
    $image_uuid=get_post_meta($post->ID,'truefy-is-watermarked',true);
    $ml_model_version=get_post_meta($post->ID,'truefy-ml-model-version',true);
    $visible_self_brand_watermark=get_post_meta($post->ID,'truefy-visible-self-brand-watermark',true);

    wp_nonce_field(basename(__FILE__), "truefy-meta-box-nonce");

    ?>
    <div>


        <!--        <label for="meta-box-checkbox">Check Box</label>-->
        <?php
        //        $checkbox_value = get_post_meta($object->ID, "meta-box-checkbox", true);

        if($image_uuid == "")
        {
            ?>
            <h3>No Watermark present</h3>
            <label for="truefy-embed-apply-checkbox">Apply Watermark</label>
            <input name="truefy-embed-apply-checkbox" type="checkbox" value="true">
            <?php
        }
        else
        {
            ?>
            <h3>Watermark is already applied</h3>
            <h4>Image UUID: <?php echo esc_textarea($image_uuid)?></h4>
            <h4>Model Version: <?php echo esc_textarea($ml_model_version)?></h4>
            <h4>Visible Brand Logo Watermark: <?php echo esc_textarea($visible_self_brand_watermark)?></h4>
            <div style="margin-bottom: 10px">
                <label for="truefy-embed-remove-checkbox">Remove Watermark</label>
                <input name="truefy-embed-remove-checkbox" type="checkbox" value="true">
                <br>
            </div>

            <!--            --><?php
//            if($visible_self_brand_watermark){
//                ?>
            <label for="truefy-embed-modify-vw-checkbox" >Modify/Add Visible Watermark</label>
            <input name="truefy-embed-modify-vw-checkbox" type="checkbox" value="true">
            <label for="truefy-embed-vw-location" style="display:block">Watermark Location:</label>
            <input list="truefy-embed-vw-locations" name="truefy-embed-vw-location" id="truefy-embed-vw-location" value="bottom-right-center">
            <datalist id="truefy-embed-vw-locations">
                <option value="top-left-corner">
                <option value="top-center">
                <option value="top-right-corner">
                <option value="left-center">
                <option value="center">
                <option value="right-center">
                <option value="bottom-left-corner">
                <option value="bottom-center">
                <option value="bottom-right-corner">
            </datalist>
            <label for="truefy-embed-vw-opacity" style="display:block">Watermark Opacity:</label>
            <input name="truefy-embed-vw-opacity" id="truefy-embed-vw-location" type="number" min="10" max="100" value="100">
            <label for="truefy-embed-vw-resize" style="display:block">Watermark Resize Factor:</label>
            <input name="truefy-embed-vw-resize" id="truefy-embed-vw-resize" type="number" min="10" max="100" value="100">
            <label for="truefy-embed-vw-offset-x" style="display:block">Watermark Offset X:</label>
            <input name="truefy-embed-vw-offset-x" id="truefy-embed-vw-offset-x" type="number" min="-500" max="500" value="0">
            <label for="truefy-embed-vw-offset-y" style="display:block">Watermark Offset Y:</label>
            <input name="truefy-embed-vw-offset-y" id="truefy-embed-vw-offset-y" type="number" min="-500" max="500" value="0">
            <?php
//            }

        }
        ?>
    </div>
    <?php


}

function truefy_embed_save_post_hook($post_id){
    truefy_embed_error_log('Save post trigged');
    truefy_embed_error_log($post_id);
    // Check to see if we are autosaving
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
        return;
    $is_autosave = wp_is_post_autosave( $post_id );
    $is_revision = wp_is_post_revision( $post_id );
    // Check to see if its a revision
    if ( $is_autosave || $is_revision ) {
        return;
    }

    $post_data=get_post($post_id);
    truefy_embed_error_log($post_data);
    $post_permalink=get_post_permalink($post_id);
    truefy_embed_error_log($post_permalink);
//    $images_data=get_attached_media('',$post_id); # this is not giving the figcaption, also the middle image is missing
//    truefy_embed_error_log($images_data);
    $blocks=parse_blocks($post_data->post_content);
    $truefy_api_key = get_option('truefy_embed_api_key');
//    truefy_embed_error_log($post_content);
    $request_body=array();
    foreach ( $blocks as $block ) {

        // Image block name
        if ( 'core/image' === $block['blockName'] ) {

            truefy_embed_error_log($block);
            $image_id=$block['attrs']['id'];
            $image_uuid=get_post_meta($image_id,'truefy-is-watermarked',true);
            $file_name=get_post_meta($image_id,'_wp_attached_file',true);
            $file_name=explode('/',$file_name);
            $file_name=end($file_name);
            truefy_embed_error_log($image_id);
//            $dom=new DOMDocument();
//            @$dom->loadHTML($block['innerHTML']);
//
//// stores all elements of figure
//            $figures = $dom->getElementsByTagName('figcaption');
//            truefy_embed_error_log($figures);
            $image_caption=wp_strip_all_tags($block['innerHTML']);
            truefy_embed_error_log($image_caption); // Caption
            // TODO create a hash of the metadata, only call API if there is any change in hash
            $request_body[] = array('image_uuid' => $image_uuid, 'metadata' => array("image_caption" => $image_caption,
                "source_url" => $post_permalink, "file_name" => $file_name,"wp_post_id"=>$image_id));
        }

    }
    if(count($request_body)!=0){
        $request_body_json=json_encode($request_body);
        truefy_embed_error_log($request_body_json);
        truefy_embed_api_post_image_metadata($truefy_api_key,$request_body_json);
    }
}
function truefy_embed_api_post_image_metadata($api_key, $request_body){



    $url='https://api.truefy.ai/v1/integrations/wordpress/embed/metadata';
    $post_args= array(
        'headers' => array(

            'Content-Type' => "application/json",
            'x-api-key'=>$api_key
        ),
        'body' => $request_body
    ) ;
    $response = wp_remote_post($url,$post_args);
    $response=wp_remote_retrieve_body($response);
    truefy_embed_error_log($response);


}



/**
 * Add the options page
 */
function truefy_embed_options_page(){
    if(function_exists('add_options_page')){
        add_options_page(
            'Truefy Embed',
            'Truefy Embed',
            'manage_options',
            'truefy_embed',
            'truefy_embed_options'
        );
    }
} // function truefy_embed_uploadresize_options_page(){



/**
 * Define the Options page for the plugin
 */
function truefy_embed_options(){

    if(isset($_POST['truefy_embed_options_update'])) {

        $auto_watermarking_enabled = trim(esc_sql(sanitize_text_field($_POST['auto_watermarking_enabled'])));

        $truefy_api_key = trim(esc_sql(sanitize_text_field($_POST['truefy_api_key'])));

        $auto_visible_self_brand_watermarking_enabled = trim(esc_sql(sanitize_text_field($_POST['auto_visible_self_brand_watermarking_enabled'])));


        if ($auto_watermarking_enabled == 'yes') {
            update_option('truefy_embed_auto_watermarking_enabled','yes'); }
        else {
            update_option('truefy_embed_auto_watermarking_enabled','no'); }

        if ($auto_visible_self_brand_watermarking_enabled == 'yes') {
            update_option('truefy_embed_auto_visible_self_brand_watermarking_enabled','yes'); }
        else {
            update_option('truefy_embed_auto_visible_self_brand_watermarking_enabled','no'); }


        if ($truefy_api_key==''){
            update_option('truefy_embed_api_key','NO_API_KEY');
        }else{
            update_option('truefy_embed_api_key',$truefy_api_key);
        }
        // Saving Settings in Truefy DB
        $request_body = json_encode(array('auto_watermarking_enabled' => $auto_watermarking_enabled,
            'auto_visible_self_brand_watermarking_enabled' => $auto_visible_self_brand_watermarking_enabled));

        $url='https://api.truefy.ai/v1/integrations/wordpress/embed/settings';
        $post_args= array(
            'headers' => array(

                'Content-Type' => "application/json",
                'x-api-key'=>$truefy_api_key
            ),
            'body' => $request_body
        ) ;
        $response = wp_remote_post($url,$post_args);
        $response=wp_remote_retrieve_body($response);
        truefy_embed_error_log($response);
        // esc_html is not needed
        echo wp_kses_post('<div id="message" class="updated fade"><p><strong>Options have been updated.</strong></p></div>');
    } // if



    // get options and show settings form
    $auto_watermarking_enabled = get_option('truefy_embed_auto_watermarking_enabled');
    $auto_visible_self_brand_watermarking_enabled = get_option('truefy_embed_auto_visible_self_brand_watermarking_enabled');
    $truefy_api_key = get_option('truefy_embed_api_key');


    ?>
    <style type="text/css">
        .resizeimage-button {
            color: #FFF;
            background: none repeat scroll 0% 0% #FC9A24;
            border-radius: 3px;
            display: inline-block;
            border-bottom: 4px solid #EC8A14;
            margin-right:5px;
            line-height:1.05em;
            text-align: center;
            text-decoration: none;
            padding: 9px 20px 8px;
            font-size: 15px;
            font-weight: bold;
            text-shadow: 0 -1px 1px rgba(0,0,0,0.2);
        }

        .resizeimage-button:active,
        .resizeimage-button:hover,
        .resizeimage-button:focus {
            background-color: #EC8A14;
            color: #FFF;
        }

        .media-upload-form div.error, .wrap div.error, .wrap div.updated {
            margin: 25px 0px 25px;
        }

    </style>

    <div class="wrap">
        <form method="post" accept-charset="utf-8">

            <h2><img src="<?php echo esc_url(plugins_url('/assets/Truefy_logo_wp.svg', __FILE__ )); ?>" style="float:right; border:1px solid #ddd;margin:0 0 15px 15px;width:100px; height:100px;" />Truefy Embed</h2>

            <div style="max-width:700px">
                <p>This plugin performs invisible watermarking on Images by sending the uploaded images to Truefy Servers.</p>
                <p> You will need a Truefy Embed Account to access the API Key required to use this service, you can get it from the Integration Settings Page in Truefy Embed website or by visiting
                    this url: <a href="https://embed.truefy.ai/settings/integrations">https://embed.truefy.ai/settings/integrations</a> </p>
                <p> If you don't already have a Truefy Embed account, you can request for a demo account by visiting this url:
                    <a href="https://embed.truefy.ai/company/contact?subject=requestDemo">https://embed.truefy.ai/company/contact?subject=requestDemo</a></p>


            </div>

            <hr style="margin-top:20px; margin-bottom:0;">
            <hr style="margin-top:1px; margin-bottom:40px;">


            <h3>Integration options</h3>
            <p style="max-width:700px">The following settings will be applied when calling the Truefy Embed APIs.</p>

            <table class="form-table">

                <tr>
                    <th scope="row">Auto Watermarking Enabled</th>
                    <td valign="top">
                        <select name="auto_watermarking_enabled" id="auto_watermarking_enabled">
                            <option value="no" label="no" <?php echo esc_textarea(($auto_watermarking_enabled == 'no') ? 'selected="selected"' : ''); ?>>NO - do not resize images</option>
                            <option value="yes" label="yes" <?php echo esc_textarea(($auto_watermarking_enabled == 'yes') ? 'selected="selected"' : ''); ?>>YES - resize large images</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <!--                    TODO if enabled, ask to upload brand watermark image if not already enabled-->
                    <th scope="row">Automatically Add Visible Brand Watermark to Images</th>
                    <td valign="top">
                        <select name="auto_visible_self_brand_watermarking_enabled" id="auto_visible_self_brand_watermarking_enabled">
                            <option value="no" label="no" <?php echo esc_textarea(($auto_visible_self_brand_watermarking_enabled == 'no') ? 'selected="selected"' : ''); ?>>NO - do not resize images</option>
                            <option value="yes" label="yes" <?php echo esc_textarea(($auto_visible_self_brand_watermarking_enabled == 'yes') ? 'selected="selected"' : ''); ?>>YES - resize large images</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Truefy WordPress API key</th>
                    <td>
                        <label for="truefy_api_key">API Key</label>
                        <input name="truefy_api_key"  id="truefy_api_key" class="large-text" type="text" value="<?php echo esc_textarea($truefy_api_key); ?>">
                    </td>
                </tr>

            </table>



            <hr style="margin-top:30px;">

            <p class="submit" style="margin-top:10px;border-top:1px solid #eee;padding-top:20px;">
                <input type="hidden" id="convert-bmp" name="convertbmp" value="no" />
                <input type="hidden" name="action" value="update" />
                <input id="submit" name="truefy_embed_options_update" class="button button-primary" type="submit" value="Update Options">
            </p>
        </form>

    </div>
    <?php
}




/**
 * This function will apply changes to the uploaded file
 * @param $image_data - contains file, url, type
 */
function truefy_embed_upload_watermark($image_data){


    truefy_embed_error_log("**-start--resize-image-upload");


    $auto_watermarking_enabled = get_option('truefy_embed_auto_watermarking_enabled');
    $auto_watermarking_enabled = ($auto_watermarking_enabled=='yes') ? true : false;
    $auto_visible_self_brand_watermarking_enabled = get_option('truefy_embed_auto_visible_self_brand_watermarking_enabled');
    $auto_visible_self_brand_watermarking_enabled = ($auto_visible_self_brand_watermarking_enabled == 'yes') ? true : false;

    $truefy_api_key = get_option('truefy_embed_api_key');



    if($auto_watermarking_enabled ) {

        $valid_types = array('image/gif', 'image/png', 'image/jpeg', 'image/jpg');

        if (empty($image_data['file']) || empty($image_data['type'])) {
            truefy_embed_error_log("--non-data-in-file-( " . print_r($image_data, true) . " )");
            return $image_data;
        } else if (!in_array($image_data['type'], $valid_types)) {
            truefy_embed_error_log("--non-image-type-uploaded-( " . $image_data['type'] . " )");
            return $image_data;
        }

        truefy_embed_error_log("--filename-( " . $image_data['file'] . " )");


        $local_file = $image_data['file']; //path to a local file on your server


        $url='https://api.truefy.ai/v1/integrations/wordpress/embed/uploadRaw';
        $image = file_get_contents( $local_file );
        $mime = mime_content_type( $local_file );
        truefy_embed_error_log($mime);
        $content_disposition='attachment; filename='.basename($local_file);
        // Re-watermarking image
        if($auto_visible_self_brand_watermarking_enabled){
            $content_disposition='attachment; filename='.basename($local_file).';visible_self_brand_watermarking=true';
        }
        $post_args= array(
            'headers' => array(
                'Content-Disposition' => $content_disposition,
                'Content-Type' => $mime,
                'x-api-key'=>$truefy_api_key
            ),
            'body' => $image
        ) ;
        $response = wp_remote_post($url,$post_args);
        $response=wp_remote_retrieve_body($response);
        truefy_embed_error_log(@$response);
        $json_response = json_decode($response, true);
        $image_url = $json_response['data']['image_url'];
        $image_uuid = $json_response['data']['image_uuid'];
        $ml_model_version = $json_response['data']['ml_model_version'];
        $tmp_file = download_url($image_url);
        truefy_embed_error_log("--image-downloaded");
        copy($tmp_file, $image_data['file']);
        unlink($tmp_file); // must unlink afterwards
        truefy_embed_error_log("--image-saved");

        // This is not working, since $attachment_id is always returned as Zero
//        $site_url=get_site_url();
//        $file_name=$image_data['file']; // /var/www/html/wp-content/uploads/2022/04/278133252_7406082889461687_5998719870559528969_n-7.jpg
//        $file_name=explode('/wp-content/uploads/',$file_name);
//        truefy_embed_error_log($file_name);
//        truefy_embed_error_log($site_url);
//        $content_url=$site_url."/wp-content/uploads/".$file_name[1];
//        truefy_embed_error_log($content_url);
//        $attachment_id =  attachment_url_to_postid($content_url);
//        update_metadata( 'post', $attachment_id, 'truefy-watermarked', 1, '' );
//        truefy_embed_error_log("--Added Truefy Metadata-( ".$attachment_id." )");

//    }

        truefy_embed_error_log("**-end--resize-image-upload " . $image_uuid . "\n");
        // Images are not rendering if the return is missing, when clicked on the media player, so commenting it out
//    apply_filters("wp_generate_attachment_metadata",$image_data,$image_uuid);
//    apply_filters("wp_generate_attachment_metadata",$image_data,$image_uuid);
//    $image_data['image_uuid']=$image_uuid;
        add_filter('wp_generate_attachment_metadata', function ($data) use ($image_uuid,$ml_model_version,$auto_visible_self_brand_watermarking_enabled) {
            truefy_embed_error_log("Applying metadata " . $image_uuid . "\n");
            truefy_embed_error_log($data);

            $file_name = $data['file'];
//            $site_url = get_site_url();
            $uploads_dir=wp_upload_dir();
            $uploads_url=$uploads_dir['baseurl'];
            truefy_embed_error_log($uploads_url);
            truefy_embed_error_log($file_name);
//            truefy_embed_error_log($site_url);
//            $content_url = $site_url . "/wp-content/uploads/" . $file_name;
            $content_url = $uploads_url. "/" . $file_name;
            $attachment_id = attachment_url_to_postid($content_url);
//            $post = get_post($attachment_id);
//            $post_id = (!empty($post) ? (int)$post->post_parent : 0);


//        update_post_meta( $attachment_id, $this->is_watermarked_metakey, 1 );
            update_metadata('post', $attachment_id, 'truefy-is-watermarked', $image_uuid, '');
            update_metadata('post', $attachment_id, 'truefy-ml-model-version', $ml_model_version, '');
            if($auto_visible_self_brand_watermarking_enabled){
                update_metadata('post', $attachment_id, 'truefy-visible-self-brand-watermark', 'default', '');
            }
            truefy_embed_error_log("--Added Truefy Metadata-( " . $attachment_id . "-" . $image_uuid . " )");
//    }

            // pass forward attachment metadata , this is must, otherwise images are not rendering in Media Library
            return $data;
        });
    }
    return $image_data;
}


/**
 * Simple debug logging function. Will only output to the log file
 * if 'debugging' is turned on.
 */
function truefy_embed_error_log($message) {
    $DEBUG_LOGGER=true;

    if($DEBUG_LOGGER) {
        error_log(print_r($message, true));
    }
}
