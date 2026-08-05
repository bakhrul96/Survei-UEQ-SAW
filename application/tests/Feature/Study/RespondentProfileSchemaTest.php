<?php

use Illuminate\Support\Facades\Schema;

it('uses a MySQL-safe unique index name for respondent profiles', function () {
    $indexNames = collect(Schema::getIndexes('respondent_profiles'))
        ->pluck('name')
        ->all();

    expect($indexNames)->toContain('respondent_profiles_period_respondent_unique');
});
