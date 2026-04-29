<?php

declare(strict_types=1);

/**
 * HTML колонок мегаменю из инфоблока (разделы = колонки, элементы, свойство LINK = URL).
 */
function uuopera_megamenu_render_html(): string
{
    if (!\Bitrix\Main\Loader::includeModule('iblock')) {
        return '';
    }
    $iblockId = uuopera_megamenu_iblock_id();
    if ($iblockId <= 0) {
        return '';
    }

    $borderMenu = "border-b border-b-[var(--menu-border-color,theme('colors.brown.DEFAULT'))]";

    $sections = [];
    $rs = CIBlockSection::GetList(
        ['SORT' => 'ASC', 'NAME' => 'ASC'],
        ['IBLOCK_ID' => $iblockId, 'ACTIVE' => 'Y', 'GLOBAL_ACTIVE' => 'Y', 'CHECK_PERMISSIONS' => 'Y'],
        false,
        ['ID', 'NAME', 'PICTURE']
    );
    while ($row = $rs->GetNext()) {
        $sections[] = $row;
    }
    if ($sections === []) {
        return '';
    }

    $out = '';

    foreach ($sections as $sec) {
        $sid = (int) $sec['ID'];
        $title = (string) $sec['NAME'];
        $pictureId = (int) ($sec['PICTURE'] ?? 0);
        $imgSrc = '';
        if ($pictureId > 0) {
            $path = CFile::GetPath($pictureId);
            if (is_string($path) && $path !== '') {
                $imgSrc = $path;
            }
        }
        $hasImage = $imgSrc !== '';
        $liClass = $hasImage ? 'group/item flex flex-col lg:gap-5' : 'group/item flex flex-col lg:gap-10';

        $out .= '<li class="' . htmlspecialcharsbx($liClass) . '" data-accordion>';

        if ($hasImage) {
            $out .= '<a href="javascript:void(0);" class="relative group/link cursor-default lg:pointer-events-none" data-accordion-toggle>';
            $out .= '<div class="relative pb-[48%] hidden lg:block">';
            $out .= '<img src="' . htmlspecialcharsbx($imgSrc) . '" alt="' . htmlspecialcharsbx($title) . '" class="absolute image-cover">';
            $out .= '</div>';
            $out .= '<div class="relative lg:absolute lg:inset-0 flex justify-center lg:justify-start lg:items-end px-3 md:px-6 lg:px-5 py-4 md:py-6 lg:py-3 '
                . $borderMenu . ' lg:border-none">';
            $out .= '<div class="text-p2 lg:text-p1 lg:text-white "><span>' . htmlspecialcharsbx($title) . '</span></div>';
            $out .= '<span class="absolute right-[39px] sm:right-[44px] top-[50%] -translate-y-[50%] lg:hidden w-2 h-2 bg-current rounded-full group-[&.open]/item:opacity-40"></span>';
            $out .= '</div></a>';
        } else {
            $out .= '<a href="javascript:void(0);" class="relative link-hover text-p2 lg:text-p1 flex justify-center lg:justify-start px-3 md:px-6 lg:px-5 py-4 md:py-6 lg:py-0 '
                . $borderMenu . ' lg:border-none cursor-default lg:pointer-events-none" data-accordion-toggle>';
            $out .= '<span>' . htmlspecialcharsbx($title) . '</span>';
            $out .= '<span class="absolute right-[39px] sm:right-[44px] top-[50%] -translate-y-[50%] lg:hidden w-2 h-2 bg-current rounded-full group-[&.open]/item:opacity-40"></span>';
            $out .= '</a>';
        }

        $out .= '<ul class="flex flex-col gap-3 lg:gap-2 px-3 md:px-6 lg:px-5 py-5 lg:py-0 '
            . $borderMenu . ' lg:border-none lg:text-p2" data-accordion-content data-modal-menu-submenu>';

        $elRes = CIBlockElement::GetList(
            ['SORT' => 'ASC', 'NAME' => 'ASC'],
            [
                'IBLOCK_ID' => $iblockId,
                'SECTION_ID' => $sid,
                'ACTIVE' => 'Y',
                'CHECK_PERMISSIONS' => 'Y',
            ],
            false,
            false,
            ['ID', 'NAME', 'PROPERTY_LINK']
        );
        while ($el = $elRes->GetNext()) {
            $href = trim((string) ($el['PROPERTY_LINK_VALUE'] ?? ''));
            if ($href === '') {
                continue;
            }
            $name = (string) ($el['NAME'] ?? '');
            $target = '';
            if (preg_match('#^https?://#i', $href)) {
                $target = ' target="_blank" rel="noopener noreferrer"';
            }
            $out .= '<li><a href="' . htmlspecialcharsbx($href) . '"' . $target . ' class="link-hover flex">'
                . htmlspecialcharsbx($name) . '</a></li>';
        }

        $out .= '</ul></li>' . "\n";
    }

    return $out;
}
