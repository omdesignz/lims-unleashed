<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Features extends Model
{
    use HasFactory;

    public CONST MENU_NAME = null;

    /**
     * Determine if the given feature is enabled.
     *
     * @param  string  $feature
     * @return bool
     */
    public static function enabled(string $feature): bool
    {
        return in_array($feature, config('vap.features', []));
    }

    /**
     * Determine if the feature is enabled and has a given option enabled.
     *
     * @param  string  $feature
     * @param  string  $option
     * @return bool
     */
    public static function optionEnabled(string $feature, string $option): bool
    {
        return static::enabled($feature) &&
            config("jetstream-options.{$feature}.{$option}") === true;
    }

    /**
     * Determine if the application is allowing profile photo uploads.
     *
     * @return bool
     */
    public static function managesProfilePhotos(): bool
    {
        return static::enabled(static::profilePhotos());
    }

    /**
     * Determine if the application is using any API features.
     *
     * @return bool
     */
    public static function hasApiFeatures(): bool
    {
        return static::enabled(static::api());
    }

    /**
     * Determine if the application is using any team features.
     *
     * @return bool
     */
    public static function hasTeamFeatures(): bool
    {
        return static::enabled(static::teams());
    }

    /**
     * Determine if invitations are sent to team members.
     *
     * @return bool
     */
    public static function sendsTeamInvitations(): bool
    {
        return static::optionEnabled(static::teams(), 'invitations');
    }

    /**
     * Determine if the application has terms of service / privacy policy confirmation enabled.
     *
     * @return bool
     */
    public static function hasTermsAndPrivacyPolicyFeature(): bool
    {
        return static::enabled(static::termsAndPrivacyPolicy());
    }

    /**
     * Determine if the application is using any account deletion features.
     *
     * @return bool
     */
    public static function hasAccountDeletionFeatures(): bool
    {
        return static::enabled(static::accountDeletion());
    }

    /**
     * Enable the profile photo upload feature.
     *
     * @return string
     */
    public static function profilePhotos(): string
    {
        return 'profile-photos';
    }

    /**
     * Enable the API feature.
     *
     * @return string
     */
    public static function api(): string
    {
        return 'api';
    }

    /**
     * Enable the teams feature.
     *
     * @param  array  $options
     * @return string
     */
    public static function teams(array $options = []): string
    {
        if (! empty($options)) {
            config(['jetstream-options.teams' => $options]);
        }

        return 'teams';
    }

    /**
     * Enable the terms of service and privacy policy feature.
     *
     * @return string
     */
    public static function termsAndPrivacyPolicy(): string
    {
        return 'terms';
    }

    /**
     * Enable the account deletion feature.
     *
     * @return string
     */
    public static function accountDeletion(): string
    {
        return 'account-deletion';
    }
}
