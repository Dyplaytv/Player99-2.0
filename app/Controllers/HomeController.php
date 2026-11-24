<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Models\Client;
use App\Services\LandingPages;
use App\Services\SiteAnalyticsService;

class HomeController extends Controller
{
    public function index(Request $request): void
    {
        SiteAnalyticsService::record('home');

        $ref = $request->input('ref') ?: ($_SESSION['affiliate_ref'] ?? ($_COOKIE['affiliate_ref'] ?? ''));
        if (!empty($request->input('ref'))) {
            $ref = strtoupper(trim((string)$request->input('ref')));
            Client::trackAffiliateClick($ref);
        }

        $this->render('landing/home', [
            'banners' => LandingPages::publicItems('home'),
            'ref' => $ref
        ], null);
    }

    public function parceiros(Request $request): void
    {
        $this->renderLandingPage('parceiros', 'Player99 - Seja um Parceiro', 'Seja um parceiro Player99 e lucre com o melhor player de Mídia Indoor do mercado.');
    }

    public function afiliados(Request $request): void
    {
        $this->renderLandingPage('afiliados', 'Player99 - Seja um Afiliado', 'Seja um afiliado Player99 e lucre com o melhor player de Mídia Indoor do mercado.');
    }

    public function privacidade(Request $request): void
    {
        $this->render('landing/legal', [
            'docType' => 'privacidade',
            'pageTitle' => 'Política de Privacidade - Player99',
            'pageDescription' => 'Política de Privacidade e Proteção de Dados (LGPD) da plataforma Player99.',
        ], null);
    }

    public function termos(Request $request): void
    {
        $this->render('landing/legal', [
            'docType' => 'termos',
            'pageTitle' => 'Termos de Serviço - Player99',
            'pageDescription' => 'Termos de Serviço e Condições de Uso da plataforma Player99.',
        ], null);
    }

    private function renderLandingPage(string $page, string $title, string $description): void
    {
        SiteAnalyticsService::record($page);

        $this->render('landing/page', [
            'pageKey' => $page,
            'pageTitle' => $title,
            'pageDescription' => $description,
            'banners' => LandingPages::publicItems($page),
        ], null);
    }
}
