<?php

use App\Models\ModernFolder;
use Diglactic\Breadcrumbs\Breadcrumbs;

use Diglactic\Breadcrumbs\Generator as BreadcrumbTrail;

Breadcrumbs::for('home', function (BreadcrumbTrail $trail) {
    $trail->push('Home', route('modern-folders.index'));
});
Breadcrumbs::for('modern-folders.index', function (BreadcrumbTrail $trail, ?ModernFolder $folder = null) {
    $trail->parent('home');
    // $trail->push($folder->name, route('modern-folders.show', $folder->slug));

    if($folder) {
        foreach($folder->ancestorsAndSelf($folder->id) as $ancestor) {
            $trail->push($ancestor->name, route('modern-folders.show', $ancestor->slug), [
                'current' => $folder->is($ancestor),
            ]);
        }
    }
});