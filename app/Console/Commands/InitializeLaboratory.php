<?php

namespace App\Console\Commands;

use App\Models\LabNetwork;
use App\Models\Role;
use App\Models\User;
use App\Models\VAPLab;
use App\Settings\GeneralSettings;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

#[Signature('app:initialize-laboratory {email} {name} {lab}')]
#[Description('Initialize an empty installation without creating a default password')]
class InitializeLaboratory extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $data = Validator::make($this->arguments(), [
            'email' => ['required', 'email', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'lab' => ['required', 'string', 'max:255'],
        ])->validate();

        if (User::withTrashed()->exists() || VAPLab::withTrashed()->exists()) {
            $this->error('Initialization requires an empty installation. No existing account or laboratory was changed.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($data): void {
            $user = User::create([
                'name' => $data['name'], 'email' => $data['email'], 'is_active' => true,
                'password' => Hash::make(Str::random(80)), 'email_verified_at' => now(),
            ]);
            $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
            $user->assignRole($role);
            $network = LabNetwork::create(['name' => $data['lab'], 'primary_color' => '#24664f']);
            $lab = VAPLab::create(['name' => $data['lab'], 'code' => 'MAIN', 'network_id' => $network->id]);
            $network->update(['main_lab_id' => $lab->id]);
            DB::table('lab_user')->insert([
                'lab_id' => $lab->id, 'user_id' => $user->id,
                'can_view_network' => true, 'can_manage_branding' => true,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $settings = app(GeneralSettings::class);
            $settings->app_client_lab_name = $data['lab'];
            $settings->app_primary_color = '#24664f';
            $settings->save();
        });

        $this->info('Laboratory initialized. Use the password reset flow to set your private password. No email was sent.');

        return self::SUCCESS;
    }
}
