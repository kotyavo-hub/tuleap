<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\BrowserDetection;

/**
 * @psalm-immutable
 */
final class BrowserDeprecationMessage
{
    /**
     * Allow to dismiss IE deprecation message
     *
     * @tlp-config-key
     */
    public const TEMPORARILY_ALLOW_IE                      = 'temporarily_allow_dismiss_ie_deprecation_message';
    private const IE_DISMISS_EXPECTED_CONFIRMATION_MESSAGE = 'I_understand_this_is_a_temporary_configuration_switch_(please_warn_the_Tuleap_dev_team_when_enabling_this)';

    /**
     * @var string
     */
    public $title;
    /**
     * @var string
     */
    public $message;
    /**
     * @var bool
     */
    public $can_be_dismiss;

    private function __construct(string $title, string $message, bool $can_be_dismiss)
    {
        $this->title          = $title;
        $this->message        = $message;
        $this->can_be_dismiss = $can_be_dismiss;
    }

    public static function fromDetectedBrowser(DetectedBrowser $detected_browser): ?self
    {
        if ($detected_browser->isIE()) {
            return new self(
                _('Your web browser is not supported'),
                _('Internet Explorer is not supported. Please upgrade to a modern, fully supported browser such as Firefox, Chrome or Edge.'),
                \ForgeConfig::get(self::TEMPORARILY_ALLOW_IE) === self::IE_DISMISS_EXPECTED_CONFIRMATION_MESSAGE,
            );
        }

        if ($detected_browser->isEdgeLegacy()) {
            return new self(
                _('Your web browser is not supported'),
                _('Edge Legacy is not supported. Please upgrade to the latest version of Edge or use another modern alternative such as Firefox or Chrome.'),
                true,
            );
        }

        if ($detected_browser->isAnOutdatedBrowser()) {
            $browser_name = $detected_browser->getName() ?? '';
            return new self(
                _('Your web browser is not supported'),
                sprintf(
                    _('You are using an outdated version of %s. You might encounter issues if you continue.'),
                    $browser_name,
                ),
                true,
            );
        }

        return null;
    }
}
