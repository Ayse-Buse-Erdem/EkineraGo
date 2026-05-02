<?php

declare(strict_types=1);

if (!function_exists('ensure_upload_directory')) {
    function ensure_upload_directory(string $directory): void
    {
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }
    }
}

if (!function_exists('is_valid_image_upload')) {
    function is_valid_image_upload(array $file): bool
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return false;
        }

        if (($file['size'] ?? 0) > MAX_UPLOAD_SIZE) {
            return false;
        }

        $tmpName = $file['tmp_name'] ?? '';

        if (!$tmpName || !is_uploaded_file($tmpName)) {
            return false;
        }

        $mimeType = mime_content_type($tmpName);

        if (!in_array($mimeType, ALLOWED_IMAGE_MIME_TYPES, true)) {
            return false;
        }

        $extension = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));

        return in_array($extension, ALLOWED_IMAGE_EXTENSIONS, true);
    }
}

if (!function_exists('upload_image')) {
    function upload_image(array $file, string $folder): ?string
    {
        if (!is_valid_image_upload($file)) {
            return null;
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $fileName = bin2hex(random_bytes(16)) . '.' . $extension;

        $relativeFolder = trim($folder, '/');
        $targetDirectory = UPLOAD_PATH . DIRECTORY_SEPARATOR . $relativeFolder;

        ensure_upload_directory($targetDirectory);

        $targetPath = $targetDirectory . DIRECTORY_SEPARATOR . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            return null;
        }

        return 'uploads/' . $relativeFolder . '/' . $fileName;
    }
}