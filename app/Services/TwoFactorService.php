<?php

namespace App\Services;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorService
{
    public function __construct(protected Google2FA $google2fa) {}

    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey();
    }

    public function getQrCodeSvg(string $email, string $secret): string
    {
        $issuer = config('app.name', 'Q4I Comms');
        $otpAuthUrl = $this->google2fa->getQRCodeUrl($issuer, $email, $secret);

        $renderer = new ImageRenderer(
            new RendererStyle(220),
            new SvgImageBackEnd()
        );

        return (new Writer($renderer))->writeString($otpAuthUrl);
    }

    public function verifyCode(string $secret, string $code): bool
    {
        $sanitized = preg_replace('/\s+/', '', $code) ?? '';
        if ($sanitized === '' || $secret === '') {
            return false;
        }

        return (bool) $this->google2fa->verifyKey($secret, $sanitized);
    }
}
