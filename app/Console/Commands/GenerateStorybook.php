<?php

namespace App\Console\Commands;

use App\Support\DesignSystem\StorybookExport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateStorybook extends Command
{
    protected $signature = 'design-system:storybook {--check : Fail instead of writing when the generated files are out of date}';

    protected $description = 'Render the styleguide examples and Design Tokens into the Storybook stories';

    public function handle(): int
    {
        $files = StorybookExport::files();
        $stale = array_values(array_diff(self::existingStories(), array_keys($files)));
        $changed = array_keys(array_filter($files, fn (string $contents, string $path) => ! File::exists(base_path($path)) || File::get(base_path($path)) !== $contents, ARRAY_FILTER_USE_BOTH));

        if ($this->option('check')) {
            foreach ([...$changed, ...$stale] as $path) {
                $this->error("Out of date: {$path}");
            }

            if ($changed !== [] || $stale !== []) {
                $this->line('Run php artisan design-system:storybook and commit the result.');

                return self::FAILURE;
            }

            $this->info('Storybook stories are up to date ('.count($files).' files).');

            return self::SUCCESS;
        }

        foreach ($files as $path => $contents) {
            File::ensureDirectoryExists(dirname(base_path($path)));
            File::put(base_path($path), $contents);
        }

        foreach ($stale as $path) {
            File::delete(base_path($path));
        }

        $this->info('Wrote '.count($files).' files, '.count($changed).' changed, '.count($stale).' removed.');

        return self::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private static function existingStories(): array
    {
        return collect(File::glob(base_path(StorybookExport::STORIES.'/*/*.stories.js')))
            ->map(fn (string $path) => ltrim(str_replace(base_path(), '', $path), '/'))
            ->values()
            ->all();
    }
}
