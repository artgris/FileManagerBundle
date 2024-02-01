<?php


namespace Artgris\Bundle\FileManagerBundle\Helpers;


use Symfony\Component\String\Slugger\AsciiSlugger;

class FileManagerUploadHandler extends UploadHandler {

    protected function get_unique_filename($file_path, $name, $size, $type, $error,
                                           $index, $content_range) {

        $name = $this->set_filename_sanitizer($name);

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

    protected function set_filename_sanitizer($name): string
    {

        if(!isset($this->options['filename_sanitizer']['slugger']) || $this->options['filename_sanitizer']['slugger'] !== true){
            return $name;
        }

        $file_extension = pathinfo($name, PATHINFO_EXTENSION);
        $new_file_name = str_replace('.' . $file_extension, '', $name);

        $slugger = new AsciiSlugger();

        if (isset($this->options['filename_sanitizer']['prepend'])) {
            $new_file_name = $slugger->slug($new_file_name)->prepend($this->options['filename_sanitizer']['prepend']);
        }

        if (isset($this->options['filename_sanitizer']['append'])) {
            $new_file_name = $slugger->slug($new_file_name)->append($this->options['filename_sanitizer']['append']);
        }

        if (isset($this->options['filename_sanitizer']['transformer'])) {
            $new_file_name = match ($this->options['filename_sanitizer']['transformer']) {
                'uppercase' => $slugger->slug($new_file_name)->upper(),
                'lowercase' => $slugger->slug($new_file_name)->lower(),
                'camel' => $slugger->slug($new_file_name)->camel(),
                'snake' => $slugger->slug($new_file_name)->snake(),
                'cameltitle' => $slugger->slug($new_file_name)->camel()->title(),
                'title' => $slugger->slug($new_file_name)->title(),
                'titleall' => $slugger->slug($new_file_name)->title(true),
                'folded' => $slugger->slug($new_file_name)->folded(),
                default => $slugger->slug($new_file_name),
            };
        } else {
            $new_file_name = $slugger->slug($new_file_name);
        }


        return $new_file_name  . "." . $file_extension;
    }

}