<?php
/**
 * Enhanced Upload Handler for BoteBank Admin System
 * Handles file uploads with proper organization and security
 */

class BoteBankUploadHandler {
    
    private $uploadBaseDir;
    private $allowedTypes;
    private $maxFileSize;
    
    public function __construct($baseDir = '../uploads/') {
        $this->uploadBaseDir = rtrim($baseDir, '/') . '/';
        $this->allowedTypes = [
            'image' => ['jpg', 'jpeg', 'png', 'gif', 'svg'],
            'document' => ['pdf', 'doc', 'docx', 'txt'],
            'general' => ['jpg', 'jpeg', 'png', 'gif', 'svg', 'pdf', 'doc', 'docx', 'txt']
        ];
        $this->maxFileSize = 5 * 1024 * 1024; // 5MB
    }
    
    /**
     * Upload system files (logos, banners) to organized directory structure
     */
    public function uploadSystemFile($file, $subDir = 'general', $customName = null) {
        try {
            // Validate file upload
            $this->validateUpload($file);
            
            // Get target directory for system files
            $targetDir = $this->uploadBaseDir . 'system/' . $subDir . '/';
            $this->ensureDirectoryExists($targetDir);
            
            // Generate filename
            $filename = $this->generateSystemFilename($file, $subDir, $customName);
            $targetPath = $targetDir . $filename;
            
            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                return [
                    'success' => true,
                    'filename' => $filename,
                    'path' => 'system/' . $subDir . '/' . $filename,
                    'full_path' => $targetPath
                ];
            } else {
                throw new Exception('Failed to move uploaded file');
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Generate secure filename for system files
     */
    private function generateSystemFilename($file, $subDir, $customName = null) {
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if ($customName) {
            $baseName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $customName);
        } else {
            $prefixes = [
                'logos' => 'barangay_logo_',
                'banners' => 'banner_',
                'general' => 'system_file_'
            ];
            $baseName = ($prefixes[$subDir] ?? $prefixes['general']) . time() . '_' . uniqid();
        }
        
        return $baseName . '.' . $extension;
    }
    
    /**
     * Upload file to organized directory structure
     */
    public function uploadFile($file, $uploadType = 'general', $customName = null) {
        try {
            // Validate file upload
            $this->validateUpload($file);
            
            // Get target directory
            $targetDir = $this->getTargetDirectory($uploadType);
            $this->ensureDirectoryExists($targetDir);
            
            // Generate filename
            $filename = $this->generateFilename($file, $uploadType, $customName);
            $targetPath = $targetDir . $filename;
            
            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                // Return relative path from admin directory
                $relativePath = $this->getRelativePath($targetPath);
                
                // Generate thumbnail for images if needed
                if ($uploadType === 'profile' && $this->isImageFile($file)) {
                    $this->generateThumbnail($targetPath, $uploadType);
                }
                
                return [
                    'success' => true,
                    'filename' => $filename,
                    'path' => $relativePath,
                    'full_path' => $targetPath
                ];
            } else {
                throw new Exception('Failed to move uploaded file');
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Validate file upload
     */
    private function validateUpload($file) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds maximum size limit',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds form size limit',
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
            ];
            throw new Exception($errors[$file['error']] ?? 'Unknown upload error');
        }
        
        if ($file['size'] > $this->maxFileSize) {
            throw new Exception('File size exceeds maximum limit of ' . ($this->maxFileSize / 1024 / 1024) . 'MB');
        }
        
        // Additional security checks
        $this->validateFileType($file);
        $this->scanForMalware($file);
    }
    
    /**
     * Validate file type
     */
    private function validateFileType($file) {
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $mimeType = mime_content_type($file['tmp_name']);
        
        $allowedMimes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'txt' => 'text/plain'
        ];
        
        if (!array_key_exists($extension, $allowedMimes)) {
            throw new Exception('File type not allowed: ' . $extension);
        }
        
        if ($mimeType !== $allowedMimes[$extension]) {
            throw new Exception('File mime type does not match extension');
        }
    }
    
    /**
     * Basic malware scanning
     */
    private function scanForMalware($file) {
        // Check for PHP code in uploaded files
        $content = file_get_contents($file['tmp_name']);
        if (strpos($content, '<?php') !== false || strpos($content, '<?=') !== false) {
            throw new Exception('Potentially malicious file detected');
        }
    }
    
    /**
     * Get target directory based on upload type
     */
    private function getTargetDirectory($uploadType) {
        $directories = [
            'logo' => $this->uploadBaseDir . 'system/logos/',
            'banner' => $this->uploadBaseDir . 'system/banners/',
            'profile' => $this->uploadBaseDir . 'profiles/',
            'rewards' => $this->uploadBaseDir . 'rewards/',
            'document' => $this->uploadBaseDir . 'documents/',
            'general' => $this->uploadBaseDir
        ];
        
        return $directories[$uploadType] ?? $directories['general'];
    }
    
    /**
     * Ensure directory exists
     */
    private function ensureDirectoryExists($directory) {
        if (!is_dir($directory)) {
            if (!mkdir($directory, 0755, true)) {
                throw new Exception('Failed to create upload directory: ' . $directory);
            }
        }
        
        if (!is_writable($directory)) {
            throw new Exception('Upload directory is not writable: ' . $directory);
        }
    }
    
    /**
     * Generate secure filename
     */
    private function generateFilename($file, $uploadType, $customName = null) {
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if ($customName) {
            $baseName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $customName);
        } else {
            $prefixes = [
                'logo' => 'barangay_logo_',
                'banner' => 'banner_',
                'profile' => 'profile_',
                'rewards' => 'reward_',
                'document' => 'doc_',
                'general' => 'file_'
            ];
            $baseName = ($prefixes[$uploadType] ?? $prefixes['general']) . time() . '_' . uniqid();
        }
        
        return $baseName . '.' . $extension;
    }
    
    /**
     * Get relative path from admin directory
     */
    private function getRelativePath($fullPath) {
        $adminDir = dirname(__DIR__) . '/uploads/';
        return str_replace($adminDir, '', $fullPath);
    }
    
    /**
     * Check if file is an image
     */
    private function isImageFile($file) {
        $imageTypes = ['jpg', 'jpeg', 'png', 'gif'];
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        return in_array($extension, $imageTypes);
    }
    
    /**
     * Generate thumbnail for images
     */
    private function generateThumbnail($imagePath, $uploadType) {
        if ($uploadType !== 'profile') return;
        
        $thumbDir = dirname($imagePath) . '/thumbs/';
        $this->ensureDirectoryExists($thumbDir);
        
        $thumbPath = $thumbDir . basename($imagePath);
        
        // Simple thumbnail generation
        $imageInfo = getimagesize($imagePath);
        if ($imageInfo === false) return;
        
        list($width, $height, $type) = $imageInfo;
        
        // Create image resource based on type
        switch ($type) {
            case IMAGETYPE_JPEG:
                $source = imagecreatefromjpeg($imagePath);
                break;
            case IMAGETYPE_PNG:
                $source = imagecreatefrompng($imagePath);
                break;
            case IMAGETYPE_GIF:
                $source = imagecreatefromgif($imagePath);
                break;
            default:
                return;
        }
        
        // Calculate thumbnail dimensions (150x150 max)
        $thumbSize = 150;
        $ratio = min($thumbSize / $width, $thumbSize / $height);
        $newWidth = (int)($width * $ratio);
        $newHeight = (int)($height * $ratio);
        
        // Create thumbnail
        $thumb = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preserve transparency for PNG and GIF
        if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
            imagecolortransparent($thumb, imagecolorallocatealpha($thumb, 0, 0, 0, 127));
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
        }
        
        imagecopyresampled($thumb, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        
        // Save thumbnail
        switch ($type) {
            case IMAGETYPE_JPEG:
                imagejpeg($thumb, $thumbPath, 90);
                break;
            case IMAGETYPE_PNG:
                imagepng($thumb, $thumbPath);
                break;
            case IMAGETYPE_GIF:
                imagegif($thumb, $thumbPath);
                break;
        }
        
        // Clean up
        imagedestroy($source);
        imagedestroy($thumb);
    }
    
    /**
     * Delete uploaded file
     */
    public function deleteFile($filename, $uploadType = 'general') {
        $targetDir = $this->getTargetDirectory($uploadType);
        $filePath = $targetDir . $filename;
        
        if (file_exists($filePath)) {
            unlink($filePath);
            
            // Also delete thumbnail if it exists
            if ($uploadType === 'profile') {
                $thumbPath = $targetDir . 'thumbs/' . $filename;
                if (file_exists($thumbPath)) {
                    unlink($thumbPath);
                }
            }
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Handle image upload for carousel specifically
     */
    public function handleImageUpload($file, $targetDir = 'carousel') {
        try {
            // Validate file upload
            $this->validateUpload($file);
            
            if (!$this->isImageFile($file)) {
                throw new Exception('Only image files are allowed for carousel slides.');
            }
            
            // Get target directory
            $uploadDir = $this->uploadBaseDir . $targetDir . '/';
            $this->ensureDirectoryExists($uploadDir);
            
            // Generate filename
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $filename = 'slide_' . time() . '_' . uniqid() . '.' . $extension;
            $targetPath = $uploadDir . $filename;
            
            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                return [
                    'success' => true,
                    'filename' => $filename,
                    'path' => $targetDir . '/' . $filename
                ];
            } else {
                throw new Exception('Failed to move uploaded file');
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
?>
