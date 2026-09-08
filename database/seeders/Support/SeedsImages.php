<?php

namespace Database\Seeders\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Write generated placeholder images to the public disk.
 */
trait SeedsImages
{
    /**
     * Store an image unless the file is already there.
     *
     * Placeholders are drawn deterministically from the record name, so a file
     * that exists already holds exactly what would be written again. Skipping
     * it keeps re-seeding quick.
     *
     * @param  callable(): string  $generate
     */
    protected function putImage(string $path, callable $generate): string
    {
        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            $disk->put($path, $generate());
        }

        return $path;
    }
}
