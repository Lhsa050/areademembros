<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Security;
use App\Models\FiscalInvoice;
use App\Models\FiscalSale;
use App\Services\Fiscal\CertificateService;
use App\Services\FiscalService;

/**
 * Painel fiscal: configurações, emissão, cancelamento e exportação.
 */
class FiscalController
{
    public function index(): void
    {
        Auth::require();

        $filters = [
            'q' => $_GET['q'] ?? '',
            'status' => $_GET['status'] ?? '',
            'invoice_status' => $_GET['invoice_status'] ?? '',
        ];
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 30;
        $offset = ($page - 1) * $perPage;

        try {
            $sales = FiscalSale::search($filters, $perPage, $offset);
            $total = FiscalSale::countSearch($filters);
            $totals = FiscalSale::totals();
        } catch (\Throwable $e) {
            flash('error', 'Rode as migracoes do banco em Configuracoes > Banco de Dados antes de usar o Fiscal.');
            $sales = [];
            $total = 0;
            $totals = [
                'total_sales' => 0,
                'paid_amount' => 0,
                'issued_count' => 0,
                'error_count' => 0,
                'refunded_count' => 0,
            ];
        }

        view('admin.fiscal.index', [
            'sales' => $sales,
            'totals' => $totals,
            'filters' => $filters,
            'page' => $page,
            'totalPages' => max(1, (int) ceil($total / $perPage)),
            'total' => $total,
            'user' => Auth::user(),
        ]);
    }

    public function settings(): void
    {
        Auth::require();
        $service = new FiscalService();
        try {
            $certificate = CertificateService::getCertificateInfo();
        } catch (\Throwable $e) {
            flash('error', 'Certificado fiscal salvo nao abriu: ' . $e->getMessage());
            $certificate = null;
        }

        view('admin.fiscal.settings', [
            'settings' => $service->settings(),
            'certificate' => $certificate,
            'user' => Auth::user(),
        ]);
    }

    public function updateSettings(): void
    {
        Auth::require();
        Security::requireCsrf();

        try {
            (new FiscalService())->saveSettings($_POST);
            flash('success', 'Configuracoes fiscais salvas.');
        } catch (\Throwable $e) {
            flash('error', 'Erro ao salvar configuracoes fiscais: ' . $e->getMessage());
        }

        redirect(url('/fiscal/settings'));
    }

    public function uploadCertificate(): void
    {
        Auth::require();
        Security::requireCsrf();

        try {
            CertificateService::saveUploaded($_FILES['certificate'] ?? [], $_POST['certificate_password'] ?? '');
            flash('success', 'Certificado A1 salvo e validado.');
        } catch (\Throwable $e) {
            flash('error', 'Erro no certificado: ' . $e->getMessage());
        }

        redirect(url('/fiscal/settings'));
    }

    public function taxation(): void
    {
        Auth::require();

        flash('error', 'A tela de tributacao detalhada ainda nao esta disponivel. Use Fiscal > Configuracoes para definir os padroes fiscais.');
        redirect(url('/fiscal/settings'));
    }

    public function saveTaxGroup(?string $id = null): void
    {
        Auth::require();
        Security::requireCsrf();

        flash('error', 'Cadastro de grupos tributarios ainda nao esta disponivel nesta versao.');
        redirect(url('/fiscal/settings'));
    }

    public function saveTaxRule(?string $id = null): void
    {
        Auth::require();
        Security::requireCsrf();

        flash('error', 'Cadastro de regras tributarias ainda nao esta disponivel nesta versao.');
        redirect(url('/fiscal/settings'));
    }

    public function closing(): void
    {
        Auth::require();

        flash('error', 'Fechamento fiscal ainda nao possui uma tela dedicada. Use a exportacao do Fiscal para enviar dados ao contador.');
        redirect(url('/fiscal'));
    }

    public function generateClosing(): void
    {
        Auth::require();
        Security::requireCsrf();

        flash('error', 'Geracao de fechamento fiscal ainda nao esta disponivel nesta versao.');
        redirect(url('/fiscal'));
    }

    public function downloadClosing(string $id): void
    {
        Auth::require();

        flash('error', 'Download de fechamento fiscal ainda nao esta disponivel nesta versao.');
        redirect(url('/fiscal'));
    }

    public function issue(string $id): void
    {
        Auth::require();
        Security::requireCsrf();

        try {
            (new FiscalService())->issueSale((int) $id);
            flash('success', 'Emissao enviada/processada com sucesso.');
        } catch (\Throwable $e) {
            flash('error', 'Nao foi possivel emitir: ' . $e->getMessage());
        }

        redirect(url('/fiscal'));
    }

    public function cancel(string $id): void
    {
        Auth::require();
        Security::requireCsrf();

        $reason = trim($_POST['reason'] ?? 'Cancelamento solicitado pelo administrador.');
        $reasonCode = $_POST['reason_code'] ?? '1';

        try {
            (new FiscalService())->cancelInvoice((int) $id, $reason, $reasonCode);
            flash('success', 'Cancelamento enviado com sucesso.');
        } catch (\Throwable $e) {
            flash('error', 'Nao foi possivel cancelar: ' . $e->getMessage());
        }

        redirect(url('/fiscal'));
    }

    public function export(): void
    {
        Auth::require();

        $filters = [
            'q' => $_GET['q'] ?? '',
            'status' => $_GET['status'] ?? '',
            'invoice_status' => $_GET['invoice_status'] ?? '',
        ];

        $csv = (new FiscalService())->exportCsv($filters);
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="fiscal-' . date('Y-m-d') . '.csv"');
        echo "\xEF\xBB\xBF" . $csv;
        exit;
    }

    public function download(string $id, string $type): void
    {
        Auth::require();

        $invoice = FiscalInvoice::find((int) $id);
        if (!$invoice) {
            flash('error', 'Nota fiscal nao encontrada.');
            redirect(url('/fiscal'));
        }

        $path = $type === 'pdf' ? ($invoice['pdf_path'] ?? '') : ($invoice['xml_path'] ?? '');
        if (!$path || !is_file($path)) {
            flash('error', 'Arquivo fiscal nao encontrado.');
            redirect(url('/fiscal'));
        }

        $filename = basename($path);
        header('Content-Type: ' . ($type === 'pdf' ? 'application/pdf' : 'application/xml'));
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }
}
