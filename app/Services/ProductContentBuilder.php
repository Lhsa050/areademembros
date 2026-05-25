<?php

namespace App\Services;

use App\Models\Lesson;
use App\Models\Module;
use App\Models\ProductFile;

class ProductContentBuilder
{
    private const PRODUCT_FILE_EXTENSIONS = ['pdf', 'zip', 'rar', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'mp3', 'mp4'];

    public static function createInitialModules(int $productId, array $modules): void
    {
        $moduleSort = 1;

        foreach ($modules as $moduleData) {
            if (!is_array($moduleData)) {
                continue;
            }

            $lessons = $moduleData['lessons'] ?? [];
            if (!is_array($lessons)) {
                $lessons = [];
            }

            $moduleTitle = trim((string) ($moduleData['title'] ?? ''));
            if ($moduleTitle === '' && !self::hasLessonData($lessons)) {
                continue;
            }

            if ($moduleTitle === '') {
                $moduleTitle = 'Módulo ' . $moduleSort;
            }

            $moduleId = Module::create([
                'product_id' => $productId,
                'title' => $moduleTitle,
                'sort_order' => $moduleSort,
                'release_days' => self::releaseDays($moduleData['release_days'] ?? null),
            ]);

            $lessonSort = 1;
            foreach ($lessons as $lessonData) {
                if (!is_array($lessonData)) {
                    continue;
                }

                $lessonTitle = trim((string) ($lessonData['title'] ?? ''));
                $youtubeInput = trim((string) ($lessonData['youtube_id'] ?? ''));
                $description = trim((string) ($lessonData['description'] ?? ''));

                if ($lessonTitle === '' && $youtubeInput === '' && $description === '') {
                    continue;
                }

                if ($lessonTitle === '') {
                    $lessonTitle = 'Aula ' . $lessonSort;
                }

                Lesson::create([
                    'module_id' => $moduleId,
                    'title' => $lessonTitle,
                    'description' => $description,
                    'youtube_id' => $youtubeInput !== '' ? Lesson::extractYoutubeId($youtubeInput) : null,
                    'file' => '',
                    'sort_order' => $lessonSort,
                    'release_days' => self::releaseDays($lessonData['release_days'] ?? null),
                ]);

                $lessonSort++;
            }

            $moduleSort++;
        }
    }

    public static function createInitialProductFiles(int $productId, array $items, array $uploads): ?string
    {
        $sortOrder = 1;
        $firstFilePath = null;

        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }

            $title = trim((string) ($item['title'] ?? ''));
            $fileUrl = trim((string) ($item['file_url'] ?? ''));
            $upload = self::fileUploadAt($uploads, $index);
            $data = null;

            if ($fileUrl !== '') {
                if (!filter_var($fileUrl, FILTER_VALIDATE_URL)) {
                    continue;
                }

                $data = [
                    'product_id' => $productId,
                    'title' => $title !== '' ? $title : 'Link ' . $sortOrder,
                    'file' => $fileUrl,
                    'file_type' => 'link',
                    'sort_order' => $sortOrder,
                    'release_days' => self::releaseDays($item['release_days'] ?? null),
                ];

                if (ProductFile::supportsOpenInNewTab()) {
                    $data['open_in_new_tab'] = !empty($item['open_in_new_tab']) ? 1 : 0;
                }
            } elseif ($upload && ($upload['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK && !empty($upload['name'])) {
                $filePath = self::moveProductFileUpload($upload);
                if ($filePath === null) {
                    continue;
                }

                $data = [
                    'product_id' => $productId,
                    'title' => $title !== '' ? $title : pathinfo((string) $upload['name'], PATHINFO_FILENAME),
                    'file' => $filePath,
                    'file_type' => 'upload',
                    'sort_order' => $sortOrder,
                    'release_days' => self::releaseDays($item['release_days'] ?? null),
                ];

                $firstFilePath ??= $filePath;
            }

            if ($data === null) {
                continue;
            }

            ProductFile::create($data);
            if ($firstFilePath === null) {
                $firstFilePath = $data['file'];
            }
            $sortOrder++;
        }

        return $firstFilePath;
    }

    private static function hasLessonData(array $lessons): bool
    {
        foreach ($lessons as $lessonData) {
            if (!is_array($lessonData)) {
                continue;
            }

            if (
                trim((string) ($lessonData['title'] ?? '')) !== ''
                || trim((string) ($lessonData['youtube_id'] ?? '')) !== ''
                || trim((string) ($lessonData['description'] ?? '')) !== ''
            ) {
                return true;
            }
        }

        return false;
    }

    private static function releaseDays(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return max(0, (int) $value);
    }

    private static function fileUploadAt(array $uploads, int|string $index): ?array
    {
        if (!isset($uploads['name'][$index]['file'])) {
            return null;
        }

        return [
            'name' => $uploads['name'][$index]['file'] ?? '',
            'type' => $uploads['type'][$index]['file'] ?? '',
            'tmp_name' => $uploads['tmp_name'][$index]['file'] ?? '',
            'error' => $uploads['error'][$index]['file'] ?? UPLOAD_ERR_NO_FILE,
            'size' => $uploads['size'][$index]['file'] ?? 0,
        ];
    }

    private static function moveProductFileUpload(array $file): ?string
    {
        $ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if (!in_array($ext, self::PRODUCT_FILE_EXTENSIONS, true)) {
            return null;
        }

        $uploadDir = ABSPATH . '/public/assets/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filename = uniqid('pfile_') . '.' . $ext;
        $finalPath = $uploadDir . $filename;

        if (!move_uploaded_file((string) $file['tmp_name'], $finalPath)) {
            return null;
        }

        return 'assets/uploads/' . $filename;
    }
}
