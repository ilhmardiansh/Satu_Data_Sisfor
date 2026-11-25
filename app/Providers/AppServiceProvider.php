<?php

namespace App\Providers;

use App\Models\DataForm;
use App\Models\DataRequest;
use App\Models\ProgramProfile;
use Illuminate\Foundation\Console\ServeCommand;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if (class_exists(ServeCommand::class)) {
            foreach (['TEMP', 'TMP', 'TMPDIR', 'USERPROFILE', 'LOCALAPPDATA'] as $envVar) {
                if (! in_array($envVar, ServeCommand::$passthroughVariables, true)) {
                    ServeCommand::$passthroughVariables[] = $envVar;
                }
            }
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('*', function ($view): void {
            $appLang = session('lang', 'id') === 'en' ? 'en' : 'id';
            $t = static fn (string $id, string $en): string => $appLang === 'en' ? $en : $id;

            $siteProfil = null;
            $pendingRequestsCount = 0;
            $formsNeedingPreprocessingCount = 0;

            try {
                if (Schema::hasTable('profil_prodi')) {
                    $siteProfil = ProgramProfile::query()->first();
                }

                $user = Auth::user();
                if ($user && $user->isStaff()) {
                    if (Schema::hasTable('permohonan_data')) {
                        $pendingRequestsCount = DataRequest::query()
                            ->where('status', 'menunggu')
                            ->count();
                    }

                    if (Schema::hasTable('formulir')) {
                        $formsNeedingPreprocessingCount = DataForm::closed()
                            ->where('status_preprocessing', 'proses')
                            ->count();
                    }
                }
            } catch (Throwable) {
                // Database may not be migrated yet during CLI bootstrap.
            }

            $view->with([
                'appLang' => $appLang,
                't' => $t,
                'siteProfil' => $siteProfil,
                'pendingRequestsCount' => $pendingRequestsCount,
                'formsNeedingPreprocessingCount' => $formsNeedingPreprocessingCount,
            ]);
        });
    }
}
