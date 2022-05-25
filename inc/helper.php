<?php

if ( ! function_exists('etrs_sanitize_array') ) {
    /**
     * It sanitize a multi-dimensional array
     * @param array &$array The array of the data to sanitize
     * @return mixed
     */
    function etrs_sanitize_array( &$array )
    {
        foreach ( $array as &$value ) {
            if ( ! is_array( $value ) ) {
                // sanitize if value is not an array
                $value = sanitize_text_field( $value );
            } else {
                // go inside this function again
                etrs_sanitize_array( $value );
            }
        }
        return $array;
    }
}