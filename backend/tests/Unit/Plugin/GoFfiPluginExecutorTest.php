<?php

declare(strict_types=1);

namespace Tests\Unit\Plugin;

use App\Modules\Plugin\Domain\Exceptions\PluginExecutionException;
use App\Modules\Plugin\Infrastructure\Adapters\GoFfiPluginExecutor;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

final class GoFfiPluginExecutorTest extends TestCase
{
    public function test_library_not_found_throws_exception(): void
    {
        Config::set('plugins.so_path', '/nonexistent/lib.so');

        $this->expectException(PluginExecutionException::class);
        $this->expectExceptionMessage('Plugin library not found');

        new GoFfiPluginExecutor;
    }

    public function test_real_library_returns_error_for_unknown_plugin(): void
    {
        $libraryPath = storage_path('app/plugins/libplugins.so');

        if (! file_exists($libraryPath)) {
            $this->markTestSkipped('Plugin library not built. Run: task plugin-build');
        }

        putenv("PLUGIN_SO_PATH={$libraryPath}");

        $executor = new GoFfiPluginExecutor;

        $this->expectException(PluginExecutionException::class);
        $this->expectExceptionMessage('not found');

        $executor->execute('nonexistent', 'action', []);
    }
}
