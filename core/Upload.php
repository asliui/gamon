<?php

declare(strict_types=1);

namespace WebGamon\Core;

/**
 * Validated image uploads for report attachments.
 */
final class Upload
{
    private const ALLOWED_MIMES = [
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png'],
    ];

    /**
     * @return string|null Relative path (e.g. uploads/file.jpg) or null if no file.
     */
    public static function storeReportImage(array $file, array $config): ?string
    {
        if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            Response::json(['ok' => false, 'error' => 'Image upload failed'], 400);
        }

        $maxBytes = (int)($config['upload']['max_bytes'] ?? (5 * 1024 * 1024));
        if ((int)($file['size'] ?? 0) > $maxBytes) {
            Response::json(['ok' => false, 'error' => 'Image exceeds maximum allowed size'], 413);
        }

        $tmpName = (string)($file['tmp_name'] ?? '');
        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            Response::json(['ok' => false, 'error' => 'Invalid upload'], 400);
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmpName);
        if ($mime === false || !isset(self::ALLOWED_MIMES[$mime])) {
            Response::json(['ok' => false, 'error' => 'Only JPG and PNG images are allowed'], 400);
        }

        $ext = self::ALLOWED_MIMES[$mime][0];
        $newFileName = bin2hex(random_bytes(16)) . '.' . $ext;
        $destinationFolder = dirname(__DIR__) . '/uploads/';

        if (!is_dir($destinationFolder)) {
            mkdir($destinationFolder, 0755, true);
        }

        $destination = $destinationFolder . $newFileName;
        if (!move_uploaded_file($tmpName, $destination)) {
            Response::json(['ok' => false, 'error' => 'Could not save uploaded image'], 500);
        }

        return 'uploads/' . $newFileName;
    }
}
