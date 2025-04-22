<?php

namespace FF\Attachment\Pic;

use Exception;
use FF\Attachment\Attachment\AbstractUploader;
use FF\Attachment\Attachment\Contracts\Uploader as IUploader;
use FF\Attachment\Pic\Contracts\UrlUploader as IUrlUploader;
use Illuminate\Support\Facades\Storage;

class UrlUploader extends AbstractUploader implements IUrlUploader, IUploader
{
    public function setFile(
        string $url,
        string $uploadDisk = null,
        bool $verifySSL = false
    ) {
        $explodeUrl = explode('/', $url);
        $fileName = end($explodeUrl);

        $file = $this->fileGetContents($url, $verifySSL);

        $temporaryPath = 'temporary/UrlUploaderTemp';
        Storage::disk(config('attachment.storage'))->put(
            $temporaryPath,
            $file,
            'public'
        );

        $this->attachmentProcessor->setFile(
            file: $file,
            fileSize: Storage::disk(config('attachment.storage'))->size(
                $temporaryPath,
            ),
            originPathName: $url,
            fileName: $fileName,
            title: $fileName,
            fileType: Storage::disk(config('attachment.storage'))->mimeType(
                $temporaryPath,
            ),
            uploadDisk: $uploadDisk,
        );
    }

    private function fileGetContents(string $url, bool $verifySSL = false)
    {
        if ($verifySSL) {
            $agent =
                'Mozilla/5.0 (Windows; U; Windows NT 6.0; en-US; rv:1.9.0.3) Gecko/2008092417 Firefox/3.0.3';
            $header = [];
            $header[] =
                'Accept: text/xml,text/csv,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5';
            $header[] = 'Connection: keep-alive';
            $header[] = 'Keep-Alive: 300';

            try {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

                curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
                curl_setopt($ch, CURLOPT_USERAGENT, $agent);

                $response = curl_exec($ch);
            } catch (Exception $e) {
                trigger_error(
                    sprintf(
                        'Curl failed with error #%d: %s',
                        $e->getCode(),
                        $e->getMessage(),
                    ),
                    E_USER_ERROR,
                );
            } finally {
                if (is_resource($ch)) {
                    curl_close($ch);
                }
            }

            if ($response === false) {
                throw new Exception('Curl error: ' . curl_error($ch));
            }

            return $response;
        }

        return file_get_contents($url);
    }

    public function setPicClassName(?string $picClassName = null) {
        $this->attachmentProcessor->setPicClassName($picClassName);
    }
}
