<?php

namespace FF\Attachment\Attachment\Utils;

use Storage;

class StorageVisibility
{

    static public function makeDirectoryWithAllVisibility(string $disk, string $directory, string $visibility): void
    {

        Storage::disk($disk)->makeDirectory($directory);

        $directories = explode('/', $directory);

        $currentDirectory = "";
        foreach($directories as $index => $directoryDetail) {
            if($index === 0) {
                $currentDirectory = $directoryDetail;
            }
            else {
                $currentDirectory .= '/' . $directoryDetail;
            }

            Storage::disk($disk)->setVisibility($currentDirectory, $visibility);
        }
    }

}