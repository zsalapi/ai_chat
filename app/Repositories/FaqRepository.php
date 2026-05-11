<?php

namespace App\Repositories;

use App\Models\Faq;
use Illuminate\Database\Eloquent\Collection;

class FaqRepository implements FaqRepositoryInterface
{
    public function findMatchingAnswer(string $text): ?Faq
    {
        return Faq::whereRaw('LOWER(?) LIKE CONCAT(\'%\', LOWER(question), \'%\')', [$text])
                  ->orWhereRaw('LOWER(question) LIKE CONCAT(\'%\', LOWER(?), \'%\')', [$text])
                  ->first();
    }

    public function getAll(): Collection
    {
        return Faq::all();
    }
}
