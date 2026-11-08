<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;

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

        $uploadsDir = dirname(__DIR__, 2) . '/uploads/landing/';
        $hasImg1 = file_exists($uploadsDir . 'img1.jpg');
        $hasImg2 = file_exists($uploadsDir . 'img2.jpg');
        $hasImg3 = file_exists($uploadsDir . 'img3.jpg');

        $this->render('landing/home', [
            'hasImg1' => $hasImg1,
            'hasImg2' => $hasImg2,
            'hasImg3' => $hasImg3,
            'ref' => $ref
        ], null);
    }
}
