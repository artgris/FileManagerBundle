<?php


namespace Artgris\Bundle\FileManagerBundle\Helpers;


use Symfony\Component\String\Slugger\AsciiSlugger;

class FileManagerUploadHandler extends UploadHandler {

    /**
     * Creates one image version (upload.image_versions) of a file already stored in upload_dir,
     * without an upload request (used by the artgris:filemanager:thumbnails command).
     */
    public function createVersion(string $fileName, string $version): bool {
        return (bool) $this->create_scaled_image($fileName, $version, $this->options['image_versions'][$version]);
    }

    protected function get_full_url(): string {
        // On the console there is no HTTP host to build the URL from, and the URL is not needed there
        if (!isset($_SERVER['HTTP_HOST']) && !isset($_SERVER['SERVER_NAME'])) {
            return '';
        }

        return parent::get_full_url();
    }

    protected function get_server_var($id) {
        // A missing key (e.g. HTTP_REFERER on the console) gave null, which string functions no longer accept
        return $_SERVER[$id] ?? '';
    }

    protected function basename($filepath, $suffix = '') {
        // The parent passes its default null suffix to basename(), deprecated since PHP 8.1
        return parent::basename($filepath, $suffix);
    }

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