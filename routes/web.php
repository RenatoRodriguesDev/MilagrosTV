<?php

use App\Http\Controllers\CatalogController;
use App\Http\Controllers\PushController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SubtitleController;
use App\Http\Controllers\UserAuthController;
use App\Http\Controllers\VideoController;
use App\Http\Controllers\WatchlistController;
use App\Http\Controllers\WatchProgressController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EpisodeController;
use App\Http\Controllers\Admin\FileDetectionController;
use App\Http\Controllers\Admin\LogController;
use App\Http\Controllers\Admin\MonitorController;
use App\Http\Controllers\Admin\MovieController;
use App\Http\Controllers\Admin\SerieController;
use App\Http\Controllers\Admin\StorageController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

// Pesquisa global (pública dentro do auth)
Route::get('/search', [SearchController::class, 'search'])->name('search')->middleware('auth');

// Idioma (público)
Route::get('/locale/{lang}', function (string $lang) {
    if (in_array($lang, ['pt', 'en', 'es'])) {
        session(['locale' => $lang]);
    }
    return back();
})->name('locale.switch');

// Auth
Route::middleware('guest')->group(function () {
    Route::get('/login', [UserAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [UserAuthController::class, 'login']);
    Route::get('/register', [UserAuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [UserAuthController::class, 'register']);
});
Route::post('/logout', [UserAuthController::class, 'logout'])->name('logout')->middleware('auth');

// Catálogo e features — protegido por auth
Route::middleware('auth')->group(function () {
    Route::get('/', [CatalogController::class, 'index'])->name('catalog.index');
    Route::get('/series/{serie}', [CatalogController::class, 'serie'])->name('catalog.serie');
    Route::get('/movies/{movie}', [CatalogController::class, 'movie'])->name('catalog.movie');
    Route::post('/watched', [CatalogController::class, 'toggleWatched'])->name('catalog.watched');
    Route::get('/video/episode/{episode}', [VideoController::class, 'stream'])->name('video.episode');
    Route::get('/video/movie/{movie}', [VideoController::class, 'streamMovie'])->name('video.movie');
    Route::get('/subtitles/search', [SubtitleController::class, 'search'])->name('subtitles.search');
    Route::get('/subtitles/download', [SubtitleController::class, 'download'])->name('subtitles.download');

    // Progresso de visualização
    Route::get('/progress/{episode}', [WatchProgressController::class, 'show'])->name('progress.show');
    Route::post('/progress/{episode}', [WatchProgressController::class, 'store'])->name('progress.store');
    Route::delete('/progress/{episode}/dismiss', [WatchProgressController::class, 'destroy'])->name('progress.dismiss');

    // Watchlist
    Route::get('/watchlist', [WatchlistController::class, 'index'])->name('watchlist.index');
    Route::post('/watchlist', [WatchlistController::class, 'toggle'])->name('watchlist.toggle');

    // Perfil
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // Notificações
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::get('/notifications/count', [NotificationController::class, 'unreadCount'])->name('notifications.count');

    // Push notifications
    Route::get('/push/vapid-key', [PushController::class, 'vapidKey'])->name('push.vapid-key');
    Route::post('/push/subscribe', [PushController::class, 'subscribe'])->name('push.subscribe');
    Route::post('/push/unsubscribe', [PushController::class, 'unsubscribe'])->name('push.unsubscribe');
});

// Admin - autenticação
Route::get('/admin/login', [AuthController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [AuthController::class, 'login'])->name('admin.login.post');
Route::post('/admin/logout', [AuthController::class, 'logout'])->name('admin.logout');

// Admin - área protegida
Route::prefix('admin')->name('admin.')->middleware(\App\Http\Middleware\AdminAuth::class)->group(function () {

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Utilizadores
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}/activity', [UserController::class, 'activity'])->name('users.activity');
    Route::post('/users/{user}/toggle-admin', [UserController::class, 'toggleAdmin'])->name('users.toggle-admin');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    // Detecção automática de ficheiros
    Route::get('/files/scan', [FileDetectionController::class, 'scan'])->name('files.scan');

    // Logs
    Route::get('/logs', [LogController::class, 'index'])->name('logs.index');
    Route::post('/logs/clear', [LogController::class, 'clear'])->name('logs.clear');

    // Storage
    Route::get('/storage', [StorageController::class, 'index'])->name('storage.index');
    Route::delete('/storage/file', [StorageController::class, 'destroy'])->name('storage.destroy');

    // Monitor
    Route::get('/monitor', [MonitorController::class, 'index'])->name('monitor.index');
    Route::get('/monitor/stats', [MonitorController::class, 'stats'])->name('monitor.stats');

    // Episódios bulk
    Route::post('/series/{serie}/episodes/bulk-update', [EpisodeController::class, 'bulkUpdate'])->name('series.episodes.bulk-update');
    Route::post('/series/{serie}/episodes/bulk-destroy', [EpisodeController::class, 'bulkDestroy'])->name('series.episodes.bulk-destroy');

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
    Route::get('/series/{serie}/episodes/tmdb-season', [EpisodeController::class, 'importFromTmdb'])->name('series.episodes.tmdb-season');
    Route::get('/episodes/scan', [EpisodeController::class, 'scan'])->name('episodes.scan');
    Route::delete('/episodes/{episode}', [EpisodeController::class, 'destroy'])->name('episodes.destroy');
});
