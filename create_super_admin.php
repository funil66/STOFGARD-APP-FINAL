<?php
$user = \App\Models\User::firstOrNew(['email' => 'allissonsousa.adv@gmail.com']);
$user->name = 'Allisson Sousa';
$user->password = bcrypt('Swordfish66@');
$user->is_super_admin = true;
$user->is_admin = true;
$user->tenant_id = '1';
$user->role = 'dono';
$user->save();
echo "Super Admin criado com sucesso!";
