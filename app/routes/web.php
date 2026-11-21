<?php

use Illuminate\Support\Facades\Route;

// A SPA em Vue cuida de todo o roteamento de telas.
Route::view('/{any?}', 'app')->where('any', '^(?!api|up).*$');
