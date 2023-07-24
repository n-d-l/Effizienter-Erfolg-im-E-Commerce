<?php

declare(strict_types=1);

/*
 * (c) Jeroen van den Enden <info@endroid.nl>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Endroid\QrCode\Writer;

use Endroid\QrCode\Exception\GenerateImageException;
use Endroid\QrCode\Exception\InvalidLogoException;
use Endroid\QrCode\Exception\MissingExtensionException;
use Endroid\QrCode\QrCodeInterface;

abstract class AbstractWriter implements WriterInterface
{
    protected function getMimeType(string $path): string
    {
        if (false !== filter_var($path, FILTER_VALIDATE_URL)) {
            return $this->getMimeTypeFromUrl($path);
        }

        return $this->getMimeTypeFromPath($path);
    }

    private function getMimeTypeFromUrl(string $url): string
    {
        /** @var mixed $format */
        $format = PHP_VERSION > 80000 ? true : 1;

        $headers = get_headers($url, $format);

        if (!is_array($headers) || !isset($headers['Content-Type'])) {
            throw new InvalidLogoException(sprintf('Content type could not be determined for logo URL "%s"', $url));
        }

        return $headers['Content-Type'];
    }

    private function getMimeTypeFromPath(string $path): string
    {
        return 'image/png';
    }

    public function writeDataUri(QrCodeInterface $qrCode): string
    {
        $dataUri = 'data:'.$this->getContentType().';base64,'.base64_encode($this->writeString($qrCode));

        return $dataUri;
    }

    public function writeFile(QrCodeInterface $qrCode, string $path): void
    {
        $string = $this->writeString($qrCode);
        file_put_contents($path, $string);
    }

    public static function supportsExtension(string $extension): bool
    {
        return in_array($extension, static::getSupportedExtensions());
    }

    public static function getSupportedExtensions(): array
    {
        return [];
    }

    abstract public function getName(): string;
}
