<?php

declare(strict_types=1);

if (!file_exists(__DIR__.'/vendor/autoload.php')) {
    throw new Exception(sprintf(
        'Could not find composer autoload file %s. Did you run `composer update` in %s?',
        __DIR__.'/vendor/autoload.php',
        __DIR__
    ));
}

require_once __DIR__.'/vendor/autoload.php';

use pointybeard\Symphony\Extensions\Saxon;
use pointybeard\Symphony\Extensions\Saxon\Exceptions;
use Saxon\SaxonProcessor;

// Check if the class already exists before declaring it again.
if (!class_exists('\\Extension_Saxon')) {
    class Extension_Saxon extends Extension
    {
        const PRODUCTION_MODE_ENABLED = 'yes';
        const PRODUCTION_MODE_DISABLED = 'no';

        public static function init()
        {
        }

        public function install()
        {
            // Check that Saxon is installed
            if (false == Saxon\XSLTProcess::isXSLTProcessorAvailable()) {
                throw new Exceptions\SaxonNotInstalledException();
            }

            // Check its version 1.1.2 (unfortunately 1.2.1 causes issues - see https://saxonica.plan.io/issues/4371)
            $version = (new SaxonProcessor())->version();
            if (false === stristr($version, 'Saxon/C 1.1.2')) {
                throw new Exceptions\IncorrectSaxonVersionInstalledException($version);
            }
        }

        public function getSubscribedDelegates(): array
        {
            return [
                [
                    'page' => '/system/preferences/',
                    'delegate' => 'AddCustomPreferenceFieldsets',
                    'callback' => 'appendPreferences',
                ],
                [
                    'page' => '/system/preferences/',
                    'delegate' => 'Save',
                    'callback' => 'savePreferences',
                ],
            ];
        }

        /**
         * Append Saxon XSLT Processor  preferences.
         *
         * @param array $context delegate context
         */
        public function appendPreferences(array &$context): void
        {
            // Create preference group
            $group = new XMLElement('fieldset');
            $group->setAttribute('class', 'settings');
            $group->appendChild(new XMLElement('legend', __('Saxon/C XSLT Processor')));

            // Append enable cache
            $label = Widget::Label();
            $input = Widget::Input('settings[saxon][production_mode_enabled]', self::PRODUCTION_MODE_ENABLED, 'checkbox');

            if (self::isProductionModeEnabled()) {
                $input->setAttribute('checked', 'checked');
            }

            $label->setValue($input->generate().' '.__('Enable Production Mode'));
            $group->appendChild($label);

            // Append help
            $group->appendChild(new XMLElement('p', __('When production mode is enabled, the Saxon XSLTProcess class will use optimised code that runs appoximately 100x faster then when it isn\'t enabled. The downside is that any errors produced during the XSLT transformation process will be much less informative. This is a limitation of the Saxon/C library and will hopefully be addressed in future versions.'), ['class' => 'help']));

            // Append new preference group
            $context['wrapper']->appendChild($group);
        }

        /**
         * Save preferences.
         *
         * @param array $context
         *                       delegate context
         */
        public function savePreferences(array &$context): void
        {
            if (!is_array($context['settings'])) {
                // Disable production mode by default
                $context['settings'] = [
                'saxon' => [
                    'production_mode_enabled' => self::PRODUCTION_MODE_DISABLED,
                ],
            ];
            } else {
                if (false == isset($context['settings']['saxon']['production_mode_enabled'])) {
                    // Disable production mode if it has not been checked
                    $context['settings']['saxon']['production_mode_enabled'] = self::PRODUCTION_MODE_DISABLED;
                }
            }
        }

        /**
         * Check if production mode is enabled.
         */
        public static function isProductionModeEnabled(): bool
        {
            return self::PRODUCTION_MODE_ENABLED == Symphony::Configuration()->get('production_mode_enabled', 'saxon');
        }

        public static function providerOf($type = null)
        {
            return [
                'xslt_processing' => [[
                    'class' => '\\pointybeard\\Symphony\\Extensions\\Saxon\\XSLTProcess',
                    'name' => 'Saxon/C (XSLT 3.0)',
                ]],
            ];
        }
    }
}
