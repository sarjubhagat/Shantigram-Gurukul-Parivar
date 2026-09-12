<?php

use App\Http\Controllers\Admin\AccessControl\AuthController;
use App\Http\Controllers\Admin\AccessControl\PermissionController;
use App\Http\Controllers\Admin\ContentLibrary\AudioLibraryController;
use App\Http\Controllers\Admin\ContentLibrary\ImageLibraryController;
use App\Http\Controllers\Admin\ContentLibrary\PdfLibraryController;
use App\Http\Controllers\Admin\ContentLibrary\VideoLibraryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Member\SelfServiceController;
use App\Services\ModuleResolver;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect('/member-update'));

Route::prefix('member-update')->name('member-update.')->group(function () {
    Route::get('/', [SelfServiceController::class, 'index'])->name('index');
    Route::post('/send-otp', [SelfServiceController::class, 'sendOtp'])->name('send_otp');
    Route::post('/verify-otp', [SelfServiceController::class, 'verifyOtp'])->name('verify_otp');
    Route::post('/select-member', [SelfServiceController::class, 'selectMember'])->name('select_member');
    Route::get('/profile', [SelfServiceController::class, 'profile'])->name('profile');
    Route::post('/submit', [SelfServiceController::class, 'submit'])->name('submit');
    Route::post('/logout', [SelfServiceController::class, 'logout'])->name('logout');
});

// Auth routes
Route::middleware('guest')->prefix('admin')->group(function () {
    Route::get('/login',  [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::get('/', fn() => redirect()->route('login'));
    Route::post('/', fn() => redirect()->route('login'));
});


Route::prefix('admin')->name('admin.')->middleware(['auth'])->group(function () {

    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    // Dashboard — only static route
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile — current user's own profile & password
    Route::get('profile',           [ProfileController::class, 'show'])->name('profile');
    Route::post('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // // Video Library — extra routes beyond the standard dynamic set
    // Route::prefix('video-library')->name('video-library.')->group(function () {
    //     Route::get('/', [VideoLibraryController::class, 'index'])->name('index')->middleware('permission:video-library-list');
    //     Route::any('/get_data', [VideoLibraryController::class, 'get_data'])->name('get_data')->middleware('permission:video-library-list');
    //     Route::post('/complete', [VideoLibraryController::class, 'complete'])->name('complete')->middleware('permission:video-library-create');
    //     Route::post('/delete', [VideoLibraryController::class, 'DeleteRecord'])->name('delete')->middleware('permission:video-library-delete');
    //     Route::match(['GET','POST','PATCH','HEAD','OPTIONS','DELETE'], '/tus/{any?}', [VideoLibraryController::class, 'tus'])
    //         ->where('any', '.*')->name('tus')->middleware('permission:video-library-create');
    //     Route::get('/{id}/stream/master.m3u8', [VideoLibraryController::class, 'masterPlaylist'])->name('master')->middleware('permission:video-library-list');
    //     Route::get('/{id}/stream/{quality}/{file}', [VideoLibraryController::class, 'stream'])
    //         ->where(['quality' => '[a-z0-9]+', 'file' => '[a-zA-Z0-9_.%-]+'])->name('stream');
    //     Route::get('/{id}/status', [VideoLibraryController::class, 'videoStatus'])->name('status')->middleware('permission:video-library-list');
    //     Route::get('/{id}/preview', [VideoLibraryController::class, 'preview'])->name('preview')->middleware('permission:video-library-list');
    //     Route::post('/{id}/update', [VideoLibraryController::class, 'update'])->name('update')->middleware('permission:video-library-edit');
    //     Route::get('/{id}', [VideoLibraryController::class, 'show'])->name('show')->middleware('permission:video-library-list');
    // });

    // ── Fully dynamic module routing ───────────────────────
    // Resolves controller from modules table using controller_path + controller_class.
    // e.g. controller_path="AccessControl" + controller_class="RoleController"
    //      → App\Http\Controllers\Admin\AccessControl\RoleController
    if (ModuleResolver::resolve()) {
        $slug      = Config::get('module.name');
        $ctrlClass = ModuleResolver::buildControllerClass();

        if (class_exists($ctrlClass)) {
            Route::prefix($slug)->name($slug . '.')->group(function () use ($ctrlClass, $slug,) {

                $export_perm = 'permission:' . $slug . '-export';
                $list_perm = 'permission:' . $slug . '-list';
                $create_perm = 'permission:' . $slug . '-create';
                $delete_perm = 'permission:' . $slug . '-delete';
                $edit_perm = 'permission:' . $slug . '-edit';
                $download_perm = 'permission:' . $slug . '-download';

                Route::get('/', [$ctrlClass, 'index'])->name('index')->middleware($list_perm);
                Route::any('/get_data', [$ctrlClass, 'get_data'])->name('get_data')->middleware($list_perm);
                Route::post('/create',  [$ctrlClass, 'create'])->name('create')->middleware($create_perm);
                Route::post('/delete',   [$ctrlClass, 'deleteRecord'])->name('delete')->middleware($delete_perm);
                Route::post('/{id}/update', [$ctrlClass, 'update'])->name('update')->middleware($edit_perm);  // mainly used when direct ajax update or create
                Route::any('/export', [$ctrlClass, 'ExportRecord'])->name('export')->middleware($export_perm);
                Route::get('/{id}/download', [$ctrlClass, 'download'])->name('download')->middleware($download_perm);

                // Full-page create/edit (e.g. Roles); modules using modals leave AddEditAction as 404
                Route::get('/add', [$ctrlClass, 'AddEditAction'])->name('add')->middleware($create_perm);
                Route::post('/add', [$ctrlClass, 'handlePost'])->name('add.store')->middleware($create_perm);
                Route::get('/{id}/edit', [$ctrlClass, 'AddEditAction'])->name('edit')->middleware($edit_perm);
                Route::post('/{id}/edit', [$ctrlClass, 'handlePost'])->name('edit.store')->middleware($edit_perm);


                //  Audio & video common
                Route::post('/complete', [$ctrlClass, 'complete'])->name('complete')->middleware($create_perm);
                Route::match(['GET', 'POST', 'PATCH', 'HEAD', 'OPTIONS', 'DELETE'], '/tus/{any?}', [$ctrlClass, 'tus'])
                    ->where('any', '.*')->name('tus')
                    ->middleware($create_perm);

                if (str_contains($ctrlClass, 'AudioLibraryController')) {
                    // Audio Routes
                    Route::get('/{id}/stream/master.m3u8', [AudioLibraryController::class, 'masterPlaylist'])->name('master')->middleware($list_perm);
                    Route::get('/{id}/stream/{file}', [AudioLibraryController::class, 'stream'])
                        ->where('file', '[a-zA-Z0-9_.%-]+')->name('stream')->middleware($list_perm);
                    Route::get('/{id}/status', [AudioLibraryController::class, 'audioStatus'])->name('status')->middleware($list_perm);
                    Route::get('/{id}/preview', [AudioLibraryController::class, 'preview'])->name('preview')->middleware($list_perm);
                    Route::get('/{id}', [AudioLibraryController::class, 'show'])->name('show')->middleware($list_perm);
                }
                
                if (str_contains($ctrlClass, 'VideoLibraryController')) {
                    // Video Routes
                    Route::get('/{id}/stream/{quality}/{file}', [VideoLibraryController::class, 'stream'])->where('file', '[a-zA-Z0-9_.%-]+')->name('stream');
                    Route::get('/{id}/stream/master.m3u8', [VideoLibraryController::class, 'masterPlaylist'])->name('master')->middleware($list_perm);
                    Route::get('/{id}/status', [VideoLibraryController::class, 'videoStatus'])->name('status')->middleware($list_perm);
                    Route::get('/{id}/preview', [VideoLibraryController::class, 'preview'])->name('preview')->middleware($list_perm);
                    Route::get('/{id}', [VideoLibraryController::class, 'show'])->name('show')->middleware($list_perm);
                }

                if (str_contains($ctrlClass, 'PdfLibraryController')) {
                    Route::get('/{id}/status', [PdfLibraryController::class, 'pdfStatus'])->name('status')->middleware($list_perm);
                    Route::get('/{id}/preview', [PdfLibraryController::class, 'preview'])->name('preview')->middleware($list_perm);
                    Route::match(['GET', 'HEAD'], '/{id}/stream', [PdfLibraryController::class, 'stream'])->name('stream')->middleware($list_perm);
                    Route::get('/{id}', [PdfLibraryController::class, 'show'])->name('show')->middleware($list_perm);
                }

                if (str_contains($ctrlClass, 'ImageLibraryController')) {
                    Route::get('/gallery/ids', [ImageLibraryController::class, 'galleryIds'])->name('gallery.ids')->middleware($list_perm);
                    Route::get('/gallery', [ImageLibraryController::class, 'gallery'])->name('gallery')->middleware($list_perm);
                    Route::post('/bulk', [ImageLibraryController::class, 'bulkStore'])->name('bulk')->middleware($create_perm);
                    Route::post('/bulk_destroy', [ImageLibraryController::class, 'bulkDestroy'])->name('bulk_destroy')->middleware($delete_perm);
                    Route::post('/bulk_download', [ImageLibraryController::class, 'bulkDownload'])->name('bulk_download')->middleware($download_perm);
                    Route::delete('/{id}', [ImageLibraryController::class, 'destroy'])->name('destroy')->middleware($delete_perm);
                    Route::get('/{id}', [ImageLibraryController::class, 'show'])->name('show')->middleware($list_perm);
                }

                if (str_contains($ctrlClass, 'ParivarController')) {
                    Route::post('/generate-aadhaar', [$ctrlClass, 'generateAadhaar'])
                        ->name('generate_aadhaar')
                        ->middleware($list_perm);
                    Route::get('/check-phone', [$ctrlClass, 'checkPhone'])
                        ->name('check_phone')
                        ->middleware($list_perm);
                }

                if (str_contains($ctrlClass, 'IcardPrintController')) {
                    Route::get('/settings', [$ctrlClass, 'settings'])->name('settings')->middleware($edit_perm);
                    Route::post('/save-settings', [$ctrlClass, 'saveSettings'])->name('save_settings')->middleware($edit_perm);
                    Route::get('/print', [$ctrlClass, 'printCards'])->name('print')->middleware($list_perm);
                }

                if (str_contains($ctrlClass, 'DataUpdateController')) {
                    Route::get('/{id}/show', [$ctrlClass, 'show'])->name('show')->middleware($list_perm);
                    Route::post('/{id}/review', [$ctrlClass, 'review'])->name('review')->middleware($edit_perm);
                }
        
            });
        }
    }
});
