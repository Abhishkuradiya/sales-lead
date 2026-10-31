<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Api extends BaseConfig
{
    public string $baseUrl = 'https://erp.supplyr.in';
    public string $publicSettingsPath = '/api/auth/public-settings';
    public string $loginPath = '/api/auth/login';
    public string $verifyOtpPath = '/api/auth/verify-otp';
    public string $mePath = '/api/auth/me';
    public string $thirdPartyDirectoryPath = '/api/parties/third-party-directory';
    public string $thirdPartyDirectoryCreatePath = '/api/parties/third-party-directory';
    public string $manufacturingOptionsPath = '/api/manufacturing-options';
    public string $manufacturedItemsPath = '/api/manufactured-items';
    public string $rawMaterialsPath = '/api/raw-materials';
    public int $timeout = 20;
}
