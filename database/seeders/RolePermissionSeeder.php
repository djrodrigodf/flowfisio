<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Permissões agrupadas por módulo
        $permissions = [
            // Módulo de Pacientes
            'pacientes.ver',
            'pacientes.criar',
            'pacientes.editar',
            'pacientes.excluir',

            // Módulo de Agendamentos
            'agendamentos.ver',
            'agendamentos.criar',
            'agendamentos.editar',
            'agendamentos.excluir',

            // Módulo de Profissionais
            'profissionais.ver',
            'profissionais.gerenciar',

            // Módulo de Prontuário (PEP)
            'prontuario.ver',
            'prontuario.editar',

            // Módulo de Usuários (Admin)
            'usuarios.ver',
            'usuarios.gerenciar',

            // Acesso ao Dashboard
            'dashboard.ver',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Perfis
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $secretaria = Role::firstOrCreate(['name' => 'secretaria']);
        $profissional = Role::firstOrCreate(['name' => 'profissional']);
        $dono = Role::firstOrCreate(['name' => 'dono']);

        // Admin tem tudo
        $admin->syncPermissions(Permission::all());

        // Secretária - foco em agendamentos e pacientes
        $secretaria->syncPermissions([
            'dashboard.ver',
            'pacientes.ver',
            'pacientes.criar',
            'pacientes.editar',
            'agendamentos.ver',
            'agendamentos.criar',
            'agendamentos.editar',
            'agendamentos.excluir',
        ]);

        // Profissional - foco em visualizar agenda e editar prontuário
        $profissional->syncPermissions([
            'dashboard.ver',
            'agendamentos.ver',
            'pacientes.ver',
            'prontuario.ver',
            'prontuario.editar',
        ]);

        // Dono - visão geral, não edita nada
        $dono->syncPermissions([
            'dashboard.ver',
            'pacientes.ver',
            'agendamentos.ver',
            'profissionais.ver',
        ]);
    }
}
