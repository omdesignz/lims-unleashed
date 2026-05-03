<?php

namespace App\Enums;

enum MaintenanceTaskPeriodicityUnit: string
{
    case HOURS = 'hours';
    case DAYS = 'days';
    case WEEKS = 'weeks';
    case MONTHS = 'months';
    case YEARS = 'years';

    public function label(): string
    {
        return match($this) {
            self::HOURS => trans('gestlab.general.labels.maintenance_tasks.periodicity_unit_options.hours'),
            self::DAYS => trans('gestlab.general.labels.maintenance_tasks.periodicity_unit_options.days'),
            self::WEEKS => trans('gestlab.general.labels.maintenance_tasks.periodicity_unit_options.weeks'),
            self::MONTHS => trans('gestlab.general.labels.maintenance_tasks.periodicity_unit_options.months'),
            self::YEARS => trans('gestlab.general.labels.maintenance_tasks.periodicity_unit_options.years'),
        };
    }
}