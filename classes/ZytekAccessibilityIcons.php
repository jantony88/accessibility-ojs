<?php

/**
 * @file plugins/generic/zytekAccessibility/classes/ZytekAccessibilityIcons.php
 *
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class ZytekAccessibilityIcons
 *
 * @brief Line icons of the panel (24×24, drawn with currentColor). Inner SVG
 *  markup only; templates/widget.tpl wraps it in a decorative <svg>.
 */

namespace APP\plugins\generic\zytekAccessibility\classes;

class ZytekAccessibilityIcons
{
    public const ICONS = [
        // Tools
        'textSize' => '<circle cx="10.5" cy="10.5" r="6.5"/><path d="M20 20l-4.8-4.8M10.5 7.5v6M7.5 10.5h6"/>',
        'textSpacing' => '<path d="M6 4v16M3 7l3-3 3 3M3 17l3 3 3-3M12 6h9M12 12h9M12 18h9"/>',
        'readableFont' => '<path d="M2.5 19L7 5l4.5 14M4 15h6"/><path d="M20.5 19v-7a3 3 0 0 0-6 0M20.5 15.5c-1.5-1.3-6-1.4-6 1.3 0 2.6 4.6 2.5 6 .2"/>',
        'bigCursor' => '<path d="M5 3l14 7.5-6.2 1.8L10.5 19z"/><path d="M13 13l5 5"/>',
        'tooltips' => '<path d="M4 4h16v11H10l-5 4.5V15H4z"/><path d="M12 7.5h.01M12 10v3"/>',
        'hideImages' => '<rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="8.5" cy="9.5" r="1.5"/><path d="M21 15l-4.5-4.5L8 19"/><path d="M3 3l18 18"/>',
        'readAloud' => '<path d="M4 9.5v5h3.5L12 18.5v-13L7.5 9.5z"/><path d="M15.5 9a4 4 0 0 1 0 6M18.5 6.5a7.5 7.5 0 0 1 0 11"/>',
        'contrast' => '<circle cx="12" cy="12" r="9"/><path d="M12 3a9 9 0 0 1 0 18z" fill="currentColor"/>',
        'invertColors' => '<path d="M7 3.5L3.5 7 7 10.5M3.5 7H17M17 13.5l3.5 3.5-3.5 3.5M20.5 17H7"/>',
        'brightness' => '<circle cx="12" cy="12" r="4"/><path d="M12 2.5v2M12 19.5v2M5.3 5.3l1.4 1.4M17.3 17.3l1.4 1.4M2.5 12h2M19.5 12h2M5.3 18.7l1.4-1.4M17.3 6.7l1.4-1.4"/>',
        'grayscale' => '<circle cx="9" cy="12" r="6"/><circle cx="15" cy="12" r="6"/>',
        'saturation' => '<path d="M12 3s6.5 6.6 6.5 11.2a6.5 6.5 0 0 1-13 0C5.5 9.6 12 3 12 3z"/><path d="M9 14.5a3 3 0 0 0 3 3"/>',
        'highlightLinks' => '<path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1"/><path d="M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"/>',
        'readingGuide' => '<rect x="3" y="10" width="18" height="4" rx="2"/><path d="M5 5.5h14M5 18.5h14" opacity=".45"/>',
        'readingMode' => '<path d="M3 5h5.5A3.5 3.5 0 0 1 12 8.5V20a2.5 2.5 0 0 0-2.5-2.5H3zM21 5h-5.5A3.5 3.5 0 0 0 12 8.5V20a2.5 2.5 0 0 1 2.5-2.5H21z"/>',
        'focusHighlight' => '<path d="M4 8.5V4h4.5M15.5 4H20v4.5M20 15.5V20h-4.5M8.5 20H4v-4.5"/><circle cx="12" cy="12" r="3"/>',
        'stopAnimations' => '<circle cx="12" cy="12" r="9"/><path d="M10 9v6M14 9v6"/>',
        'pageStructure' => '<path d="M9 6h12M9 12h12M9 18h12M4 6h.01M4 12h.01M4 18h.01"/>',
        // Panel controls
        'accessibility' => '<circle cx="12" cy="12" r="10"/><circle cx="12" cy="6.8" r="1.6" fill="currentColor"/><path d="M7 9.6c1.8.5 3.4.8 5 .8s3.2-.3 5-.8M12 10.4v3.8m0 0-2.2 4.6M12 14.2l2.2 4.6"/>',
        'language' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a13.5 13.5 0 0 1 0 18M12 3a13.5 13.5 0 0 0 0 18"/>',
        'hide' => '<path d="M3 3l18 18M10.6 6.1A9.6 9.6 0 0 1 12 6c5.5 0 9 6 9 6a16 16 0 0 1-2.9 3.5M6.7 6.7C4.1 8.3 3 12 3 12s3.5 6 9 6c1.5 0 2.9-.4 4.1-1M9.9 9.9a3 3 0 0 0 4.2 4.2"/>',
        'close' => '<path d="M6 6l12 12M18 6L6 18"/>',
        'reset' => '<path d="M4 4.5v5h5"/><path d="M4.6 14.5a7.5 7.5 0 1 0 1.8-7.4L4 9.5"/>',
    ];

    /**
     * @return array name => inner SVG markup (trusted constant)
     */
    public static function getAll(): array
    {
        return self::ICONS;
    }
}
