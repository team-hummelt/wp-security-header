<?php

namespace WPSecurity\Header;

defined('ABSPATH') or die();

trait WpSecurityHeaderTrait
{
    protected array $default_values;
    protected string $styleSrc = '';
    protected string $fontSrc = '';
    protected string $scriptSrc = '';
    protected string $imgSrc = '';
    protected string $formAction = '';
    protected string $connectSrc = '';
    protected string $baseUri = '';
    protected int $cspAktiv = 0;

    protected function wp_security_headers_default_settings($args = '', $csp = []): array
    {

        if ($csp) {
            if ($csp['csp_aktiv'] == 1) {
                $this->cspAktiv = 1;
            }
            if ($csp['google_fonts']) {
                $this->styleSrc = ' https://fonts.googleapis.com';
                $this->fontSrc = ' fonts.gstatic.com';
            }

            if ($csp['google_apis']) {
                $this->imgSrc = ' https://*.googleapis.com https://*.gstatic.com *.google.com *.googleusercontent.com';
                $this->formAction = ' *.google.com';
                $this->connectSrc = ' https://*.googleapis.com *.google.com https://*.gstatic.com';
                $this->baseUri = ' *.google.com';
            }
            if ($csp['adobe_fonts']) {
                $this->scriptSrc = ' use.typekit.net';
                $this->styleSrc .= ' use.typekit.net';
                $this->imgSrc .= ' p.typekit.net';
                $this->connectSrc .= ' performance.typekit.net';
            }
            if ($csp['matomo_aktiv'] == 1) {
                $this->scriptSrc .= ' ' . $csp['matomo_subdomain'];
                $this->connectSrc .= ' ' . $csp['matomo_subdomain'];
                $this->imgSrc .= ' ' . $csp['matomo_subdomain'];
            }
        }

        $this->default_values = [
            'header' => [
                'csp' => [
                    '0' => [
                        'name' => 'default-src',
                        'value' => "'none'",
                        'aktiv' => $this->cspAktiv,
                        'id' => 1,
                        'help' => '',
                    ],
                    '1' => [
                        'name' => 'object-src',
                        'value' => "'none'",
                        'aktiv' => $this->cspAktiv,
                        'id' => 2,
                        'help' => '',
                    ],
                    '2' => [
                        'name' => 'script-src',
                        'value' => "'self' https: http:%s 'strict-dynamic' $this->scriptSrc",
                        'aktiv' => $this->cspAktiv,
                        'id' => 3,
                        'help' => '',
                    ],
                    '3' => [
                        'name' => 'style-src',
                        'value' => "'self' 'unsafe-inline' $this->styleSrc",
                        'aktiv' => $this->cspAktiv,
                        'id' => 4,
                        'help' => '',
                    ],
                    '4' => [
                        'name' => 'img-src',
                        'value' => "'self' $this->imgSrc data: *",
                        'aktiv' => $this->cspAktiv,
                        'id' => 5,
                        'help' => '',
                    ],
                    '5' => [
                        'name' => 'form-action',
                        'value' => "'self' $this->formAction",
                        'aktiv' => $this->cspAktiv,
                        'id' => 6,
                        'help' => '',
                    ],
                    '6' => [
                        'name' => 'connect-src',
                        'value' => "'self' $this->connectSrc data: blob:",
                        'aktiv' => $this->cspAktiv,
                        'id' => 7,
                        'help' => '',
                    ],
                    '7' => [
                        'name' => 'frame-ancestors',
                        'value' => "'self'",
                        'aktiv' => $this->cspAktiv,
                        'id' => 8,
                        'help' => '',
                    ],
                    '8' => [
                        'name' => 'base-uri',
                        'value' => "'self' $this->baseUri",
                        'aktiv' => $this->cspAktiv,
                        'id' => 9,
                        'help' => '',
                    ],
                    '9' => [
                        'name' => 'media-src',
                        'value' => "*",
                        'aktiv' => $this->cspAktiv,
                        'id' => 10,
                        'help' => '',
                    ],
                    '10' => [
                        'name' => 'font-src',
                        'value' => "$this->fontSrc * data:",
                        'aktiv' => $this->cspAktiv,
                        'id' => 11,
                        'help' => '',
                    ],
                    '11' => [
                        'name' => 'worker-src',
                        'value' => "blob:",
                        'aktiv' => $this->cspAktiv,
                        'id' => 12,
                        'help' => '',
                    ],
                    '13' => [
                        'name' => 'child-src',
                        'value' => "*",
                        'aktiv' => $this->cspAktiv,
                        'id' => 14,
                        'help' => '',
                    ],
                    '12' => [
                        'name' => 'report-uri',
                        'value' => "",
                        'aktiv' => 0,
                        'id' => 13,
                        'help' => '',
                    ],
                ],
                'pr' => [
                    '0' => [
                        'name' => 'fullscreen',
                        'value' => "(self)",
                        'aktiv' => 1,
                        'id' => 1,
                        'help' => '',
                    ],
                    '1' => [
                        'name' => 'geolocation',
                        'value' => "*",
                        'aktiv' => 1,
                        'id' => 2,
                        'help' => '',
                    ],
                    '2' => [
                        'name' => 'accelerometer',
                        'value' => "()",
                        'aktiv' => 1,
                        'id' => 3,
                        'help' => '',
                    ],
                    '3' => [
                        'name' => 'autoplay',
                        'value' => "(self)",
                        'aktiv' => 1,
                        'id' => 4,
                        'help' => '',
                    ],
                    '4' => [
                        'name' => 'camera',
                        'value' => "()",
                        'aktiv' => 1,
                        'id' => 5,
                        'help' => '',
                    ],
                    '5' => [
                        'name' => 'encrypted-media',
                        'value' => "()",
                        'aktiv' => 1,
                        'id' => 6,
                        'help' => '',
                    ],
                    '6' => [
                        'name' => 'gyroscope',
                        'value' => "()",
                        'aktiv' => 1,
                        'id' => 7,
                        'help' => '',
                    ],
                    '7' => [
                        'name' => 'magnetometer',
                        'value' => "()",
                        'aktiv' => 1,
                        'id' => 8,
                        'help' => '',
                    ],
                    '8' => [
                        'name' => 'microphone',
                        'value' => "()",
                        'aktiv' => 1,
                        'id' => 9,
                        'help' => '',
                    ],
                    '9' => [
                        'name' => 'midi',
                        'value' => "()",
                        'aktiv' => 1,
                        'id' => 10,
                        'help' => '',
                    ],
                    '10' => [
                        'name' => 'payment',
                        'value' => "()",
                        'aktiv' => 1,
                        'id' => 11,
                        'help' => '',
                    ],
                    '11' => [
                        'name' => 'picture-in-picture',
                        'value' => "(self)",
                        'aktiv' => 1,
                        'id' => 12,
                        'help' => '',
                    ],
                    '12' => [
                        'name' => 'usb',
                        'value' => "(self)",
                        'aktiv' => 1,
                        'id' => 13,
                        'help' => '',
                    ],
                ],
                'ah' => [
                    '0' => [
                        'name' => 'Strict-Transport-Security',
                        'value' => "max-age=15768000; preload; includeSubDomains",
                        'aktiv' => 1,
                        'id' => 1,
                        'help' => '',
                    ],
                    '1' => [
                        'name' => 'X-Frame-Options',
                        'value' => "sameorigin",
                        'aktiv' => 1,
                        'id' => 2,
                        'help' => '',
                    ],
                    '2' => [
                        'name' => 'X-Content-Type-Options',
                        'value' => "nosniff",
                        'aktiv' => 1,
                        'id' => 3,
                        'help' => '',
                    ],
                    '3' => [
                        'name' => 'X-XSS-Protection',
                        'value' => "1; mode=block",
                        'aktiv' => 1,
                        'id' => 4,
                        'help' => '',
                    ],
                    '4' => [
                        'name' => 'Referrer-Policy',
                        'value' => "no-referrer",
                        'aktiv' => 1,
                        'id' => 5,
                        'help' => '',
                    ],
                ],
            ],
            'select_user_role' => [
                "0" => [
                    'value' => 'read',
                    'name' => __('Subscriber', 'wp-security-header')
                ],
                "1" => [
                    'value' => 'edit_posts',
                    'name' => __('Contributor', 'wp-security-header')
                ],
                "2" => [
                    'value' => 'publish_posts',
                    'name' => __('Author', 'wp-security-header')
                ],
                "3" => [
                    'value' => 'publish_pages',
                    'name' => __('Editor', 'wp-security-header')
                ],
                "4" => [
                    'value' => 'manage_options',
                    'name' => __('Administrator', 'wp-security-header')
                ],
            ],
            'ajaxLanguage' => [
                //Bitte Eingaben überprüfen.
                'check_entry' => __('Please check entries.', 'wp-security-header'),
                //Header Eintrag löschen
                'delete_header' => __('Delete header entry', 'wp-security-header'),
                //<span class="swal-delete-body">Der Header Eintrag wird <b>unwiderruflich gelöscht!</b> Das Löschen kann <b>nicht</b> rückgängig gemacht werden.</span>
                'delete_header_html' => __('<span class="swal-delete-body">The header entry will <b>be deleted irrevocably!</b> The deletion <b>cannot</b> be undone.</span>', 'wp-security-header'),
                //PIN eingeben
                'enter_pin' => __('Enter PIN', 'wp-security-header'),
                //Geben Sie den PIN zum löschen ein.
                'enter_pin_label' => __('Enter the PIN for deletion.', 'wp-security-header'),
                //Der eingegebene PIN ist falsch!
                'pin_incorrect' => __('The PIN entered is incorrect!', 'wp-security-header'),
                'Cancel' => __('Cancel', 'wp-security-header'),
                //Alle Werte werden <b>zurückgesetzt!</b> Die Änderungen können <b>nicht</b> rückgängig gemacht werden.<br><br>
                'reset_html' => __('All values are <b>reset!</b> The changes <b>cannot</b> be undone.<br><br>', 'wp-security-header'),
                //Einstellungen zurückgesetzt!
                'settings_reset' => __('Settings reset!', 'wp-security-header'),
                //Einstellungen zurücksetzen?
                'reset_settings' => __('Reset settings?', 'wp-security-header'),
                //Alle Einstellungen zurücksetzen
                'reset_all_settings' => __('Reset all settings', 'wp-security-header'),
                __('Plugin has been disabled.','wp-security-header'),
                __('Contact the support.' ,'wp-security-header'),
                //Bitte das Feld "Matomo Domain" überprüfen.
                __('Please check the "Matomo Domain" field.', 'wp-security-header')
            ],
        ];

        if ($args) {
            foreach ($this->default_values as $key => $val) {
                if ($key == $args) {
                    return $val;
                }
            }
        }
        return $this->default_values;
    }

    protected function wp_security_headers_language(): array
    {
        return [
            __('sections', 'wp-security-header'),
            __('Security Header', 'wp-security-header'),
            __('Save', 'wp-security-header'),
            //neuen Wert hinzufügen
            __('add new value', 'wp-security-header'),
            //ist der Platzhalter für nonce
            __('is the placeholder for nonce', 'wp-security-header'),
            __('Restore default', 'wp-security-header'),
            __('active', 'wp-security-header'),
            __('Ajax transmission error', 'wp-security-header'),
            __('Error', 'wp-security-header'),
            __('Saved', 'wp-security-header'),
            __('Changes saved successfully.', 'wp-security-header'),
            __('Minimum requirement for using this function', 'wp-security-header'),
            __('User role', 'wp-security-header'),
            //Beim erneuten Laden der Standardwerte werden die Einstellungen berücksichtigt.
            __('When reloading the default values, the settings are taken into account.', 'wp-security-header'),
            //Einstellungen für Standardwerte
            __('Settings for default values', 'wp-security-header'),
            __('Adobe Fonts', 'wp-security-header'),
            __('Google Apis', 'wp-security-header'),
            __('Google Fonts', 'wp-security-header'),
            __('Google', 'wp-security-header'),
            __('Domain', 'wp-security-header'),
            __('Matomo', 'wp-security-header'),
            __('Subdomain', 'wp-security-header'),
            __('subdomain', 'wp-security-header'),
            __('for', 'wp-security-header'),
            //Subdomain ohne Punkt am ende eingeben.
            __('Enter subdomain without dot at the end.', 'wp-security-header'),
            __('Set Meta Tags', 'wp-security-header')
        ];
    }

    protected static function isHttps(): bool
    {
        if (array_key_exists("HTTPS", $_SERVER) && 'on' === $_SERVER["HTTPS"]) {
            return true;
        }
        if (array_key_exists("SERVER_PORT", $_SERVER) && 443 === (int)$_SERVER["SERVER_PORT"]) {
            return true;
        }
        if (array_key_exists("HTTP_X_FORWARDED_SSL", $_SERVER) && 'on' === $_SERVER["HTTP_X_FORWARDED_SSL"]) {
            return true;
        }
        if (array_key_exists("HTTP_X_FORWARDED_PROTO", $_SERVER) && 'https' === $_SERVER["HTTP_X_FORWARDED_PROTO"]) {
            return true;
        }
        return false;
    }
}