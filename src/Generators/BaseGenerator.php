<?php

namespace Deto\Generator\Generators;

use Deto\Generator\Utils\FileUtil;

class BaseGenerator
{
    public function rollbackFile($path, $fileName)
    {
        if (file_exists($path.$fileName)) {
            return FileUtil::deleteFile($path, $fileName);
        }

        return false;
    }
}
