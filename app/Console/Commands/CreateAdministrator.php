<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Events\Registered;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class CreateAdministrator extends Command
{
    protected $signature = 'tourism:create-admin';

    protected $description = 'Crear una cuenta nueva de superadministración de forma interactiva';

    public function handle(): int
    {
        $name = text('Nombre', required: true);
        $email = strtolower(text('Correo electrónico', required: true));
        $secret = password('Contraseña (mínimo 12 caracteres)', required: true);
        $confirmation = password('Confirma la contraseña', required: true);

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $secret,
            'password_confirmation' => $confirmation,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = DB::transaction(function () use ($name, $email, $secret): User {
            $this->call('db:seed', ['--class' => RolesAndPermissionsSeeder::class, '--force' => true]);
            $user = User::create(['name' => $name, 'email' => $email, 'password' => $secret]);
            $user->assignRole(Role::SuperAdmin->value);
            AuditLog::record('user.admin_created', $user, ['role' => Role::SuperAdmin->value]);

            return $user;
        });

        event(new Registered($user));

        $this->info('Cuenta creada. Verifica el correo y configura el doble factor antes de acceder al panel.');

        return self::SUCCESS;
    }
}
