<?php

/**
 * @Project NUKEVIET 3.0
 * @Author VINADES.,JSC (contact@vinades.vn)
 * @Copyright (C) 2010 VINADES.,JSC. All rights reserved
 * @Createdate 2-9-2010 14:43
 */
if ( ! defined( 'NV_IS_FILE_ADMIN' ) ) die( 'Stop!!!' );

$CKEditorFuncNum = $nv_Request->get_string( 'CKEditorFuncNum', 'post,get', 0 );
$imgfolder = nv_check_path_upload( $nv_Request->get_string( 'currentpath', 'post,get' ) );
$check_allow_upload_dir = nv_check_allow_upload_dir( $imgfolder );

if ( ! isset( $check_allow_upload_dir['upload_file'] ) )
{
    echo "<script type=\"text/javascript\">window.parent.CKEDITOR.tools.callFunction(" . $CKEditorFuncNum . ", '', '" . $lang_module['notlevel'] . "');</script>";
    exit;
}

if ( ! isset( $_FILES, $_FILES['upload'], $_FILES['upload']['tmp_name'] ) )
{
    echo "<script type=\"text/javascript\">window.parent.CKEDITOR.tools.callFunction(" . $CKEditorFuncNum . ", '', '" . $lang_module['errorNotSelectFile'] . "');</script>";
    exit;
}

$type = $nv_Request->get_string( 'type', 'post,get' );
$allow_files_type = array();
if ( $type == "image" and in_array( 'images', $admin_info['allow_files_type'] ) )
{
    $allow_files_type = array( 'images' );
} elseif ( $type == "flash" and in_array( 'flash', $admin_info['allow_files_type'] ) )
{
    $allow_files_type = array( 'flash' );
}

if ( empty( $allow_files_type ) )
{
    echo "<script type=\"text/javascript\">window.parent.CKEDITOR.tools.callFunction(" . $CKEditorFuncNum . ", '', '" . $lang_module['notlevel'] . "');</script>";
    exit;
}

require_once ( NV_ROOTDIR . "/includes/class/upload.class.php" );
$upload = new upload( $allow_files_type, $global_config['forbid_extensions'], $global_config['forbid_mimes'], NV_UPLOAD_MAX_FILESIZE, NV_MAX_WIDTH, NV_MAX_HEIGHT );
$upload_info = $upload->save_file( $_FILES['upload'], NV_ROOTDIR . '/' . $imgfolder, false );

if ( ! empty( $upload_info['error'] ) )
{
    echo "<script type=\"text/javascript\">window.parent.CKEDITOR.tools.callFunction(" . $CKEditorFuncNum . ", '', '" . $upload_info['error'] . "');</script>";
    exit;
}

$file = $upload_info['basename'];
$results = array();

$md5 = md5( $imgfolder );
$tempFile = NV_ROOTDIR . "/" . NV_FILES_DIR . "/dcache/" . $md5;

if ( file_exists( $tempFile ) )
{
    $results = file_get_contents( $tempFile );
    $results = unserialize( $results );
}

$results[$file] = array();
$results[$file][0] = $file;

$max = 16;
if ( strlen( $file ) > $max )
{
    preg_match( "/^(.+)\.([a-zA-Z0-9]+)$/", $file, $matches );
    $results[$file][0] = substr( $matches[1], 0, ( $max - 3 - strlen( $matches[2] ) ) ) . "..." . $matches[2];
}

$results[$file][1] = $upload_info['ext'];
$results[$file][2] = "file";

$filesize = @filesize( $upload_info['name'] );
$results[$file][3] = nv_convertfromBytes( $filesize );

$results[$file][4] = NV_BASE_SITEURL . 'images/file.gif';
$results[$file][5] = 32;
$results[$file][6] = 32;
$results[$file][7] = "|";

if ( $upload_info['is_img'] and $upload_info['ext'] != "swf" )
{
    $results[$file][2] = "image";
    $results[$file][4] = NV_BASE_SITEURL . $imgfolder . '/' . $file;
    $results[$file][5] = $upload_info['img_info'][0];
    $results[$file][6] = $upload_info['img_info'][1];
    $results[$file][7] = $upload_info['img_info'][0] . "|" . $upload_info['img_info'][1];

    if ( $upload_info['img_info'][0] > 80 or $upload_info['img_info'][1] > 80 )
    {
        if ( ( $_src = nv_get_viewImage( $imgfolder . '/' . $file, 80, 80 ) ) !== false )
        {
            $results[$file][4] = NV_BASE_SITEURL . $_src[0];
            $results[$file][5] = $_src[1];
            $results[$file][6] = $_src[2];
        }
        else
        {
            if ( $results[$file][5] > 80 )
            {
                $results[$file][6] = round( 80 / $results[$file][5] * $results[$file][6] );
                $results[$file][5] = 80;
            }

            if ( $results[$file][6] > 80 )
            {
                $results[$file][5] = round( 80 / $results[$file][6] * $results[$file][5] );
                $results[$file][6] = 80;
            }
        }
    }
} elseif ( in_array( $upload_info['ext'], $array_flash ) )
{
    $results[$file][2] = "flash";
    $results[$file][4] = NV_BASE_SITEURL . 'images/flash.gif';

    if ( $upload_info['ext'] == "swf" )
    {
        $results[$file][7] = $upload_info['img_info'][0] . "|" . $upload_info['img_info'][1];
    }
}

$results[$file][8] = $admin_info['userid'];
$results[$file][9] = NV_CURRENTTIME;

ksort( $results );
file_put_contents( $tempFile, serialize( $results ) );

nv_insert_logs( NV_LANG_DATA, $module_name, $lang_module['upload_file'], $imgfolder . "/" . $file, $admin_info['userid'] );

echo "<script type=\"text/javascript\">window.parent.CKEDITOR.tools.callFunction(" . $CKEditorFuncNum . ", '" . NV_BASE_SITEURL . $imgfolder . "/" . $file . "', '');</script>";
exit;

?>