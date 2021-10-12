<?php

namespace AsposeImagingConverter\Core;

use RecursiveFilterIterator;

class RFIterator extends RecursiveFilterIterator
{
    public function accept()
    {
        $path = $this->current()->getPathname();
        return true;
    }
}

