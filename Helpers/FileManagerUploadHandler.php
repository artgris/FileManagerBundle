<?php


namespace Artgris\Bundle\FileManagerBundle\Helpers;


class FileManagerUploadHandler extends UploadHandler {

    protected function get_unique_filename($file_path, $name, $size, $type, $error,
        $index, $content_range) {

        if ($this->options['override']) {
            return $name;
        }

        parent::get_unique_filename(
            $file_path, $name, $size, $type, $error,
            $index, $content_range
        );
    }

}