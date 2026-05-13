<?php

declare(strict_types=1);

namespace App;

class Form
{
    /**
     * Build a modal form configuration array.
     *
     * @param  string $modalId Unique modal identifier (e.g. 'student.add')
     * @param  string $title   Modal title (localized)
     * @return array{id: string, title: string}
     */
    public static function buildModal(string $modalId, string $title): array
    {
        return [
            'id'    => $modalId,
            'title' => $title,
        ];
    }
}