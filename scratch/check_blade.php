<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

$user = User::first();
Auth::login($user);

$reqTrans = Request::create("/transferencias?tab=enviar", 'GET');
$resTrans = app()->handle($reqTrans);
$html = $resTrans->getContent();

if (str_contains($html, '@endsection')) {
    echo "ERROR: El texto '@endsection' se encuentra presente en el HTML generado.\n";
} else {
    echo "CORRECTO: No hay '@endsection' en el HTML. La plantilla Blade compiló perfectamente.\n";
}
