<?php
/*
Plugin Name: Descripción por Categorías
Plugin URI: https://github.com/iflorido/descripcion-categorias-WP
Description: Añade descripciones superior e inferior en categorías de entradas.
Version: 1.0.4
Author: Ignacio Florido - iflorido@gmail.com
Author URI: https://cv.iflorido.es
Update URI: https://github.com/iflorido/descripcion-categorias-WP
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Mostrar campos al crear categoría
add_action( 'category_add_form_fields', function() {
    ?>
    <div class="form-field">
        <label for="topdesc"><?php _e( 'Descripción superior', 'descripciones-categorias' ); ?></label>
        <?php
        wp_editor( '', 'topdesc', [
            'textarea_name' => 'topdesc',
            'quicktags' => ['buttons' => 'em,strong,link'],
            'tinymce' => true,
            'editor_css' => '<style>#wp-topdesc-editor-container .wp-editor-area{height:75px; width:80%;}</style>',
        ] );
        ?>
        <p class="description"><?php _e( 'Este texto se mostrará encima del listado de entradas.', 'descripciones-categorias' ); ?></p>
    </div>

    <div class="form-field">
        <label for="seconddesc"><?php _e( 'Descripción inferior', 'descripciones-categorias' ); ?></label>
        <?php
        wp_editor( '', 'seconddesc', [
            'textarea_name' => 'seconddesc',
            'quicktags' => ['buttons' => 'em,strong,link'],
            'tinymce' => true,
            'editor_css' => '<style>#wp-seconddesc-editor-container .wp-editor-area{height:75px; width:80%;}</style>',
        ] );
        ?>
        <p class="description"><?php _e( 'Este texto se mostrará debajo del listado de entradas.', 'descripciones-categorias' ); ?></p>
    </div>
    <?php
}, 10, 2 );

// Mostrar campos al editar categoría
add_action( 'category_edit_form_fields', function( $term ) {
    $topdesc = htmlspecialchars_decode( get_term_meta( $term->term_id, 'topdesc', true ) );
    $second_desc = htmlspecialchars_decode( get_term_meta( $term->term_id, 'seconddesc', true ) );
    ?>
    <tr class="form-field">
        <th scope="row"><label for="topdesc"><?php _e( 'Descripción superior', 'descripciones-categorias' ); ?></label></th>
        <td>
            <?php
            wp_editor( $topdesc, 'topdesc', [
                'textarea_name' => 'topdesc',
                'quicktags' => ['buttons' => 'em,strong,link'],
                'tinymce' => true,
                'editor_css' => '<style>#wp-topdesc-editor-container .wp-editor-area{height:125px; width:100%;}</style>',
            ] );
            ?>
            <p class="description"><?php _e( 'Este texto se mostrará encima del listado de entradas.', 'descripciones-categorias' ); ?></p>
        </td>
    </tr>

    <tr class="form-field">
        <th scope="row"><label for="seconddesc"><?php _e( 'Descripción inferior', 'descripciones-categorias' ); ?></label></th>
        <td>
            <?php
            wp_editor( $second_desc, 'seconddesc', [
                'textarea_name' => 'seconddesc',
                'quicktags' => ['buttons' => 'em,strong,link'],
                'tinymce' => true,
                'editor_css' => '<style>#wp-seconddesc-editor-container .wp-editor-area{height:125px; width:100%;}</style>',
            ] );
            ?>
            <p class="description"><?php _e( 'Este texto se mostrará debajo del listado de entradas.', 'descripciones-categorias' ); ?></p>
        </td>
    </tr>
    <?php
}, 10, 2 );

// Guardar campos
add_action( 'edit_term', 'guardar_campos_categoria', 10, 3 );
add_action( 'created_term', 'guardar_campos_categoria', 10, 3 );
function guardar_campos_categoria( $term_id, $tt_id = '', $taxonomy = '' ) {
    if ( 'category' === $taxonomy ) {
        if ( isset( $_POST['topdesc'] ) ) {
            update_term_meta( $term_id, 'topdesc', wp_kses_post( $_POST['topdesc'] ) );
        }
        if ( isset( $_POST['seconddesc'] ) ) {
            update_term_meta( $term_id, 'seconddesc', wp_kses_post( $_POST['seconddesc'] ) );
        }
    }
}

// Mostrar descripciones en el archivo de categoría
add_action( 'archive_template', function( $template ) {
    if ( is_category() ) {
        add_action( 'loop_start', 'mostrar_desc_superior_cat' );
        add_action( 'loop_end', 'mostrar_desc_inferior_cat' );
    }
    return $template;
});

function mostrar_desc_superior_cat() {
    if ( is_category() ) {
        $term = get_queried_object();
        $topdesc = get_term_meta( $term->term_id, 'topdesc', true );
        if ( $topdesc ) {
            echo '<div class="term-description term-description-top">';
            echo wp_kses_post( wpautop( $topdesc ) );
            echo '</div><div class="seccionarticulo">';
        }
    }
}

function mostrar_desc_inferior_cat() {
    if ( is_category() ) {
        $term = get_queried_object();
        $seconddesc = get_term_meta( $term->term_id, 'seconddesc', true );
        if ( $seconddesc ) {
            echo '</div><div class="term-description term-description-bottom">';
            echo wp_kses_post( wpautop( $seconddesc ) );
            echo '</div>';
        }
    }
}

add_filter( 'site_transient_update_plugins', 'iflorido_check_for_plugin_update' );
add_filter( 'plugins_api', 'iflorido_plugin_info', 20, 3 );

function iflorido_check_for_plugin_update( $transient ) {
    if ( empty($transient->checked) ) return $transient;

    $plugin_slug = 'descripcion-categorias-wp';
    $plugin_file = $plugin_slug . '/' . $plugin_slug . '.php';
    $github_repo = 'iflorido/descripcion-categorias-WP';

    $response = wp_remote_get("https://api.github.com/repos/$github_repo/releases/latest");

    if (
        is_wp_error($response) ||
        wp_remote_retrieve_response_code($response) !== 200
    ) {
        return $transient;
    }

    $release = json_decode(wp_remote_retrieve_body($response));
    if ( version_compare($release->tag_name, $transient->checked[$plugin_file], '>') ) {
        $transient->response[$plugin_file] = (object)[
            'slug'        => $plugin_slug,
            'plugin'      => $plugin_file,
            'new_version' => $release->tag_name,
            'package' => "https://github.com/iflorido/descripcion-categorias-WP/releases/download/{$release->tag_name}/descripcion-categorias-wp-{$release->tag_name}.zip",
            'url'         => "https://github.com/$github_repo",
        ];
    }

    return $transient;
}

function iflorido_plugin_info($result, $action, $args) {
    $plugin_slug = 'descripcion-categorias-wp';

    if ($action !== 'plugin_information' || $args->slug !== $plugin_slug) {
        return false;
    }

    $github_repo = 'iflorido/descripcion-categorias-WP';

    // Obtener datos del repositorio
    $repo_response = wp_remote_get("https://api.github.com/repos/$github_repo");
    if ( is_wp_error($repo_response) ) return false;
    $repo_data = json_decode(wp_remote_retrieve_body($repo_response));

    // Obtener la última release para la versión
    $release_response = wp_remote_get("https://api.github.com/repos/$github_repo/releases/latest");
    $release_data = !is_wp_error($release_response) ? json_decode(wp_remote_retrieve_body($release_response)) : null;
    $version = $release_data->tag_name ?? '1.0.4';

    return (object)[
        'name' => $repo_data->name,
        'slug' => $plugin_slug,
        'version' => $version,
        'author' => '<a href="https://cv.iflorido.es">Ignacio Florido - iflorido@gmail.com</a>',
        'homepage' => $repo_data->html_url,
        'download_link' => "https://github.com/$github_repo/releases/download/{$version}/descripcion-categorias-wp-{$version}.zip",
        'sections' => [
            'description' => $repo_data->description,
            'changelog' => 'Ver cambios en el repositorio.',
        ],
    ];
}