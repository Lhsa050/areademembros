<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Security;
use App\Models\Funnel;
use App\Models\Offer;
use App\Models\Product;
use App\Models\Setting;
use App\Models\SupportContact;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use Ramsey\Uuid\Uuid;

/**
 * Importacao de membros via planilha.
 *
 * Para suportar arquivos grandes, o XLSX e lido em partes e convertido para um
 * JSONL temporario. O processamento final usa lotes pequenos e transacoes curtas.
 */
class ImportController
{
    private const SCAN_CHUNK_ROWS = 5000;
    private const PROCESS_BATCH_ROWS = 1000;
    private const DB_CHUNK_SIZE = 500;
    private const IMPORT_TTL_SECONDS = 172800; // 48h

    private function resolveFunnel(string $funnelId): array
    {
        $funnel = Funnel::find((int) $funnelId);
        if (!$funnel) {
            flash('error', 'Funil nao encontrado.');
            redirect(url('/funnels'));
            exit;
        }
        return $funnel;
    }

    public function showUpload(string $funnelId): void
    {
        Auth::require();
        $funnel = $this->resolveFunnel($funnelId);
        $this->cleanupOldImportJobs();

        view('admin.import.upload', [
            'funnel' => $funnel,
            'user' => Auth::user()
        ]);
    }

    public function parseXlsx(string $funnelId): void
    {
        Auth::require();
        Security::requireCsrf();
        $this->prepareLongRunningRequest();

        $funnel = $this->resolveFunnel($funnelId);

        if (!isset($_FILES['xlsx_file']) || $_FILES['xlsx_file']['error'] !== UPLOAD_ERR_OK) {
            flash('error', 'Erro no upload do arquivo. Tente novamente.');
            redirect(url('/funnels/' . $funnel['id'] . '/import'));
            return;
        }

        $file = $_FILES['xlsx_file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['xlsx', 'xls'], true)) {
            flash('error', 'Formato invalido. Envie um arquivo .xlsx ou .xls');
            redirect(url('/funnels/' . $funnel['id'] . '/import'));
            return;
        }

        if ($ext === 'xlsx' && !class_exists(\ZipArchive::class)) {
            flash('error', 'O servidor precisa da extensao PHP ZipArchive habilitada para ler arquivos .xlsx.');
            redirect(url('/funnels/' . $funnel['id'] . '/import'));
            return;
        }

        $token = $this->newImportToken();
        $sourcePath = $this->importDir() . '/' . $token . '.' . $ext;
        $dataPath = $this->importDir() . '/' . $token . '.jsonl';

        if (!move_uploaded_file($file['tmp_name'], $sourcePath)) {
            flash('error', 'Nao foi possivel salvar o arquivo temporario de importacao.');
            redirect(url('/funnels/' . $funnel['id'] . '/import'));
            return;
        }

        $job = [
            'token' => $token,
            'funnel_id' => (int) $funnel['id'],
            'original_name' => $file['name'],
            'data_path' => $dataPath,
            'created_at' => time(),
            'status' => 'analyzed',
            'byte_offset' => 0,
            'products' => [],
            'mapping' => [],
            'counts' => [
                'total_rows' => 0,
                'paid_rows' => 0,
                'skipped_rows' => 0,
                'invalid_email_rows' => 0,
                'unique_members' => 0,
            ],
            'stats' => $this->emptyImportStats(),
        ];

        try {
            $this->normalizeSpreadsheet($sourcePath, $job);
        } catch (\Throwable $e) {
            @unlink($sourcePath);
            @unlink($dataPath);
            flash('error', 'Erro ao analisar o arquivo: ' . $e->getMessage());
            redirect(url('/funnels/' . $funnel['id'] . '/import'));
            return;
        }

        @unlink($sourcePath);

        if (($job['counts']['paid_rows'] ?? 0) <= 0) {
            @unlink($dataPath);
            flash('error', 'Nenhum registro valido com Payment status = "Paid" foi encontrado.');
            redirect(url('/funnels/' . $funnel['id'] . '/import'));
            return;
        }

        $this->saveImportJob($job);
        $_SESSION['import_job_' . $funnel['id']] = $token;

        view('admin.import.mapping', [
            'funnel' => $funnel,
            'jobToken' => $token,
            'importProducts' => $job['products'],
            'funnelProducts' => Product::getByFunnel($funnel['id']),
            'funnelOffers' => $this->offersWithProductIds((int) $funnel['id']),
            'memberCount' => $job['counts']['unique_members'],
            'totalRows' => $job['counts']['total_rows'],
            'skippedRows' => $job['counts']['skipped_rows'],
            'paidRows' => $job['counts']['paid_rows'],
            'invalidEmailRows' => $job['counts']['invalid_email_rows'],
            'user' => Auth::user()
        ]);
    }

    public function processImport(string $funnelId): void
    {
        Auth::require();
        Security::requireCsrf();
        $funnel = $this->resolveFunnel($funnelId);

        $token = trim((string) ($_POST['job_token'] ?? ''));
        $job = $this->loadImportJob($token);

        if (!$job || (int) ($job['funnel_id'] ?? 0) !== (int) $funnel['id']) {
            flash('error', 'Importacao expirada ou nao encontrada. Envie o arquivo novamente.');
            redirect(url('/funnels/' . $funnel['id'] . '/import'));
            return;
        }

        $mapping = $_POST['product_mapping'] ?? [];
        $cleanMapping = $this->validateProductMapping($funnel, $job, is_array($mapping) ? $mapping : []);
        if ($cleanMapping === null) {
            redirect(url('/funnels/' . $funnel['id'] . '/import'));
            return;
        }

        $job['mapping'] = $cleanMapping;
        $job['byte_offset'] = 0;
        $job['status'] = 'processing';
        $job['stats'] = $this->emptyImportStats();
        $this->saveImportJob($job);

        view('admin.import.processing', [
            'funnel' => $funnel,
            'jobToken' => $token,
            'counts' => $job['counts'],
            'batchUrl' => url('/funnels/' . $funnel['id'] . '/import/batch'),
            'membersUrl' => url('/funnels/' . $funnel['id'] . '/members'),
            'user' => Auth::user()
        ]);
    }

    public function processBatch(string $funnelId): void
    {
        Auth::require();
        Security::requireCsrf();
        $this->prepareLongRunningRequest();

        $funnel = $this->resolveFunnel($funnelId);
        $input = $this->requestInput();
        $token = trim((string) ($input['job_token'] ?? ''));
        $job = $this->loadImportJob($token);

        if (!$job || (int) ($job['funnel_id'] ?? 0) !== (int) $funnel['id']) {
            json_response(['success' => false, 'error' => 'Importacao nao encontrada ou expirada.'], 404);
        }

        if (($job['status'] ?? '') === 'done') {
            json_response($this->batchPayload($job, true));
        }

        try {
            [$rows, $offset, $eof] = $this->readNextNormalizedRows(
                $job['data_path'],
                (int) ($job['byte_offset'] ?? 0),
                self::PROCESS_BATCH_ROWS
            );

            if (!empty($rows)) {
                $batchStats = $this->importNormalizedRows($funnel, $rows, $job['mapping'] ?? []);
                foreach ($batchStats as $key => $value) {
                    $job['stats'][$key] = (int) ($job['stats'][$key] ?? 0) + (int) $value;
                }
            }

            $job['byte_offset'] = $offset;
            $job['stats']['processed_rows'] = (int) ($job['stats']['processed_rows'] ?? 0) + count($rows);

            if ($eof) {
                $job['status'] = 'done';
                @unlink((string) ($job['data_path'] ?? ''));
            }

            $this->saveImportJob($job);
            json_response($this->batchPayload($job, $eof));
        } catch (\Throwable $e) {
            $job['stats']['errors'] = (int) ($job['stats']['errors'] ?? 0) + 1;
            $this->saveImportJob($job);
            json_response(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    private function normalizeSpreadsheet(string $sourcePath, array &$job): void
    {
        $reader = $this->createSpreadsheetReader($sourcePath);
        $worksheets = $reader->listWorksheetInfo($sourcePath);
        $sheetInfo = $worksheets[0] ?? null;
        if (!$sheetInfo) {
            throw new \RuntimeException('Nao foi possivel ler a primeira aba da planilha.');
        }

        $highestRow = (int) ($sheetInfo['totalRows'] ?? 0);
        $totalColumns = max(1, (int) ($sheetInfo['totalColumns'] ?? 1));
        if ($highestRow < 2) {
            throw new \RuntimeException('Arquivo vazio ou sem dados.');
        }

        $header = $this->readSpreadsheetRows($sourcePath, 1, 1, $totalColumns)[0] ?? [];
        $normalizedHeader = [];
        foreach ($header as $index => $column) {
            $normalizedHeader[$index] = strtolower(trim((string) $column));
        }

        $colMap = [
            'email' => $this->findColumn($normalizedHeader, ['email address', 'email', 'e-mail']),
            'product_id' => $this->findColumn($normalizedHeader, ['product_id', 'productid', 'id do produto', 'product id']),
            'cpf' => $this->findColumn($normalizedHeader, ['cpf', 'documento']),
            'name' => $this->findColumn($normalizedHeader, ['full name', 'fullname', 'name', 'nome', 'nome completo', 'full_name']),
            'phone' => $this->findColumn($normalizedHeader, ['mobile_no', 'mobile', 'phone', 'telefone', 'celular', 'whatsapp']),
            'payment_status' => $this->findColumn($normalizedHeader, ['payment status', 'payment_status']),
            'product_name' => $this->findColumn($normalizedHeader, ['product name', 'product_name', 'productname', 'nome do produto', 'produto']),
        ];

        if ($colMap['email'] === null) {
            throw new \RuntimeException('Coluna "Email address" nao encontrada. Colunas detectadas: ' . implode(', ', array_filter($normalizedHeader)));
        }

        if ($colMap['payment_status'] === null) {
            throw new \RuntimeException('Coluna "Payment status" nao encontrada. Colunas detectadas: ' . implode(', ', array_filter($normalizedHeader)));
        }

        $handle = fopen($job['data_path'], 'wb');
        if (!$handle) {
            throw new \RuntimeException('Nao foi possivel criar o arquivo temporario normalizado.');
        }

        $uniqueEmails = [];
        $products = [];

        try {
            for ($startRow = 2; $startRow <= $highestRow; $startRow += self::SCAN_CHUNK_ROWS) {
                $endRow = min($highestRow, $startRow + self::SCAN_CHUNK_ROWS - 1);
                $rows = $this->readSpreadsheetRows($sourcePath, $startRow, $endRow, $totalColumns);

                foreach ($rows as $row) {
                    if ($this->rowIsEmpty($row)) {
                        continue;
                    }

                    $job['counts']['total_rows']++;

                    $status = strtolower($this->cell($row, $colMap['payment_status']));
                    if ($status !== 'paid') {
                        $job['counts']['skipped_rows']++;
                        continue;
                    }

                    $email = strtolower($this->cell($row, $colMap['email']));
                    if ($email === '' || strlen($email) > 191 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $job['counts']['skipped_rows']++;
                        $job['counts']['invalid_email_rows']++;
                        continue;
                    }

                    $productId = $this->cell($row, $colMap['product_id']);
                    $productName = $this->cell($row, $colMap['product_name']);
                    $productKey = $productId !== '' ? $productId : $productName;
                    $productHash = $productKey !== '' ? $this->productKeyHash($productKey) : '';

                    if ($productHash !== '' && !isset($products[$productHash])) {
                        $products[$productHash] = [
                            'key' => $productKey,
                            'product_id' => $productId,
                            'product_name' => $productName,
                        ];
                    }

                    $payload = [
                        'email' => $email,
                        'name' => $this->truncate($this->cell($row, $colMap['name']), 200),
                        'cpf' => $this->truncate(preg_replace('/[^0-9]/', '', $this->cell($row, $colMap['cpf'])), 20),
                        'phone' => $this->truncate(preg_replace('/[^0-9+]/', '', $this->cell($row, $colMap['phone'])), 30),
                        'product_hash' => $productHash,
                    ];

                    fwrite($handle, json_encode($payload, JSON_UNESCAPED_UNICODE) . "\n");
                    $job['counts']['paid_rows']++;
                    $uniqueEmails[$email] = true;
                }
            }
        } finally {
            fclose($handle);
        }

        $job['products'] = $products;
        $job['counts']['unique_members'] = count($uniqueEmails);
    }

    private function readSpreadsheetRows(string $path, int $startRow, int $endRow, int $totalColumns): array
    {
        $reader = $this->createSpreadsheetReader($path);
        $reader->setReadFilter(new ImportChunkReadFilter($startRow, $endRow));

        $spreadsheet = $reader->load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $endColumn = Coordinate::stringFromColumnIndex($totalColumns);
        $rows = $sheet->rangeToArray("A{$startRow}:{$endColumn}{$endRow}", null, true, true, false);
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return $rows;
    }

    private function createSpreadsheetReader(string $path): \PhpOffice\PhpSpreadsheet\Reader\IReader
    {
        $reader = IOFactory::createReader(IOFactory::identify($path));
        $reader->setReadDataOnly(true);

        if (method_exists($reader, 'setReadEmptyCells')) {
            $reader->setReadEmptyCells(false);
        }

        return $reader;
    }

    private function importNormalizedRows(array $funnel, array $rows, array $mapping): array
    {
        $stats = [
            'created' => 0,
            'updated' => 0,
            'products_granted' => 0,
            'products_skipped' => 0,
            'errors' => 0,
        ];

        $membersByEmail = [];
        foreach ($rows as $row) {
            $email = strtolower(trim((string) ($row['email'] ?? '')));
            if ($email === '') {
                $stats['errors']++;
                continue;
            }

            if (!isset($membersByEmail[$email])) {
                $membersByEmail[$email] = [
                    'email' => $email,
                    'name' => $row['name'] ?: 'Importado',
                    'cpf' => $row['cpf'] ?: null,
                    'phone' => $row['phone'] ?: null,
                    'product_ids' => [],
                ];
            }

            if (empty($membersByEmail[$email]['name']) && !empty($row['name'])) {
                $membersByEmail[$email]['name'] = $row['name'];
            }
            if (empty($membersByEmail[$email]['cpf']) && !empty($row['cpf'])) {
                $membersByEmail[$email]['cpf'] = $row['cpf'];
            }
            if (empty($membersByEmail[$email]['phone']) && !empty($row['phone'])) {
                $membersByEmail[$email]['phone'] = $row['phone'];
            }

            $productHash = (string) ($row['product_hash'] ?? '');
            $mappedProductIds = $this->mappedProductIds($mapping[$productHash] ?? null);
            if ($productHash !== '' && !empty($mappedProductIds)) {
                foreach ($mappedProductIds as $productId) {
                    $membersByEmail[$email]['product_ids'][] = $productId;
                }
            } elseif ($productHash !== '') {
                $stats['products_skipped']++;
            }
        }

        if (empty($membersByEmail)) {
            return $stats;
        }

        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            $loginMode = Setting::get('login_mode', 'email_only', (int) $funnel['id']);
            $defaultPassword = Setting::get('default_password', '', (int) $funnel['id']);
            $passwordHash = ($loginMode === 'password' && $defaultPassword !== '')
                ? password_hash($defaultPassword, PASSWORD_BCRYPT)
                : null;

            $emails = array_keys($membersByEmail);
            $existing = $this->fetchMembersByEmails((int) $funnel['id'], $emails);
            $newMembers = array_diff_key($membersByEmail, $existing);

            if (!empty($newMembers)) {
                $this->bulkInsertMembers((int) $funnel['id'], $newMembers, $passwordHash);
                $stats['created'] += count($newMembers);
            }

            $stats['updated'] += count($existing);

            $memberIds = $this->fetchMemberIdsByEmails((int) $funnel['id'], $emails);
            SupportContact::linkFunnelMembersByEmails((int) $funnel['id'], $emails);
            $pairs = [];
            foreach ($membersByEmail as $email => $memberData) {
                $memberId = $memberIds[$email] ?? null;
                if (!$memberId) {
                    $stats['errors']++;
                    continue;
                }

                foreach (array_unique($memberData['product_ids']) as $productId) {
                    $pairs[$memberId . ':' . $productId] = [
                        'member_id' => (int) $memberId,
                        'product_id' => (int) $productId,
                    ];
                }
            }

            if (!empty($pairs)) {
                $stats['products_granted'] += $this->bulkGrantProducts($pairs);
                $this->touchMembers(array_values($memberIds));
            }

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }

        return $stats;
    }

    private function bulkInsertMembers(int $funnelId, array $members, ?string $passwordHash): void
    {
        $now = date('Y-m-d H:i:s');

        foreach (array_chunk($members, self::DB_CHUNK_SIZE, true) as $chunk) {
            $placeholders = [];
            $params = [];

            foreach ($chunk as $member) {
                $placeholders[] = '(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
                array_push(
                    $params,
                    Uuid::uuid4()->toString(),
                    $funnelId,
                    $this->truncate($member['name'] ?: 'Importado', 200),
                    $this->truncate($member['email'], 191),
                    $this->truncate($member['cpf'] ?? null, 20),
                    $this->truncate($member['phone'] ?? null, 30),
                    $passwordHash,
                    'active',
                    $now,
                    $now
                );
            }

            Database::query(
                "INSERT INTO members (uuid, funnel_id, name, email, cpf, phone, password, status, created_at, updated_at)
                 VALUES " . implode(', ', $placeholders) . "
                 ON DUPLICATE KEY UPDATE email = VALUES(email)",
                $params
            );
        }
    }

    private function bulkGrantProducts(array $pairs): int
    {
        $grantableCount = $this->countPairsWithoutActiveAccess($pairs);

        foreach (array_chunk($pairs, self::DB_CHUNK_SIZE, true) as $chunk) {
            $placeholders = [];
            $params = [];

            foreach ($chunk as $pair) {
                $placeholders[] = "(?, ?, 'admin', NOW(), NULL)";
                $params[] = $pair['member_id'];
                $params[] = $pair['product_id'];
            }

            Database::query(
                "INSERT INTO member_products (member_id, product_id, granted_by, granted_at, revoked_at)
                 VALUES " . implode(', ', $placeholders) . "
                 ON DUPLICATE KEY UPDATE
                    granted_at = IF(revoked_at IS NULL, granted_at, NOW()),
                    revoked_at = NULL,
                    granted_by = VALUES(granted_by)",
                $params
            );
        }

        return $grantableCount;
    }

    private function countPairsWithoutActiveAccess(array $pairs): int
    {
        $activePairs = [];
        $memberIds = array_values(array_unique(array_column($pairs, 'member_id')));
        $productIds = array_values(array_unique(array_column($pairs, 'product_id')));

        foreach (array_chunk($memberIds, self::DB_CHUNK_SIZE) as $memberChunk) {
            foreach (array_chunk($productIds, self::DB_CHUNK_SIZE) as $productChunk) {
                $memberPlaceholders = implode(', ', array_fill(0, count($memberChunk), '?'));
                $productPlaceholders = implode(', ', array_fill(0, count($productChunk), '?'));
                $params = array_merge($memberChunk, $productChunk);

                $rows = Database::fetchAll(
                    "SELECT member_id, product_id
                     FROM member_products
                     WHERE revoked_at IS NULL
                       AND member_id IN ({$memberPlaceholders})
                       AND product_id IN ({$productPlaceholders})",
                    $params
                );

                foreach ($rows as $row) {
                    $activePairs[$row['member_id'] . ':' . $row['product_id']] = true;
                }
            }
        }

        $count = 0;
        foreach ($pairs as $key => $pair) {
            if (!isset($activePairs[$key])) {
                $count++;
            }
        }

        return $count;
    }

    private function fetchMembersByEmails(int $funnelId, array $emails): array
    {
        $members = [];
        foreach (array_chunk($emails, self::DB_CHUNK_SIZE) as $chunk) {
            $placeholders = implode(', ', array_fill(0, count($chunk), '?'));
            $rows = Database::fetchAll(
                "SELECT id, email FROM members WHERE funnel_id = ? AND email IN ({$placeholders})",
                array_merge([$funnelId], $chunk)
            );

            foreach ($rows as $row) {
                $members[strtolower($row['email'])] = $row;
            }
        }

        return $members;
    }

    private function fetchMemberIdsByEmails(int $funnelId, array $emails): array
    {
        $members = $this->fetchMembersByEmails($funnelId, $emails);
        $ids = [];

        foreach ($members as $email => $member) {
            $ids[$email] = (int) $member['id'];
        }

        return $ids;
    }

    private function touchMembers(array $memberIds): void
    {
        $memberIds = array_values(array_unique(array_filter(array_map('intval', $memberIds))));
        foreach (array_chunk($memberIds, self::DB_CHUNK_SIZE) as $chunk) {
            $placeholders = implode(', ', array_fill(0, count($chunk), '?'));
            Database::query("UPDATE members SET updated_at = NOW() WHERE id IN ({$placeholders})", $chunk);
        }
    }

    private function validateProductMapping(array $funnel, array $job, array $mapping): ?array
    {
        $allowedProducts = Product::getByFunnel((int) $funnel['id']);
        $allowedIds = array_flip(array_map('intval', array_column($allowedProducts, 'id')));
        $allowedOffers = $this->offersWithProductIds((int) $funnel['id']);
        $clean = [];

        foreach (($job['products'] ?? []) as $hash => $product) {
            $target = $this->parseMappingTarget((string) ($mapping[$hash] ?? ''));
            if (!$target) {
                flash('error', 'Mapeie todos os produtos do arquivo para produtos ou ofertas validos deste funil.');
                return null;
            }

            if ($target['type'] === 'product') {
                $productId = (int) $target['id'];
                if ($productId <= 0 || !isset($allowedIds[$productId])) {
                    flash('error', 'Mapeie todos os produtos do arquivo para produtos validos deste funil.');
                    return null;
                }

                $clean[$hash] = [
                    'type' => 'product',
                    'id' => $productId,
                    'product_ids' => [$productId],
                ];
                continue;
            }

            $offerId = (int) $target['id'];
            if ($offerId <= 0 || empty($allowedOffers[$offerId])) {
                flash('error', 'Mapeie todos os produtos do arquivo para ofertas validas deste funil.');
                return null;
            }

            $productIds = array_values(array_filter(
                array_map('intval', $allowedOffers[$offerId]['product_ids'] ?? []),
                fn($productId) => $productId > 0 && isset($allowedIds[$productId])
            ));

            if (empty($productIds)) {
                flash('error', 'A oferta "' . ($allowedOffers[$offerId]['title'] ?? 'selecionada') . '" nao possui produtos validos neste funil.');
                return null;
            }

            $clean[$hash] = [
                'type' => 'offer',
                'id' => $offerId,
                'product_ids' => array_values(array_unique($productIds)),
            ];
        }

        return $clean;
    }

    private function parseMappingTarget(string $value): ?array
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/^(product|offer):([0-9]+)$/', $value, $matches)) {
            return [
                'type' => $matches[1],
                'id' => (int) $matches[2],
            ];
        }

        // Compatibilidade com jobs antigos que gravavam apenas o ID do produto.
        if (ctype_digit($value)) {
            return [
                'type' => 'product',
                'id' => (int) $value,
            ];
        }

        return null;
    }

    private function mappedProductIds($mappingItem): array
    {
        if (is_array($mappingItem)) {
            $productIds = $mappingItem['product_ids'] ?? [];
        } elseif ($mappingItem !== null && $mappingItem !== '') {
            $productIds = [$mappingItem];
        } else {
            $productIds = [];
        }

        $productIds = array_values(array_unique(array_filter(
            array_map('intval', is_array($productIds) ? $productIds : []),
            fn($productId) => $productId > 0
        )));

        return $productIds;
    }

    private function offersWithProductIds(int $funnelId): array
    {
        $offers = [];

        foreach (Offer::getByFunnel($funnelId) as $offer) {
            $offer['product_ids'] = array_values(array_unique(array_map('intval', Offer::getProductIds((int) $offer['id']))));
            $offer['product_count'] = count($offer['product_ids']);
            $offers[(int) $offer['id']] = $offer;
        }

        return $offers;
    }

    private function readNextNormalizedRows(string $path, int $offset, int $limit): array
    {
        if (!is_file($path)) {
            throw new \RuntimeException('Arquivo temporario de importacao nao encontrado.');
        }

        $handle = fopen($path, 'rb');
        if (!$handle) {
            throw new \RuntimeException('Nao foi possivel abrir o arquivo temporario de importacao.');
        }

        fseek($handle, $offset);
        $rows = [];

        while (count($rows) < $limit && ($line = fgets($handle)) !== false) {
            $row = json_decode($line, true);
            if (is_array($row)) {
                $rows[] = $row;
            }
        }

        $newOffset = ftell($handle);
        $eof = feof($handle);
        fclose($handle);

        return [$rows, $newOffset, $eof];
    }

    private function batchPayload(array $job, bool $done): array
    {
        $paidRows = max(1, (int) ($job['counts']['paid_rows'] ?? 1));
        $processedRows = min($paidRows, (int) ($job['stats']['processed_rows'] ?? 0));

        return [
            'success' => true,
            'done' => $done,
            'progress' => [
                'processed' => $processedRows,
                'total' => $paidRows,
                'percent' => round(($processedRows / $paidRows) * 100, 1),
            ],
            'stats' => $job['stats'],
        ];
    }

    private function emptyImportStats(): array
    {
        return [
            'processed_rows' => 0,
            'created' => 0,
            'updated' => 0,
            'products_granted' => 0,
            'products_skipped' => 0,
            'errors' => 0,
        ];
    }

    private function requestInput(): array
    {
        $raw = file_get_contents('php://input');
        $json = $raw ? json_decode($raw, true) : null;
        return is_array($json) ? $json : $_POST;
    }

    private function importDir(): string
    {
        $dir = ABSPATH . '/storage/imports';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return $dir;
    }

    private function newImportToken(): string
    {
        return bin2hex(random_bytes(16));
    }

    private function jobPath(string $token): string
    {
        if (!preg_match('/^[a-f0-9]{32}$/', $token)) {
            return '';
        }
        return $this->importDir() . '/' . $token . '.job.json';
    }

    private function saveImportJob(array $job): void
    {
        file_put_contents($this->jobPath((string) $job['token']), json_encode($job, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    private function loadImportJob(string $token): ?array
    {
        $path = $this->jobPath($token);
        if ($path === '' || !is_file($path)) {
            return null;
        }

        $job = json_decode((string) file_get_contents($path), true);
        return is_array($job) ? $job : null;
    }

    private function cleanupOldImportJobs(): void
    {
        $cutoff = time() - self::IMPORT_TTL_SECONDS;
        foreach (glob($this->importDir() . '/*') ?: [] as $file) {
            if (is_file($file) && filemtime($file) < $cutoff) {
                @unlink($file);
            }
        }
    }

    private function prepareLongRunningRequest(): void
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '768M');
    }

    private function productKeyHash(string $key): string
    {
        return hash('sha256', trim($key));
    }

    private function rowIsEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }
        return true;
    }

    private function cell(array $row, ?int $index): string
    {
        if ($index === null || !array_key_exists($index, $row)) {
            return '';
        }

        return trim((string) $row[$index]);
    }

    private function truncate(?string $value, int $max): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $max, 'UTF-8');
        }

        return substr($value, 0, $max);
    }

    private function findColumn(array $header, array $names): ?int
    {
        foreach ($header as $index => $columnName) {
            if ($columnName === '') {
                continue;
            }

            foreach ($names as $name) {
                if ($columnName === strtolower($name)) {
                    return $index;
                }
            }
        }

        foreach ($header as $index => $columnName) {
            if ($columnName === '') {
                continue;
            }

            foreach ($names as $name) {
                if (str_contains($columnName, strtolower($name))) {
                    return $index;
                }
            }
        }

        return null;
    }
}

final class ImportChunkReadFilter implements IReadFilter
{
    public function __construct(
        private readonly int $startRow,
        private readonly int $endRow
    ) {
    }

    public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool
    {
        return $row >= $this->startRow && $row <= $this->endRow;
    }
}
