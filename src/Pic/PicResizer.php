<?php

namespace FF\Attachment\Pic;

abstract class PicResizer
{
    protected PicHandler $picHandler;

    private string $errorMessage = '';

    public function __construct()
    {
    }

    public function setPicHandler(PicHandler $picHandler)
    {
        $this->picHandler = $picHandler;
    }

    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }
}
