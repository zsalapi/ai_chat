<?php

namespace App\Repositories;

use App\Models\Faq;
use Illuminate\Database\Eloquent\Collection;

interface FaqRepositoryInterface
{
    public function findMatchingAnswer(string $text): ?Faq;
    public function getAll(): Collection;
}
