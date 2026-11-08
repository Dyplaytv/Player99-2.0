<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Services\LandingPages;

class HomeController extends Controller
{
    public function index(Request $request): void
    {
        // Tratamento de afiliados
        $ref = $request->input('ref');
        if (!empty($ref)) {
            $ref = strtoupper(trim($ref));
            setcookie('affiliate_ref', $ref, time() + (86400 * 30), "/");
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

    private function renderLandingPage(string $page, string $title, string $description): void
    {
        $this->render('landing/page', [
            'pageKey' => $page,
            'pageTitle' => $title,
            'pageDescription' => $description,
            'banners' => LandingPages::publicItems($page),
        ], null);
    }
}
