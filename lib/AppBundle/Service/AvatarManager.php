<?php


namespace AppBundle\Service;


class AvatarManager
{
    public static function validateFilename( $name ) {
        $file = array(
            'name' => '',
            'type' => ''
        );
        // the file extentions must be exactly png or svg
        if ( $name && strrpos( $name, 'png', -3 ) !== false   ) {
            list( $file['name'], $file['type'] ) = explode( '.', $name );

        } else {
            $file['name'] = 'invalid';
            $file['type'] = 'invalid';
        }
        return $file;
    }

    public static function sanitizeDownloadingName( $name ) {
        //Strip out any % encoded octets
        $sanitized = preg_replace( '|%[a-fA-F0-9][a-fA-F0-9]|', '', $name );
        //Limit to A-Z,a-z,0-9,_,-
        $sanitized = preg_replace( '/[^A-Za-z0-9_-]/', '', $sanitized );
        return $sanitized;
    }
    public static function validateImagedata( $data, $filetype ) {
        if ( $filetype === 'png' ) {
                if ( ( substr( $data, 0, 22 ) ) !== 'data:image/png;base64,' ) {
                        // doesn't contain the expected first 22 characters
                        return false;
                }
                $base64 = str_replace('data:image/png;base64,', '', $data);
                if ( ( base64_encode( base64_decode( $base64, true ) ) ) !== $base64) {
                        // decoding and re-encoding the data fails
                        return false;
                }
                // all is fine
                return $base64;
        } else {
                return false;
        }
}

}