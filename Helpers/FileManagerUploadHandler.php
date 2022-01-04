<?php


namespace Artgris\Bundle\FileManagerBundle\Helpers;


class FileManagerUploadHandler extends \UploadHandler {

    protected function get_unique_filename($file_path, $name, $size, $type, $error,
        $index, $content_range) {
        if ($this->options['override']) {
            return $name;
        }

        while (is_dir($this->get_upload_path($name))) {
            $name = $this->upcount_name($name);
        }
        // Keep an existing filename if this is part of a chunked upload:
        $uploaded_bytes = $this->fix_integer_overflow((int)@$content_range[1]);
        while (is_file($this->get_upload_path($name))) {
            if ($uploaded_bytes === $this->get_file_size(
                    $this->get_upload_path($name)
                )) {
                break;
            }
            $name = $this->upcount_name($name);
        }

        return $name;
    }

}