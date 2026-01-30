<?php
/**
 * Bild-Upload Helper für Lager- und Waffenbörse
 */

class ImageUpload {
    private $upload_dir;
    private $upload_type;
    private $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    private $max_file_size = 5242880; // 5MB
    private $max_width = 1920;
    private $max_height = 1920;

    public function __construct($type = 'storage') {
        $this->upload_type = $type;
        $this->upload_dir = __DIR__ . '/../uploads/' . $type . '/';
        if (!is_dir($this->upload_dir)) {
            mkdir($this->upload_dir, 0755, true);
        }
    }
    
    /**
     * Upload einzelnes Bild
     */
    public function uploadSingle($file, $prefix = '') {
        if (!isset($file['error']) || is_array($file['error'])) {
            throw new Exception('Invalid file upload');
        }
        
        // Fehlerprüfung
        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new Exception('File too large (max 5MB)');
            case UPLOAD_ERR_NO_FILE:
                throw new Exception('No file uploaded');
            default:
                throw new Exception('Upload error');
        }
        
        // Größenprüfung
        if ($file['size'] > $this->max_file_size) {
            throw new Exception('File too large (max 5MB)');
        }
        
        // Typ-Prüfung
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime_type = $finfo->file($file['tmp_name']);
        
        if (!in_array($mime_type, $this->allowed_types)) {
            throw new Exception('Invalid file type. Only JPG, PNG, GIF, WEBP allowed');
        }
        
        // Dateiname generieren
        $extension = $this->getExtension($mime_type);
        $filename = $prefix . '_' . uniqid() . '_' . time() . '.' . $extension;
        $filepath = $this->upload_dir . $filename;
        
        // Bild verarbeiten und speichern
        $this->processImage($file['tmp_name'], $filepath, $mime_type);

        return [
            'filename' => $filename,
            'filepath' => 'uploads/' . $this->upload_type . '/' . $filename,
            'filesize' => filesize($filepath)
        ];
    }
    
    /**
     * Upload mehrere Bilder
     */
    public function uploadMultiple($files, $prefix = '') {
        $uploaded = [];
        
        // Reformat files array
        $file_count = count($files['name']);
        for ($i = 0; $i < $file_count; $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            
            $file = [
                'name' => $files['name'][$i],
                'type' => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i]
            ];
            
            try {
                $uploaded[] = $this->uploadSingle($file, $prefix);
            } catch (Exception $e) {
                // Fehler loggen, aber weitermachen
                error_log("Image upload error: " . $e->getMessage());
            }
        }
        
        return $uploaded;
    }
    
    /**
     * Bild verarbeiten (resize, optimize)
     */
    private function processImage($source, $destination, $mime_type) {
        list($width, $height) = getimagesize($source);
        
        // Skalierung berechnen
        $scale = min(
            $this->max_width / $width,
            $this->max_height / $height,
            1
        );
        
        $new_width = intval($width * $scale);
        $new_height = intval($height * $scale);
        
        // Bild laden
        switch ($mime_type) {
            case 'image/jpeg':
            case 'image/jpg':
                $image = imagecreatefromjpeg($source);
                break;
            case 'image/png':
                $image = imagecreatefrompng($source);
                break;
            case 'image/gif':
                $image = imagecreatefromgif($source);
                break;
            case 'image/webp':
                $image = imagecreatefromwebp($source);
                break;
            default:
                throw new Exception('Unsupported image type');
        }
        
        // Neues Bild erstellen
        $new_image = imagecreatetruecolor($new_width, $new_height);
        
        // Transparenz erhalten für PNG/GIF
        if ($mime_type === 'image/png' || $mime_type === 'image/gif') {
            imagealphablending($new_image, false);
            imagesavealpha($new_image, true);
        }
        
        // Resize
        imagecopyresampled(
            $new_image, $image,
            0, 0, 0, 0,
            $new_width, $new_height,
            $width, $height
        );
        
        // Speichern
        switch ($mime_type) {
            case 'image/jpeg':
            case 'image/jpg':
                imagejpeg($new_image, $destination, 85);
                break;
            case 'image/png':
                imagepng($new_image, $destination, 8);
                break;
            case 'image/gif':
                imagegif($new_image, $destination);
                break;
            case 'image/webp':
                imagewebp($new_image, $destination, 85);
                break;
        }
        
        imagedestroy($image);
        imagedestroy($new_image);
    }
    
    /**
     * Extension ermitteln
     */
    private function getExtension($mime_type) {
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp'
        ];
        return $extensions[$mime_type] ?? 'jpg';
    }
    
    /**
     * Bild löschen
     */
    public function delete($filename) {
        $filepath = $this->upload_dir . $filename;
        if (file_exists($filepath)) {
            return unlink($filepath);
        }
        return false;
    }
    
    /**
     * Thumbnail erstellen
     */
    public function createThumbnail($filename, $width = 300, $height = 300) {
        $source = $this->upload_dir . $filename;
        $thumb_name = 'thumb_' . $filename;
        $destination = $this->upload_dir . $thumb_name;
        
        if (!file_exists($source)) {
            return false;
        }
        
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime_type = $finfo->file($source);
        
        list($orig_width, $orig_height) = getimagesize($source);
        
        // Aspect ratio beibehalten
        $ratio = min($width / $orig_width, $height / $orig_height);
        $new_width = intval($orig_width * $ratio);
        $new_height = intval($orig_height * $ratio);
        
        // Laden
        switch ($mime_type) {
            case 'image/jpeg':
            case 'image/jpg':
                $image = imagecreatefromjpeg($source);
                break;
            case 'image/png':
                $image = imagecreatefrompng($source);
                break;
            case 'image/gif':
                $image = imagecreatefromgif($source);
                break;
            case 'image/webp':
                $image = imagecreatefromwebp($source);
                break;
            default:
                return false;
        }
        
        $thumb = imagecreatetruecolor($new_width, $new_height);
        
        if ($mime_type === 'image/png' || $mime_type === 'image/gif') {
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
        }
        
        imagecopyresampled($thumb, $image, 0, 0, 0, 0, $new_width, $new_height, $orig_width, $orig_height);
        
        // Speichern
        imagejpeg($thumb, $destination, 80);
        
        imagedestroy($image);
        imagedestroy($thumb);
        
        return $thumb_name;
    }
}
?>
