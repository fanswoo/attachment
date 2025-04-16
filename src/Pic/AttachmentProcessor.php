<?php

namespace FF\Attachment\Pic;

use Exception;
use FF\Attachment\Attachment\AttachmentProcessor as BaseAttachmentProcessor;
use FF\Attachment\Attachment\Contracts\AttachmentProcessor as IAttachmentProcessor;

class AttachmentProcessor extends BaseAttachmentProcessor implements
    IAttachmentProcessor
{

    public function setPicClassName(?string $picClassName = null) {

        if(!$picClassName) {
            return;
        }

        if (!class_exists($picClassName)) {
            throw new Exception("class {$picClassName} not exists.");
        }

        $this->validator->setPicClassName($picClassName);
        $this->attachmentCreator->setPicClassName($picClassName);
    }

}
