<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Security;
use App\Core\Validator;
use App\Models\Generation;

use App\Services\HtmlGenerator;

/**
 * Controller do Gerador de HTML
 */
class GeneratorController
{
    /**
     * Página do gerador
     */
    public function index(): void
    {
        Auth::require();

        $generations = Generation::recent(10);

        view('admin.generator.index', [
            'generations' => $generations,
            'errors' => Validator::getErrors(),
            'user' => Auth::user()
        ]);
    }

    /**
     * Gera o HTML
     */
    public function generate(): void
    {
        Auth::require();
        Security::requireCsrf();

        if (!Validator::make($_POST, [
            'site_name' => 'required|max:200',
            'theme' => 'required|in:elegante-escuro,elegante-claro,moderno-azul,moderno-verde,premium-dourado,minimalista'
        ])) {
            back();
        }

        $siteName = $_POST['site_name'];
        $theme = $_POST['theme'];

        // Gera o HTML
        $generator = new HtmlGenerator($siteName, $theme);
        $filename = $generator->saveToFile();
        $links = $generator->getLinks();

        // Salva no histórico
        $generationId = Generation::create([
            'site_name' => $siteName,
            'theme' => $theme,
            'html_file' => $filename
        ]);

        // Salva links na sessão para exibir
        $_SESSION['generated_links'] = $links;
        $_SESSION['generated_id'] = $generationId;

        flash('success', 'HTML gerado com sucesso!');
        redirect(url('/generator'));
    }

    /**
     * Download do HTML
     */
    public function download(string $id): void
    {
        Auth::require();

        $generation = Generation::find((int) $id);
        if (!$generation) {
            flash('error', 'Geração não encontrada.');
            redirect(url('/generator'));
        }

        $filepath = ABSPATH . '/storage/generated/' . $generation['html_file'];
        
        if (!file_exists($filepath)) {
            flash('error', 'Arquivo não encontrado.');
            redirect(url('/generator'));
        }

        // Download
        header('Content-Type: text/html');
        header('Content-Disposition: attachment; filename="' . $generation['html_file'] . '"');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit;
    }

    /**
     * Preview do HTML
     */
    public function preview(string $id): void
    {
        Auth::require();

        $generation = Generation::find((int) $id);
        if (!$generation) {
            flash('error', 'Geração não encontrada.');
            redirect(url('/generator'));
        }

        $filepath = ABSPATH . '/storage/generated/' . $generation['html_file'];
        
        if (!file_exists($filepath)) {
            flash('error', 'Arquivo não encontrado.');
            redirect(url('/generator'));
        }

        // Exibe o HTML
        readfile($filepath);
        exit;
    }
}
