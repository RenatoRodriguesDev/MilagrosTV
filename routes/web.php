<?php

use App\Http\Controllers\CatalogController;
use App\Http\Controllers\VideoController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\EpisodeController;
use App\Http\Controllers\Admin\MovieController;
use App\Http\Controllers\Admin\SerieController;
use Illuminate\Support\Facades\Route;

// Idioma
Route::get('/locale/{lang}', function (string $lang) {
    if (in_array($lang, ['pt', 'en', 'es'])) {
        session(['locale' => $lang]);
    }
    return back();
})->name('locale.switch');

// Catálogo público
Route::get('/', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/series/{serie}', [CatalogController::class, 'serie'])->name('catalog.serie');
Route::post('/watched', [CatalogController::class, 'toggleWatched'])->name('catalog.watched');
Route::get('/video/episode/{episode}', [VideoController::class, 'stream'])->name('video.episode');

// Admin - autenticação
Route::get('/admin/login', [AuthController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [AuthController::class, 'login'])->name('admin.login.post');
Route::post('/admin/logout', [AuthController::class, 'logout'])->name('admin.logout');

// Admin - área protegida
Route::prefix('admin')->name('admin.')->middleware(\App\Http\Middleware\AdminAuth::class)->group(function () {

    Route::get('/', function () {
        return redirect()->route('admin.movies.index');
    })->name('dashboard');

    // Filmes
    Route::get('/movies', [MovieController::class, 'index'])->name('movies.index');
    Route::get('/movies/create', [MovieController::class, 'create'])->name('movies.create');
    Route::post('/movies', [MovieController::class, 'store'])->name('movies.store');
    Route::get('/movies/{movie}/edit', [MovieController::class, 'edit'])->name('movies.edit');
    Route::put('/movies/{movie}', [MovieController::class, 'update'])->name('movies.update');
    Route::delete('/movies/{movie}', [MovieController::class, 'destroy'])->name('movies.destroy');
    Route::get('/movies/tmdb-search', [MovieController::class, 'search'])->name('movies.tmdb-search');
    Route::get('/movies/tmdb-details', [MovieController::class, 'tmdbDetails'])->name('movies.tmdb-details');

    // Séries
    Route::get('/series', [SerieController::class, 'index'])->name('series.index');
    Route::get('/series/create', [SerieController::class, 'create'])->name('series.create');
    Route::post('/series', [SerieController::class, 'store'])->name('series.store');
    Route::get('/series/{serie}/edit', [SerieController::class, 'edit'])->name('series.edit');
    Route::put('/series/{serie}', [SerieController::class, 'update'])->name('series.update');
    Route::delete('/series/{serie}', [SerieController::class, 'destroy'])->name('series.destroy');
    Route::get('/series/tmdb-search', [SerieController::class, 'search'])->name('series.tmdb-search');
    Route::get('/series/tmdb-details', [SerieController::class, 'tmdbDetails'])->name('series.tmdb-details');

    // Episódios
    Route::post('/series/{serie}/episodes', [EpisodeController::class, 'store'])->name('series.episodes.store');
    Route::post('/series/{serie}/episodes/import', [EpisodeController::class, 'importBatch'])->name('series.episodes.import');
    Route::get('/episodes/scan', [EpisodeController::class, 'scan'])->name('episodes.scan');
    Route::delete('/episodes/{episode}', [EpisodeController::class, 'destroy'])->name('episodes.destroy');
});
